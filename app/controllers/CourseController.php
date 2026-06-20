<?php
/**
 * doodle - Course controller
 *
 * Handles course detail page, enrollment, learning (lesson viewing),
 * and student-facing course content navigation.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class CourseController extends Controller
{
    public function view(int $id): void
    {
        $course = Database::fetch(
            "SELECT c.*, u.full_name AS teacher_name, u.bio AS teacher_bio, u.avatar AS teacher_avatar,
                    cat.name AS category_name
             FROM courses c
             JOIN users u ON c.teacher_id = u.id
             LEFT JOIN categories cat ON c.category_id = cat.id
             WHERE c.id = :id",
            ['id' => $id]
        );
        if (!$course) {
            http_response_code(404);
            $this->view('errors/404', ['pageTitle' => 'Course not found', 'message' => 'Course not found.']);
            return;
        }

        // Access control: if course is draft/private, only owner/admin/enrolled can see
        $canView = ($course['status'] === 'published' && $course['visibility'] === 'public')
                || Auth::isAdmin()
                || (Auth::isTeacher() && $course['teacher_id'] == Auth::id())
                || (Auth::isStudent() && Auth::isEnrolled($id));
        if (!$canView) {
            $this->flash('error', 'This course is not yet published.');
            $this->redirect('catalog');
        }

        $modules = Database::fetchAll(
            "SELECT m.*,
                    (SELECT COUNT(*) FROM lessons l WHERE l.module_id = m.id) AS lesson_count
             FROM modules m
             WHERE m.course_id = :cid
             ORDER BY m.sort_order, m.id",
            ['cid' => $id]
        );
        $totalLessons = 0;
        foreach ($modules as $m) $totalLessons += $m['lesson_count'];

        $assignments = Database::fetchAll("SELECT * FROM assignments WHERE course_id = :cid ORDER BY due_date, id", ['cid' => $id]);
        $quizzes     = Database::fetchAll("SELECT * FROM quizzes WHERE course_id = :cid ORDER BY id", ['cid' => $id]);
        $announcements = Database::fetchAll(
            "SELECT a.*, u.full_name AS author_name
             FROM announcements a
             JOIN users u ON a.user_id = u.id
             WHERE a.course_id = :cid OR a.course_id IS NULL
             ORDER BY a.created_at DESC LIMIT 5",
            ['cid' => $id]
        );

        $isEnrolled = Auth::isStudent() && Auth::isEnrolled($id);
        $isOwner    = Auth::isTeacher() && $course['teacher_id'] == Auth::id();
        $progress   = 0;
        $completedLessons = 0;
        if ($isEnrolled) {
            $enroll = Database::fetch("SELECT * FROM enrollments WHERE course_id = :cid AND student_id = :sid", ['cid' => $id, 'sid' => Auth::id()]);
            $progress = (float) $enroll['progress'];
            $completedLessons = (int) Database::count('lesson_completions lc JOIN lessons l ON lc.lesson_id = l.id JOIN modules m ON l.module_id = m.id', 'm.course_id = :cid AND lc.student_id = :sid', ['cid' => $id, 'sid' => Auth::id()]);
        }

        $this->view('course/view', [
            'pageTitle'   => $course['title'],
            'course'      => $course,
            'modules'     => $modules,
            'totalLessons'=> $totalLessons,
            'assignments' => $assignments,
            'quizzes'     => $quizzes,
            'announcements' => $announcements,
            'isEnrolled'  => $isEnrolled,
            'isOwner'     => $isOwner,
            'progress'    => $progress,
            'completedLessons' => $completedLessons,
        ]);
    }

    public function enroll(int $id): void
    {
        Auth::requireLogin();
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $id]);
        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->redirect('catalog');
        }
        if (!Auth::isStudent()) {
            $this->flash('error', 'Only student accounts can enroll in courses.');
            $this->redirect('course/view/' . $id);
        }
        // Enroll key check
        if (!empty($course['enroll_key'])) {
            $key = $this->input('enroll_key', '');
            if (!hash_equals($course['enroll_key'], $key)) {
                $this->flash('error', 'Incorrect enrollment key.');
                $this->redirect('course/view/' . $id);
            }
        }
        if (Auth::isEnrolled($id)) {
            $this->flash('info', 'You are already enrolled.');
            $this->redirect('course/learn/' . $id);
        }
        Database::insert('enrollments', [
            'course_id'  => $id,
            'student_id' => Auth::id(),
            'status'     => 'active',
            'progress'   => 0,
            'enrolled_at'=> date('Y-m-d H:i:s'),
        ]);
        Database::query('UPDATE courses SET enroll_count = enroll_count + 1 WHERE id = :id', ['id' => $id]);
        Gamification::award(Auth::id(), 'course_enroll', $id);

        // Notify teacher
        Database::insert('notifications', [
            'user_id'  => $course['teacher_id'],
            'title'    => 'New enrollment',
            'body'     => Auth::user()['full_name'] . ' enrolled in ' . $course['title'],
            'link'     => 'course/students/' . $id,
            'is_read'  => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->flash('success', 'You are now enrolled. Start learning!');
        $this->redirect('course/learn/' . $id);
    }

    public function unenroll(int $id): void
    {
        Auth::requireLogin();
        if (!Auth::isEnrolled($id)) {
            $this->flash('error', 'You are not enrolled in this course.');
            $this->redirect('course/view/' . $id);
        }
        Database::delete('enrollments', 'course_id = :cid AND student_id = :sid', ['cid' => $id, 'sid' => Auth::id()]);
        Database::query('UPDATE courses SET enroll_count = GREATEST(enroll_count - 1, 0) WHERE id = :id', ['id' => $id]);
        $this->flash('success', 'You have been unenrolled.');
        $this->redirect('course/view/' . $id);
    }

    /** Learning view — students browse lessons here. */
    public function learn(int $courseId, int $lessonId = 0): void
    {
        Auth::requireLogin();
        $course = Database::fetch(
            "SELECT c.*, u.full_name AS teacher_name
             FROM courses c JOIN users u ON c.teacher_id = u.id
             WHERE c.id = :id",
            ['id' => $courseId]
        );
        if (!$course) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Course not found.']);
            return;
        }
        $isOwner = Auth::isAdmin() || (Auth::isTeacher() && $course['teacher_id'] == Auth::id());
        if (!$isOwner && !Auth::isEnrolled($courseId)) {
            $this->flash('error', 'You must be enrolled to access course content.');
            $this->redirect('course/view/' . $courseId);
        }

        $modules = Database::fetchAll(
            "SELECT m.* FROM modules m WHERE m.course_id = :cid ORDER BY m.sort_order, m.id",
            ['cid' => $courseId]
        );
        $modulesWithLessons = [];
        $allLessons = [];
        foreach ($modules as $m) {
            $lessons = Database::fetchAll("SELECT * FROM lessons WHERE module_id = :mid ORDER BY sort_order, id", ['mid' => $m['id']]);
            foreach ($lessons as &$l) {
                $l['is_completed'] = false;
                if (Auth::isStudent()) {
                    $l['is_completed'] = Database::fetch('SELECT id FROM lesson_completions WHERE lesson_id = :lid AND student_id = :sid', ['lid' => $l['id'], 'sid' => Auth::id()]) !== null;
                }
                $allLessons[] = $l;
            }
            unset($l);
            $m['lessons'] = $lessons;
            $modulesWithLessons[] = $m;
        }

        // Find the current lesson
        $currentLesson = null;
        if ($lessonId > 0) {
            foreach ($allLessons as $l) {
                if ($l['id'] == $lessonId) { $currentLesson = $l; break; }
            }
        }
        if (!$currentLesson && !empty($allLessons)) {
            $currentLesson = $allLessons[0];
        }

        // Mark as complete on access (for students)
        if ($currentLesson && Auth::isStudent()) {
            $alreadyDone = Database::fetch('SELECT id FROM lesson_completions WHERE lesson_id = :lid AND student_id = :sid', ['lid' => $currentLesson['id'], 'sid' => Auth::id()]);
            if (!$alreadyDone) {
                // Only auto-complete text lessons; video/file lessons require an explicit mark
                if ($currentLesson['content_type'] !== 'video') {
                    Database::insert('lesson_completions', [
                        'lesson_id'    => $currentLesson['id'],
                        'student_id'   => Auth::id(),
                        'completed_at' => date('Y-m-d H:i:s'),
                    ]);
                    $this->recalcProgress($courseId, Auth::id());
                }
            }
        }

        // Find prev/next
        $prev = null; $next = null;
        for ($i = 0; $i < count($allLessons); $i++) {
            if ($allLessons[$i]['id'] == ($currentLesson['id'] ?? 0)) {
                if ($i > 0) $prev = $allLessons[$i - 1];
                if ($i < count($allLessons) - 1) $next = $allLessons[$i + 1];
                break;
            }
        }

        $enroll = Auth::isStudent() ? Database::fetch('SELECT * FROM enrollments WHERE course_id = :cid AND student_id = :sid', ['cid' => $courseId, 'sid' => Auth::id()]) : null;
        $progress = $enroll ? (float) $enroll['progress'] : 0;
        $completedCount = count(array_filter($allLessons, fn($l) => $l['is_completed']));

        $this->view('course/learn', [
            'pageTitle'  => $course['title'],
            'course'     => $course,
            'modules'    => $modulesWithLessons,
            'allLessons' => $allLessons,
            'currentLesson' => $currentLesson,
            'prev'       => $prev,
            'next'       => $next,
            'progress'   => $progress,
            'completedCount' => $completedCount,
            'isOwner'    => $isOwner,
        ]);
    }

    public function completeLesson(int $lessonId): void
    {
        Auth::requireLogin();
        $lesson = Database::fetch("SELECT l.*, m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :id", ['id' => $lessonId]);
        if (!$lesson) { $this->json(['ok' => false, 'error' => 'Lesson not found'], 404); }
        if (!Auth::isEnrolled($lesson['course_id'])) { $this->json(['ok' => false, 'error' => 'Not enrolled'], 403); }
        $exists = Database::fetch('SELECT id FROM lesson_completions WHERE lesson_id = :lid AND student_id = :sid', ['lid' => $lessonId, 'sid' => Auth::id()]);
        if (!$exists) {
            Database::insert('lesson_completions', [
                'lesson_id'    => $lessonId,
                'student_id'   => Auth::id(),
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            // Award XP
            Gamification::award(Auth::id(), 'lesson_complete', $lessonId);
        }
        $progress = $this->recalcProgress($lesson['course_id'], Auth::id());
        // Check for course completion → issue certificate
        if ($progress >= 100) {
            Gamification::award(Auth::id(), 'course_complete', $lesson['course_id']);
            try { CertificateController::issueIfNeeded($lesson['course_id'], Auth::id()); } catch (Throwable $e) {}
        }
        $this->json(['ok' => true, 'progress' => $progress]);
    }

    private function recalcProgress(int $courseId, int $studentId): float
    {
        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = :cid", ['cid' => $courseId])['c'];
        $done  = (int) Database::count('lesson_completions lc JOIN lessons l ON lc.lesson_id = l.id JOIN modules m ON l.module_id = m.id', 'm.course_id = :cid AND lc.student_id = :sid', ['cid' => $courseId, 'sid' => $studentId]);
        $pct = $total > 0 ? round(($done / $total) * 100, 2) : 0;
        $updates = ['progress' => $pct, 'updated_at' => date('Y-m-d H:i:s')];
        if ($pct >= 100) $updates['completed_at'] = date('Y-m-d H:i:s');
        Database::update('enrollments', $updates, 'course_id = :cid AND student_id = :sid', ['cid' => $courseId, 'sid' => $studentId]);
        return $pct;
    }

    public function students(int $courseId): void
    {
        Auth::requireLogin();
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect(''); }
        if (!Auth::ownsCourse($courseId)) {
            Auth::requireRole('admin');
        }
        $students = Database::fetchAll(
            "SELECT u.id, u.full_name, u.email, u.avatar, e.progress, e.enrolled_at, e.completed_at,
                    (SELECT COUNT(*) FROM lesson_completions lc JOIN lessons l ON lc.lesson_id = l.id JOIN modules m ON l.module_id = m.id WHERE m.course_id = e.course_id AND lc.student_id = u.id) AS lessons_done
             FROM enrollments e
             JOIN users u ON e.student_id = u.id
             WHERE e.course_id = :cid AND e.status = 'active'
             ORDER BY e.enrolled_at DESC",
            ['cid' => $courseId]
        );
        $this->view('course/students', [
            'pageTitle' => 'Enrolled students · ' . $course['title'],
            'course'    => $course,
            'students'  => $students,
        ]);
    }
}
