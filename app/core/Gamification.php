<?php
/**
 * doodle - Gamification engine
 *
 * Award points, badges, levels, and streaks based on user actions.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class Gamification
{
    /** XP required to reach each level (cumulative). */
    private const LEVEL_THRESHOLDS = [0, 100, 250, 500, 1000, 1750, 2750, 4000, 5500, 7500, 10000];

    /** XP awarded per action. */
    private const POINTS = [
        'lesson_complete'   => 10,
        'quiz_pass'         => 50,
        'quiz_perfect'      => 100,
        'quiz_attempt'      => 5,
        'assignment_submit' => 30,
        'assignment_graded_high' => 75,
        'forum_post'        => 15,
        'forum_topic'       => 20,
        'course_enroll'     => 25,
        'course_complete'   => 200,
        'flashcard_review'  => 3,
        'daily_login'       => 5,
        'ai_chat'           => 2,
        'message_sent'      => 2,
    ];

    /** Initialize streak row for user if missing. */
    public static function ensureStreak(int $userId): void
    {
        $row = Database::fetch('SELECT user_id FROM user_streaks WHERE user_id = :uid', ['uid' => $userId]);
        if (!$row) {
            Database::insert('user_streaks', [
                'user_id'        => $userId,
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_activity'  => null,
                'total_xp'       => 0,
                'level'          => 1,
            ]);
        }
    }

    /** Award points for an action. Returns total XP after award. */
    public static function award(int $userId, string $action, ?int $refId = null, ?string $reason = null): int
    {
        $points = self::POINTS[$action] ?? 0;
        if ($points <= 0) return 0;

        self::ensureStreak($userId);
        Database::insert('points_log', [
            'user_id'  => $userId,
            'points'   => $points,
            'reason'   => $reason ?? str_replace('_', ' ', $action),
            'ref_type' => $action,
            'ref_id'   => $refId,
        ]);
        Database::query('UPDATE user_streaks SET total_xp = total_xp + :p WHERE user_id = :uid', ['p' => $points, 'uid' => $userId]);

        // Update streak
        self::updateStreak($userId);

        // Recompute level
        $streak = Database::fetch('SELECT total_xp FROM user_streaks WHERE user_id = :uid', ['uid' => $userId]);
        $totalXp = (int) ($streak['total_xp'] ?? 0);
        $newLevel = self::levelForXp($totalXp);
        Database::query('UPDATE user_streaks SET level = :l WHERE user_id = :uid', ['l' => $newLevel, 'uid' => $userId]);

        // Check auto-awarded badges
        self::checkAutoBadges($userId);

        return $totalXp;
    }

    /** Update streak based on today's activity. */
    public static function updateStreak(int $userId): void
    {
        $today = date('Y-m-d');
        $row = Database::fetch('SELECT current_streak, longest_streak, last_activity FROM user_streaks WHERE user_id = :uid', ['uid' => $userId]);
        if (!$row) return;
        if ($row['last_activity'] === $today) return; // already counted today

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($row['last_activity'] === $yesterday) {
            $newStreak = $row['current_streak'] + 1;
        } else {
            $newStreak = 1; // reset
        }
        $longest = max($row['longest_streak'], $newStreak);
        Database::query('UPDATE user_streaks SET current_streak = :c, longest_streak = :l, last_activity = :t WHERE user_id = :uid',
            ['c' => $newStreak, 'l' => $longest, 't' => $today, 'uid' => $userId]);
    }

    public static function levelForXp(int $xp): int
    {
        $level = 1;
        foreach (self::LEVEL_THRESHOLDS as $i => $threshold) {
            if ($xp >= $threshold) $level = $i + 1;
            else break;
        }
        return $level;
    }

    public static function xpForLevel(int $level): int
    {
        return self::LEVEL_THRESHOLDS[min($level - 1, count(self::LEVEL_THRESHOLDS) - 1)];
    }

    public static function nextLevelXp(int $level): int
    {
        return self::LEVEL_THRESHOLDS[min($level, count(self::LEVEL_THRESHOLDS) - 1)] ?? PHP_INT_MAX;
    }

    /** Progress (0-100) toward the next level. */
    public static function levelProgress(int $xp): array
    {
        $level = self::levelForXp($xp);
        $curThreshold = self::xpForLevel($level);
        $nextThreshold = self::nextLevelXp($level);
        $pct = $nextThreshold > $curThreshold ? (($xp - $curThreshold) / ($nextThreshold - $curThreshold)) * 100 : 100;
        return [
            'level'         => $level,
            'current_xp'    => $xp,
            'level_start'   => $curThreshold,
            'next_level_xp' => $nextThreshold,
            'progress_pct'  => round($pct, 1),
            'xp_to_next'    => max(0, $nextThreshold - $xp),
        ];
    }

    /** Check & auto-award badges. */
    public static function checkAutoBadges(int $userId): void
    {
        $badges = Database::fetchAll('SELECT * FROM badges WHERE auto_rule IS NOT NULL AND auto_rule != ""');
        foreach ($badges as $b) {
            if (self::hasBadge($userId, $b['id'])) continue;
            if (self::evaluateRule($userId, $b['auto_rule'])) {
                Database::insert('user_badges', ['user_id' => $userId, 'badge_id' => $b['id']]);
                // Notify
                Database::insert('notifications', [
                    'user_id' => $userId,
                    'title'   => 'Badge unlocked!',
                    'body'    => 'You earned the "' . $b['name'] . '" badge.',
                    'link'    => 'gamification',
                    'is_read' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public static function hasBadge(int $userId, int $badgeId): bool
    {
        return Database::fetch('SELECT id FROM user_badges WHERE user_id = :u AND badge_id = :b', ['u' => $userId, 'b' => $badgeId]) !== null;
    }

    private static function evaluateRule(int $userId, string $rule): bool
    {
        switch ($rule) {
            case 'first_lesson':
                return Database::count('lesson_completions', 'student_id = :uid', ['uid' => $userId]) >= 1;
            case 'ten_lessons':
                return Database::count('lesson_completions', 'student_id = :uid', ['uid' => $userId]) >= 10;
            case 'fifty_lessons':
                return Database::count('lesson_completions', 'student_id = :uid', ['uid' => $userId]) >= 50;
            case 'quiz_master':
                return Database::count('attempts', 'student_id = :uid AND passed = 1', ['uid' => $userId]) >= 10;
            case 'perfect_score':
                return Database::fetch('SELECT id FROM attempts WHERE student_id = :uid AND percentage = 100', ['uid' => $userId]) !== null;
            case 'social_butterfly':
                return Database::count('forum_posts', 'user_id = :uid', ['uid' => $userId]) >= 10;
            case 'course_complete':
                return Database::count('enrollments', 'student_id = :uid AND status = "completed" OR (progress = 100)', ['uid' => $userId]) >= 1;
            case 'early_bird':
                $hour = (int) date('G');
                return $hour >= 5 && $hour < 8;
            case 'night_owl':
                $hour = (int) date('G');
                return $hour >= 22 || $hour < 2;
            case 'streak_7':
                $s = Database::fetch('SELECT current_streak FROM user_streaks WHERE user_id = :uid', ['uid' => $userId]);
                return $s && $s['current_streak'] >= 7;
            case 'streak_30':
                $s = Database::fetch('SELECT current_streak FROM user_streaks WHERE user_id = :uid', ['uid' => $userId]);
                return $s && $s['current_streak'] >= 30;
            case 'helpful_peer':
                return Database::count('forum_posts', 'user_id = :uid', ['uid' => $userId]) >= 5;
        }
        return false;
    }

    /** Get user's earned badges + all available badges. */
    public static function getUserBadges(int $userId): array
    {
        $all = Database::fetchAll('SELECT * FROM badges ORDER BY points_required, name');
        $earned = Database::fetchAll('SELECT badge_id, awarded_at FROM user_badges WHERE user_id = :uid', ['uid' => $userId]);
        $earnedMap = [];
        foreach ($earned as $e) $earnedMap[$e['badge_id']] = $e['awarded_at'];
        foreach ($all as &$b) {
            $b['earned'] = isset($earnedMap[$b['id']]);
            $b['awarded_at'] = $earnedMap[$b['id']] ?? null;
        }
        return $all;
    }

    /** Leaderboard: top N users by XP. */
    public static function leaderboard(int $limit = 20, ?int $courseId = null): array
    {
        if ($courseId) {
            return Database::fetchAll(
                "SELECT u.id, u.full_name, u.avatar, COALESCE(s.total_xp, 0) AS xp, COALESCE(s.level, 1) AS level, COALESCE(s.current_streak, 0) AS streak
                 FROM users u
                 JOIN enrollments e ON e.student_id = u.id AND e.course_id = :cid AND e.status = 'active'
                 LEFT JOIN user_streaks s ON s.user_id = u.id
                 ORDER BY xp DESC, u.full_name ASC
                 LIMIT :limit",
                ['cid' => $courseId, 'limit' => $limit]
            );
        }
        return Database::fetchAll(
            "SELECT u.id, u.full_name, u.avatar, COALESCE(s.total_xp, 0) AS xp, COALESCE(s.level, 1) AS level, COALESCE(s.current_streak, 0) AS streak
             FROM users u
             LEFT JOIN user_streaks s ON s.user_id = u.id
             WHERE u.status = 'active' AND u.role IN ('student', 'teacher')
             ORDER BY xp DESC, u.full_name ASC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }

    /** Get the user's stats summary. */
    public static function userStats(int $userId): array
    {
        self::ensureStreak($userId);
        $row = Database::fetch('SELECT * FROM user_streaks WHERE user_id = :uid', ['uid' => $userId]);
        $xp = (int) ($row['total_xp'] ?? 0);
        $levelInfo = self::levelProgress($xp);
        $badges = self::getUserBadges($userId);
        $earnedBadges = array_filter($badges, fn($b) => $b['earned']);
        return [
            'xp'             => $xp,
            'level'          => $levelInfo['level'],
            'level_progress' => $levelInfo,
            'current_streak' => (int) ($row['current_streak'] ?? 0),
            'longest_streak' => (int) ($row['longest_streak'] ?? 0),
            'badges_total'   => count($badges),
            'badges_earned'  => count($earnedBadges),
            'recent_points'  => Database::fetchAll('SELECT * FROM points_log WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10', ['uid' => $userId]),
        ];
    }
}
