<?php
/**
 * doodle - Home / Dashboard controller
 *
 * @package doodle
 * @author  Death Legion Team
 */

class HomeController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            $this->landing();
            return;
        }
        // Route by role
        $role = Auth::role();
        if ($role === 'admin')   { $this->adminHome(); return; }
        if ($role === 'teacher') { $this->teacherHome(); return; }
        $this->studentHome();
    }

    /** Public landing page (when logged out). */
    private function landing(): void
    {
        $stats = [
            'courses'   => Database::count('courses', "status = 'published'"),
            'students'  => Database::count('users', "role = 'student'"),
            'teachers'  => Database::count('users', "role = 'teacher'"),
            'lessons'   => Database::count('lessons'),
        ];
        $featured = Database::fetchAll(
            "SELECT c.*, u.full_name AS teacher_name, cat.name AS category_name
             FROM courses c
             JOIN users u ON c.teacher_id = u.id
             LEFT JOIN categories cat ON c.category_id = cat.id
             WHERE c.status = 'published' AND c.visibility = 'public'
             ORDER BY c.enroll_count DESC, c.created_at DESC
             LIMIT 6"
        );
        $this->viewRaw('home/landing', [
            'pageTitle' => APP_NAME . ' · Personalized Learning Platform',
            'stats'     => $stats,
            'featured'  => $featured,
        ]);
    }

    private function adminHome(): void
    {
        $stats = [
            'users'      => Database::count('users'),
            'students'   => Database::count('users', "role = 'student'"),
            'teachers'   => Database::count('users', "role = 'teacher'"),
            'courses'    => Database::count('courses'),
            'published'  => Database::count('courses', "status = 'published'"),
            'enrollments'=> Database::count('enrollments'),
            'submissions'=> Database::count('submissions', "status = 'submitted'"),
            'forum'      => Database::count('forum_topics'),
        ];
        $recentUsers = Database::fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 6");
        $recentCourses = Database::fetchAll("SELECT c.*, u.full_name AS teacher_name FROM courses c JOIN users u ON c.teacher_id = u.id ORDER BY c.created_at DESC LIMIT 6");
        $this->view('admin/dashboard', [
            'pageTitle' => 'Admin Dashboard',
            'stats'     => $stats,
            'recentUsers' => $recentUsers,
            'recentCourses' => $recentCourses,
        ]);
    }

    private function teacherHome(): void
    {
        $tid = Auth::id();
        $stats = [
            'courses'    => Database::count('courses', 'teacher_id = :tid', ['tid' => $tid]),
            'published'  => Database::count('courses', 'teacher_id = :tid AND status = :s', ['tid' => $tid, 's' => 'published']),
            'students'   => (int) Database::fetch("SELECT COUNT(DISTINCT e.student_id) AS c FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.teacher_id = :tid", ['tid' => $tid])['c'],
            'pending'    => Database::count('submissions s JOIN assignments a ON s.assignment_id = a.id JOIN courses c ON a.course_id = c.id', 'c.teacher_id = :tid AND s.status = :st', ['tid' => $tid, 'st' => 'submitted']),
            'lessons'    => (int) Database::fetch("SELECT COUNT(*) AS c FROM lessons l JOIN modules m ON l.module_id = m.id JOIN courses c ON m.course_id = c.id WHERE c.teacher_id = :tid", ['tid' => $tid])['c'],
            'quizzes'    => Database::count('quizzes q JOIN courses c ON q.course_id = c.id', 'c.teacher_id = :tid', ['tid' => $tid]),
        ];
        $courses = Database::fetchAll(
            "SELECT c.*, COUNT(e.id) AS student_count
             FROM courses c
             LEFT JOIN enrollments e ON e.course_id = c.id AND e.status = 'active'
             WHERE c.teacher_id = :tid
             GROUP BY c.id
             ORDER BY c.created_at DESC
             LIMIT 6",
            ['tid' => $tid]
        );
        $pending = Database::fetchAll(
            "SELECT s.*, a.title AS assignment_title, c.title AS course_title, u.full_name AS student_name, u.id AS student_id
             FROM submissions s
             JOIN assignments a ON s.assignment_id = a.id
             JOIN courses c ON a.course_id = c.id
             JOIN users u ON s.student_id = u.id
             WHERE c.teacher_id = :tid AND s.status = :st
             ORDER BY s.submitted_at DESC
             LIMIT 8",
            ['tid' => $tid, 'st' => 'submitted']
        );
        $this->view('teacher/dashboard', [
            'pageTitle' => 'Teacher Studio',
            'stats'     => $stats,
            'courses'   => $courses,
            'pending'   => $pending,
        ]);
    }

    private function studentHome(): void
    {
        $sid = Auth::id();
        $enrollments = Database::fetchAll(
            "SELECT e.*, c.title, c.slug, c.cover_image, u.full_name AS teacher_name,
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
            'enrolled'    => count($enrollments),
            'completed'   => Database::count('enrollments', "student_id = :sid AND status = 'completed'", ['sid' => $sid]),
            'inProgress'  => count(array_filter($enrollments, fn($e) => $e['progress'] > 0 && $e['progress'] < 100)),
            'quizzes'     => Database::count('attempts', 'student_id = :sid', ['sid' => $sid]),
        ];
        $upcoming = Database::fetchAll(
            "SELECT a.*, c.title AS course_title, c.slug AS course_slug
             FROM assignments a
             JOIN enrollments e ON e.course_id = a.course_id AND e.student_id = :sid AND e.status = 'active'
             JOIN courses c ON a.course_id = c.id
             WHERE a.due_date IS NOT NULL AND a.due_date > :now
             ORDER BY a.due_date ASC
             LIMIT 5",
            ['sid' => $sid, 'now' => date('Y-m-d H:i:s')]
        );
        $this->view('student/dashboard', [
            'pageTitle'   => 'My Learning',
            'stats'       => $stats,
            'enrollments' => $enrollments,
            'upcoming'    => $upcoming,
        ]);
    }
}
