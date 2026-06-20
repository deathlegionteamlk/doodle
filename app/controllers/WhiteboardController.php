<?php
/**
 * doodle - Whiteboard controller
 *
 * Per-course collaborative whiteboard. Strokes are stored as JSON and
 * polled by clients for near-real-time sync.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class WhiteboardController extends Controller
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
        $this->assertCourseAccess($course);

        $boards = Database::fetchAll(
            "SELECT w.*, u.full_name AS creator_name,
                    (SELECT COUNT(*) FROM whiteboard_strokes WHERE whiteboard_id = w.id) AS stroke_count
             FROM whiteboards w JOIN users u ON w.created_by = u.id
             WHERE w.course_id = :cid ORDER BY w.created_at DESC",
            ['cid' => $courseId]
        );
        $this->view('whiteboard/course', [
            'pageTitle' => 'Whiteboards · ' . $course['title'],
            'course'    => $course,
            'boards'    => $boards,
        ]);
    }

    public function view(int $boardId): void
    {
        $board = Database::fetch(
            "SELECT w.*, c.title AS course_title, c.id AS course_id, c.teacher_id
             FROM whiteboards w JOIN courses c ON w.course_id = c.id
             WHERE w.id = :id",
            ['id' => $boardId]
        );
        if (!$board) { $this->redirect(''); }
        $this->assertCourseAccess($board);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->input('action') === 'clear') {
            Database::delete('whiteboard_strokes', 'whiteboard_id = :id', ['id' => $boardId]);
            $this->flash('success', 'Whiteboard cleared.');
            $this->redirect('whiteboard/view/' . $boardId);
        }

        $strokes = Database::fetchAll(
            "SELECT s.*, u.full_name AS author_name, u.avatar
             FROM whiteboard_strokes s JOIN users u ON s.user_id = u.id
             WHERE s.whiteboard_id = :bid ORDER BY s.id",
            ['bid' => $boardId]
        );
        $this->view('whiteboard/view', [
            'pageTitle' => 'Whiteboard · ' . $board['name'],
            'board'     => $board,
            'strokes'   => $strokes,
        ]);
    }

    public function create(int $courseId): void
    {
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect(''); }
        $this->assertCourseAccess($course);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($this->input('name', 'New Whiteboard'));
            $id = Database::insert('whiteboards', [
                'course_id'  => $courseId,
                'name'       => $name,
                'created_by' => Auth::id(),
            ]);
            $this->flash('success', 'Whiteboard created.');
            $this->redirect('whiteboard/view/' . $id);
        }
        $this->redirect('whiteboard/course/' . $courseId);
    }

    /** AJAX endpoint: save a stroke. */
    public function addStroke(): void
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false], 405);
        $boardId = (int) $this->input('whiteboard_id', 0);
        $board = Database::fetch('SELECT * FROM whiteboards WHERE id = :id', ['id' => $boardId]);
        if (!$board) $this->json(['ok' => false, 'error' => 'Not found'], 404);
        $this->assertCourseAccess($board);
        $data = $this->input('stroke_data', '');
        $color = $this->input('color', '#4F46E5');
        $tool = $this->input('tool', 'pen');
        // Validate stroke data is JSON
        json_decode($data);
        if (json_last_error() !== JSON_ERROR_NONE) $this->json(['ok' => false, 'error' => 'Invalid stroke data'], 400);
        $id = Database::insert('whiteboard_strokes', [
            'whiteboard_id' => $boardId,
            'user_id'       => Auth::id(),
            'stroke_data'   => $data,
            'color'         => substr($color, 0, 20),
            'tool'          => substr($tool, 0, 20),
        ]);
        $this->json(['ok' => true, 'stroke_id' => $id, 'author' => Auth::user()['full_name']]);
    }

    /** AJAX endpoint: poll for new strokes since a given ID. */
    public function poll(int $boardId, int $sinceId = 0): void
    {
        Auth::requireLogin();
        $board = Database::fetch('SELECT * FROM whiteboards WHERE id = :id', ['id' => $boardId]);
        if (!$board) $this->json(['ok' => false], 404);
        $this->assertCourseAccess($board);
        $strokes = Database::fetchAll(
            "SELECT s.*, u.full_name AS author_name
             FROM whiteboard_strokes s JOIN users u ON s.user_id = u.id
             WHERE s.whiteboard_id = :bid AND s.id > :since
             ORDER BY s.id",
            ['bid' => $boardId, 'since' => $sinceId]
        );
        $this->json(['ok' => true, 'strokes' => $strokes]);
    }

    public function delete(int $boardId): void
    {
        $board = Database::fetch(
            "SELECT w.*, c.teacher_id FROM whiteboards w JOIN courses c ON w.course_id = c.id WHERE w.id = :id",
            ['id' => $boardId]
        );
        if (!$board) { $this->redirect(''); }
        if (!Auth::isAdmin() && !(Auth::isTeacher() && $board['teacher_id'] == Auth::id()) && $board['created_by'] != Auth::id()) {
            $this->flash('error', 'Only the creator or course teacher can delete this.');
            $this->redirect('');
        }
        Database::delete('whiteboards', 'id = :id', ['id' => $boardId]);
        $this->flash('success', 'Whiteboard deleted.');
        $this->redirect('whiteboard/course/' . $board['course_id']);
    }

    private function assertCourseAccess(array $courseOrBoard): void
    {
        $courseId = $courseOrBoard['course_id'] ?? $courseOrBoard['id'];
        $teacherId = $courseOrBoard['teacher_id'] ?? null;
        if ($teacherId === null) {
            $course = Database::fetch('SELECT teacher_id FROM courses WHERE id = :id', ['id' => $courseId]);
            $teacherId = $course['teacher_id'] ?? 0;
        }
        $canAccess = Auth::isAdmin()
                  || (Auth::isTeacher() && $teacherId == Auth::id())
                  || (Auth::isStudent() && Auth::isEnrolled($courseId));
        if (!$canAccess) {
            $this->flash('error', 'Access denied.');
            $this->redirect('');
        }
    }
}
