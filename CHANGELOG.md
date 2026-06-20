# Changelog

All notable changes to **doodle** will be documented in this file.

---

## [v0.2-beta] — 2026-06-20

The second beta. Massive security hardening + 11 new feature systems + UI overhaul.

### Added — Security hardening
- **Login rate limiting** — configurable max attempts + lockout duration (default 5 attempts / 15 min)
- **Two-factor authentication (TOTP, RFC 6238)** — pure PHP, no dependencies. Google Authenticator / Authy / 1Password compatible. Includes 8 single-use backup codes per user.
- **Security headers middleware** — HSTS, X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, Content-Security-Policy
- **HTML sanitizer** — strips `<script>`, `<iframe>`, `<object>`, `<embed>`, `<style>`, event handlers, `javascript:` URIs from rich-text content
- **Password strength validator** — entropy-based scoring (0-100) with actionable feedback
- **Path traversal protection** — `basename()` + null-byte stripping + character whitelisting
- **File upload MIME verification** — `finfo` real-MIME check vs declared extension
- **Session rotation** on every successful login (anti-fixation)
- **reCAPTCHA v3 support** (configurable in admin settings)

### Added — Live Whiteboard (collaborative)
- Per-course whiteboards with pen, eraser, rectangle, circle, line tools
- 6-color palette + adjustable brush size
- Real-time stroke polling (2-second interval)
- Touch-friendly (works on tablets/phones)
- Touch + mouse support
- Stroke history per user

### Added — Code Playground
- Programming exercises with hidden + visible test cases
- **PHP and Python 3 execution** (sandboxed via `proc_open` with `disable_functions`)
- Auto-grading: pass/fail per test case, with diff shown for non-hidden tests
- Difficulty levels (easy / medium / hard) + points
- Per-student submission history with code viewer
- Teacher management UI for creating exercises + test cases

### Added — Course Reviews
- 5-star rating + optional text review
- Anonymous posting option
- Auto-recalculated course rating displayed on course page
- Per-user edit/delete of own reviews

### Added — Student Notes
- Private per-lesson notes (one note per lesson per user)
- Notes listing page with quick navigation back to lesson

### Added — Bookmarks
- Save favorite lessons for quick access
- Bookmark toggle on lesson page (AJAX)
- Bookmarks listing page

### Added — Global Search
- Single search bar in topbar searches across:
  - Courses (title + description)
  - Lessons (in enrolled/owned courses)
  - Forum topics
  - Users (admin only)
- Keyboard shortcut: `/` to focus search

### Added — Course Wiki
- Per-course collaborative wiki pages
- Full revision history per page
- HTML content with sanitizer
- Anyone enrolled can edit; teachers can delete

### Added — Study Groups
- Per-course student-formed groups
- Join / leave freely
- Group leader role for creator
- Member count display

### Added — OAuth Login (Google + GitHub)
- One-click sign-in/up via Google or GitHub
- Auto-link to existing accounts by email
- New accounts auto-created as students
- Fully configurable in admin settings (client ID + secret)

### Added — Web Push Notifications (subscriptions)
- Web Push API subscription management
- VAPID key configuration in admin settings
- Subscribe/unsubscribe AJAX endpoints
- Foundation for sending push notifications from server

### Added — Email Queue
- SMTP configuration in admin settings (host, port, user, pass, from)
- Email queue table for async sending
- Foundation for email notifications

### Added — Backup & Restore
- Admin-triggered SQLite database snapshots
- Downloadable backup files
- Backup history with size + creator
- One-click delete old backups

### Added — Maintenance Mode
- Admin toggle in settings
- Non-admin users see a branded maintenance page (HTTP 503)
- Admins can still log in and access everything
- Custom maintenance message

### Added — CSV User Import/Export
- Admin can import users from CSV (full_name, username, email, role, password)
- Auto-generates random passwords for users without one
- Skip-on-duplicate (no overwrite)
- Export all users as CSV

### Added — AI Course Recommendations
- Personalized course recommendations on student dashboard
- Based on enrolled categories + course ratings + enrollment count
- Falls back to top-rated courses for new users

### Added — UI/UX Polish
- **Toast notifications** — flash messages now appear as auto-dismissing toasts (bottom-right)
- **Drag-drop upload zones** (CSS-ready)
- **Star ratings** component for reviews
- **Loading spinners** (CSS-only)
- **Print-friendly views** — sidebar/topbar/actions hidden when printing
- **Accessibility** — `*:focus-visible` outlines, keyboard shortcuts (`/` for search, `Esc` to close modals), ARIA-friendly markup
- **Tabbed settings page** — General / Security / OAuth / Email / Push / Maintenance

### Changed
- Updated sidebar navigation with new sections (Search, Notes, Bookmarks, 2FA, Backups)
- Topbar search now searches globally (not just courses)
- `Auth::login()` now returns an array (`['ok' => bool, 'error' => string, 'requires_2fa' => bool]`) instead of bool
- Login flow handles 2FA challenge screen
- Admin settings page split into tabs
- Version bumped to 0.2.0-beta

### Security
- All previous v1/v2 security features retained
- New: rate limiting prevents brute-force login attacks
- New: 2FA protects accounts even if password is leaked
- New: CSP header prevents XSS via injected scripts
- New: HTML sanitizer prevents stored XSS in wiki/note/review content
- New: file upload MIME verification prevents malicious file uploads
- New: session rotation prevents session fixation

### Database
- v3 migration adds 17 new tables (login_attempts, user_2fa, oauth_accounts, course_reviews, student_notes, bookmarks, whiteboards, whiteboard_strokes, wiki_pages, wiki_revisions, course_groups, group_members, push_subscriptions, email_queue, search_index, backups, code_test_cases)
- Auto-migrates on first page load after upgrade (writes `.v3_migrated` flag)
- All existing data preserved

---

## [v0.1-beta] — 2026-06-20

The first public beta of doodle. See v0.1-beta release notes for full details.

### Highlights
- Full LMS: courses, modules, lessons, quizzes, assignments, gradebook, forum
- AI Learning Assistant (pure PHP, no external API needed)
- Gamification: XP, levels, badges, streaks, leaderboard
- Spaced-repetition flashcards (SM-2 algorithm)
- Direct messaging, calendar, certificates, polls, learning paths
- Analytics dashboard, REST API, dark mode, PWA support
- 100% free, GPL-3.0, PHP 8+, 0 dependencies

---

## Versioning

doodle uses semantic versioning with a beta suffix during initial development:
- `v0.1-beta` — first public beta
- `v0.2-beta` — security hardening + 11 new feature systems (this release)
- `v0.3-beta` — upcoming: live WebRTC sessions, peer review assignments, plagiarism detection
- `v1.0` — first stable release
