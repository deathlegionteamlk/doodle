# doodle v0.1-beta — open-source LMS with AI assistant

**The first public beta of doodle** — a complete, self-hostable learning management system with a built-in AI learning assistant. By **Death Legion Team**. GPL-3.0. PHP 8+.

![version](https://img.shields.io/badge/version-v0.1--beta-4F46E5) ![license](https://img.shields.io/badge/license-GPL--3.0-059669) ![php](https://img.shields.io/badge/PHP-8.0%2B-777BB4) ![ai](https://img.shields.io/badge/AI-built_in-EC4899)

> Moodle-like, but better — with AI built in, and entirely yours.

---

## 🎉 What is this?

doodle is a full-featured LMS that schools, companies, and individual educators can host themselves. It supports three user roles (admin, teacher, student), course authoring, AI-powered study tools, gamification, spaced-repetition flashcards, direct messaging, certificates, polls, learning paths, analytics, a REST API, dark mode, and PWA installability — all 100% free and open source.

**No Composer dependencies. No external API keys. No build step.** Just upload to any PHP 8+ server and run the installer.

---

## ✨ Highlights

### 🤖 Built-in AI Learning Assistant
A pure-PHP RAG engine — no external API, no API keys, runs entirely on your server:
- **Chat Q&A** — answers questions by retrieving relevant sentences from your course materials (TF-IDF retrieval)
- **Summarize** — "summarize [lesson title]" or "summarize this course"
- **Generate flashcards** — "make flashcards from [lesson title]" — auto-saved to your decks
- **Generate practice quizzes** — "give me a practice quiz" — multiple choice with explanations
- **Study recommendations** — "what should I study?" — personalized based on your grades, progress, streaks, due flashcards, and deadlines
- **Smart short-answer grading** — fuzzy match with token overlap + Levenshtein distance

### 📚 Full LMS
- **Three-role system** with role-based access control
- **Course catalog** with search, category, and level filters
- **Modular course authoring**: modules → lessons (text/HTML, file upload, video, external URL)
- **Quizzes**: multiple choice, true/false, short answer; auto-grading; attempt limits; time limits; shuffle
- **Assignments**: due dates, max scores, file + text submissions, teacher grading with feedback
- **Per-course discussion forum**: topics, replies, pin, lock, view counters
- **Gradebook**: per-course matrix for teachers, per-course breakdown for students
- **Announcements & notifications**

### 🏆 Gamification
- XP system, 10 levels, 12 auto-awarded badges, daily streaks 🔥, leaderboard

### 🎴 Spaced-Repetition Flashcards
- SM-2 algorithm with flip-card study UI
- One-click AI auto-generation from any course's lessons
- Due-today counter in the sidebar

### 💬 Direct Messaging
- 1-to-1 and group threads between any users
- Unread badges in topbar

### 📅 Calendar
- Monthly grid view, auto-aggregates assignment deadlines + custom events
- **iCal export** for Google Calendar / Outlook / Apple Calendar

### 🏅 Certificates
- Teacher-configurable templates per course (min score required)
- **Auto-issued** on course completion
- Beautiful printable certificate page
- **Public verification** via unique code

### 📊 Polls, Learning Paths, Analytics
- Per-course live polls (anonymous option, multiple-choice)
- Sequenced learning paths with auto-enrollment
- Analytics dashboard: 30-day activity charts, completion rates, top courses

### 📱 PWA + REST API + Dark Mode
- **Installable PWA** — users tap "Add to Home Screen" and doodle works like a native app, with offline support
- **REST API** with 9 token-authenticated endpoints for mobile apps
- **Dark mode** with one-click toggle, persisted per user, auto-detects OS preference

---

## 📦 Install in 90 seconds

### Requirements
- PHP 8.0+ (8.2+ recommended) with `pdo_sqlite` (default) or `pdo_mysql`, `mbstring`, `fileinfo`
- Apache (with `mod_rewrite`), Nginx, or PHP's built-in server
- Write permissions on `database/`, `public/uploads/`, project root

### Quick local test
```bash
unzip doodle-v0.1-beta.zip
cd doodle
php -S 127.0.0.1:8080
```
Open http://127.0.0.1:8080/install.php and follow the one-page wizard.

### Production (Apache)
```bash
sudo cp -r doodle /var/www/doodle
sudo chown -R www-data:www-data /var/www/doodle
sudo chmod -R 775 /var/www/doodle/database /var/www/doodle/public/uploads
sudo a2enmod rewrite && sudo systemctl restart apache2
```
Visit `https://your-domain.tld/install.php`.

### Switch to MySQL
Set environment variables and re-run installer:
```bash
export DOODLE_DB_TYPE=mysql
export DOODLE_DB_HOST=127.0.0.1
export DOODLE_DB_NAME=doodle
export DOODLE_DB_USER=doodle
export DOODLE_DB_PASS=your_password
```

Full installation guide in [README.md](./README.md).

---

## 📊 By the numbers

| Metric | Count |
|---|---|
| PHP files | 100+ |
| Lines of PHP | ~12,600 |
| Controllers | 21 |
| View templates | 64 |
| Database tables | 45 |
| Lines of CSS (with dark mode) | 1,049 |
| External dependencies | **0** |
| API keys required | **0** |
| Cost | **Free forever** |

---

## 🔒 Security

- Bcrypt password hashing (cost 10)
- CSRF tokens verified on every form
- PDO prepared statements throughout (no SQL injection)
- Role-based access control + ownership checks
- Session cookies HttpOnly, SameSite=Lax, Secure on HTTPS
- File uploads validated by extension AND MIME type
- `.htaccess` blocks direct access to sensitive files
- API tokens are 64-char random strings
- Installer is one-shot

---

## ⚠️ Known limitations (beta)

- No live whiteboard yet
- No browser-based code execution (schema exists, runner not implemented)
- No plagiarism detection
- No LDAP/OAuth/SAML authentication (planned for v0.2)
- UI is English-only (gettext planned for v0.2)
- No native mobile apps yet (but the REST API is ready for them)

---

## 🗺️ Roadmap

- v0.2-beta — LDAP/OAuth auth, gettext i18n, code runner, live whiteboard
- v1.0 — first stable release, mobile apps (using REST API)

---

## 🤝 Contributing

Pull requests welcome! This is open source under GPL-3.0.

- **Source**: https://github.com/DeathLegionTeam/doodle
- **Issues**: https://github.com/DeathLegionTeam/doodle/issues
- **License**: GPL-3.0 — see [LICENSE](./LICENSE)
- **Built by**: Death Legion Team

---

## 📥 Download

**Files attached to this release:**
- `doodle-v0.1-beta.zip` — the complete project (just unzip and run `php -S 127.0.0.1:8080`)
- `doodle-v0.1-beta.zip.sha256` — SHA-256 checksum
- `doodle-v0.1-beta.zip.sha512` — SHA-512 checksum

Verify integrity after download:
```bash
sha256sum -c doodle-v0.1-beta.zip.sha256
```

---

> *"Moodle-like, but better — with AI built in, and entirely yours."*
>
> — Death Legion Team
