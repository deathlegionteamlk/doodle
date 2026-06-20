<?php
/**
 * doodle - Schema migration v3.0
 *
 * Adds tables for: login rate-limiting, 2FA, OAuth, course reviews,
 * student notes, bookmarks, live whiteboard, course wiki, group
 * assignments, push subscriptions, email queue, search index,
 * backups, maintenance mode.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class SchemaV3
{
    public static function install(PDO $db): void
    {
        $isSqlite = (strpos($db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false);
        $ai = $isSqlite ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $textType = $isSqlite ? 'TEXT' : 'LONGTEXT';

        // -------------------------------------------------------------------
        // LOGIN ATTEMPTS (rate limiting + brute-force protection)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id          INTEGER PRIMARY KEY $ai,
            identifier  VARCHAR(255) NOT NULL,
            ip_address  VARCHAR(45) NOT NULL,
            user_agent  VARCHAR(255),
            success     INTEGER NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_login_attempts_lookup ON login_attempts (identifier, ip_address, created_at)"); } catch (PDOException $e) {}

        // -------------------------------------------------------------------
        // 2FA (TOTP) SECRETS
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS user_2fa (
            user_id      INTEGER PRIMARY KEY,
            secret       VARCHAR(64) NOT NULL,
            backup_codes $textType,
            enabled_at   DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // OAUTH ACCOUNT LINKS (Google, GitHub, etc.)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS oauth_accounts (
            id            INTEGER PRIMARY KEY $ai,
            user_id       INTEGER NOT NULL,
            provider      VARCHAR(30) NOT NULL,
            provider_uid  VARCHAR(255) NOT NULL,
            email         VARCHAR(255),
            access_token  $textType,
            refresh_token $textType,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (provider, provider_uid),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // COURSE REVIEWS (5-star ratings + text)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS course_reviews (
            id          INTEGER PRIMARY KEY $ai,
            course_id   INTEGER NOT NULL,
            user_id     INTEGER NOT NULL,
            rating      INTEGER NOT NULL,
            review      $textType,
            is_anonymous INTEGER NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME,
            UNIQUE (course_id, user_id),
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // STUDENT NOTES (per-lesson private notes)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS student_notes (
            id          INTEGER PRIMARY KEY $ai,
            user_id     INTEGER NOT NULL,
            lesson_id   INTEGER NOT NULL,
            content     $textType,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME,
            UNIQUE (user_id, lesson_id),
            FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
            FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // BOOKMARKS (favorite lessons)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS bookmarks (
            id          INTEGER PRIMARY KEY $ai,
            user_id     INTEGER NOT NULL,
            lesson_id   INTEGER NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (user_id, lesson_id),
            FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
            FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // LIVE WHITEBOARD (per course, stroke-based)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS whiteboards (
            id          INTEGER PRIMARY KEY $ai,
            course_id   INTEGER NOT NULL,
            name        VARCHAR(255) NOT NULL DEFAULT 'Main Whiteboard',
            created_by  INTEGER NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id)   ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS whiteboard_strokes (
            id              INTEGER PRIMARY KEY $ai,
            whiteboard_id   INTEGER NOT NULL,
            user_id         INTEGER NOT NULL,
            stroke_data     $textType NOT NULL,
            color           VARCHAR(20) NOT NULL DEFAULT '#4F46E5',
            tool            VARCHAR(20) NOT NULL DEFAULT 'pen',
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (whiteboard_id) REFERENCES whiteboards(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)       REFERENCES users(id)      ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // COURSE WIKI (collaborative pages per course)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS wiki_pages (
            id          INTEGER PRIMARY KEY $ai,
            course_id   INTEGER NOT NULL,
            title       VARCHAR(255) NOT NULL,
            slug        VARCHAR(280) NOT NULL,
            content     $textType,
            created_by  INTEGER NOT NULL,
            updated_by  INTEGER,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME,
            FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id)   ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS wiki_revisions (
            id          INTEGER PRIMARY KEY $ai,
            page_id     INTEGER NOT NULL,
            content     $textType NOT NULL,
            edited_by   INTEGER NOT NULL,
            edited_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id)   REFERENCES wiki_pages(id) ON DELETE CASCADE,
            FOREIGN KEY (edited_by) REFERENCES users(id)     ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // GROUPS + GROUP ASSIGNMENTS
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS course_groups (
            id          INTEGER PRIMARY KEY $ai,
            course_id   INTEGER NOT NULL,
            name        VARCHAR(100) NOT NULL,
            description TEXT,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS group_members (
            id          INTEGER PRIMARY KEY $ai,
            group_id    INTEGER NOT NULL,
            user_id     INTEGER NOT NULL,
            role        VARCHAR(20) NOT NULL DEFAULT 'member',
            joined_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (group_id, user_id),
            FOREIGN KEY (group_id) REFERENCES course_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)  REFERENCES users(id)         ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // WEB PUSH SUBSCRIPTIONS
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
            id            INTEGER PRIMARY KEY $ai,
            user_id       INTEGER NOT NULL,
            endpoint      $textType NOT NULL,
            p256dh_key    $textType NOT NULL,
            auth_key      $textType NOT NULL,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (user_id, endpoint),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // EMAIL QUEUE
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS email_queue (
            id          INTEGER PRIMARY KEY $ai,
            to_email    VARCHAR(255) NOT NULL,
            to_name     VARCHAR(255),
            subject     VARCHAR(255) NOT NULL,
            body_html   $textType,
            body_text   $textType,
            status      VARCHAR(20) NOT NULL DEFAULT 'queued',
            attempts    INTEGER NOT NULL DEFAULT 0,
            last_error  $textType,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            sent_at     DATETIME
        )");

        // -------------------------------------------------------------------
        // SEARCH INDEX (simple FTS emulation)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS search_index (
            id          INTEGER PRIMARY KEY $ai,
            item_type   VARCHAR(30) NOT NULL,
            item_id     INTEGER NOT NULL,
            course_id   INTEGER,
            title       VARCHAR(500) NOT NULL,
            body        $textType,
            owner_id    INTEGER,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_search_type ON search_index (item_type, item_id)"); } catch (PDOException $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_search_course ON search_index (course_id)"); } catch (PDOException $e) {}

        // -------------------------------------------------------------------
        // BACKUPS (admin-triggered snapshots)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS backups (
            id          INTEGER PRIMARY KEY $ai,
            filename    VARCHAR(255) NOT NULL,
            file_size   INTEGER,
            created_by  INTEGER,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )");

        // -------------------------------------------------------------------
        // CODE TEST CASES (proper test runner support)
        // -------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS code_test_cases (
            id              INTEGER PRIMARY KEY $ai,
            exercise_id     INTEGER NOT NULL,
            input           $textType,
            expected_output $textType NOT NULL,
            is_hidden       INTEGER NOT NULL DEFAULT 0,
            sort_order      INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (exercise_id) REFERENCES code_exercises(id) ON DELETE CASCADE
        )");

        // -------------------------------------------------------------------
        // MAINTENANCE MODE SETTING (uses settings table, no new table needed)
        // -------------------------------------------------------------------
        // Stored as setting: maintenance_mode = '0'|'1'
        // Stored as setting: maintenance_message = '...'

        // Add new settings if missing
        $defaults = [
            'maintenance_mode'       => '0',
            'maintenance_message'    => 'We are performing scheduled maintenance. Please check back soon.',
            'smtp_host'              => '',
            'smtp_port'              => '587',
            'smtp_user'              => '',
            'smtp_pass'              => '',
            'smtp_from_email'        => '',
            'smtp_from_name'         => 'doodle',
            'enable_email'           => '0',
            'enable_web_push'        => '0',
            'web_push_vapid_public'  => '',
            'web_push_vapid_private' => '',
            'oauth_google_client_id' => '',
            'oauth_google_secret'    => '',
            'oauth_github_client_id' => '',
            'oauth_github_secret'    => '',
            'recaptcha_site_key'     => '',
            'recaptcha_secret_key'   => '',
            'default_language'       => 'en',
            'login_max_attempts'     => '5',
            'login_lockout_minutes'  => '15',
        ];
        foreach ($defaults as $k => $v) {
            $exists = $db->prepare('SELECT setting_key FROM settings WHERE setting_key = ?');
            $exists->execute([$k]);
            if (!$exists->fetch()) {
                $ins = $db->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)');
                $ins->execute([$k, $v, date('Y-m-d H:i:s')]);
            }
        }

        // Add user_language column to users (separate from theme_preference)
        try {
            $db->exec("ALTER TABLE users ADD COLUMN two_factor_enabled INTEGER NOT NULL DEFAULT 0");
        } catch (PDOException $e) {}
    }
}
