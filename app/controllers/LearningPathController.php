<?php
/**
 * doodle - Learning Paths controller
 *
 * Sequences of courses with prerequisites; students enroll in a path
 * and progress through courses in order.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class LearningPathController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireLogin();
    }

    public function index(): void
    {
        $paths = Database::fetchAll(
            "SELECT lp.*, u.full_name AS creator_name,
                    (SELECT COUNT(*) FROM learning_path_courses WHERE path_id = lp.id) AS course_count,
                    (SELECT COUNT(*) FROM learning_path_enrollments WHERE path_id = lp.id) AS enroll_count
             FROM learning_paths lp
             JOIN users u ON lp.creator_id = u.id
             WHERE lp.is_published = 1 OR lp.creator_id = :uid
             ORDER BY lp.created_at DESC",
            ['uid' => Auth::id()]
        );
        $this->view('learning_paths/index', [
            'pageTitle' => 'Learning Paths',
            'paths'     => $paths,
        ]);
    }

    public function view(int $pathId): void
    {
        $path = Database::fetch(
            "SELECT lp.*, u.full_name AS creator_name
             FROM learning_paths lp JOIN users u ON lp.creator_id = u.id
             WHERE lp.id = :id",
            ['id' => $pathId]
        );
        if (!$path) { $this->redirect('learning_paths'); }
        $courses = Database::fetchAll(
            "SELECT lpc.sort_order, lpc.is_required, c.*, u.full_name AS teacher_name,
                    e.progress, e.status AS enroll_status
             FROM learning_path_courses lpc
             JOIN courses c ON lpc.course_id = c.id
             JOIN users u ON c.teacher_id = u.id
             LEFT JOIN enrollments e ON e.course_id = c.id AND e.student_id = :uid
             WHERE lpc.path_id = :pid
             ORDER BY lpc.sort_order, c.title",
            ['pid' => $pathId, 'uid' => Auth::id()]
        );
        $pathEnrollment = Auth::isStudent()
            ? Database::fetch('SELECT * FROM learning_path_enrollments WHERE path_id = :pid AND user_id = :uid', ['pid' => $pathId, 'uid' => Auth::id()])
            : null;
        $enrollCount = Database::count('learning_path_enrollments', 'path_id = :pid', ['pid' => $pathId]);
        $this->view('learning_paths/view', [
            'pageTitle' => $path['title'],
            'path'      => $path,
            'courses'   => $courses,
            'enrolled'  => $pathEnrollment !== null,
            'enrollCount' => $enrollCount,
        ]);
    }

    public function create(): void
    {
        Auth::requireAnyRole(['teacher', 'admin']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($this->input('title', ''));
            $desc = trim($this->input('description', ''));
            $hours = (int) $this->input('estimated_hours', 0) ?: null;
            $courseIds = array_values(array_filter(array_map('intval', (array) $this->input('courses', [])), fn($c) => $c > 0));
            if (empty($title) || count($courseIds) < 1) {
                $this->flash('error', 'Title and at least one course are required.');
                $this->redirect('learning_paths/create');
            }
            $slug = slugify($title);
            $i = 1;
            while (Database::fetch('SELECT id FROM learning_paths WHERE slug = :s', ['s' => $slug])) {
                $slug = slugify($title) . '-' . $i++;
            }
            $pathId = Database::insert('learning_paths', [
                'title'           => $title,
                'slug'            => $slug,
                'description'     => $desc,
                'creator_id'      => Auth::id(),
                'is_published'    => 1,
                'estimated_hours' => $hours,
            ]);
            foreach ($courseIds as $i => $cid) {
                Database::insert('learning_path_courses', [
                    'path_id'    => $pathId,
                    'course_id'  => $cid,
                    'sort_order' => $i,
                    'is_required'=> 1,
                ]);
            }
            $this->flash('success', 'Learning path created.');
            $this->redirect('learning_paths/view/' . $pathId);
        }
        // Pickable courses: teachers see their own; admins see all
        if (Auth::isAdmin()) {
            $courses = Database::fetchAll("SELECT id, title FROM courses WHERE status = 'published' ORDER BY title");
        } else {
            $courses = Database::fetchAll("SELECT id, title FROM courses WHERE teacher_id = :tid AND status = 'published' ORDER BY title", ['tid' => Auth::id()]);
        }
        $this->view('learning_paths/create', [
            'pageTitle' => 'Create Learning Path',
            'courses'   => $courses,
        ]);
    }

    public function enroll(int $pathId): void
    {
        if (!Auth::isStudent()) { $this->flash('error', 'Only students can enroll in paths.'); $this->redirect('learning_paths/view/' . $pathId); }
        $existing = Database::fetch('SELECT id FROM learning_path_enrollments WHERE path_id = :pid AND user_id = :uid', ['pid' => $pathId, 'uid' => Auth::id()]);
        if (!$existing) {
            Database::insert('learning_path_enrollments', [
                'path_id' => $pathId, 'user_id' => Auth::id(),
            ]);
            // Auto-enroll in first course
            $firstCourse = Database::fetch('SELECT course_id FROM learning_path_courses WHERE path_id = :pid ORDER BY sort_order LIMIT 1', ['pid' => $pathId]);
            if ($firstCourse && !Auth::isEnrolled($firstCourse['course_id'])) {
                Database::insert('enrollments', [
                    'course_id' => $firstCourse['course_id'], 'student_id' => Auth::id(),
                    'status' => 'active', 'progress' => 0, 'enrolled_at' => date('Y-m-d H:i:s'),
                ]);
                Database::query('UPDATE courses SET enroll_count = enroll_count + 1 WHERE id = :id', ['id' => $firstCourse['course_id']]);
                Gamification::award(Auth::id(), 'course_enroll');
            }
            $this->flash('success', 'Enrolled in learning path!');
        } else {
            $this->flash('info', 'You are already enrolled.');
        }
        $this->redirect('learning_paths/view/' . $pathId);
    }

    public function delete(int $pathId): void
    {
        $path = Database::fetch('SELECT * FROM learning_paths WHERE id = :id', ['id' => $pathId]);
        if (!$path) { $this->redirect('learning_paths'); }
        if ($path['creator_id'] != Auth::id() && !Auth::isAdmin()) { Auth::requireRole('admin'); }
        Database::delete('learning_paths', 'id = :id', ['id' => $pathId]);
        $this->flash('success', 'Learning path deleted.');
        $this->redirect('learning_paths');
    }
}
