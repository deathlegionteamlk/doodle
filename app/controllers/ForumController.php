<?php
/**
 * doodle - Forum controller
 *
 * Per-course discussion forum: topics and replies.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class ForumController extends Controller
{
    public function course(int $courseId): void
    {
        Auth::requireLogin();
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect(''); }

        $canAccess = Auth::isAdmin()
                  || (Auth::isTeacher() && $course['teacher_id'] == Auth::id())
                  || (Auth::isStudent() && Auth::isEnrolled($courseId));
        if (!$canAccess) {
            $this->flash('error', 'You must be enrolled to access the forum.');
            $this->redirect('course/view/' . $courseId);
        }

        $topics = Database::fetchAll(
            "SELECT t.*, u.full_name AS author_name, u.role AS author_role,
                    (SELECT COUNT(*) FROM forum_posts WHERE topic_id = t.id) AS post_count,
                    (SELECT MAX(created_at) FROM forum_posts WHERE topic_id = t.id) AS last_post
             FROM forum_topics t
             JOIN users u ON t.user_id = u.id
             WHERE t.course_id = :cid
             ORDER BY t.pinned DESC, COALESCE((SELECT MAX(created_at) FROM forum_posts WHERE topic_id = t.id), t.created_at) DESC",
            ['cid' => $courseId]
        );

        $this->view('forum/course', [
            'pageTitle' => 'Forum · ' . $course['title'],
            'course'    => $course,
            'topics'    => $topics,
        ]);
    }

    public function newTopic(int $courseId): void
    {
        Auth::requireLogin();
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect(''); }
        $canAccess = Auth::isAdmin() || (Auth::isTeacher() && $course['teacher_id'] == Auth::id()) || (Auth::isStudent() && Auth::isEnrolled($courseId));
        if (!$canAccess) { $this->flash('error', 'Access denied.'); $this->redirect(''); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($this->input('title', ''));
            $body  = trim($this->input('body', ''));
            if (empty($title)) {
                $this->flash('error', 'Title is required.');
                $this->redirect('forum/course/' . $courseId);
            }
            $id = Database::insert('forum_topics', [
                'course_id' => $courseId,
                'user_id'   => Auth::id(),
                'title'     => $title,
                'body'      => $body,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            Gamification::award(Auth::id(), 'forum_topic', $id);
            // Notify course teacher
            Database::insert('notifications', [
                'user_id' => $course['teacher_id'],
                'title'   => 'New forum topic',
                'body'    => Auth::user()['full_name'] . ' posted "' . $title . '"',
                'link'    => 'forum/topic/' . $id,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->flash('success', 'Topic created.');
            $this->redirect('forum/topic/' . $id);
        }
        $this->redirect('forum/course/' . $courseId);
    }

    public function topic(int $topicId): void
    {
        Auth::requireLogin();
        $topic = Database::fetch(
            "SELECT t.*, u.full_name AS author_name, u.role AS author_role, u.avatar AS author_avatar,
                    c.title AS course_title, c.id AS course_id, c.teacher_id
             FROM forum_topics t
             JOIN users u ON t.user_id = u.id
             JOIN courses c ON t.course_id = c.id
             WHERE t.id = :id",
            ['id' => $topicId]
        );
        if (!$topic) { http_response_code(404); $this->view('errors/404', ['message' => 'Topic not found.']); return; }

        $canAccess = Auth::isAdmin() || (Auth::isTeacher() && $topic['teacher_id'] == Auth::id()) || (Auth::isStudent() && Auth::isEnrolled($topic['course_id']));
        if (!$canAccess) { $this->flash('error', 'Access denied.'); $this->redirect(''); }

        // Increment views
        Database::query('UPDATE forum_topics SET views = views + 1 WHERE id = :id', ['id' => $topicId]);

        $posts = Database::fetchAll(
            "SELECT p.*, u.full_name AS author_name, u.role AS author_role, u.avatar AS author_avatar
             FROM forum_posts p JOIN users u ON p.user_id = u.id
             WHERE p.topic_id = :tid
             ORDER BY p.created_at ASC",
            ['tid' => $topicId]
        );

        $canModerate = Auth::isAdmin() || (Auth::isTeacher() && $topic['teacher_id'] == Auth::id());
        $canReply = $canModerate || (!$topic['locked'] && Auth::check());

        $this->view('forum/topic', [
            'pageTitle' => $topic['title'],
            'topic'     => $topic,
            'posts'     => $posts,
            'canModerate' => $canModerate,
            'canReply'  => $canReply,
        ]);
    }

    public function reply(int $topicId): void
    {
        Auth::requireLogin();
        $topic = Database::fetch(
            "SELECT t.*, c.teacher_id FROM forum_topics t JOIN courses c ON t.course_id = c.id WHERE t.id = :id",
            ['id' => $topicId]
        );
        if (!$topic) { $this->redirect(''); }
        $canAccess = Auth::isAdmin() || (Auth::isTeacher() && $topic['teacher_id'] == Auth::id()) || (Auth::isStudent() && Auth::isEnrolled($topic['course_id']));
        if (!$canAccess || $topic['locked']) { $this->flash('error', 'Cannot reply to this topic.'); $this->redirect(''); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = trim($this->input('body', ''));
            if (!empty($body)) {
                Database::insert('forum_posts', [
                    'topic_id' => $topicId,
                    'user_id'  => Auth::id(),
                    'body'     => $body,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                Database::query('UPDATE forum_topics SET reply_count = reply_count + 1, updated_at = :t WHERE id = :id', ['t' => date('Y-m-d H:i:s'), 'id' => $topicId]);
                Gamification::award(Auth::id(), 'forum_post');
                // Notify topic author (if not self)
                if ($topic['user_id'] != Auth::id()) {
                    Database::insert('notifications', [
                        'user_id' => $topic['user_id'],
                        'title'   => 'New reply to your topic',
                        'body'    => Auth::user()['full_name'] . ' replied to "' . $topic['title'] . '"',
                        'link'    => 'forum/topic/' . $topicId,
                        'is_read' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $this->flash('success', 'Reply posted.');
            }
        }
        $this->redirect('forum/topic/' . $topicId);
    }

    public function deleteTopic(int $topicId): void
    {
        Auth::requireLogin();
        $topic = Database::fetch(
            "SELECT t.*, c.teacher_id FROM forum_topics t JOIN courses c ON t.course_id = c.id WHERE t.id = :id",
            ['id' => $topicId]
        );
        if (!$topic) { $this->redirect(''); }
        $canMod = Auth::isAdmin() || (Auth::isTeacher() && $topic['teacher_id'] == Auth::id()) || $topic['user_id'] == Auth::id();
        if (!$canMod) { $this->flash('error', 'You cannot delete this topic.'); $this->redirect(''); }
        Database::delete('forum_topics', 'id = :id', ['id' => $topicId]);
        $this->flash('success', 'Topic deleted.');
        $this->redirect('forum/course/' . $topic['course_id']);
    }

    public function deletePost(int $postId): void
    {
        Auth::requireLogin();
        $post = Database::fetch(
            "SELECT p.*, t.course_id, c.teacher_id FROM forum_posts p
             JOIN forum_topics t ON p.topic_id = t.id
             JOIN courses c ON t.course_id = c.id
             WHERE p.id = :id",
            ['id' => $postId]
        );
        if (!$post) { $this->redirect(''); }
        $canMod = Auth::isAdmin() || (Auth::isTeacher() && $post['teacher_id'] == Auth::id()) || $post['user_id'] == Auth::id();
        if (!$canMod) { $this->flash('error', 'Cannot delete this post.'); $this->redirect(''); }
        Database::delete('forum_posts', 'id = :id', ['id' => $postId]);
        Database::query('UPDATE forum_topics SET reply_count = GREATEST(reply_count - 1, 0) WHERE id = :id', ['id' => $post['topic_id']]);
        $this->flash('success', 'Post deleted.');
        $this->redirect('forum/topic/' . $post['topic_id']);
    }

    public function togglePin(int $topicId): void
    {
        Auth::requireLogin();
        $topic = Database::fetch("SELECT t.*, c.teacher_id FROM forum_topics t JOIN courses c ON t.course_id = c.id WHERE t.id = :id", ['id' => $topicId]);
        if (!$topic) { $this->redirect(''); }
        if (!Auth::isAdmin() && !(Auth::isTeacher() && $topic['teacher_id'] == Auth::id())) { $this->flash('error', 'Only moderators can pin topics.'); $this->redirect(''); }
        Database::query('UPDATE forum_topics SET pinned = 1 - pinned WHERE id = :id', ['id' => $topicId]);
        $this->redirect('forum/topic/' . $topicId);
    }

    public function toggleLock(int $topicId): void
    {
        Auth::requireLogin();
        $topic = Database::fetch("SELECT t.*, c.teacher_id FROM forum_topics t JOIN courses c ON t.course_id = c.id WHERE t.id = :id", ['id' => $topicId]);
        if (!$topic) { $this->redirect(''); }
        if (!Auth::isAdmin() && !(Auth::isTeacher() && $topic['teacher_id'] == Auth::id())) { $this->flash('error', 'Only moderators can lock topics.'); $this->redirect(''); }
        Database::query('UPDATE forum_topics SET locked = 1 - locked WHERE id = :id', ['id' => $topicId]);
        $this->redirect('forum/topic/' . $topicId);
    }
}
