# doodle

**The most feature-complete open-source LMS — Moodle-like, but better, with a built-in AI learning assistant.**
Built by **Death Legion Team** · GPL-3.0 · PHP 8+

![doodle](https://img.shields.io/badge/version-2.0.0-4F46E5) ![license](https://img.shields.io/badge/license-GPL--3.0-059669) ![php](https://img.shields.io/badge/PHP-8.0%2B-777BB4) ![ai](https://img.shields.io/badge/AI-built_in-EC4899)

doodle is a complete, self-hostable learning management system for schools,
companies, and individual educators. It supports three user roles, course
authoring, AI-powered study tools, gamification, spaced-repetition flashcards,
direct messaging, certificates, polls, learning paths, analytics, a REST API,
and much more — all 100% free and open source.

> "Moodle-like, but better — with AI built in, and entirely yours."

---

## What's new in v2.0

The original v1.0 was already a full LMS. v2.0 adds **11 major feature systems**:

| Feature | What it does |
|---------|-------------|
| **AI Learning Assistant** | Built-in chatbot that answers questions from your course materials, summarizes lessons, generates quizzes & flashcards, recommends what to study next — all with a pure-PHP RAG engine, no external API keys required |
| **Gamification** | XP, levels, badges (12 default badges with auto-award rules), daily streaks, leaderboard, recent-activity feed |
| **Spaced-Repetition Flashcards** | SM-2 algorithm flashcards with one-click AI auto-generation from any course; due-today counter in the sidebar |
| **Direct Messaging** | 1-to-1 and group threads between students and teachers; unread badges in topbar |
| **Calendar** | Auto-aggregated assignment deadlines + custom events; iCal export for Google Calendar / Outlook |
| **Certificates** | Auto-issued on course completion with passing grade; verifiable via unique codes; beautiful printable certificate page |
| **Polls & Surveys** | Per-course live polls with anonymous option, multiple-choice support, real-time result bars |
| **Learning Paths** | Sequences of courses with ordered progression; auto-enrollment in next course on completion |
| **Analytics Dashboard** | 30-day activity charts, completion rates, top courses, submission stats — for admins and teachers |
| **REST API** | Token-authenticated JSON API for mobile apps; 9 endpoints covering courses, lessons, flashcards, notifications, leaderboard |
| **Dark Mode** | One-click theme toggle, persisted per user; auto-detects OS preference |

---

## Full feature list

### For administrators
- Full dashboard with platform-wide statistics + 30-day activity chart
- Manage every user (create, edit, suspend, delete, change roles)
- View & moderate all courses across the platform
- Organize the catalog with nested categories
- Broadcast announcements to all users, specific roles, or specific courses
- Configure global settings (registration, branding, contact email, theme color)
- Audit activity log of recent actions
- Analytics dashboard with engagement metrics and completion rates
- Manage API tokens (your own + view all issued)

### For teachers
- Teacher studio dashboard with pending-submission queue
- Create unlimited courses with cover images, levels, languages, visibility, and enrollment keys
- Build modular curricula: **Modules → Lessons**
- Lesson types: text/HTML, uploaded file (PDF, DOCX, MP4, ...), external URL (YouTube/Vimeo embed)
- Free-preview flag on individual lessons
- Create assignments with due dates and max scores
- Receive student submissions (text + file attachment)
- Grade submissions with feedback; students get auto-notified
- Build quizzes with three question types: multiple choice, true/false, short answer
- Auto-grading, attempt limits, time limits, passing score, shuffle, show-answers-after
- Full gradebook view per course
- View enrolled students with per-student progress
- Configure certificate templates (auto-issued on completion)
- Create polls & surveys for quick student feedback
- Build learning paths from your courses
- View per-course analytics

### For students
- Browse the public course catalog with search, category, and level filters
- Self-enroll (with optional enrollment key)
- Learning view with sticky curriculum sidebar and progress tracking
- Auto-completion of text lessons; manual mark-complete on video lessons
- Take quizzes with attempt tracking and instant scoring
- Submit assignments with written response + file attachment
- Per-course grade view with assignment and quiz breakdowns
- Participate in per-course discussion forums (topics + replies, pin, lock)
- Receive in-app notifications for grades, announcements, and forum replies
- Use the AI assistant to ask questions, get summaries, generate quizzes/flashcards
- Study flashcards with spaced-repetition (SM-2 algorithm)
- Earn XP, level up, unlock badges, climb the leaderboard
- Track daily streaks
- View calendar with all deadlines + add custom events
- Receive certificates on course completion (verifiable via unique code)
- Vote in course polls
- Enroll in structured learning paths
- Send direct messages to teachers and classmates
- Toggle dark mode

### AI Learning Assistant (built-in, no external API needed)
The AI engine is pure PHP — no Composer packages, no API keys, no external
dependencies. It runs entirely on your server.

Capabilities:
- **Chat Q&A** — answers questions by retrieving relevant sentences from your course materials (TF-IDF retrieval)
- **Summarize** — "summarize [lesson title]" or "summarize this course" (extractive summarization)
- **Generate flashcards** — "make flashcards from [lesson title]" (auto-saves to your decks)
- **Generate practice quizzes** — "give me a practice quiz" (multiple-choice with explanations)
- **Study recommendations** — "what should I study?" (based on your grades, progress, streaks, due flashcards, and upcoming deadlines)
- **Smart short-answer grading** — fuzzy matching with token overlap + Levenshtein distance
- **Per-course context** — chat is scoped to a specific course so answers come from the right materials

### Gamification
- **XP system** — earn XP for every action (lesson complete, quiz pass, forum post, etc.)
- **Levels** — 10 levels with increasing XP thresholds
- **Badges** — 12 default badges with auto-award rules (First Steps, Quiz Master, Perfect Score, Streak 7/30, etc.)
- **Daily streaks** — keep your streak alive by studying every day
- **Leaderboard** — top 20 users platform-wide or per course

### REST API
Token-authenticated JSON API for mobile apps and integrations:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `api/auth` | Exchange username/password for a token |
| GET | `api/me` | Current user info |
| GET | `api/courses` | List published courses |
| GET | `api/courses/:id` | Course detail |
| GET | `api/my/courses` | Student's enrolled courses |
| GET | `api/my/notifications` | Recent notifications |
| POST | `api/lessons/:id/complete` | Mark lesson complete |
| GET | `api/flashcards/due` | Due flashcards for review |
| GET | `api/leaderboard` | Top 20 users by XP |

---

## Server requirements

- **PHP 8.0 or newer** (8.2+ recommended)
- Required PHP extensions: `pdo`, `pdo_sqlite` (default) or `pdo_mysql`, `mbstring`, `json`, `fileinfo`
- Web server: Apache (with `mod_rewrite`), Nginx, or PHP's built-in server for local testing
- Write permissions on `database/`, `public/uploads/`, and project root

---

## Installation

### Quick local test
```bash
cd doodle
php -S 127.0.0.1:8080
```
Open `http://127.0.0.1:8080/install.php` and follow the wizard.

### Production (Apache)
1. Copy `doodle/` to your web root.
2. Set permissions:
   ```bash
   chmod -R 775 doodle/database doodle/public/uploads
   chown -R www-data:www-data doodle/
   ```
3. Enable `mod_rewrite`: `a2enmod rewrite && systemctl restart apache2`
4. Visit `https://your-domain.tld/install.php` and complete the one-page installer.
5. **Delete `install.php`** after installation (optional).

### Nginx
See the [Nginx configuration](#option-c--nginx) section below.

### Switching to MySQL
The default is SQLite (zero-config). To use MySQL:

1. Create an empty database and user:
   ```sql
   CREATE DATABASE doodle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'doodle'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT ALL ON doodle.* TO 'doodle'@'localhost';
   ```
2. Set environment variables:
   ```
   DOODLE_DB_TYPE=mysql
   DOODLE_DB_HOST=127.0.0.1
   DOODLE_DB_NAME=doodle
   DOODLE_DB_USER=doodle
   DOODLE_DB_PASS=strong_password
   ```
3. Visit `install.php` — the schema will be created in MySQL.

### Upgrading from v1.0
Existing installs auto-upgrade: just replace the codebase and visit any page.
doodle detects the old version, runs the v2 schema migration, and writes a
`.v2_migrated` flag. All your existing data is preserved.

---

## Configuration

All configuration lives in `config/config.php`. Key constants:

| Constant | Default | Purpose |
|----------|---------|---------|
| `APP_NAME` | `doodle` | Brand name shown in UI |
| `APP_TEAM` | `Death Legion Team` | Attribution |
| `ENVIRONMENT` | `development` | `development` shows errors; `production` hides them |
| `DB_TYPE` | `sqlite` | `sqlite` or `mysql` |
| `MAX_UPLOAD_SIZE` | 100 MB | Per-file upload limit |
| `PER_PAGE` | 12 | Pagination page size |
| `PASSWORD_MIN_LENGTH` | 8 | Minimum password length |
| `HASH_COST` | 10 | bcrypt cost factor |

---

## User roles

| Role | Can do |
|------|--------|
| **admin** | Everything. Manage users, courses, categories, settings, announcements, view analytics. |
| **teacher** | Create and manage their own courses, modules, lessons, assignments, quizzes, certificate templates, polls, learning paths. Grade submissions. View analytics. |
| **student** | Browse catalog, enroll in courses & learning paths, view lessons, take quizzes, submit assignments, post in forums, use AI assistant, study flashcards, earn XP/badges, message others, receive certificates, vote in polls. |

---

## Customization

### Theming
All styles live in `public/css/style.css`. The `:root` block defines colors via
CSS custom properties. Dark mode overrides live in `html[data-theme="dark"]`.
Change `--primary` and `--accent` to re-brand instantly.

### Adding a new lesson content type
1. Add a new option to the `<select name="content_type">` in `app/views/teacher/course_edit.php`.
2. Handle storage in `TeacherController::addLesson()`.
3. Render in `app/views/course/learn.php`.

### Adding a new question type
1. Add the type to the question form in `app/views/teacher/quiz_edit.php`.
2. Handle grading in `QuizController::submit()`.
3. Render in `app/views/quiz/take.php` and `app/views/quiz/result.php`.

### Plugging in an external LLM
The `AIEngine` class is self-contained, but you can replace any method (e.g.
`AIEngine::chat()`) with a call to OpenAI, Anthropic, or any OpenAI-compatible
endpoint. Look for the `// Default: question answering via retrieval` line.

### Localization
The UI is in English. To translate, replace English strings in the views (the
codebase is small enough for direct edits).

---

## Security

- **Passwords** hashed with bcrypt (cost 10)
- **CSRF tokens** verified on every POST/PUT/DELETE
- **PDO prepared statements** everywhere — no SQL injection
- **Role-based access control** at the controller level
- **Ownership checks** — teachers can only edit their own courses; students only see enrolled content
- **Session cookies** HttpOnly, SameSite=Lax, Secure (on HTTPS)
- **File uploads** validated by extension AND MIME type, renamed to safe random names
- **`.htaccess`** blocks direct access to sensitive files
- **API tokens** are 64-char random strings, stored hashed in DB
- **Installer is one-shot** — once `.installed` exists, it refuses to run

For production:
- Serve over HTTPS
- Set `ENVIRONMENT=production`
- Back up `database/` daily
- Consider `fail2ban` for login rate-limiting

---

## Nginx configuration

<details>
<summary>Nginx config (click to expand)</summary>

```nginx
server {
    listen 80;
    server_name your-domain.tld;
    root /var/www/doodle;
    index index.php;

    location ~ \.(sqlite|db|md|log|installed|htaccess)$ { deny all; }
    location /database/ { deny all; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location / {
        try_files $uri $uri/ /index.php?r=$uri&$args;
    }

    client_max_body_size 100M;
}
```

</details>

---

## Project structure

```
doodle/
├── config/             Configuration (config.php, database.php)
├── database/           SQLite file + schema.php + schema_v2.php
├── app/
│   ├── core/           Framework: Router, Controller, Auth, Session, CSRF, AIEngine, Gamification
│   ├── controllers/    15 controllers (Home, Auth, Admin, Teacher, Student, Course, Quiz,
│   │                   Assignment, Forum, Catalog, AI, Gamification, Flashcard, Message,
│   │                   Calendar, Certificate, Poll, LearningPath, Analytics, Api, Notifications)
│   ├── helpers/        Helper functions
│   └── views/          80+ view templates organized by feature
├── public/
│   ├── css/style.css   1000+ lines of hand-tuned CSS, dark mode included
│   └── uploads/        User-uploaded files (covers, lessons, avatars, submissions)
├── index.php           Front controller
├── install.php         One-page installer
├── .htaccess           Apache rewrite rules + security headers
├── README.md           This file
└── LICENSE             GPL-3.0
```

---

## Roadmap

- [ ] Live whiteboard (collaborative)
- [ ] Code playground with browser-based execution
- [ ] Peer review assignments
- [ ] Plagiarism detection
- [ ] LDAP / OAuth / SAML authentication
- [ ] Multi-language UI with gettext
- [ ] Mobile apps (using the REST API)
- [ ] WCAG 2.1 AA accessibility audit

Pull requests welcome — this is open source under GPL-3.0.

---

## License & credits

**doodle** is © Death Legion Team, released under the **GPL-3.0** license.

Built with:
- **PHP 8** + PDO (no framework, no Composer required)
- **SQLite** (default) or **MySQL** (production)
- Vanilla CSS with CSS custom properties (no build step)
- Google Fonts: Inter + Sora
- Material Icons

> *"Moodle-like, but better — with AI built in, and entirely yours."*
