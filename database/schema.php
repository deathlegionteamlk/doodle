<?php
/**
 * doodle - Database schema installer
 *
 * Compatible with SQLite and MySQL (uses common subset of SQL).
 *
 * @package doodle
 * @author  Death Legion Team
 */

class Schema
{
    public static function install(PDO $db, string $dbType): void
    {
        $isSqlite = ($dbType === 'sqlite');
        $ai = $isSqlite ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $textType = $isSqlite ? 'TEXT' : 'LONGTEXT';

        // ----------------------------------------------------------------------
        // USERS
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY $ai,
            username      VARCHAR(50) NOT NULL UNIQUE,
            email         VARCHAR(255) NOT NULL UNIQUE,
            password      VARCHAR(255) NOT NULL,
            full_name     VARCHAR(150) NOT NULL,
            role          VARCHAR(20) NOT NULL DEFAULT 'student',
            status        VARCHAR(20) NOT NULL DEFAULT 'active',
            bio           TEXT,
            avatar        VARCHAR(255),
            phone         VARCHAR(50),
            last_login    DATETIME,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME
        )");

        // ----------------------------------------------------------------------
        // CATEGORIES (course catalog)
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS categories (
            id          INTEGER PRIMARY KEY $ai,
            name        VARCHAR(100) NOT NULL,
            slug        VARCHAR(120) NOT NULL UNIQUE,
            description TEXT,
            parent_id   INTEGER,
            sort_order  INTEGER NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
        )");

        // ----------------------------------------------------------------------
        // COURSES
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS courses (
            id            INTEGER PRIMARY KEY $ai,
            teacher_id    INTEGER NOT NULL,
            category_id   INTEGER,
            title         VARCHAR(255) NOT NULL,
            slug          VARCHAR(280) NOT NULL,
            description   TEXT,
            cover_image   VARCHAR(255),
            status        VARCHAR(20) NOT NULL DEFAULT 'draft',
            visibility    VARCHAR(20) NOT NULL DEFAULT 'public',
            language      VARCHAR(10) DEFAULT 'en',
            level         VARCHAR(20) DEFAULT 'beginner',
            price         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            enroll_key    VARCHAR(50),
            start_date    DATE,
            end_date      DATE,
            enroll_count  INTEGER NOT NULL DEFAULT 0,
            rating        DECIMAL(3,2) NOT NULL DEFAULT 0.00,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )");

        // ----------------------------------------------------------------------
        // ENROLLMENTS
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS enrollments (
            id          INTEGER PRIMARY KEY $ai,
            course_id   INTEGER NOT NULL,
            student_id  INTEGER NOT NULL,
            status      VARCHAR(20) NOT NULL DEFAULT 'active',
            progress    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME,
            FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id)   ON DELETE CASCADE,
            UNIQUE (course_id, student_id)
        )");

        // ----------------------------------------------------------------------
        // MODULES (sections within a course)
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS modules (
            id          INTEGER PRIMARY KEY $ai,
            course_id   INTEGER NOT NULL,
            title       VARCHAR(255) NOT NULL,
            description TEXT,
            sort_order  INTEGER NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )");

        // ----------------------------------------------------------------------
        // LESSONS (content items within a module)
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS lessons (
            id           INTEGER PRIMARY KEY $ai,
            module_id    INTEGER NOT NULL,
            title        VARCHAR(255) NOT NULL,
            content      $textType,
            content_type VARCHAR(20) NOT NULL DEFAULT 'text',
            file_path    VARCHAR(255),
            file_name    VARCHAR(255),
            file_size    INTEGER,
            external_url VARCHAR(500),
            duration     INTEGER,
            sort_order   INTEGER NOT NULL DEFAULT 0,
            is_preview   INTEGER NOT NULL DEFAULT 0,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
        )");

        // ----------------------------------------------------------------------
        // LESSON COMPLETIONS (track student progress)
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS lesson_completions (
            id          INTEGER PRIMARY KEY $ai,
            lesson_id   INTEGER NOT NULL,
            student_id  INTEGER NOT NULL,
            completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (lesson_id, student_id),
            FOREIGN KEY (lesson_id)  REFERENCES lessons(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id)   ON DELETE CASCADE
        )");

        // ----------------------------------------------------------------------
        // ASSIGNMENTS
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS assignments (
            id           INTEGER PRIMARY KEY $ai,
            course_id    INTEGER NOT NULL,
            module_id    INTEGER,
            title        VARCHAR(255) NOT NULL,
            description  $textType,
            max_score    DECIMAL(6,2) NOT NULL DEFAULT 100.00,
            due_date     DATETIME,
            allow_upload INTEGER NOT NULL DEFAULT 1,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL
        )");

        // ----------------------------------------------------------------------
        // ASSIGNMENT SUBMISSIONS
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS submissions (
            id            INTEGER PRIMARY KEY $ai,
            assignment_id INTEGER NOT NULL,
            student_id    INTEGER NOT NULL,
            content       $textType,
            file_path     VARCHAR(255),
            file_name     VARCHAR(255),
            submitted_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            score         DECIMAL(6,2),
            feedback      $textType,
            status        VARCHAR(20) NOT NULL DEFAULT 'submitted',
            graded_at     DATETIME,
            graded_by     INTEGER,
            UNIQUE (assignment_id, student_id),
            FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id)    REFERENCES users(id)      ON DELETE CASCADE
        )");

        // ----------------------------------------------------------------------
        // QUIZZES
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS quizzes (
            id            INTEGER PRIMARY KEY $ai,
            course_id     INTEGER NOT NULL,
            module_id     INTEGER,
            title         VARCHAR(255) NOT NULL,
            description   TEXT,
            instructions  $textType,
            time_limit    INTEGER,
            max_attempts  INTEGER NOT NULL DEFAULT 1,
            passing_score DECIMAL(5,2) NOT NULL DEFAULT 60.00,
            shuffle       INTEGER NOT NULL DEFAULT 0,
            show_answers  INTEGER NOT NULL DEFAULT 0,
            available_from DATETIME,
            available_to  DATETIME,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL
        )");

        // ----------------------------------------------------------------------
        // QUIZ QUESTIONS
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS questions (
            id          INTEGER PRIMARY KEY $ai,
            quiz_id     INTEGER NOT NULL,
            type        VARCHAR(20) NOT NULL DEFAULT 'multiple_choice',
            question    $textType NOT NULL,
            options     $textType,
            correct_answer VARCHAR(50),
            explanation $textType,
            points      DECIMAL(6,2) NOT NULL DEFAULT 1.00,
            sort_order  INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
        )");

        // ----------------------------------------------------------------------
        // QUIZ ATTEMPTS
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS attempts (
            id          INTEGER PRIMARY KEY $ai,
            quiz_id     INTEGER NOT NULL,
            student_id  INTEGER NOT NULL,
            score       DECIMAL(6,2),
            max_score   DECIMAL(6,2),
            percentage  DECIMAL(5,2),
            passed      INTEGER,
            answers     $textType,
            started_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            submitted_at DATETIME,
            FOREIGN KEY (quiz_id)    REFERENCES quizzes(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id)   ON DELETE CASCADE
        )");

        // ----------------------------------------------------------------------
        // GRADEBOOK
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS grades (
            id           INTEGER PRIMARY KEY $ai,
            course_id    INTEGER NOT NULL,
            student_id   INTEGER NOT NULL,
            item_type    VARCHAR(30) NOT NULL,
            item_id      INTEGER,
            item_name    VARCHAR(255),
            score        DECIMAL(6,2),
            max_score    DECIMAL(6,2),
            feedback     $textType,
            graded_by    INTEGER,
            graded_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id)   ON DELETE CASCADE
        )");

        // ----------------------------------------------------------------------
        // FORUM CATEGORIES (per course)
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS forum_topics (
            id          INTEGER PRIMARY KEY $ai,
            course_id   INTEGER NOT NULL,
            user_id     INTEGER NOT NULL,
            title       VARCHAR(255) NOT NULL,
            body        $textType,
            pinned      INTEGER NOT NULL DEFAULT 0,
            locked      INTEGER NOT NULL DEFAULT 0,
            views       INTEGER NOT NULL DEFAULT 0,
            reply_count INTEGER NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS forum_posts (
            id          INTEGER PRIMARY KEY $ai,
            topic_id    INTEGER NOT NULL,
            user_id     INTEGER NOT NULL,
            body        $textType NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME,
            FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)  REFERENCES users(id)       ON DELETE CASCADE
        )");

        // ----------------------------------------------------------------------
        // ANNOUNCEMENTS
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS announcements (
            id          INTEGER PRIMARY KEY $ai,
            course_id   INTEGER,
            user_id     INTEGER NOT NULL,
            title       VARCHAR(255) NOT NULL,
            body        $textType,
            audience    VARCHAR(20) NOT NULL DEFAULT 'all',
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
        )");

        // ----------------------------------------------------------------------
        // NOTIFICATIONS
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id          INTEGER PRIMARY KEY $ai,
            user_id     INTEGER NOT NULL,
            title       VARCHAR(255) NOT NULL,
            body        $textType,
            link        VARCHAR(500),
            is_read     INTEGER NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // ----------------------------------------------------------------------
        // SYSTEM SETTINGS (key/value)
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_key   VARCHAR(100) PRIMARY KEY,
            setting_value TEXT,
            updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        // ----------------------------------------------------------------------
        // ACTIVITY LOG
        // ----------------------------------------------------------------------
        $db->exec("CREATE TABLE IF NOT EXISTS activity_log (
            id          INTEGER PRIMARY KEY $ai,
            user_id     INTEGER,
            action      VARCHAR(255) NOT NULL,
            ip_address  VARCHAR(45),
            user_agent  VARCHAR(255),
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )");

        // ----------------------------------------------------------------------
        // Indexes (common syntax)
        // ----------------------------------------------------------------------
        $indexList = [
            'users'         => ['username', 'email', 'role'],
            'courses'       => ['teacher_id', 'category_id', 'status', 'slug'],
            'enrollments'   => ['course_id', 'student_id'],
            'modules'       => ['course_id'],
            'lessons'       => ['module_id'],
            'lesson_completions' => ['lesson_id', 'student_id'],
            'assignments'   => ['course_id', 'module_id'],
            'submissions'   => ['assignment_id', 'student_id'],
            'quizzes'       => ['course_id', 'module_id'],
            'questions'     => ['quiz_id'],
            'attempts'      => ['quiz_id', 'student_id'],
            'grades'        => ['course_id', 'student_id'],
            'forum_topics'  => ['course_id', 'user_id'],
            'forum_posts'   => ['topic_id', 'user_id'],
            'announcements' => ['course_id'],
            'notifications' => ['user_id', 'is_read'],
            'activity_log'  => ['user_id', 'created_at'],
        ];
        foreach ($indexList as $table => $cols) {
            foreach ($cols as $col) {
                $idxName = 'idx_' . $table . '_' . $col;
                try {
                    $db->exec("CREATE INDEX IF NOT EXISTS $idxName ON $table ($col)");
                } catch (PDOException $e) { /* ignore duplicate-index */ }
            }
        }
    }

    public static function seed(PDO $db): void
    {
        // Default settings
        $defaults = [
            'site_name'        => 'doodle',
            'site_tagline'     => 'Personalized learning, by Death Legion Team',
            'site_description' => 'An open-source learning management system.',
            'allow_registration' => '1',
            'default_role'     => 'student',
            'contact_email'    => 'admin@example.com',
            'footer_text'      => 'Powered by doodle · GPL-3.0 · Death Legion Team',
            'theme_primary'    => '#4F46E5',
        ];
        foreach ($defaults as $k => $v) {
            // Portable: check existence then insert or update (works on SQLite + MySQL)
            $check = $db->prepare('SELECT setting_key FROM settings WHERE setting_key = ?');
            $check->execute([$k]);
            if ($check->fetch()) {
                $upd = $db->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
                $upd->execute([$v, $k]);
            } else {
                $ins = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)');
                $ins->execute([$k, $v]);
            }
        }

        // Default categories
        $cats = [
            ['Technology', 'Computers, software, programming'],
            ['Business', 'Management, marketing, finance'],
            ['Science', 'Natural and formal sciences'],
            ['Arts', 'Design, music, humanities'],
            ['Health', 'Medicine, fitness, nutrition'],
            ['Languages', 'Foreign languages and linguistics'],
        ];
        $exists = $db->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        if ($exists == 0) {
            foreach ($cats as $i => $c) {
                $db->prepare('INSERT INTO categories (name, slug, description, sort_order) VALUES (?, ?, ?, ?)')
                   ->execute([$c[0], slugify($c[0]), $c[1], $i]);
            }
        }
    }
}
