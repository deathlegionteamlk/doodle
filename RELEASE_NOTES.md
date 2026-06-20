# doodle v0.2-beta — Security hardening + 11 new features

The second beta of **doodle** — your open-source LMS with built-in AI. By **Death Legion Team**. GPL-3.0. PHP 8+.

![version](https://img.shields.io/badge/version-v0.2--beta-4F46E5) ![license](https://img.shields.io/badge/license-GPL--3.0-059669) ![php](https://img.shields.io/badge/PHP-8.0%2B-777BB4) ![ai](https://img.shields.io/badge/AI-built_in-EC4899) ![security](https://img.shields.io/badge/security-hardened-059669)

> Same Moodle-beating features, now with enterprise-grade security and 11 brand-new feature systems.

---

## 🎉 What's new in v0.2-beta

### 🔒 Security hardening (top priority)
- **Login rate limiting** — configurable brute-force protection (default: 5 attempts / 15-min lockout)
- **Two-factor authentication (TOTP, RFC 6238)** — pure PHP, no dependencies. Works with Google Authenticator, Authy, 1Password. Includes 8 backup codes per user.
- **Content-Security-Policy** + 6 other security headers
- **HTML sanitizer** — strips scripts, iframes, event handlers from rich-text content
- **Password strength validator** — entropy-based scoring
- **Path traversal protection** on all file operations
- **File upload MIME verification** via `finfo`
- **Session rotation** on every login (anti-fixation)
- **reCAPTCHA v3** support (optional)

### ✨ 11 new feature systems

| Feature | What it does |
|---|---|
| **Live Whiteboard** | Per-course collaborative canvas with pen/eraser/shapes, real-time stroke polling, touch support |
| **Code Playground** | Programming exercises with hidden test cases, **sandboxed PHP + Python execution**, auto-grading |
| **Course Reviews** | 5-star ratings + text reviews with anonymous option; auto-updates course rating |
| **Student Notes** | Private per-lesson notes; "My Notes" listing page |
| **Bookmarks** | Save favorite lessons for quick access |
| **Global Search** | Single search bar covers courses, lessons, forums, users |
| **Course Wiki** | Collaborative wiki pages per course with full revision history |
| **Study Groups** | Student-formed study groups per course |
| **OAuth Login** | Sign in / up via Google or GitHub (auto-links existing accounts) |
| **Web Push + Email** | Foundation for web push notifications + SMTP email queue |
| **Backups + Maintenance Mode + CSV Import** | Admin tools for SQLite backups, maintenance mode, bulk user import/export |

### 🎨 UI/UX polish
- **Toast notifications** — flash messages now appear as auto-dismissing toasts
- **Print-friendly views** — sidebar/topbar hidden when printing
- **Keyboard shortcuts** — `/` to focus search, `Esc` to close modals
- **Accessibility** — focus outlines, ARIA-friendly markup
- **Tabbed settings page** — General / Security / OAuth / Email / Push / Maintenance
- **Star rating component** for reviews
- **Loading spinners** (CSS-only)

---

## 📊 By the numbers

| Metric | v0.1-beta | v0.2-beta | Change |
|---|---|---|---|
| PHP files | 100 | 122 | +22 |
| Lines of PHP | ~12,600 | ~16,200 | +3,600 |
| Controllers | 21 | 28 | +7 |
| View templates | 64 | 84 | +20 |
| Database tables | 45 | 62 | +17 |
| Lines of CSS | 1,049 | 1,106 | +57 |
| External dependencies | 0 | 0 | — |
| API keys required | 0 | 0 | — |
| Cost | Free | Free | — |

---

## 📦 Install / Upgrade

### New install
```bash
unzip doodle-v0.2-beta.zip
cd doodle
php -S 127.0.0.1:8080
# open http://127.0.0.1:8080/install.php
```

### Upgrade from v0.1-beta
Just replace the codebase and visit any page. doodle auto-detects the old version, runs the v3 schema migration (adds 17 new tables), and writes a `.v3_migrated` flag. All your existing data is preserved.

```bash
cd doodle
git pull
# visit any page in your browser — migration runs automatically
```

---

## 🔐 Setting up 2FA (as a user)

1. Sign in to your doodle account
2. Click **Two-factor (2FA)** in the sidebar
3. Scan the QR code with Google Authenticator / Authy / 1Password
4. Enter the 6-digit code to confirm
5. Save your 8 backup codes in a safe place
6. Done — next login will require a 2FA code

---

## 🔧 Setting up OAuth (as admin)

1. Go to **Admin → Settings → OAuth tab**
2. For Google: create OAuth credentials at [Google Cloud Console](https://console.developers.google.com/apis/credentials). Add `https://your-domain.com/index.php?r=oauth/callback` as an authorized redirect URI.
3. For GitHub: create an OAuth app at [GitHub Developer Settings](https://github.com/settings/developers). Same callback URL.
4. Paste client ID + secret into doodle settings, save.
5. Users will now see "Continue with Google" / "Continue with GitHub" buttons on the login page.

---

## 🎨 Setting up the whiteboard

Whiteboards are per-course. From any course page (where you're enrolled or the teacher):
1. Click **Whiteboard** in the AI Tools sidebar
2. Click **New** to create a board
3. Draw with pen / eraser / shapes, pick colors, adjust brush size
4. Other users see your strokes within ~2 seconds (polling)

Touch-friendly — works on tablets and phones.

---

## 💻 Setting up code exercises (teachers)

1. From your course, click **Code exercises** in AI Tools sidebar
2. Click **Manage** → **New exercise**
3. Fill in: title, difficulty, language (PHP or Python), problem statement, starter code, solution code (private), and at least one test case
4. Mark test cases as **hidden** to prevent students from seeing them
5. Students solve the exercise, run tests, and see pass/fail per test case
6. Auto-graded — no manual work for you

Code execution is sandboxed: PHP runs with `disable_functions` blocking `exec`, `shell_exec`, `system`, file operations, etc. Python3 runs directly (use a dedicated server for production).

---

## 🛡️ Security checklist for production

- [ ] Set `ENVIRONMENT=production` in config
- [ ] Serve over HTTPS (HSTS header auto-sent on HTTPS)
- [ ] Configure SMTP in admin settings (for password resets / notifications)
- [ ] Enable 2FA on your admin account
- [ ] Set up regular database backups (Admin → Backups → Create)
- [ ] Configure login rate limiting in admin settings (defaults are sane)
- [ ] Optionally configure reCAPTCHA v3 for login/registration forms
- [ ] Optionally configure Google + GitHub OAuth for passwordless login
- [ ] Review file upload permissions — `public/uploads/` should be web-writable but not executable

---

## ⚠️ Known limitations (still beta)

- Web push notifications: subscription management works, but server-side sending requires the VAPID private key + web-push library (planned for v0.3)
- Email queue: SMTP sender not yet implemented (planned for v0.3)
- Live WebRTC video sessions: not yet (planned for v0.3)
- Peer review assignments: schema exists, UI not yet (planned for v0.3)
- Plagiarism detection: not yet (planned for v0.3)
- Code playground: PHP execution sandbox is basic — for production use a dedicated runner like [Isolator](https://github.com/openpolis/isolator) or [Judge0](https://judge0.com/)
- UI is still English-only (gettext planned for v0.3)

---

## 🗺️ Roadmap

- **v0.3-beta** — WebRTC live sessions, peer review UI, plagiarism detection, SMTP sender, web push sender, gettext i18n
- **v1.0** — first stable release, mobile apps (using REST API), WCAG 2.1 AA accessibility audit

---

## 📥 Download

**Files attached to this release:**
- `doodle-v0.2-beta.zip` — the complete project
- `doodle-v0.2-beta.zip.sha256` — SHA-256 checksum
- `doodle-v0.2-beta.zip.sha512` — SHA-512 checksum

Verify integrity after download:
```bash
sha256sum -c doodle-v0.2-beta.zip.sha256
```

---

## 🤝 Contributing

Pull requests welcome! Open source under GPL-3.0.

- **Source**: https://github.com/deathlegionteamlk/doodle
- **Issues**: https://github.com/deathlegionteamlk/doodle/issues
- **License**: GPL-3.0 — see [LICENSE](./LICENSE)
- **Built by**: Death Legion Team

---

> *"Moodle-like, but better — with AI built in, enterprise-grade security, and entirely yours."*
>
> — Death Legion Team
