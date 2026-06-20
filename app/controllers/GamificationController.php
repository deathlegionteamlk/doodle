<?php
/**
 * doodle - Gamification controller (badges, levels, leaderboard)
 *
 * @package doodle
 * @author  Death Legion Team
 */

class GamificationController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireLogin();
    }

    public function index(): void
    {
        $uid = Auth::id();
        $stats = Gamification::userStats($uid);
        $allBadges = Gamification::getUserBadges($uid);
        $leaderboard = Gamification::leaderboard(10);
        $rank = 0;
        foreach ($leaderboard as $i => $u) if ($u['id'] == $uid) { $rank = $i + 1; break; }
        $this->view('gamification/index', [
            'pageTitle'  => 'My Achievements',
            'stats'      => $stats,
            'badges'     => $allBadges,
            'leaderboard' => $leaderboard,
            'rank'       => $rank,
        ]);
    }

    public function leaderboard(): void
    {
        $courseId = (int) ($this->input('course_id', 0));
        $course = null;
        $board = Gamification::leaderboard(50, $courseId ?: null);
        if ($courseId > 0) {
            $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        }
        $this->view('gamification/leaderboard', [
            'pageTitle' => 'Leaderboard',
            'board'     => $board,
            'course'    => $course,
        ]);
    }

    public function badges(): void
    {
        $allBadges = Database::fetchAll('SELECT * FROM badges ORDER BY points_required, name');
        $earned = Database::fetchAll('SELECT badge_id, awarded_at FROM user_badges WHERE user_id = :uid', ['uid' => Auth::id()]);
        $earnedMap = [];
        foreach ($earned as $e) $earnedMap[$e['badge_id']] = $e['awarded_at'];
        foreach ($allBadges as &$b) {
            $b['earned'] = isset($earnedMap[$b['id']]);
            $b['awarded_at'] = $earnedMap[$b['id']] ?? null;
        }
        $this->view('gamification/badges', [
            'pageTitle' => 'All Badges',
            'badges'    => $allBadges,
        ]);
    }
}
