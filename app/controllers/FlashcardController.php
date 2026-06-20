<?php
/**
 * doodle - Flashcards controller (SM-2 spaced repetition + AI generation)
 *
 * @package doodle
 * @author  Death Legion Team
 */

class FlashcardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireLogin();
    }

    public function index(): void
    {
        $uid = Auth::id();
        // Show user's decks (own + course decks for enrolled courses + public)
        $decks = Database::fetchAll(
            "SELECT d.*,
                    (SELECT COUNT(*) FROM flashcards WHERE deck_id = d.id) AS card_count,
                    (SELECT COUNT(*) FROM flashcard_reviews fr
                     JOIN flashcards f ON fr.flashcard_id = f.id
                     WHERE f.deck_id = d.id AND fr.user_id = :uid AND fr.next_review <= :today) AS due_count,
                    c.title AS course_title
             FROM flashcard_decks d
             LEFT JOIN courses c ON d.course_id = c.id
             WHERE d.user_id = :uid
                OR d.is_public = 1
                OR (d.course_id IN (SELECT course_id FROM enrollments WHERE student_id = :uid AND status = 'active'))
             ORDER BY d.created_at DESC",
            ['uid' => $uid, 'today' => date('Y-m-d')]
        );

        // Count due cards across all decks
        $dueCount = Database::count('flashcard_reviews fr JOIN flashcards f ON fr.flashcard_id = f.id JOIN flashcard_decks d ON f.deck_id = d.id',
            '(d.user_id = :uid OR d.is_public = 1 OR d.course_id IN (SELECT course_id FROM enrollments WHERE student_id = :uid2 AND status = "active")) AND fr.user_id = :uid3 AND fr.next_review <= :today',
            ['uid' => $uid, 'uid2' => $uid, 'uid3' => $uid, 'today' => date('Y-m-d')]);

        $this->view('flashcards/index', [
            'pageTitle' => 'Flashcards',
            'decks'     => $decks,
            'dueCount'  => $dueCount,
        ]);
    }

    public function view(int $deckId): void
    {
        $deck = Database::fetch('SELECT * FROM flashcard_decks WHERE id = :id', ['id' => $deckId]);
        if (!$deck) { $this->redirect('flashcards'); }
        $cards = Database::fetchAll('SELECT * FROM flashcards WHERE deck_id = :did ORDER BY sort_order, id', ['did' => $deckId]);
        $this->view('flashcards/view', [
            'pageTitle' => $deck['title'],
            'deck'      => $deck,
            'cards'     => $cards,
        ]);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($this->input('title', ''));
            $desc  = trim($this->input('description', ''));
            $courseId = (int) $this->input('course_id', 0) ?: null;
            $isPublic = (int) ($this->input('is_public', '0') === '1');
            $deckId = Database::insert('flashcard_decks', [
                'course_id'  => $courseId,
                'user_id'    => Auth::id(),
                'title'      => $title,
                'description'=> $desc,
                'is_public'  => $isPublic,
                'is_ai_generated' => 0,
            ]);
            // Add cards
            $fronts = (array) ($this->input('front', []));
            $backs  = (array) ($this->input('back', []));
            for ($i = 0; $i < count($fronts); $i++) {
                if (!empty($fronts[$i]) && !empty($backs[$i])) {
                    Database::insert('flashcards', [
                        'deck_id'    => $deckId,
                        'front'      => $fronts[$i],
                        'back'       => $backs[$i],
                        'sort_order' => $i,
                    ]);
                }
            }
            $this->flash('success', 'Deck created.');
            $this->redirect('flashcards/view/' . $deckId);
        }
        $courses = Auth::isStudent()
            ? Database::fetchAll("SELECT c.id, c.title FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.student_id = :uid AND e.status = 'active'", ['uid' => Auth::id()])
            : Database::fetchAll("SELECT id, title FROM courses WHERE teacher_id = :tid ORDER BY title", ['tid' => Auth::id()]);
        $this->view('flashcards/create', [
            'pageTitle' => 'Create Deck',
            'courses'   => $courses,
        ]);
    }

    public function study(int $deckId): void
    {
        $deck = Database::fetch('SELECT * FROM flashcard_decks WHERE id = :id', ['id' => $deckId]);
        if (!$deck) { $this->redirect('flashcards'); }
        // Initialize review rows for all cards in deck that user hasn't seen yet
        $cards = Database::fetchAll('SELECT * FROM flashcards WHERE deck_id = :did ORDER BY sort_order, id', ['did' => $deckId]);
        foreach ($cards as $c) {
            $exists = Database::fetch('SELECT id FROM flashcard_reviews WHERE flashcard_id = :fid AND user_id = :uid', ['fid' => $c['id'], 'uid' => Auth::id()]);
            if (!$exists) {
                Database::insert('flashcard_reviews', [
                    'flashcard_id'  => $c['id'],
                    'user_id'       => Auth::id(),
                    'ease_factor'   => 2.50,
                    'interval_days' => 0,
                    'repetitions'   => 0,
                    'next_review'   => date('Y-m-d'),
                ]);
            }
        }
        // Pick cards due today (or all if none due — first study)
        $due = Database::fetchAll(
            "SELECT f.*, fr.id AS review_id, fr.ease_factor, fr.interval_days, fr.repetitions
             FROM flashcards f
             JOIN flashcard_reviews fr ON fr.flashcard_id = f.id
             WHERE f.deck_id = :did AND fr.user_id = :uid AND fr.next_review <= :today
             ORDER BY f.sort_order",
            ['did' => $deckId, 'uid' => Auth::id(), 'today' => date('Y-m-d')]
        );
        if (empty($due)) {
            $this->flash('success', 'No cards due for review right now. Come back later!');
            $this->redirect('flashcards/view/' . $deckId);
        }
        $this->view('flashcards/study', [
            'pageTitle' => 'Studying · ' . $deck['title'],
            'deck'      => $deck,
            'cards'     => $due,
        ]);
    }

    /** SM-2 algorithm: process a review rating (1=again, 2=hard, 3=good, 4=easy). */
    public function review(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false], 405);
        $reviewId = (int) $this->input('review_id', 0);
        $rating = (int) $this->input('rating', 3);
        if ($rating < 1) $rating = 1;
        if ($rating > 4) $rating = 4;
        $review = Database::fetch('SELECT * FROM flashcard_reviews WHERE id = :id AND user_id = :uid', ['id' => $reviewId, 'uid' => Auth::id()]);
        if (!$review) $this->json(['ok' => false, 'error' => 'Review not found'], 404);

        $ef = (float) $review['ease_factor'];
        $reps = (int) $review['repetitions'];
        $interval = (int) $review['interval_days'];

        // SM-2 algorithm
        if ($rating < 3) {
            // Failed — reset
            $reps = 0;
            $interval = 1;
        } else {
            // Passed
            if ($reps === 0) {
                $interval = 1;
            } elseif ($reps === 1) {
                $interval = 6;
            } else {
                $interval = (int) round($interval * $ef);
            }
            $reps++;
            // Update ease factor
            $ef = $ef + (0.1 - (5 - $rating) * (0.08 + (5 - $rating) * 0.02));
            if ($ef < 1.3) $ef = 1.3;
        }
        $nextReview = date('Y-m-d', strtotime("+$interval days"));

        Database::update('flashcard_reviews', [
            'ease_factor'   => round($ef, 2),
            'interval_days' => $interval,
            'repetitions'   => $reps,
            'next_review'   => $nextReview,
            'last_reviewed' => date('Y-m-d'),
        ], 'id = :id', ['id' => $reviewId]);

        Gamification::award(Auth::id(), 'flashcard_review');
        $this->json(['ok' => true, 'next_review' => $nextReview, 'interval' => $interval]);
    }

    /** AI: auto-generate a deck from course content. */
    public function generateFromCourse(int $courseId): void
    {
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect('flashcards'); }
        $canAccess = Auth::isAdmin() || (Auth::isTeacher() && $course['teacher_id'] == Auth::id()) || Auth::isEnrolled($courseId);
        if (!$canAccess) { $this->flash('error', 'Access denied.'); $this->redirect('flashcards'); }
        $lessons = Database::fetchAll(
            "SELECT l.title, l.content FROM lessons l JOIN modules m ON l.module_id = m.id
             WHERE m.course_id = :cid AND l.content_type = 'text' AND l.content IS NOT NULL AND l.content != ''",
            ['cid' => $courseId]
        );
        if (empty($lessons)) { $this->flash('error', 'This course has no text lessons to generate from.'); $this->redirect('flashcards'); }
        $deckId = Database::insert('flashcard_decks', [
            'course_id'      => $courseId,
            'user_id'        => Auth::id(),
            'title'          => 'AI Flashcards · ' . $course['title'],
            'description'    => 'Auto-generated from ' . count($lessons) . ' lesson(s)',
            'is_ai_generated'=> 1,
        ]);
        $totalCards = 0;
        foreach ($lessons as $l) {
            $text = strip_tags($l['content']);
            $cards = AIEngine::generateFlashcards($text, 8);
            foreach ($cards as $i => $c) {
                Database::insert('flashcards', [
                    'deck_id'    => $deckId,
                    'front'      => $c['front'],
                    'back'       => $c['back'],
                    'sort_order' => $totalCards + $i,
                ]);
            }
            $totalCards += count($cards);
        }
        $this->flash('success', "AI generated $totalCards flashcards from course content.");
        $this->redirect('flashcards/view/' . $deckId);
    }

    public function delete(int $deckId): void
    {
        $deck = Database::fetch('SELECT * FROM flashcard_decks WHERE id = :id', ['id' => $deckId]);
        if (!$deck) { $this->redirect('flashcards'); }
        if ($deck['user_id'] != Auth::id() && !Auth::isAdmin()) {
            $this->flash('error', 'You can only delete your own decks.');
            $this->redirect('flashcards');
        }
        Database::delete('flashcard_decks', 'id = :id', ['id' => $deckId]);
        $this->flash('success', 'Deck deleted.');
        $this->redirect('flashcards');
    }
}
