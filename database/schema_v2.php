<?php
/**
 * doodle - Schema migration v2.0 — adds tables for AI, gamification,
 * messaging, calendar, certificates, flashcards, polls, learning paths,
 * analytics, code exercises, and API tokens.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class SchemaV2
{
    public static function install(PDO $db): void
    {
        $isSqlite = (strpos($db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false);
        $ai = $isSqlite ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $textType = $isSqlite ? 'TEXT' : 'LONGTEXT';

        // -------------------------------------------------------------------
        // AI CONVERSATIONS (per-course chat)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS ai_conversations (
            id          INTEGER PRIMARY KEY $ai,
            user_id     INTEGER NOT NULL,
            course_id   INTEGER,
            title       VARCHAR(255) NOT NULL DEFAULT 'New chat',
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME,
            FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS ai_messages (
            id              INTEGER PRIMARY KEY $ai,
            conversation_id INTEGER NOT NULL,
            role            VARCHAR(20) NOT NULL,
            content         $textType NOT NULL,
            tokens_used     INTEGER DEFAULT 0,
            model           VARCHAR(50),
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (conversation_id) REFERENCES ai_conversations(id) ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // GAMIFICATION: POINTS, BADGES, LEVELS, STREAKS
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS points_log (
            id          INTEGER PRIMARY KEY $ai,
            user_id     INTEGER NOT NULL,
            points      INTEGER NOT NULL,
            reason      VARCHAR(255) NOT NULL,
            ref_type    VARCHAR(50),
            ref_id      INTEGER,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS badges (
            id              INTEGER PRIMARY KEY $ai,
            name            VARCHAR(100) NOT NULL,
            slug            VARCHAR(120) NOT NULL UNIQUE,
            description     TEXT,
            icon            VARCHAR(50) NOT NULL DEFAULT 'emoji_events',
            color           VARCHAR(20) NOT NULL DEFAULT '#4F46E5',
            points_required INTEGER DEFAULT 0,
            auto_rule       VARCHAR(100),
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS user_badges (
            id          INTEGER PRIMARY KEY $ai,
            user_id     INTEGER NOT NULL,
            badge_id    INTEGER NOT NULL,
            awarded_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (user_id, badge_id),
            FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
            FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS user_streaks (
            user_id         INTEGER PRIMARY KEY,
            current_streak  INTEGER NOT NULL DEFAULT 0,
            longest_streak  INTEGER NOT NULL DEFAULT 0,
            last_activity   DATE,
            total_xp        INTEGER NOT NULL DEFAULT 0,
            level           INTEGER NOT NULL DEFAULT 1,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // DIRECT MESSAGING
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS dm_threads (
            id          INTEGER PRIMARY KEY $ai,
            type        VARCHAR(20) NOT NULL DEFAULT 'direct',
            name        VARCHAR(255),
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS dm_participants (
            id          INTEGER PRIMARY KEY $ai,
            thread_id   INTEGER NOT NULL,
            user_id     INTEGER NOT NULL,
            joined_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_read_at DATETIME,
            UNIQUE (thread_id, user_id),
            FOREIGN KEY (thread_id) REFERENCES dm_threads(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)   REFERENCES users(id)      ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS dm_messages (
            id          INTEGER PRIMARY KEY $ai,
            thread_id   INTEGER NOT NULL,
            user_id     INTEGER NOT NULL,
            body        $textType NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (thread_id) REFERENCES dm_threads(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)   REFERENCES users(id)      ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // CALENDAR EVENTS
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS calendar_events (
            id          INTEGER PRIMARY KEY $ai,
            user_id     INTEGER,
            course_id   INTEGER,
            title       VARCHAR(255) NOT NULL,
            description TEXT,
            event_type  VARCHAR(30) NOT NULL DEFAULT 'deadline',
            start_at    DATETIME NOT NULL,
            end_at      DATETIME,
            location    VARCHAR(255),
            is_all_day  INTEGER NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // CERTIFICATES
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS certificate_templates (
            id              INTEGER PRIMARY KEY $ai,
            course_id       INTEGER NOT NULL,
            name            VARCHAR(255) NOT NULL,
            template_html   $textType,
            min_score       DECIMAL(5,2) NOT NULL DEFAULT 60.00,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS certificates (
            id              INTEGER PRIMARY KEY $ai,
            template_id     INTEGER NOT NULL,
            user_id         INTEGER NOT NULL,
            course_id       INTEGER NOT NULL,
            verify_code     VARCHAR(64) NOT NULL UNIQUE,
            final_score     DECIMAL(5,2),
            issued_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (template_id, user_id),
            FOREIGN KEY (template_id) REFERENCES certificate_templates(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)     REFERENCES users(id)                 ON DELETE CASCADE,
            FOREIGN KEY (course_id)   REFERENCES courses(id)               ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // FLASHCARDS (with SM-2 spaced repetition)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS flashcard_decks (
            id          INTEGER PRIMARY KEY $ai,
            course_id   INTEGER,
            user_id     INTEGER,
            title       VARCHAR(255) NOT NULL,
            description TEXT,
            is_public   INTEGER NOT NULL DEFAULT 0,
            is_ai_generated INTEGER NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)  REFERENCES users(id)    ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS flashcards (
            id          INTEGER PRIMARY KEY $ai,
            deck_id     INTEGER NOT NULL,
            front       $textType NOT NULL,
            back        $textType NOT NULL,
            hint        TEXT,
            sort_order  INTEGER NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (deck_id) REFERENCES flashcard_decks(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS flashcard_reviews (
            id              INTEGER PRIMARY KEY $ai,
            flashcard_id    INTEGER NOT NULL,
            user_id         INTEGER NOT NULL,
            ease_factor     DECIMAL(3,2) NOT NULL DEFAULT 2.50,
            interval_days   INTEGER NOT NULL DEFAULT 0,
            repetitions     INTEGER NOT NULL DEFAULT 0,
            next_review     DATE NOT NULL,
            last_reviewed   DATE,
            UNIQUE (flashcard_id, user_id),
            FOREIGN KEY (flashcard_id) REFERENCES flashcards(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // POLLS & SURVEYS
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS polls (
            id          INTEGER PRIMARY KEY $ai,
            course_id   INTEGER NOT NULL,
            user_id     INTEGER NOT NULL,
            question    VARCHAR(500) NOT NULL,
            description TEXT,
            poll_type   VARCHAR(20) NOT NULL DEFAULT 'multiple',
            is_anonymous INTEGER NOT NULL DEFAULT 0,
            allow_multiple INTEGER NOT NULL DEFAULT 0,
            opens_at    DATETIME,
            closes_at   DATETIME,
            is_active   INTEGER NOT NULL DEFAULT 1,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS poll_options (
            id          INTEGER PRIMARY KEY $ai,
            poll_id     INTEGER NOT NULL,
            option_text VARCHAR(500) NOT NULL,
            sort_order  INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS poll_responses (
            id          INTEGER PRIMARY KEY $ai,
            poll_id     INTEGER NOT NULL,
            option_id   INTEGER NOT NULL,
            user_id     INTEGER NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (poll_id, option_id, user_id),
            FOREIGN KEY (poll_id)   REFERENCES polls(id)        ON DELETE CASCADE,
            FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)   REFERENCES users(id)        ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // LEARNING PATHS
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS learning_paths (
            id              INTEGER PRIMARY KEY $ai,
            title           VARCHAR(255) NOT NULL,
            slug            VARCHAR(280) NOT NULL,
            description     $textType,
            cover_image     VARCHAR(255),
            creator_id      INTEGER NOT NULL,
            is_published    INTEGER NOT NULL DEFAULT 0,
            estimated_hours INTEGER,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS learning_path_courses (
            id              INTEGER PRIMARY KEY $ai,
            path_id         INTEGER NOT NULL,
            course_id       INTEGER NOT NULL,
            sort_order      INTEGER NOT NULL DEFAULT 0,
            is_required     INTEGER NOT NULL DEFAULT 1,
            UNIQUE (path_id, course_id),
            FOREIGN KEY (path_id)   REFERENCES learning_paths(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id)        ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS learning_path_enrollments (
            id          INTEGER PRIMARY KEY $ai,
            path_id     INTEGER NOT NULL,
            user_id     INTEGER NOT NULL,
            enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME,
            UNIQUE (path_id, user_id),
            FOREIGN KEY (path_id) REFERENCES learning_paths(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id)          ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // CODE EXERCISES (programming challenges with test cases)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS code_exercises (
            id              INTEGER PRIMARY KEY $ai,
            course_id       INTEGER NOT NULL,
            module_id       INTEGER,
            title           VARCHAR(255) NOT NULL,
            description     $textType,
            problem_statement $textType,
            starter_code    $textType,
            solution_code   $textType,
            language        VARCHAR(30) NOT NULL DEFAULT 'python',
            test_cases      $textType,
            difficulty      VARCHAR(20) NOT NULL DEFAULT 'easy',
            points          DECIMAL(6,2) NOT NULL DEFAULT 10.00,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS code_submissions (
            id              INTEGER PRIMARY KEY $ai,
            exercise_id     INTEGER NOT NULL,
            user_id         INTEGER NOT NULL,
            code            $textType NOT NULL,
            language        VARCHAR(30) NOT NULL,
            passed_tests    INTEGER NOT NULL DEFAULT 0,
            total_tests     INTEGER NOT NULL DEFAULT 0,
            execution_time  INTEGER,
            status          VARCHAR(20) NOT NULL DEFAULT 'pending',
            output          $textType,
            submitted_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (exercise_id) REFERENCES code_exercises(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)      REFERENCES users(id)         ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // API TOKENS (REST API for mobile clients)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS api_tokens (
            id          INTEGER PRIMARY KEY $ai,
            user_id     INTEGER NOT NULL,
            token       VARCHAR(80) NOT NULL UNIQUE,
            name        VARCHAR(100) NOT NULL,
            last_used   DATETIME,
            expires_at  DATETIME,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // ANALYTICS EVENTS (raw event log for analytics)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS analytics_events (
            id          INTEGER PRIMARY KEY $ai,
            user_id     INTEGER,
            course_id   INTEGER,
            event_type  VARCHAR(50) NOT NULL,
            event_data  $textType,
            ip_address  VARCHAR(45),
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE SET NULL,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
        )");

        // -------------------------------------------------------------------
        // PEER REVIEWS
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS peer_reviews (
            id                  INTEGER PRIMARY KEY $ai,
            assignment_id       INTEGER NOT NULL,
            reviewer_id         INTEGER NOT NULL,
            submission_id       INTEGER NOT NULL,
            score               DECIMAL(6,2),
            feedback            $textType,
            status              VARCHAR(20) NOT NULL DEFAULT 'pending',
            submitted_at        DATETIME,
            FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewer_id)   REFERENCES users(id)       ON DELETE CASCADE,
            FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // Indexes
        // -------------------------------------------------------------------
        $indexes = [
            'ai_conversations'  => ['user_id', 'course_id'],
            'ai_messages'       => ['conversation_id'],
            'points_log'        => ['user_id', 'created_at'],
            'user_badges'       => ['user_id', 'badge_id'],
            'dm_messages'       => ['thread_id', 'created_at'],
            'dm_participants'   => ['user_id', 'thread_id'],
            'calendar_events'   => ['user_id', 'course_id', 'start_at'],
            'certificates'      => ['user_id', 'course_id', 'verify_code'],
            'flashcards'        => ['deck_id'],
            'flashcard_reviews' => ['user_id', 'next_review'],
            'polls'             => ['course_id', 'is_active'],
            'poll_responses'    => ['poll_id', 'user_id'],
            'learning_paths'    => ['creator_id', 'is_published'],
            'learning_path_courses' => ['path_id', 'course_id'],
            'code_exercises'    => ['course_id'],
            'code_submissions'  => ['exercise_id', 'user_id'],
            'api_tokens'        => ['user_id', 'token'],
            'analytics_events'  => ['user_id', 'course_id', 'event_type', 'created_at'],
            'peer_reviews'      => ['assignment_id', 'reviewer_id'],
        ];
        foreach ($indexes as $table => $cols) {
            foreach ($cols as $col) {
                $idx = 'idx_v2_' . $table . '_' . $col;
                try { $db->exec("CREATE INDEX IF NOT EXISTS $idx ON $table ($col)"); } catch (PDOException $e) {}
            }
        }

        // -------------------------------------------------------------------
        // Seed default badges
        // -------------------------------------------------------------------
        $defaultBadges = [
            ['First Steps',      'first-lesson',     'Complete your first lesson',              'directions_walk', '#4F46E5', 0,   'first_lesson'],
            ['Quick Learner',    'ten-lessons',      'Complete 10 lessons',                     'speed',           '#059669', 50,  'ten_lessons'],
            ['Scholar',          'fifty-lessons',    'Complete 50 lessons',                     'school',          '#D97706', 200, 'fifty_lessons'],
            ['Quiz Master',      'quiz-master',      'Pass 10 quizzes',                         'quiz',            '#EC4899', 100, 'quiz_master'],
            ['Perfect Score',    'perfect-score',    'Get 100% on a quiz',                      'star',            '#DC2626', 50,  'perfect_score'],
            ['Social Butterfly', 'social',           'Post 10 forum replies',                    'forum',           '#0891B2', 50,  'social_butterfly'],
            ['Course Complete',  'course-complete',  'Finish an entire course',                 'emoji_events',    '#7C3AED', 200, 'course_complete'],
            ['Early Bird',       'early-bird',       'Study before 8 AM',                       'wb_sunny',        '#F59E0B', 25,  'early_bird'],
            ['Night Owl',        'night-owl',        'Study after 10 PM',                       'nights_stay',     '#6366F1', 25,  'night_owl'],
            ['Streak 7',         'streak-7',         '7-day study streak',                      'local_fire_department', '#EF4444', 100, 'streak_7'],
            ['Streak 30',        'streak-30',        '30-day study streak',                     'whatshot',        '#B91C1C', 500, 'streak_30'],
            ['Helpful Peer',     'helpful',          'Receive 5 upvotes on forum posts',        'thumb_up',        '#10B981', 100, 'helpful_peer'],
        ];
        $existingBadges = (int) $db->query("SELECT COUNT(*) FROM badges")->fetchColumn();
        if ($existingBadges == 0) {
            foreach ($defaultBadges as $b) {
                $db->prepare("INSERT INTO badges (name, slug, description, icon, color, points_required, auto_rule) VALUES (?, ?, ?, ?, ?, ?, ?)")
                   ->execute($b);
            }
        }

        // Add XP/level columns to users table if they don't exist (for SQLite, just rely on user_streaks table)
        // For MySQL, the user_streaks table is the canonical source.

        // Add 'theme_preference' column to users (if it doesn't exist) — used for dark mode
        try {
            $db->exec("ALTER TABLE users ADD COLUMN theme_preference VARCHAR(10) DEFAULT 'auto'");
        } catch (PDOException $e) { /* column already exists */ }

        // Add 'language' column to users (for per-user language)
        try {
            $db->exec("ALTER TABLE users ADD COLUMN language VARCHAR(10) DEFAULT 'en'");
        } catch (PDOException $e) { /* column already exists */ }
    }
}
