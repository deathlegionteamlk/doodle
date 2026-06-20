<?php
/**
 * doodle - Direct messaging controller
 *
 * 1-to-1 and group threads between any users.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class MessageController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireLogin();
    }

    public function index(): void
    {
        $uid = Auth::id();
        $threads = Database::fetchAll(
            "SELECT t.*,
                    (SELECT body FROM dm_messages WHERE thread_id = t.id ORDER BY created_at DESC LIMIT 1) AS last_msg,
                    (SELECT created_at FROM dm_messages WHERE thread_id = t.id ORDER BY created_at DESC LIMIT 1) AS last_msg_at,
                    (SELECT COUNT(*) FROM dm_messages m LEFT JOIN dm_participants p ON p.thread_id = m.thread_id AND p.user_id = :uid WHERE m.thread_id = t.id AND m.created_at > COALESCE(p.last_read_at, '1970-01-01')) AS unread,
                    COALESCE(p.last_read_at, '1970-01-01') AS last_read
             FROM dm_threads t
             JOIN dm_participants p ON p.thread_id = t.id
             WHERE p.user_id = :uid
             ORDER BY last_msg_at DESC NULLS LAST, t.updated_at DESC",
            ['uid' => $uid]
        );
        // SQLite doesn't support NULLS LAST syntax — use a portable approach
        if (DB_TYPE === 'sqlite') {
            $threads = Database::fetchAll(
                "SELECT t.*,
                        (SELECT body FROM dm_messages WHERE thread_id = t.id ORDER BY created_at DESC LIMIT 1) AS last_msg,
                        (SELECT created_at FROM dm_messages WHERE thread_id = t.id ORDER BY created_at DESC LIMIT 1) AS last_msg_at,
                        (SELECT COUNT(*) FROM dm_messages m LEFT JOIN dm_participants p ON p.thread_id = m.thread_id AND p.user_id = :uid WHERE m.thread_id = t.id AND m.created_at > COALESCE(p.last_read_at, '1970-01-01')) AS unread,
                        COALESCE(p.last_read_at, '1970-01-01') AS last_read
                 FROM dm_threads t
                 JOIN dm_participants p ON p.thread_id = t.id
                 WHERE p.user_id = :uid
                 ORDER BY last_msg_at IS NULL, last_msg_at DESC, t.updated_at DESC",
                ['uid' => $uid]
            );
        }
        // Pre-fetch participants for each thread
        foreach ($threads as &$t) {
            $t['participants'] = Database::fetchAll(
                "SELECT u.id, u.full_name, u.avatar, u.role FROM dm_participants p JOIN users u ON p.user_id = u.id WHERE p.thread_id = :tid",
                ['tid' => $t['id']]
            );
        }
        $this->view('messages/index', [
            'pageTitle' => 'Messages',
            'threads'   => $threads,
        ]);
    }

    public function view(int $threadId): void
    {
        $uid = Auth::id();
        $thread = Database::fetch('SELECT * FROM dm_threads WHERE id = :id', ['id' => $threadId]);
        if (!$thread) { $this->redirect('messages'); }
        $participant = Database::fetch('SELECT * FROM dm_participants WHERE thread_id = :tid AND user_id = :uid', ['tid' => $threadId, 'uid' => $uid]);
        if (!$participant) { $this->flash('error', 'You are not part of this conversation.'); $this->redirect('messages'); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = trim($this->input('body', ''));
            if ($body !== '') {
                Database::insert('dm_messages', [
                    'thread_id' => $threadId,
                    'user_id'   => $uid,
                    'body'      => $body,
                ]);
                Database::update('dm_threads', ['updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $threadId]);
                Gamification::award($uid, 'message_sent');
            }
            $this->redirect('messages/view/' . $threadId);
        }

        // Mark as read
        Database::update('dm_participants', ['last_read_at' => date('Y-m-d H:i:s')], 'thread_id = :tid AND user_id = :uid', ['tid' => $threadId, 'uid' => $uid]);

        $messages = Database::fetchAll(
            "SELECT m.*, u.full_name, u.avatar, u.role
             FROM dm_messages m JOIN users u ON m.user_id = u.id
             WHERE m.thread_id = :tid
             ORDER BY m.created_at ASC, m.id ASC",
            ['tid' => $threadId]
        );
        $participants = Database::fetchAll(
            "SELECT u.id, u.full_name, u.avatar, u.role FROM dm_participants p JOIN users u ON p.user_id = u.id WHERE p.thread_id = :tid",
            ['tid' => $threadId]
        );
        $this->view('messages/view', [
            'pageTitle'    => 'Conversation',
            'thread'       => $thread,
            'messages'     => $messages,
            'participants' => $participants,
        ]);
    }

    public function new(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $uid = Auth::id();
            $recipientIds = array_filter(array_map('intval', (array) $this->input('recipients', [])));
            $recipientIds = array_diff($recipientIds, [$uid]); // can't DM self
            if (empty($recipientIds)) {
                $this->flash('error', 'Select at least one recipient.');
                $this->redirect('messages/new');
            }
            $body = trim($this->input('body', ''));
            if ($body === '') {
                $this->flash('error', 'Message body cannot be empty.');
                $this->redirect('messages/new');
            }
            // Create thread
            $threadId = Database::insert('dm_threads', [
                'type' => count($recipientIds) > 1 ? 'group' : 'direct',
                'name' => count($recipientIds) > 1 ? ($this->input('name', 'Group chat')) : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            // Add participants (sender + recipients)
            Database::insert('dm_participants', ['thread_id' => $threadId, 'user_id' => $uid, 'last_read_at' => date('Y-m-d H:i:s')]);
            foreach ($recipientIds as $rid) {
                Database::insert('dm_participants', ['thread_id' => $threadId, 'user_id' => $rid]);
                // Notify
                Database::insert('notifications', [
                    'user_id' => $rid,
                    'title'   => 'New message',
                    'body'    => Auth::user()['full_name'] . ' sent you a message.',
                    'link'    => 'messages/view/' . $threadId,
                    'is_read' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            // Save first message
            Database::insert('dm_messages', [
                'thread_id' => $threadId,
                'user_id'   => $uid,
                'body'      => $body,
            ]);
            $this->flash('success', 'Message sent.');
            $this->redirect('messages/view/' . $threadId);
        }
        // User picker: exclude self, only active users. For students, restrict to teachers of their courses + admins.
        if (Auth::isStudent()) {
            $users = Database::fetchAll(
                "SELECT DISTINCT u.id, u.full_name, u.avatar, u.role, u.email
                 FROM users u
                 LEFT JOIN courses c ON c.teacher_id = u.id
                 LEFT JOIN enrollments e ON e.course_id = c.id AND e.student_id = :uid
                 WHERE u.id != :uid AND u.status = 'active'
                   AND (u.role = 'admin' OR e.id IS NOT NULL)
                 ORDER BY u.full_name",
                ['uid' => Auth::id()]
            );
        } else {
            $users = Database::fetchAll(
                "SELECT id, full_name, avatar, role, email FROM users WHERE id != :uid AND status = 'active' ORDER BY full_name",
                ['uid' => Auth::id()]
            );
        }
        $this->view('messages/new', [
            'pageTitle' => 'New message',
            'users'     => $users,
        ]);
    }
}
