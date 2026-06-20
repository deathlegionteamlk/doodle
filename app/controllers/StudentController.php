<?php
/**
 * doodle - Student controller
 *
 * Student dashboard, my courses, my grades, and assignment submissions.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class StudentController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireAnyRole(['student', 'admin']);
    }

    public function index(): void
    {
        $sid = Auth::id();
        $enrollments = Database::fetchAll(
            "SELECT e.*, c.title, c.slug, c.cover_image, c.id AS course_id, u.full_name AS teacher_name,
                    (SELECT COUNT(*) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = c.id) AS lesson_count,
                    (SELECT COUNT(*) FROM lesson_completions lc JOIN lessons l ON lc.lesson_id = l.id JOIN modules m ON l.module_id = m.id WHERE m.course_id = c.id AND lc.student_id = e.student_id) AS completed
             FROM enrollments e
             JOIN courses c ON e.course_id = c.id
             JOIN users u ON c.teacher_id = u.id
             WHERE e.student_id = :sid AND e.status = 'active'
             ORDER BY e.enrolled_at DESC",
            ['sid' => $sid]
        );
        $stats = [
            'enrolled'   => count($enrollments),
            'completed'  => Database::count('enrollments', "student_id = :sid AND status = 'completed'", ['sid' => $sid]),
            'inProgress' => count(array_filter($enrollments, fn($e) => $e['progress'] > 0 && $e['progress'] < 100)),
            'quizzes'    => Database::count('attempts', 'student_id = :sid', ['sid' => $sid]),
            'avgScore'   => (float) (Database::fetch("SELECT AVG(percentage) AS a FROM attempts WHERE student_id = :sid", ['sid' => $sid])['a'] ?? 0),
        ];
        $upcoming = Database::fetchAll(
            "SELECT a.*, c.title AS course_title, c.id AS course_id
             FROM assignments a
             JOIN enrollments e ON e.course_id = a.course_id AND e.student_id = :sid AND e.status = 'active'
             JOIN courses c ON a.course_id = c.id
             WHERE a.due_date IS NOT NULL AND a.due_date > :now
             ORDER BY a.due_date ASC
             LIMIT 5",
            ['sid' => $sid, 'now' => date('Y-m-d H:i:s')]
        );
        $this->view('student/dashboard', [
            'pageTitle'  => 'My Learning',
            'stats'      => $stats,
            'enrollments'=> $enrollments,
            'upcoming'   => $upcoming,
        ]);
    }

    public function grades(int $courseId = 0): void
    {
        $sid = Auth::id();
        if ($courseId > 0) {
            // Single course gradebook
            $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
            if (!$course) { $this->redirect('student/grades'); }
            $grades = Database::fetchAll(
                "SELECT g.*, u.full_name AS grader_name
                 FROM grades g LEFT JOIN users u ON g.graded_by = u.id
                 WHERE g.course_id = :cid AND g.student_id = :sid
                 ORDER BY g.graded_at DESC",
                ['cid' => $courseId, 'sid' => $sid]
            );
            $attempts = Database::fetchAll(
                "SELECT att.*, q.title AS quiz_title FROM attempts att JOIN quizzes q ON att.quiz_id = q.id
                 WHERE q.course_id = :cid AND att.student_id = :sid
                 ORDER BY att.submitted_at DESC",
                ['cid' => $courseId, 'sid' => $sid]
            );
            $this->view('student/grades_course', [
                'pageTitle' => 'My Grades · ' . $course['title'],
                'course' => $course,
                'grades' => $grades,
                'attempts' => $attempts,
            ]);
            return;
        }

        // All enrolled courses
        $courses = Database::fetchAll(
            "SELECT c.*, e.progress,
                    (SELECT COUNT(*) FROM grades g WHERE g.course_id = c.id AND g.student_id = :sid AND g.score IS NOT NULL) AS graded_items,
                    (SELECT COALESCE(SUM(g.score), 0) FROM grades g WHERE g.course_id = c.id AND g.student_id = :sid) AS earned,
                    (SELECT COALESCE(SUM(g.max_score), 0) FROM grades g WHERE g.course_id = c.id AND g.student_id = :sid) AS possible
             FROM enrollments e
             JOIN courses c ON e.course_id = c.id
             WHERE e.student_id = :sid AND e.status = 'active'
             ORDER BY e.enrolled_at DESC",
            ['sid' => $sid]
        );
        $this->view('student/grades', [
            'pageTitle' => 'My Grades',
            'courses' => $courses,
        ]);
    }

    public function submitAssignment(int $id): void
    {
        $a = Database::fetch('SELECT * FROM assignments WHERE id = :id', ['id' => $id]);
        if (!$a) { $this->flash('error', 'Assignment not found.'); $this->redirect('student'); }
        if (!Auth::isEnrolled($a['course_id'])) { $this->flash('error', 'You must be enrolled to submit.'); $this->redirect('course/view/' . $a['course_id']); }
        $existing = Database::fetch('SELECT * FROM submissions WHERE assignment_id = :aid AND student_id = :sid', ['aid' => $id, 'sid' => Auth::id()]);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = trim($this->input('content', ''));
            $filePath = $existing['file_path'] ?? null;
            $fileName = $existing['file_name'] ?? null;
            if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                if (isset(ALLOWED_UPLOAD_TYPES[$ext]) && $_FILES['file']['size'] <= MAX_UPLOAD_SIZE) {
                    $safeName = 'submission_' . Auth::id() . '_' . time() . '.' . $ext;
                    if (!is_dir(UPLOAD_DIR . '/submissions')) mkdir(UPLOAD_DIR . '/submissions', 0755, true);
                    move_uploaded_file($_FILES['file']['tmp_name'], UPLOAD_DIR . '/submissions/' . $safeName);
                    $filePath = 'submissions/' . $safeName;
                    $fileName = $_FILES['file']['name'];
                }
            }
            if ($existing) {
                Database::update('submissions', [
                    'content' => $content,
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'submitted_at' => date('Y-m-d H:i:s'),
                    'status' => 'submitted',
                ], 'id = :id', ['id' => $existing['id']]);
            } else {
                Database::insert('submissions', [
                    'assignment_id' => $id,
                    'student_id' => Auth::id(),
                    'content' => $content,
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                ]);
                Gamification::award(Auth::id(), 'assignment_submit', $id);
            }
            $this->flash('success', 'Assignment submitted. Your teacher will grade it soon.');
            $this->redirect('course/view/' . $a['course_id']);
        }
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $a['course_id']]);
        $this->view('student/submit_assignment', [
            'pageTitle' => 'Submit · ' . $a['title'],
            'assignment' => $a,
            'course' => $course,
            'existing' => $existing,
        ]);
    }
}
