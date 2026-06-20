<?php
/**
 * doodle - Teacher controller
 *
 * Course authoring: courses, modules, lessons (text/file/video),
 * assignments, quizzes & questions, gradebook.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class TeacherController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireAnyRole(['teacher', 'admin']);
    }

    public function index(): void
    {
        $tid = Auth::id();
        $stats = [
            'courses'   => Database::count('courses', 'teacher_id = :tid', ['tid' => $tid]),
            'published' => Database::count('courses', 'teacher_id = :tid AND status = :s', ['tid' => $tid, 's' => 'published']),
            'students'  => (int) Database::fetch("SELECT COUNT(DISTINCT e.student_id) AS c FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.teacher_id = :tid AND e.status = 'active'", ['tid' => $tid])['c'],
            'pending'   => Database::count('submissions s JOIN assignments a ON s.assignment_id = a.id JOIN courses c ON a.course_id = c.id', 'c.teacher_id = :tid AND s.status = :st', ['tid' => $tid, 'st' => 'submitted']),
        ];
        $courses = Database::fetchAll(
            "SELECT c.*, COUNT(e.id) AS student_count,
                    (SELECT COUNT(*) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = c.id) AS lesson_count
             FROM courses c
             LEFT JOIN enrollments e ON e.course_id = c.id AND e.status = 'active'
             WHERE c.teacher_id = :tid
             GROUP BY c.id
             ORDER BY c.created_at DESC",
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
            'stats' => $stats,
            'courses' => $courses,
            'pending' => $pending,
        ]);
    }

    public function courses(): void
    {
        $tid = Auth::id();
        $courses = Database::fetchAll(
            "SELECT c.*, COUNT(e.id) AS student_count,
                    (SELECT COUNT(*) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = c.id) AS lesson_count
             FROM courses c
             LEFT JOIN enrollments e ON e.course_id = c.id AND e.status = 'active'
             WHERE c.teacher_id = :tid
             GROUP BY c.id
             ORDER BY c.created_at DESC",
            ['tid' => $tid]
        );
        $categories = Database::fetchAll("SELECT * FROM categories ORDER BY name");
        $this->view('teacher/courses', [
            'pageTitle' => 'My Courses',
            'courses' => $courses,
            'categories' => $categories,
        ]);
    }

    public function createCourse(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($this->input('title', ''));
            $description = trim($this->input('description', ''));
            $categoryId = (int) $this->input('category_id', 0) ?: null;
            $level = $this->input('level', 'beginner');
            $visibility = $this->input('visibility', 'public');
            $language = $this->input('language', 'en');
            $startDate = $this->input('start_date', '') ?: null;
            $endDate = $this->input('end_date', '') ?: null;
            $enrollKey = trim($this->input('enroll_key', '')) ?: null;

            if (empty($title)) {
                $this->flash('error', 'Course title is required.');
                $this->redirect('teacher/courses');
            }
            $slug = slugify($title);
            $i = 1;
            while (Database::fetch('SELECT id FROM courses WHERE slug = :s', ['s' => $slug])) {
                $slug = slugify($title) . '-' . $i++;
            }
            // Cover image
            $coverImage = null;
            if (!empty($_FILES['cover']['name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true) && $_FILES['cover']['size'] < 5*1024*1024) {
                    $name = 'cover_' . time() . '.' . $ext;
                    if (!is_dir(UPLOAD_DIR . '/covers')) mkdir(UPLOAD_DIR . '/covers', 0755, true);
                    move_uploaded_file($_FILES['cover']['tmp_name'], UPLOAD_DIR . '/covers/' . $name);
                    $coverImage = 'covers/' . $name;
                }
            }
            $id = Database::insert('courses', [
                'teacher_id' => Auth::id(),
                'category_id' => $categoryId,
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'cover_image' => $coverImage,
                'status' => 'draft',
                'visibility' => $visibility,
                'language' => $language,
                'level' => $level,
                'price' => 0,
                'enroll_key' => $enrollKey,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->flash('success', 'Course created. Now add modules and lessons.');
            $this->redirect('teacher/courses/edit/' . $id);
        }
        $this->redirect('teacher/courses');
    }

    public function editCourse(int $id): void
    {
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $id]);
        if (!$course) { $this->flash('error', 'Course not found.'); $this->redirect('teacher/courses'); }
        if (!Auth::ownsCourse($id)) { Auth::requireRole('admin'); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $this->input('action', 'save');
            if ($action === 'publish') {
                Database::update('courses', ['status' => 'published', 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
                $this->flash('success', 'Course published. It is now visible in the catalog.');
            } elseif ($action === 'unpublish') {
                Database::update('courses', ['status' => 'draft', 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
                $this->flash('success', 'Course reverted to draft.');
            } elseif ($action === 'delete') {
                Database::delete('courses', 'id = :id', ['id' => $id]);
                $this->flash('success', 'Course deleted.');
                $this->redirect('teacher/courses');
            } else {
                $updates = [
                    'title'       => trim($this->input('title', $course['title'])),
                    'description' => trim($this->input('description', $course['description'])),
                    'category_id' => (int) $this->input('category_id', 0) ?: null,
                    'level'       => $this->input('level', $course['level']),
                    'visibility'  => $this->input('visibility', $course['visibility']),
                    'language'    => $this->input('language', $course['language']),
                    'start_date'  => $this->input('start_date', '') ?: null,
                    'end_date'    => $this->input('end_date', '') ?: null,
                    'enroll_key'  => trim($this->input('enroll_key', '')) ?: null,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ];
                if (!empty($_FILES['cover']['name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true) && $_FILES['cover']['size'] < 5*1024*1024) {
                        $name = 'cover_' . $id . '_' . time() . '.' . $ext;
                        if (!is_dir(UPLOAD_DIR . '/covers')) mkdir(UPLOAD_DIR . '/covers', 0755, true);
                        move_uploaded_file($_FILES['cover']['tmp_name'], UPLOAD_DIR . '/covers/' . $name);
                        $updates['cover_image'] = 'covers/' . $name;
                    }
                }
                Database::update('courses', $updates, 'id = :id', ['id' => $id]);
                $this->flash('success', 'Course updated.');
            }
            $this->redirect('teacher/courses/edit/' . $id);
        }

        $modules = Database::fetchAll(
            "SELECT m.*, (SELECT COUNT(*) FROM lessons l WHERE l.module_id = m.id) AS lesson_count
             FROM modules m WHERE m.course_id = :cid ORDER BY m.sort_order, m.id",
            ['cid' => $id]
        );
        $categories = Database::fetchAll("SELECT * FROM categories ORDER BY name");
        $this->view('teacher/course_edit', [
            'pageTitle' => 'Edit · ' . $course['title'],
            'course' => $course,
            'modules' => $modules,
            'categories' => $categories,
        ]);
    }

    public function addModule(int $courseId): void
    {
        $course = Database::fetch('SELECT id FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course || !Auth::ownsCourse($courseId)) { Auth::requireRole('admin'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($this->input('title', ''));
            $desc  = trim($this->input('description', ''));
            $order = (int) Database::fetch("SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM modules WHERE course_id = :cid", ['cid' => $courseId])['n'];
            if (!empty($title)) {
                Database::insert('modules', [
                    'course_id' => $courseId,
                    'title' => $title,
                    'description' => $desc,
                    'sort_order' => $order,
                ]);
                $this->flash('success', 'Module added.');
            }
        }
        $this->redirect('teacher/courses/edit/' . $courseId);
    }

    public function deleteModule(int $moduleId): void
    {
        $m = Database::fetch('SELECT * FROM modules WHERE id = :id', ['id' => $moduleId]);
        if (!$m) { $this->redirect('teacher/courses'); }
        if (!Auth::ownsCourse($m['course_id'])) { Auth::requireRole('admin'); }
        Database::delete('modules', 'id = :id', ['id' => $moduleId]);
        $this->flash('success', 'Module deleted.');
        $this->redirect('teacher/courses/edit/' . $m['course_id']);
    }

    public function addLesson(int $moduleId): void
    {
        $m = Database::fetch('SELECT * FROM modules WHERE id = :id', ['id' => $moduleId]);
        if (!$m) { $this->redirect('teacher/courses'); }
        if (!Auth::ownsCourse($m['course_id'])) { Auth::requireRole('admin'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title       = trim($this->input('title', ''));
            $contentType = $this->input('content_type', 'text');
            $content     = $this->input('content', '');
            $externalUrl = trim($this->input('external_url', ''));
            $isPreview   = (int) ($this->input('is_preview', '0') === '1');
            $order = (int) Database::fetch("SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM lessons WHERE module_id = :mid", ['mid' => $moduleId])['n'];
            $filePath = null; $fileName = null; $fileSize = null;
            if (in_array($contentType, ['file','video'], true) && !empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                if (isset(ALLOWED_UPLOAD_TYPES[$ext]) && $_FILES['file']['size'] <= MAX_UPLOAD_SIZE) {
                    $safeName = 'lesson_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (!is_dir(UPLOAD_DIR . '/lessons')) mkdir(UPLOAD_DIR . '/lessons', 0755, true);
                    move_uploaded_file($_FILES['file']['tmp_name'], UPLOAD_DIR . '/lessons/' . $safeName);
                    $filePath = 'lessons/' . $safeName;
                    $fileName = $_FILES['file']['name'];
                    $fileSize = $_FILES['file']['size'];
                } else {
                    $this->flash('error', 'Invalid file type or size exceeds limit.');
                    $this->redirect('teacher/courses/edit/' . $m['course_id']);
                }
            }
            Database::insert('lessons', [
                'module_id' => $moduleId,
                'title' => $title,
                'content' => $content,
                'content_type' => $contentType,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'external_url' => $externalUrl,
                'sort_order' => $order,
                'is_preview' => $isPreview,
            ]);
            $this->flash('success', 'Lesson added.');
        }
        $this->redirect('teacher/courses/edit/' . $m['course_id']);
    }

    public function deleteLesson(int $lessonId): void
    {
        $l = Database::fetch("SELECT l.*, m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = :id", ['id' => $lessonId]);
        if (!$l) { $this->redirect('teacher/courses'); }
        if (!Auth::ownsCourse($l['course_id'])) { Auth::requireRole('admin'); }
        if (!empty($l['file_path']) && file_exists(UPLOAD_DIR . '/' . $l['file_path'])) {
            unlink(UPLOAD_DIR . '/' . $l['file_path']);
        }
        Database::delete('lessons', 'id = :id', ['id' => $lessonId]);
        $this->flash('success', 'Lesson deleted.');
        $this->redirect('teacher/courses/edit/' . $l['course_id']);
    }

    // ----- ASSIGNMENTS -----

    public function assignment(int $id): void
    {
        $a = Database::fetch('SELECT a.*, c.title AS course_title, c.teacher_id FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = :id', ['id' => $id]);
        if (!$a) { $this->redirect('teacher'); }
        if (!Auth::ownsCourse($a['course_id'])) { Auth::requireRole('admin'); }
        $submissions = Database::fetchAll(
            "SELECT s.*, u.full_name AS student_name, u.email AS student_email, u.id AS student_id
             FROM submissions s JOIN users u ON s.student_id = u.id
             WHERE s.assignment_id = :aid
             ORDER BY s.submitted_at DESC",
            ['aid' => $id]
        );
        $this->view('teacher/assignment', [
            'pageTitle' => 'Assignment · ' . $a['title'],
            'assignment' => $a,
            'submissions' => $submissions,
        ]);
    }

    public function createAssignment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseId = (int) $this->input('course_id', 0);
            if (!Auth::ownsCourse($courseId)) { Auth::requireRole('admin'); }
            $title    = trim($this->input('title', ''));
            $desc     = trim($this->input('description', ''));
            $maxScore = (float) $this->input('max_score', 100);
            $dueDate  = $this->input('due_date', '') ?: null;
            $moduleId = (int) $this->input('module_id', 0) ?: null;
            if (!empty($title)) {
                $id = Database::insert('assignments', [
                    'course_id' => $courseId,
                    'module_id' => $moduleId,
                    'title' => $title,
                    'description' => $desc,
                    'max_score' => $maxScore,
                    'due_date' => $dueDate,
                    'allow_upload' => 1,
                ]);
                $this->flash('success', 'Assignment created.');
                $this->redirect('teacher/assignment/' . $id);
            }
        }
        $this->redirect('teacher');
    }

    public function gradeSubmission(int $id): void
    {
        $s = Database::fetch(
            "SELECT s.*, a.title AS assignment_title, a.max_score, a.course_id, c.teacher_id,
                    u.full_name AS student_name, u.id AS student_id
             FROM submissions s
             JOIN assignments a ON s.assignment_id = a.id
             JOIN courses c ON a.course_id = c.id
             JOIN users u ON s.student_id = u.id
             WHERE s.id = :id",
            ['id' => $id]
        );
        if (!$s) { $this->redirect('teacher'); }
        if (!Auth::ownsCourse($s['course_id'])) { Auth::requireRole('admin'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $score    = (float) $this->input('score', 0);
            $feedback = trim($this->input('feedback', ''));
            $maxScore = (float) $s['max_score'];
            if ($score < 0) $score = 0;
            if ($score > $maxScore) $score = $maxScore;
            Database::update('submissions', [
                'score' => $score,
                'feedback' => $feedback,
                'status' => 'graded',
                'graded_at' => date('Y-m-d H:i:s'),
                'graded_by' => Auth::id(),
            ], 'id = :id', ['id' => $id]);
            // Add to gradebook
            Database::insert('grades', [
                'course_id' => $s['course_id'],
                'student_id' => $s['student_id'],
                'item_type' => 'assignment',
                'item_id' => $s['assignment_id'],
                'item_name' => $s['assignment_title'],
                'score' => $score,
                'max_score' => $maxScore,
                'feedback' => $feedback,
                'graded_by' => Auth::id(),
            ]);
            // Notify student
            Database::insert('notifications', [
                'user_id' => $s['student_id'],
                'title' => 'Assignment graded',
                'body'   => 'Your submission for "' . $s['assignment_title'] . '" received ' . $score . '/' . $maxScore,
                'link'   => 'student/grades',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->flash('success', 'Submission graded and student notified.');
            $this->redirect('teacher/assignment/' . $s['assignment_id']);
        }
        $this->view('teacher/grade', ['pageTitle' => 'Grade submission', 'sub' => $s]);
    }

    // ----- QUIZZES -----

    public function quizzes(): void
    {
        $tid = Auth::id();
        $quizzes = Database::fetchAll(
            "SELECT q.*, c.title AS course_title,
                    (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS question_count,
                    (SELECT COUNT(*) FROM attempts WHERE quiz_id = q.id) AS attempt_count
             FROM quizzes q JOIN courses c ON q.course_id = c.id
             WHERE c.teacher_id = :tid
             ORDER BY q.created_at DESC",
            ['tid' => $tid]
        );
        $courses = Database::fetchAll("SELECT id, title FROM courses WHERE teacher_id = :tid ORDER BY title", ['tid' => $tid]);
        $this->view('teacher/quizzes', [
            'pageTitle' => 'Quiz Bank',
            'quizzes'   => $quizzes,
            'courses'   => $courses,
        ]);
    }

    public function createQuiz(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseId = (int) $this->input('course_id', 0);
            if (!Auth::ownsCourse($courseId)) { Auth::requireRole('admin'); }
            $title = trim($this->input('title', ''));
            $desc  = trim($this->input('description', ''));
            $instr = trim($this->input('instructions', ''));
            $time  = (int) $this->input('time_limit', 0) ?: null;
            $att   = (int) $this->input('max_attempts', 1);
            $pass  = (float) $this->input('passing_score', 60);
            $shuf  = (int) ($this->input('shuffle', '0') === '1');
            $showA = (int) ($this->input('show_answers', '0') === '1');
            if (!empty($title)) {
                $id = Database::insert('quizzes', [
                    'course_id' => $courseId,
                    'title' => $title,
                    'description' => $desc,
                    'instructions' => $instr,
                    'time_limit' => $time,
                    'max_attempts' => max(1, $att),
                    'passing_score' => $pass,
                    'shuffle' => $shuf,
                    'show_answers' => $showA,
                ]);
                $this->flash('success', 'Quiz created. Add questions next.');
                $this->redirect('teacher/quiz/' . $id);
            }
        }
        $this->redirect('teacher/quizzes');
    }

    public function quiz(int $id): void
    {
        $q = Database::fetch("SELECT q.*, c.title AS course_title, c.teacher_id FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = :id", ['id' => $id]);
        if (!$q) { $this->redirect('teacher/quizzes'); }
        if (!Auth::ownsCourse($q['course_id'])) { Auth::requireRole('admin'); }
        $questions = Database::fetchAll("SELECT * FROM questions WHERE quiz_id = :qid ORDER BY sort_order, id", ['qid' => $id]);
        $this->view('teacher/quiz_edit', [
            'pageTitle' => 'Quiz · ' . $q['title'],
            'quiz' => $q,
            'questions' => $questions,
        ]);
    }

    public function addQuestion(int $quizId): void
    {
        $q = Database::fetch("SELECT q.*, c.teacher_id FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = :id", ['id' => $quizId]);
        if (!$q) { $this->redirect('teacher/quizzes'); }
        if (!Auth::ownsCourse($q['course_id'])) { Auth::requireRole('admin'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type    = $this->input('type', 'multiple_choice');
            $question= trim($this->input('question', ''));
            $points  = (float) $this->input('points', 1);
            $expl    = trim($this->input('explanation', ''));
            $correct = '';
            $options = null;
            if ($type === 'multiple_choice') {
                $opts = array_values(array_filter(array_map('trim', $_POST['options'] ?? []), fn($o) => $o !== ''));
                $correctIdx = (int) $this->input('correct', 0);
                $correct = (string) $correctIdx;
                $options = json_encode($opts);
            } elseif ($type === 'true_false') {
                $correct = $this->input('tf_correct', 'true');
            } elseif ($type === 'short_answer') {
                $correct = trim($this->input('short_answer', ''));
            }
            $order = (int) Database::fetch("SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM questions WHERE quiz_id = :qid", ['qid' => $quizId])['n'];
            Database::insert('questions', [
                'quiz_id' => $quizId,
                'type' => $type,
                'question' => $question,
                'options' => $options,
                'correct_answer' => $correct,
                'explanation' => $expl,
                'points' => $points,
                'sort_order' => $order,
            ]);
            $this->flash('success', 'Question added.');
        }
        $this->redirect('teacher/quiz/' . $quizId);
    }

    public function deleteQuestion(int $id): void
    {
        $q = Database::fetch('SELECT * FROM questions WHERE id = :id', ['id' => $id]);
        if (!$q) { $this->redirect('teacher/quizzes'); }
        $quiz = Database::fetch("SELECT q.course_id, c.teacher_id FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = :id", ['id' => $q['quiz_id']]);
        if (!Auth::ownsCourse($quiz['course_id'])) { Auth::requireRole('admin'); }
        Database::delete('questions', 'id = :id', ['id' => $id]);
        $this->flash('success', 'Question deleted.');
        $this->redirect('teacher/quiz/' . $q['quiz_id']);
    }

    // ----- GRADEBOOK -----

    public function gradebook(): void
    {
        $tid = Auth::id();
        $courses = Database::fetchAll("SELECT id, title FROM courses WHERE teacher_id = :tid ORDER BY title", ['tid' => $tid]);
        $this->view('teacher/gradebook', [
            'pageTitle' => 'Gradebook',
            'courses'   => $courses,
        ]);
    }

    public function gradebookCourse(int $courseId): void
    {
        if (!Auth::ownsCourse($courseId)) { Auth::requireRole('admin'); }
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        $students = Database::fetchAll(
            "SELECT u.id, u.full_name, u.email
             FROM enrollments e JOIN users u ON e.student_id = u.id
             WHERE e.course_id = :cid AND e.status = 'active'
             ORDER BY u.full_name",
            ['cid' => $courseId]
        );
        $assignments = Database::fetchAll("SELECT * FROM assignments WHERE course_id = :cid ORDER BY id", ['cid' => $courseId]);
        $grades = [];
        $rows = Database::fetchAll("SELECT * FROM grades WHERE course_id = :cid", ['cid' => $courseId]);
        foreach ($rows as $r) {
            $grades[$r['student_id']][$r['item_id']] = $r;
        }
        $this->view('teacher/gradebook_course', [
            'pageTitle' => 'Gradebook · ' . $course['title'],
            'course' => $course,
            'students' => $students,
            'assignments' => $assignments,
            'grades' => $grades,
        ]);
    }
}
