<?php
/**
 * doodle - Quiz controller
 *
 * Students take quizzes; auto-graded on submission.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class QuizController extends Controller
{
    public function take(int $id): void
    {
        Auth::requireLogin();
        $q = Database::fetch(
            "SELECT q.*, c.title AS course_title, c.id AS course_id, c.teacher_id
             FROM quizzes q JOIN courses c ON q.course_id = c.id
             WHERE q.id = :id",
            ['id' => $id]
        );
        if (!$q) { http_response_code(404); $this->view('errors/404', ['message' => 'Quiz not found.']); return; }

        // Permission: admin, owner, or enrolled student
        $canAccess = Auth::isAdmin()
                  || (Auth::isTeacher() && $q['teacher_id'] == Auth::id())
                  || (Auth::isStudent() && Auth::isEnrolled($q['course_id']));
        if (!$canAccess) {
            $this->flash('error', 'You must be enrolled in this course to take quizzes.');
            $this->redirect('course/view/' . $q['course_id']);
        }

        // Count prior attempts for students
        if (Auth::isStudent()) {
            $attempts = Database::count('attempts', 'quiz_id = :qid AND student_id = :sid', ['qid' => $id, 'sid' => Auth::id()]);
            if ($attempts >= $q['max_attempts']) {
                $this->flash('info', 'You have used all ' . $q['max_attempts'] . ' attempts for this quiz.');
                $this->redirect('course/view/' . $q['course_id']);
            }
        }

        $questions = Database::fetchAll("SELECT * FROM questions WHERE quiz_id = :qid ORDER BY sort_order, id", ['qid' => $id]);
        if ($q['shuffle']) shuffle($questions);

        $this->view('quiz/take', [
            'pageTitle' => 'Quiz · ' . $q['title'],
            'quiz' => $q,
            'questions' => $questions,
            'attemptNumber' => Auth::isStudent() ? Database::count('attempts', 'quiz_id = :qid AND student_id = :sid', ['qid' => $id, 'sid' => Auth::id()]) + 1 : 0,
        ]);
    }

    public function submit(int $id): void
    {
        Auth::requireLogin();
        $q = Database::fetch(
            "SELECT q.*, c.id AS course_id, c.teacher_id FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = :id",
            ['id' => $id]
        );
        if (!$q) { $this->redirect(''); }
        $canAccess = Auth::isAdmin() || (Auth::isTeacher() && $q['teacher_id'] == Auth::id()) || (Auth::isStudent() && Auth::isEnrolled($q['course_id']));
        if (!$canAccess) { $this->flash('error', 'Access denied.'); $this->redirect(''); }

        $questions = Database::fetchAll("SELECT * FROM questions WHERE quiz_id = :qid ORDER BY sort_order, id", ['qid' => $id]);
        $answers = [];
        $score = 0; $maxScore = 0;
        $results = [];

        foreach ($questions as $qq) {
            $maxScore += (float) $qq['points'];
            $userAnswer = $this->input('q_' . $qq['id'], '');
            $isCorrect = false;

            if ($qq['type'] === 'multiple_choice') {
                $opts = json_decode($qq['options'] ?? '[]', true) ?: [];
                $correctIdx = (int) $qq['correct_answer'];
                $userIdx = (int) $userAnswer;
                $isCorrect = ($userIdx === $correctIdx);
                $answers[$qq['id']] = [
                    'type' => 'mc',
                    'user' => $opts[$userIdx] ?? '(no answer)',
                    'correct' => $opts[$correctIdx] ?? '',
                    'is_correct' => $isCorrect,
                ];
            } elseif ($qq['type'] === 'true_false') {
                $isCorrect = (strcasecmp($userAnswer, $qq['correct_answer']) === 0);
                $answers[$qq['id']] = [
                    'type' => 'tf',
                    'user' => $userAnswer ?: '(no answer)',
                    'correct' => $qq['correct_answer'],
                    'is_correct' => $isCorrect,
                ];
            } elseif ($qq['type'] === 'short_answer') {
                $isCorrect = (strcasecmp(trim($userAnswer), trim($qq['correct_answer'])) === 0);
                $answers[$qq['id']] = [
                    'type' => 'sa',
                    'user' => $userAnswer ?: '(no answer)',
                    'correct' => $qq['correct_answer'],
                    'is_correct' => $isCorrect,
                ];
            }
            if ($isCorrect) $score += (float) $qq['points'];
            $results[] = ['question' => $qq, 'user_answer' => $answers[$qq['id']]];
        }

        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;
        $passed = $percentage >= $q['passing_score'] ? 1 : 0;

        // Record attempt (only for students)
        $attemptId = null;
        if (Auth::isStudent()) {
            $attemptId = Database::insert('attempts', [
                'quiz_id'      => $id,
                'student_id'   => Auth::id(),
                'score'        => $score,
                'max_score'    => $maxScore,
                'percentage'   => $percentage,
                'passed'       => $passed,
                'answers'      => json_encode($answers),
                'started_at'   => date('Y-m-d H:i:s', strtotime('-5 minutes')),
                'submitted_at' => date('Y-m-d H:i:s'),
            ]);
            // Award XP
            Gamification::award(Auth::id(), 'quiz_attempt', $id);
            if ($passed) Gamification::award(Auth::id(), 'quiz_pass', $id);
            if ($percentage >= 100) Gamification::award(Auth::id(), 'quiz_perfect', $id);
            // Add to gradebook
            Database::insert('grades', [
                'course_id' => $q['course_id'],
                'student_id' => Auth::id(),
                'item_type' => 'quiz',
                'item_id' => $id,
                'item_name' => $q['title'],
                'score' => $score,
                'max_score' => $maxScore,
                'feedback' => $passed ? 'Passed' : 'Did not pass',
                'graded_by' => null,
            ]);
        }

        $this->view('quiz/result', [
            'pageTitle' => 'Quiz Result · ' . $q['title'],
            'quiz' => $q,
            'score' => $score,
            'maxScore' => $maxScore,
            'percentage' => $percentage,
            'passed' => $passed,
            'results' => $results,
            'attemptId' => $attemptId,
        ]);
    }
}
