<?php
/**
 * doodle - Notifications controller
 *
 * @package doodle
 * @author  Death Legion Team
 */

class NotificationsController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $notifs = Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 100",
            ['uid' => Auth::id()]
        );
        $this->view('notifications/index', [
            'pageTitle' => 'Notifications',
            'notifs'    => $notifs,
        ]);
    }

    public function markAllRead(): void
    {
        Auth::requireLogin();
        Database::query('UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0', ['uid' => Auth::id()]);
        $this->flash('success', 'All notifications marked as read.');
        $this->redirectBack();
    }

    public function markRead(int $id): void
    {
        Auth::requireLogin();
        $n = Database::fetch('SELECT * FROM notifications WHERE id = :id AND user_id = :uid', ['id' => $id, 'uid' => Auth::id()]);
        if ($n) {
            Database::query('UPDATE notifications SET is_read = 1 WHERE id = :id', ['id' => $id]);
            if (!empty($n['link'])) {
                $this->redirect($n['link']);
                return;
            }
        }
        $this->redirect('notifications');
    }
}
