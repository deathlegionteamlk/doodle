<?php
/**
 * doodle - Polls & Surveys controller
 *
 * Live polls within courses.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class PollController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireLogin();
    }

    public function course(int $courseId): void
    {
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect(''); }
        $canAccess = Auth::isAdmin() || (Auth::isTeacher() && $course['teacher_id'] == Auth::id()) || Auth::isEnrolled($courseId);
        if (!$canAccess) { $this->flash('error', 'Access denied.'); $this->redirect(''); }

        $polls = Database::fetchAll(
            "SELECT p.*, u.full_name AS author_name,
                    (SELECT COUNT(DISTINCT pr.user_id) FROM poll_responses pr WHERE pr.poll_id = p.id) AS response_count,
                    (SELECT COUNT(*) FROM poll_options WHERE poll_id = p.id) AS option_count
             FROM polls p JOIN users u ON p.user_id = u.id
             WHERE p.course_id = :cid
             ORDER BY p.created_at DESC",
            ['cid' => $courseId]
        );
        $this->view('polls/course', [
            'pageTitle' => 'Polls · ' . $course['title'],
            'course'    => $course,
            'polls'     => $polls,
        ]);
    }

    public function view(int $pollId): void
    {
        $poll = Database::fetch(
            "SELECT p.*, c.title AS course_title, c.id AS course_id, c.teacher_id, u.full_name AS author_name
             FROM polls p
             JOIN courses c ON p.course_id = c.id
             JOIN users u ON p.user_id = u.id
             WHERE p.id = :id",
            ['id' => $pollId]
        );
        if (!$poll) { $this->redirect(''); }
        $canAccess = Auth::isAdmin() || (Auth::isTeacher() && $poll['teacher_id'] == Auth::id()) || Auth::isEnrolled($poll['course_id']);
        if (!$canAccess) { $this->flash('error', 'Access denied.'); $this->redirect(''); }

        // Handle voting
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $optionIds = (array) $this->input('option_ids', []);
            if (!$poll['allow_multiple']) $optionIds = array_slice($optionIds, 0, 1);
            foreach ($optionIds as $oid) {
                $oid = (int) $oid;
                $opt = Database::fetch('SELECT id FROM poll_options WHERE id = :id AND poll_id = :pid', ['id' => $oid, 'pid' => $pollId]);
                if ($opt) {
                    try {
                        Database::insert('poll_responses', [
                            'poll_id' => $pollId, 'option_id' => $oid, 'user_id' => Auth::id(),
                        ]);
                    } catch (PDOException $e) { /* already voted for this option */ }
                }
            }
            $this->flash('success', 'Vote recorded.');
            $this->redirect('poll/view/' . $pollId);
        }

        $options = Database::fetchAll('SELECT * FROM poll_options WHERE poll_id = :pid ORDER BY sort_order, id', ['pid' => $pollId]);
        $totalResponses = Database::count('poll_responses', 'poll_id = :pid', ['pid' => $pollId]);
        $userResponses = Database::fetchAll('SELECT option_id FROM poll_responses WHERE poll_id = :pid AND user_id = :uid', ['pid' => $pollId, 'uid' => Auth::id()]);
        $userVotes = array_column($userResponses, 'option_id');

        foreach ($options as &$opt) {
            $opt['count'] = Database::count('poll_responses', 'option_id = :oid', ['oid' => $opt['id']]);
            $opt['pct'] = $totalResponses > 0 ? round(($opt['count'] / $totalResponses) * 100, 1) : 0;
        }

        $hasVoted = count($userVotes) > 0;

        $this->view('polls/view', [
            'pageTitle'      => 'Poll · ' . $poll['question'],
            'poll'           => $poll,
            'options'        => $options,
            'totalResponses' => $totalResponses,
            'userVotes'      => $userVotes,
            'hasVoted'       => $hasVoted,
        ]);
    }

    public function create(int $courseId): void
    {
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect(''); }
        if (!Auth::ownsCourse($courseId)) { Auth::requireRole('admin'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $question = trim($this->input('question', ''));
            $description = trim($this->input('description', ''));
            $type = $this->input('poll_type', 'multiple');
            $allowMultiple = (int) ($this->input('allow_multiple', '0') === '1');
            $isAnonymous = (int) ($this->input('is_anonymous', '0') === '1');
            $options = array_values(array_filter(array_map('trim', (array) $this->input('options', [])), fn($o) => $o !== ''));
            if (!empty($question) && count($options) >= 2) {
                $pollId = Database::insert('polls', [
                    'course_id'      => $courseId,
                    'user_id'        => Auth::id(),
                    'question'       => $question,
                    'description'    => $description,
                    'poll_type'      => $type,
                    'is_anonymous'   => $isAnonymous,
                    'allow_multiple' => $allowMultiple,
                    'is_active'      => 1,
                ]);
                foreach ($options as $i => $opt) {
                    Database::insert('poll_options', [
                        'poll_id' => $pollId, 'option_text' => $opt, 'sort_order' => $i,
                    ]);
                }
                $this->flash('success', 'Poll created.');
            } else {
                $this->flash('error', 'Question and at least 2 options are required.');
            }
            $this->redirect('poll/course/' . $courseId);
        }
    }

    public function delete(int $pollId): void
    {
        $poll = Database::fetch('SELECT * FROM polls WHERE id = :id', ['id' => $pollId]);
        if (!$poll) { $this->redirect(''); }
        if (!Auth::ownsCourse($poll['course_id']) && $poll['user_id'] != Auth::id()) { Auth::requireRole('admin'); }
        Database::delete('polls', 'id = :id', ['id' => $pollId]);
        $this->flash('success', 'Poll deleted.');
        $this->redirect('poll/course/' . $poll['course_id']);
    }

    public function toggle(int $pollId): void
    {
        $poll = Database::fetch('SELECT * FROM polls WHERE id = :id', ['id' => $pollId]);
        if (!$poll) { $this->redirect(''); }
        if (!Auth::ownsCourse($poll['course_id'])) { Auth::requireRole('admin'); }
        Database::query('UPDATE polls SET is_active = 1 - is_active WHERE id = :id', ['id' => $pollId]);
        $this->redirect('poll/view/' . $pollId);
    }
}
