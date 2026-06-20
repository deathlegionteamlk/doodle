<?php
/**
 * doodle - Course reviews + student notes + bookmarks + global search
 * Combined into one controller for related student-facing features.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class ReviewController extends Controller
{
    public function __construct() { parent::__construct(); Auth::requireLogin(); }

    // --- Course Reviews ---
    public function add(int $courseId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('course/view/' . $courseId);
        $rating = (int) $this->input('rating', 5);
        $review = trim($this->input('review', ''));
        $anon = (int) ($this->input('is_anonymous', '0') === '1');
        if ($rating < 1) $rating = 1;
        if ($rating > 5) $rating = 5;
        // Must be enrolled to review
        if (!Auth::isEnrolled($courseId) && !Auth::isAdmin()) {
            $this->flash('error', 'Only enrolled students can review.');
            $this->redirect('course/view/' . $courseId);
        }
        $existing = Database::fetch('SELECT id FROM course_reviews WHERE course_id = :cid AND user_id = :uid', ['cid' => $courseId, 'uid' => Auth::id()]);
        if ($existing) {
            Database::update('course_reviews', [
                'rating' => $rating, 'review' => $review, 'is_anonymous' => $anon, 'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $existing['id']]);
            $this->flash('success', 'Review updated.');
        } else {
            Database::insert('course_reviews', [
                'course_id' => $courseId, 'user_id' => Auth::id(),
                'rating' => $rating, 'review' => $review, 'is_anonymous' => $anon,
            ]);
            Gamification::award(Auth::id(), 'forum_post', $courseId);
            $this->flash('success', 'Review posted!');
        }
        $this->recalcCourseRating($courseId);
        $this->redirect('course/view/' . $courseId);
    }

    public function delete(int $reviewId): void
    {
        $r = Database::fetch('SELECT * FROM course_reviews WHERE id = :id', ['id' => $reviewId]);
        if (!$r) { $this->redirect(''); }
        if ($r['user_id'] != Auth::id() && !Auth::isAdmin()) { $this->flash('error', 'Not yours.'); $this->redirect(''); }
        Database::delete('course_reviews', 'id = :id', ['id' => $reviewId]);
        $this->recalcCourseRating($r['course_id']);
        $this->flash('success', 'Review deleted.');
        $this->redirect('course/view/' . $r['course_id']);
    }

    private function recalcCourseRating(int $courseId): void
    {
        $avg = Database::fetch('SELECT AVG(rating) AS a FROM course_reviews WHERE course_id = :cid', ['cid' => $courseId])['a'] ?? 0;
        Database::update('courses', ['rating' => round((float) $avg, 2)], 'id = :id', ['id' => $courseId]);
    }

    // --- Student Notes (per-lesson) ---
    public function note(int $lessonId): void
    {
        $lesson = Database::fetch("SELECT l.*, m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :id", ['id' => $lessonId]);
        if (!$lesson) { $this->redirect(''); }
        if (!Auth::isEnrolled($lesson['course_id']) && !Auth::ownsCourse($lesson['course_id']) && !Auth::isAdmin()) {
            $this->flash('error', 'Access denied.'); $this->redirect('');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = trim($this->input('content', ''));
            $existing = Database::fetch('SELECT id FROM student_notes WHERE user_id = :uid AND lesson_id = :lid', ['uid' => Auth::id(), 'lid' => $lessonId]);
            if ($existing) {
                Database::update('student_notes', ['content' => $content, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $existing['id']]);
            } else {
                Database::insert('student_notes', [
                    'user_id' => Auth::id(), 'lesson_id' => $lessonId, 'content' => $content,
                ]);
            }
            $this->json(['ok' => true]);
        }
        $this->redirect('');
    }

    public function myNotes(): void
    {
        $notes = Database::fetchAll(
            "SELECT n.*, l.title AS lesson_title, c.title AS course_title, c.id AS course_id
             FROM student_notes n
             JOIN lessons l ON n.lesson_id = l.id
             JOIN modules m ON l.module_id = m.id
             JOIN courses c ON m.course_id = c.id
             WHERE n.user_id = :uid
             ORDER BY n.updated_at DESC",
            ['uid' => Auth::id()]
        );
        $this->view('review/my_notes', [
            'pageTitle' => 'My Notes',
            'notes'     => $notes,
        ]);
    }

    // --- Bookmarks ---
    public function bookmark(int $lessonId): void
    {
        $lesson = Database::fetch("SELECT l.*, m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :id", ['id' => $lessonId]);
        if (!$lesson) $this->json(['ok' => false], 404);
        $existing = Database::fetch('SELECT id FROM bookmarks WHERE user_id = :uid AND lesson_id = :lid', ['uid' => Auth::id(), 'lid' => $lessonId]);
        if ($existing) {
            Database::delete('bookmarks', 'id = :id', ['id' => $existing['id']]);
            $this->json(['ok' => true, 'bookmarked' => false]);
        } else {
            Database::insert('bookmarks', ['user_id' => Auth::id(), 'lesson_id' => $lessonId]);
            $this->json(['ok' => true, 'bookmarked' => true]);
        }
    }

    public function myBookmarks(): void
    {
        $marks = Database::fetchAll(
            "SELECT b.*, l.title AS lesson_title, c.title AS course_title, c.id AS course_id
             FROM bookmarks b
             JOIN lessons l ON b.lesson_id = l.id
             JOIN modules m ON l.module_id = m.id
             JOIN courses c ON m.course_id = c.id
             WHERE b.user_id = :uid
             ORDER BY b.created_at DESC",
            ['uid' => Auth::id()]
        );
        $this->view('review/my_bookmarks', [
            'pageTitle' => 'My Bookmarks',
            'bookmarks' => $marks,
        ]);
    }

    // --- Global Search ---
    public function search(): void
    {
        $q = trim($this->input('q', ''));
        $results = ['courses' => [], 'lessons' => [], 'forum' => [], 'users' => []];
        $total = 0;
        if (strlen($q) >= 2) {
            $like = '%' . $q . '%';
            // Courses (public)
            $results['courses'] = Database::fetchAll(
                "SELECT id, title, description, level, enroll_count
                 FROM courses
                 WHERE status = 'published' AND visibility = 'public'
                   AND (title LIKE :q OR description LIKE :q)
                 ORDER BY enroll_count DESC LIMIT 10",
                ['q' => $like]
            );
            // Lessons (in enrolled courses)
            $enrolledClause = Auth::isStudent() ? "AND m.course_id IN (SELECT course_id FROM enrollments WHERE student_id = :uid AND status = 'active')" : "";
            $params = ['q' => $like];
            if (Auth::isStudent()) $params['uid'] = Auth::id();
            $results['lessons'] = Database::fetchAll(
                "SELECT l.id, l.title, c.title AS course_title, c.id AS course_id
                 FROM lessons l
                 JOIN modules m ON l.module_id = m.id
                 JOIN courses c ON m.course_id = c.id
                 WHERE (c.teacher_id = :tid OR c.visibility = 'public' $enrolledClause)
                   AND l.title LIKE :q
                 LIMIT 10",
                array_merge($params, ['tid' => Auth::id()])
            );
            // Forum
            $results['forum'] = Database::fetchAll(
                "SELECT t.id, t.title, c.title AS course_title, c.id AS course_id
                 FROM forum_topics t JOIN courses c ON t.course_id = c.id
                 WHERE t.title LIKE :q
                 LIMIT 10",
                ['q' => $like]
            );
            // Users (admin only)
            if (Auth::isAdmin()) {
                $results['users'] = Database::fetchAll(
                    "SELECT id, full_name, username, role FROM users WHERE full_name LIKE :q OR username LIKE :q LIMIT 10",
                    ['q' => $like]
                );
            }
            foreach ($results as $r) $total += count($r);
        }
        $this->view('review/search', [
            'pageTitle' => 'Search',
            'q'         => $q,
            'results'   => $results,
            'total'     => $total,
        ]);
    }
}
