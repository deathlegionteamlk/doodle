<?php
/**
 * doodle - AI Learning Assistant controller
 *
 * Chat interface, question generation, summarization, flashcard generation,
 * study recommendations — all powered by the built-in AIEngine.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class AIController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireLogin();
    }

    public function index(): void
    {
        $uid = Auth::id();
        $conversations = Database::fetchAll(
            "SELECT ac.*, c.title AS course_title
             FROM ai_conversations ac
             LEFT JOIN courses c ON ac.course_id = c.id
             WHERE ac.user_id = :uid
             ORDER BY ac.updated_at DESC, ac.created_at DESC",
            ['uid' => $uid]
        );
        // Recommendations
        $recs = AIEngine::studyRecommendations($uid);
        $this->view('ai/index', [
            'pageTitle'     => 'AI Learning Assistant',
            'conversations' => $conversations,
            'recommendations' => $recs,
        ]);
    }

    public function chat(int $convId = 0): void
    {
        $uid = Auth::id();
        $conv = null;
        $messages = [];
        $courseId = (int) ($this->input('course_id', 0));
        $course = null;

        if ($convId > 0) {
            $conv = Database::fetch('SELECT * FROM ai_conversations WHERE id = :id AND user_id = :uid', ['id' => $convId, 'uid' => $uid]);
            if (!$conv) { $this->flash('error', 'Conversation not found.'); $this->redirect('ai'); }
            $messages = Database::fetchAll('SELECT * FROM ai_messages WHERE conversation_id = :cid ORDER BY created_at ASC, id ASC', ['cid' => $convId]);
            $courseId = (int) $conv['course_id'];
        } else {
            // Create new conversation
            if ($courseId > 0) {
                $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
                if ($course && (Auth::isAdmin() || (Auth::isTeacher() && $course['teacher_id'] == $uid) || Auth::isEnrolled($courseId))) {
                    // ok
                } else {
                    $courseId = 0;
                }
            }
            $convId = Database::insert('ai_conversations', [
                'user_id'   => $uid,
                'course_id' => $courseId ?: null,
                'title'     => $course ? 'AI Chat · ' . $course['title'] : 'AI Chat',
            ]);
            $this->redirect('ai/chat/' . $convId);
        }

        if ($courseId > 0) {
            $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        }

        // Handle new message
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userMsg = trim($this->input('message', ''));
            if ($userMsg !== '') {
                // Save user message
                Database::insert('ai_messages', [
                    'conversation_id' => $convId,
                    'role'            => 'user',
                    'content'         => $userMsg,
                ]);
                // Generate response
                $response = AIEngine::chat($userMsg, $courseId ?: null, $uid);
                Database::insert('ai_messages', [
                    'conversation_id' => $convId,
                    'role'            => 'assistant',
                    'content'         => $response,
                    'model'           => 'doodle-ai-v1',
                ]);
                // Update conversation title if first message
                if (count($messages) === 0) {
                    Database::update('ai_conversations', ['title' => truncate($userMsg, 50), 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $convId]);
                } else {
                    Database::update('ai_conversations', ['updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $convId]);
                }
                // Award XP
                Gamification::award($uid, 'ai_chat');
                $this->redirect('ai/chat/' . $convId);
            }
        }

        // Reload messages after potential POST
        $messages = Database::fetchAll('SELECT * FROM ai_messages WHERE conversation_id = :cid ORDER BY created_at ASC, id ASC', ['cid' => $convId]);

        $this->view('ai/chat', [
            'pageTitle' => 'AI Assistant · ' . $conv['title'],
            'conv'      => $conv,
            'messages'  => $messages,
            'course'    => $course,
        ]);
    }

    public function new(): void
    {
        $courseId = (int) ($this->input('course_id', 0));
        $url = 'ai/chat/0';
        if ($courseId > 0) $url .= '?course_id=' . $courseId;
        $this->redirect($url);
    }

    public function delete(int $convId): void
    {
        $uid = Auth::id();
        $conv = Database::fetch('SELECT id FROM ai_conversations WHERE id = :id AND user_id = :uid', ['id' => $convId, 'uid' => $uid]);
        if ($conv) {
            Database::delete('ai_conversations', 'id = :id', ['id' => $convId]);
            $this->flash('success', 'Conversation deleted.');
        }
        $this->redirect('ai');
    }

    /** AJAX endpoint for chat (no full page reload). */
    public function send(): void
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false, 'error' => 'POST required'], 405);
        $convId = (int) $this->input('conversation_id', 0);
        $msg = trim($this->input('message', ''));
        if ($convId <= 0 || $msg === '') $this->json(['ok' => false, 'error' => 'Invalid request'], 400);
        $conv = Database::fetch('SELECT * FROM ai_conversations WHERE id = :id AND user_id = :uid', ['id' => $convId, 'uid' => Auth::id()]);
        if (!$conv) $this->json(['ok' => false, 'error' => 'Conversation not found'], 404);

        Database::insert('ai_messages', [
            'conversation_id' => $convId,
            'role'            => 'user',
            'content'         => $msg,
        ]);
        $response = AIEngine::chat($msg, $conv['course_id'] ? (int) $conv['course_id'] : null, Auth::id());
        Database::insert('ai_messages', [
            'conversation_id' => $convId,
            'role'            => 'assistant',
            'content'         => $response,
            'model'           => 'doodle-ai-v1',
        ]);
        Database::update('ai_conversations', ['updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $convId]);
        Gamification::award(Auth::id(), 'ai_chat');
        $this->json(['ok' => true, 'response' => $response]);
    }

    /** Generate a quiz from course content and save it as a real quiz. */
    public function generateQuiz(int $courseId): void
    {
        Auth::requireLogin();
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->flash('error', 'Course not found.'); $this->redirect('ai'); }
        $canAccess = Auth::isAdmin() || (Auth::isTeacher() && $course['teacher_id'] == Auth::id()) || Auth::isEnrolled($courseId);
        if (!$canAccess) { $this->flash('error', 'Access denied.'); $this->redirect('ai'); }

        $lessons = Database::fetchAll(
            "SELECT l.title, l.content FROM lessons l JOIN modules m ON l.module_id = m.id
             WHERE m.course_id = :cid AND l.content_type = 'text' AND l.content IS NOT NULL AND l.content != ''",
            ['cid' => $courseId]
        );
        $corpus = '';
        foreach ($lessons as $l) $corpus .= strip_tags($l['content']) . "\n\n";
        $questions = AIEngine::generateQuestions($corpus, 5);

        if (empty($questions)) {
            $this->flash('error', 'Could not generate questions — add more text-based lessons first.');
            $this->redirect('course/view/' . $courseId);
        }

        // Only teachers/admins can save as a real quiz; students get a preview
        if (Auth::isTeacher() || Auth::isAdmin()) {
            $quizId = Database::insert('quizzes', [
                'course_id'      => $courseId,
                'title'          => 'AI-Generated Practice Quiz',
                'description'    => 'Auto-generated from course materials on ' . date('M j, Y'),
                'instructions'   => 'This quiz was generated by the doodle AI assistant.',
                'max_attempts'   => 3,
                'passing_score'  => 60,
                'shuffle'        => 1,
                'show_answers'   => 1,
            ]);
            foreach ($questions as $i => $q) {
                Database::insert('questions', [
                    'quiz_id'        => $quizId,
                    'type'           => 'multiple_choice',
                    'question'       => $q['question'],
                    'options'        => json_encode($q['options']),
                    'correct_answer' => (string) $q['correct'],
                    'explanation'    => $q['explanation'] ?? '',
                    'points'         => 1,
                    'sort_order'     => $i,
                ]);
            }
            $this->flash('success', 'AI generated a ' . count($questions) . '-question quiz for you.');
            $this->redirect('teacher/quiz/' . $quizId);
        } else {
            // Students see the questions inline
            $this->view('ai/generated_quiz', [
                'pageTitle' => 'AI Practice Quiz · ' . $course['title'],
                'course'    => $course,
                'questions' => $questions,
            ]);
        }
    }

    /** Summarize a lesson and display it. */
    public function summarize(int $lessonId): void
    {
        Auth::requireLogin();
        $lesson = Database::fetch(
            "SELECT l.*, m.course_id, c.title AS course_title, c.teacher_id
             FROM lessons l
             JOIN modules m ON l.module_id = m.id
             JOIN courses c ON m.course_id = c.id
             WHERE l.id = :id",
            ['id' => $lessonId]
        );
        if (!$lesson) { $this->redirect(''); }
        $canAccess = Auth::isAdmin() || (Auth::isTeacher() && $lesson['teacher_id'] == Auth::id()) || Auth::isEnrolled($lesson['course_id']);
        if (!$canAccess) { $this->flash('error', 'Access denied.'); $this->redirect(''); }

        $text = strip_tags($lesson['content'] ?? '');
        $summary = AIEngine::summarize($text, 3);
        $keywords = AIEngine::keywords($text, 10);

        $this->view('ai/summary', [
            'pageTitle' => 'AI Summary · ' . $lesson['title'],
            'lesson'    => $lesson,
            'summary'   => $summary,
            'keywords'  => $keywords,
        ]);
    }
}
