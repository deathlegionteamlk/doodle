# Changelog

All notable changes to **doodle** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [v0.1-beta] — 2026-06-20

The first public beta of doodle, the open-source LMS by Death Legion Team.
Moodle-like, but better — with a built-in AI learning assistant, gamification,
spaced-repetition flashcards, and much more. 100% free, GPL-3.0, PHP 8+.

### Added — Core LMS
- **Three-role system**: admin, teacher, student — with role-based access control enforced at the controller level
- **Course catalog** with search, category filter, and level filter
- **Course authoring**: modules → lessons (text/HTML, file upload, video, external URL), free-preview flag, enrollment keys
- **Quizzes**: multiple choice, true/false, short answer; auto-grading; attempt limits; time limits; shuffle; show-answers-after
- **Assignments**: due dates, max scores, file + text submissions, teacher grading with feedback
- **Per-course discussion forum**: topics, replies, pin, lock, view counters
- **Gradebook**: per-course matrix view for teachers, per-course breakdown for students
- **Announcements**: broadcast to all users, specific roles, or specific courses
- **Notifications**: in-app notifications for grades, replies, enrollments, announcements
- **One-page installer** at `install.php` with admin account creation
- **SQLite (default, zero-config) or MySQL** (production) — portable schema
- **Auto-detects base URL**, supports Apache mod_rewrite and Nginx

### Added — AI Learning Assistant (pure PHP, no external API)
- **Chat Q&A**: retrieves answers from your course materials using TF-IDF
- **Summarize**: "summarize [lesson]" or "summarize this course"
- **Generate flashcards**: "make flashcards from [lesson]" — auto-saves to your decks
- **Generate practice quizzes**: "give me a practice quiz" — multiple choice with explanations
- **Study recommendations**: "what should I study?" — personalized based on grades, progress, streaks, due cards, deadlines
- **Smart short-answer grading**: fuzzy match with token overlap + Levenshtein distance
- **Per-course context**: chat scoped to a specific course

### Added — Gamification
- **XP system**: 14 action types award points (lesson complete, quiz pass, forum post, etc.)
- **Levels**: 10 levels with increasing XP thresholds
- **12 default badges** with auto-award rules: First Steps, Quiz Master, Perfect Score, Streak 7/30, Night Owl, Early Bird, etc.
- **Daily streaks** with longest-streak tracking
- **Leaderboard**: top 20 users platform-wide or per course

### Added — Spaced-Repetition Flashcards
- **SM-2 algorithm**: ease factor, interval, repetitions, next-review scheduling
- **One-click AI auto-generation** from any course's lessons
- **Flip-card study UI** with 4-rating review (Again / Hard / Good / Easy)
- **Due-today counter** in the sidebar

### Added — Direct Messaging
- 1-to-1 and group threads between any users
- Unread badge in topbar
- Real-time-feel chat UI with auto-scroll and auto-grow textarea

### Added — Calendar
- Monthly grid view with prev/next navigation
- Auto-aggregates assignment deadlines + custom events
- **iCal export** for Google Calendar / Outlook / Apple Calendar
- Custom event creation (reminder, deadline, live session, exam, meeting)

### Added — Certificates
- Teacher-configurable certificate templates per course (min score required)
- **Auto-issued** on course completion with passing grade
- Beautiful printable certificate page with verification code
- **Public verification page** at `/certificates/verify?code=XXX`

### Added — Polls & Surveys
- Per-course live polls
- Anonymous option, multiple-choice support
- Real-time result bars with percentages

### Added — Learning Paths
- Sequences of courses with ordered progression
- Auto-enrollment in next course on completion
- Progress tracking per path

### Added — Analytics Dashboard
- 30-day activity bar charts
- Course completion rates
- Top courses by enrollment
- Submission grading stats (pending / graded / average score)
- Per-student drill-down view for teachers

### Added — REST API
- Token-authenticated JSON API for mobile apps and integrations
- 9 endpoints: `auth`, `me`, `courses`, `course/:id`, `my/courses`, `my/notifications`, `lessons/:id/complete`, `flashcards/due`, `leaderboard`
- User-managed API tokens with revocation

### Added — UX & Polish
- **Dark mode** with one-click toggle, persisted per user, auto-detects OS preference
- Responsive design (sidebar collapses on mobile)
- Material Icons + Inter/Sora fonts
- XP chip in topbar showing current XP and streak
- Auto-dismissing flash messages
- CSRF protection on every form
- Bcrypt password hashing (cost 10)
- PDO prepared statements throughout (no SQL injection)

### Security
- CSRF tokens verified on every POST/PUT/DELETE
- Bcrypt password hashing
- Role-based access control at controller level
- Ownership checks (teachers can only edit their own courses; students only see enrolled content)
- Session cookies HttpOnly, SameSite=Lax, Secure on HTTPS
- File uploads validated by extension AND MIME type, renamed to safe random names
- `.htaccess` blocks direct access to `.sqlite`, `.db`, `.md`, `.log`, `.installed` files
- API tokens are 64-char random strings
- Installer is one-shot — once `.installed` exists, it refuses to run

### Documentation
- Comprehensive `README.md` with installation, configuration, customization, and security sections
- Apache and Nginx configuration examples
- MySQL migration instructions
- API documentation with cURL examples
- `CHANGELOG.md` (this file)
- `LICENSE` (GPL-3.0)

### Known Limitations (beta)
- No live whiteboard yet
- No browser-based code execution (code exercises schema exists, runner not implemented)
- No plagiarism detection
- No LDAP/OAuth/SAML authentication (planned for v0.2)
- UI is English-only (gettext planned for v0.2)
- No mobile apps yet (but the REST API is ready for them)

### Stats
- 100 PHP files, ~12,600 lines of PHP
- 21 controllers, 64 view templates
- 45 database tables (v1: 19 + v2: 26)
- 1,049 lines of CSS (with dark mode)
- Zero Composer dependencies, zero external API keys required

---

## Versioning

doodle uses semantic versioning with a beta suffix during initial development:
- `v0.1-beta` — first public beta (this release)
- `v0.2-beta` — upcoming: LDAP, gettext i18n, code runner
- `v1.0` — first stable release

---

## Links

- **Source**: https://github.com/DeathLegionTeam/doodle
- **License**: GPL-3.0
- **Built by**: Death Legion Team
