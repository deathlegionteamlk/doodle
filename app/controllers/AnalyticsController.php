<?php
/**
 * doodle - Analytics controller
 *
 * Engagement, performance, and completion analytics for admins and teachers.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class AnalyticsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireAnyRole(['admin', 'teacher']);
    }

    public function index(): void
    {
        if (Auth::isAdmin()) {
            $stats = $this->adminStats();
        } else {
            $stats = $this->teacherStats(Auth::id());
        }
        $this->view('analytics/index', [
            'pageTitle' => 'Analytics',
            'stats'     => $stats,
        ]);
    }

    private function adminStats(): array
    {
        // Activity over last 30 days
        $activity = Database::fetchAll(
            "SELECT DATE(created_at) AS d, COUNT(*) AS c, 'enrollments' AS type
             FROM enrollments WHERE created_at >= :start GROUP BY DATE(created_at)
             UNION ALL
             SELECT DATE(created_at), COUNT(*), 'submissions' FROM submissions WHERE created_at >= :start2 GROUP BY DATE(created_at)
             UNION ALL
             SELECT DATE(created_at), COUNT(*), 'lesson_completions' FROM lesson_completions WHERE created_at >= :start3 GROUP BY DATE(created_at)",
            ['start' => date('Y-m-d', strtotime('-30 days')), 'start2' => date('Y-m-d', strtotime('-30 days')), 'start3' => date('Y-m-d', strtotime('-30 days'))]
        );
        $activityByDay = [];
        foreach ($activity as $a) {
            $d = $a['d'];
            if (!isset($activityByDay[$d])) $activityByDay[$d] = ['enrollments' => 0, 'submissions' => 0, 'lesson_completions' => 0];
            $activityByDay[$d][$a['type']] = (int) $a['c'];
        }
        ksort($activityByDay);

        // Top 10 courses by enrollments
        $topCourses = Database::fetchAll(
            "SELECT c.title, COUNT(e.id) AS enroll_count, c.enroll_count AS total
             FROM courses c LEFT JOIN enrollments e ON e.course_id = c.id AND e.status = 'active'
             WHERE c.status = 'published'
             GROUP BY c.id ORDER BY enroll_count DESC LIMIT 10"
        );
        // Completion rates
        $completionRates = Database::fetchAll(
            "SELECT c.title,
                    COUNT(e.id) AS enrolled,
                    SUM(CASE WHEN e.progress >= 100 THEN 1 ELSE 0 END) AS completed
             FROM courses c LEFT JOIN enrollments e ON e.course_id = c.id AND e.status = 'active'
             WHERE c.status = 'published'
             GROUP BY c.id ORDER BY enrolled DESC LIMIT 10"
        );
        return [
            'activityByDay'    => $activityByDay,
            'topCourses'       => $topCourses,
            'completionRates'  => $completionRates,
            'totalUsers'       => Database::count('users'),
            'totalCourses'     => Database::count('courses'),
            'totalEnrollments' => Database::count('enrollments'),
            'totalSubmissions' => Database::count('submissions'),
            'avgProgress'      => (float) (Database::fetch('SELECT AVG(progress) AS a FROM enrollments WHERE status = "active"')['a'] ?? 0),
        ];
    }

    private function teacherStats(int $tid): array
    {
        $activity = Database::fetchAll(
            "SELECT DATE(created_at) AS d, COUNT(*) AS c FROM lesson_completions lc
             JOIN lessons l ON lc.lesson_id = l.id
             JOIN modules m ON l.module_id = m.id
             JOIN courses c ON m.course_id = c.id
             WHERE c.teacher_id = :tid AND lc.created_at >= :start
             GROUP BY DATE(lc.created_at) ORDER BY d",
            ['tid' => $tid, 'start' => date('Y-m-d', strtotime('-30 days'))]
        );
        $activityByDay = [];
        foreach ($activity as $a) $activityByDay[$a['d']] = (int) $a['c'];

        $courses = Database::fetchAll(
            "SELECT c.title, COUNT(e.id) AS enrolled,
                    AVG(e.progress) AS avg_progress,
                    SUM(CASE WHEN e.progress >= 100 THEN 1 ELSE 0 END) AS completed
             FROM courses c LEFT JOIN enrollments e ON e.course_id = c.id AND e.status = 'active'
             WHERE c.teacher_id = :tid
             GROUP BY c.id ORDER BY enrolled DESC",
            ['tid' => $tid]
        );
        $submissionStats = Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN s.status = 'submitted' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN s.status = 'graded' THEN 1 ELSE 0 END) AS graded,
                    AVG(s.score) AS avg_score
             FROM submissions s
             JOIN assignments a ON s.assignment_id = a.id
             JOIN courses c ON a.course_id = c.id
             WHERE c.teacher_id = :tid",
            ['tid' => $tid]
        );
        return [
            'activityByDay'   => $activityByDay,
            'courses'         => $courses,
            'submissionStats' => $submissionStats,
        ];
    }

    /** Per-student analytics for teachers. */
    public function student(int $studentId): void
    {
        $student = Database::fetch('SELECT * FROM users WHERE id = :id', ['id' => $studentId]);
        if (!$student) { $this->redirect('analytics'); }
        $enrollments = Database::fetchAll(
            "SELECT e.*, c.title FROM enrollments e JOIN courses c ON e.course_id = c.id
             WHERE e.student_id = :sid AND (c.teacher_id = :tid OR :is_admin = 1)
             ORDER BY e.enrolled_at DESC",
            ['sid' => $studentId, 'tid' => Auth::id(), 'is_admin' => Auth::isAdmin() ? 1 : 0]
        );
        $attempts = Database::fetchAll(
            "SELECT att.*, q.title AS quiz_title, c.title AS course_title
             FROM attempts att JOIN quizzes q ON att.quiz_id = q.id
             JOIN courses c ON q.course_id = c.id
             WHERE att.student_id = :sid AND (c.teacher_id = :tid OR :is_admin = 1)
             ORDER BY att.submitted_at DESC LIMIT 20",
            ['sid' => $studentId, 'tid' => Auth::id(), 'is_admin' => Auth::isAdmin() ? 1 : 0]
        );
        $this->view('analytics/student', [
            'pageTitle'   => 'Analytics · ' . $student['full_name'],
            'student'     => $student,
            'enrollments' => $enrollments,
            'attempts'    => $attempts,
        ]);
    }
}
