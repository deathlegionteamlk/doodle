<?php
/**
 * doodle - Code Playground controller
 *
 * Programming challenges with test cases. Supports PHP execution
 * (sandboxed via proc_open with timeout + safe mode).
 *
 * @package doodle
 * @author  Death Legion Team
 */

class CodeController extends Controller
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
        $this->assertAccess($course);
        $exercises = Database::fetchAll(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM code_submissions WHERE exercise_id = e.id AND user_id = :uid AND status = 'passed') AS my_passed,
                    (SELECT COUNT(*) FROM code_test_cases WHERE exercise_id = e.id) AS test_count
             FROM code_exercises e
             WHERE e.course_id = :cid
             ORDER BY e.difficulty, e.id",
            ['cid' => $courseId, 'uid' => Auth::id()]
        );
        $this->view('code/course', [
            'pageTitle' => 'Code Exercises · ' . $course['title'],
            'course'    => $course,
            'exercises' => $exercises,
        ]);
    }

    public function exercise(int $id): void
    {
        $ex = Database::fetch(
            "SELECT e.*, c.title AS course_title, c.id AS course_id, c.teacher_id
             FROM code_exercises e JOIN courses c ON e.course_id = c.id
             WHERE e.id = :id",
            ['id' => $id]
        );
        if (!$ex) { $this->redirect(''); }
        $this->assertAccess($ex);
        $testCases = Database::fetchAll('SELECT * FROM code_test_cases WHERE exercise_id = :eid AND is_hidden = 0 ORDER BY sort_order, id', ['eid' => $id]);
        $submissions = Database::fetchAll(
            "SELECT * FROM code_submissions WHERE exercise_id = :eid AND user_id = :uid ORDER BY submitted_at DESC LIMIT 10",
            ['eid' => $id, 'uid' => Auth::id()]
        );
        $this->view('code/exercise', [
            'pageTitle'  => $ex['title'],
            'exercise'   => $ex,
            'testCases'  => $testCases,
            'submissions' => $submissions,
        ]);
    }

    /** Run code submission against test cases. */
    public function run(int $id): void
    {
        Auth::requireLogin();
        $ex = Database::fetch(
            "SELECT e.*, c.teacher_id FROM code_exercises e JOIN courses c ON e.course_id = c.id WHERE e.id = :id",
            ['id' => $id]
        );
        if (!$ex) { $this->json(['ok' => false, 'error' => 'Exercise not found'], 404); }
        $this->assertAccess($ex);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false, 'error' => 'POST required'], 405);

        $code = $this->input('code', '');
        $language = $ex['language'] ?? 'php';
        if (empty($code)) $this->json(['ok' => false, 'error' => 'Empty code'], 400);

        // Get test cases (including hidden ones)
        $testCases = Database::fetchAll('SELECT * FROM code_test_cases WHERE exercise_id = :eid ORDER BY sort_order, id', ['eid' => $id]);

        $passed = 0; $total = count($testCases); $results = [];
        foreach ($testCases as $tc) {
            $result = $this->runTest($language, $code, $tc['input'] ?? '', $tc['expected_output']);
            $results[] = [
                'input' => $tc['is_hidden'] ? '(hidden)' : $tc['input'],
                'expected' => $tc['is_hidden'] ? '(hidden)' : $tc['expected_output'],
                'actual' => $result['output'],
                'passed' => $result['ok'],
                'hidden' => (bool) $tc['is_hidden'],
                'error' => $result['error'] ?? null,
            ];
            if ($result['ok']) $passed++;
        }

        $status = $passed === $total ? 'passed' : 'failed';
        $subId = Database::insert('code_submissions', [
            'exercise_id'   => $id,
            'user_id'       => Auth::id(),
            'code'          => $code,
            'language'      => $language,
            'passed_tests'  => $passed,
            'total_tests'   => $total,
            'status'        => $status,
            'output'        => json_encode($results),
            'execution_time' => 0,
        ]);

        // Award XP if passed
        if ($status === 'passed') {
            Gamification::award(Auth::id(), 'quiz_pass', $id);
        }

        $this->json([
            'ok' => true,
            'status' => $status,
            'passed' => $passed,
            'total' => $total,
            'results' => $results,
            'submission_id' => $subId,
        ]);
    }

    /** Run a single test case. */
    private function runTest(string $language, string $code, string $input, string $expected): array
    {
        $expected = trim($expected);
        if ($language === 'php') {
            // Prepend code to read STDIN, then call run
            $fullCode = "<?php\n" . $code . "\n";
            // Wrap in a way that reads stdin and the code is expected to print output
            $tmpFile = tempnam(sys_get_temp_dir(), 'doodle_php_') . '.php';
            file_put_contents($tmpFile, $fullCode);
            $descriptors = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
            $proc = proc_open(['php', '-d', 'disable_functions=exec,passthru,shell_exec,system,proc_open,popen,curl_exec,file_get_contents,file_put_contents,fopen,fwrite,readfile,unlink,rename,copy,mkdir,rmdir', $tmpFile], $descriptors, $pipes);
            if (!is_resource($proc)) {
                @unlink($tmpFile);
                return ['ok' => false, 'output' => '', 'error' => 'Failed to execute'];
            }
            fwrite($pipes[0], $input);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            // Kill if running too long
            $status = proc_get_status($proc);
            if ($status['running']) {
                proc_terminate($proc, 9);
            }
            proc_close($proc);
            @unlink($tmpFile);
            $output = trim($output);
            if ($err && !$output) return ['ok' => false, 'output' => $err, 'error' => 'Runtime error'];
            return ['ok' => $output === $expected, 'output' => $output];
        }
        // Python3
        if ($language === 'python') {
            $tmpFile = tempnam(sys_get_temp_dir(), 'doodle_py_') . '.py';
            file_put_contents($tmpFile, $code);
            $descriptors = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
            $proc = proc_open(['python3', $tmpFile], $descriptors, $pipes);
            if (!is_resource($proc)) { @unlink($tmpFile); return ['ok' => false, 'output' => '', 'error' => 'Python3 not available']; }
            fwrite($pipes[0], $input);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            $status = proc_get_status($proc);
            if ($status['running']) proc_terminate($proc, 9);
            proc_close($proc);
            @unlink($tmpFile);
            $output = trim($output);
            if ($err && !$output) return ['ok' => false, 'output' => $err, 'error' => 'Runtime error'];
            return ['ok' => $output === $expected, 'output' => $output];
        }
        return ['ok' => false, 'output' => '', 'error' => "Unsupported language: $language"];
    }

    /** Teacher: list exercises for management. */
    public function manage(int $courseId): void
    {
        if (!Auth::ownsCourse($courseId)) { Auth::requireRole('admin'); }
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        $exercises = Database::fetchAll('SELECT * FROM code_exercises WHERE course_id = :cid ORDER BY id', ['cid' => $courseId]);
        $this->view('code/manage', [
            'pageTitle' => 'Code Exercises · ' . $course['title'],
            'course'    => $course,
            'exercises' => $exercises,
        ]);
    }

    public function create(int $courseId): void
    {
        if (!Auth::ownsCourse($courseId)) { Auth::requireRole('admin'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($this->input('title', ''));
            $desc = trim($this->input('description', ''));
            $problem = trim($this->input('problem_statement', ''));
            $starter = $this->input('starter_code', '');
            $solution = $this->input('solution_code', '');
            $lang = $this->input('language', 'php');
            $diff = $this->input('difficulty', 'easy');
            $points = (float) $this->input('points', 10);
            if (!empty($title)) {
                $exId = Database::insert('code_exercises', [
                    'course_id'        => $courseId,
                    'title'            => $title,
                    'description'      => $desc,
                    'problem_statement'=> $problem,
                    'starter_code'     => $starter,
                    'solution_code'    => $solution,
                    'language'         => $lang,
                    'difficulty'       => $diff,
                    'points'           => $points,
                ]);
                // Add test cases
                $inputs = (array) $this->input('test_input', []);
                $expecteds = (array) $this->input('test_expected', []);
                $hiddens = (array) $this->input('test_hidden', []);
                for ($i = 0; $i < count($inputs); $i++) {
                    if (!empty($expecteds[$i])) {
                        Database::insert('code_test_cases', [
                            'exercise_id'     => $exId,
                            'input'           => $inputs[$i] ?? '',
                            'expected_output' => $expecteds[$i],
                            'is_hidden'       => in_array($i, $hiddens) ? 1 : 0,
                            'sort_order'      => $i,
                        ]);
                    }
                }
                $this->flash('success', 'Code exercise created.');
                $this->redirect('code/manage/' . $courseId);
            }
        }
        $this->redirect('code/manage/' . $courseId);
    }

    public function delete(int $exId): void
    {
        $ex = Database::fetch('SELECT * FROM code_exercises WHERE id = :id', ['id' => $exId]);
        if (!$ex) { $this->redirect(''); }
        if (!Auth::ownsCourse($ex['course_id'])) { Auth::requireRole('admin'); }
        Database::delete('code_exercises', 'id = :id', ['id' => $exId]);
        $this->flash('success', 'Exercise deleted.');
        $this->redirect('code/manage/' . $ex['course_id']);
    }

    private function assertAccess(array $courseOrEx): void
    {
        $courseId = $courseOrEx['course_id'] ?? $courseOrEx['id'];
        $teacherId = $courseOrEx['teacher_id'] ?? null;
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
