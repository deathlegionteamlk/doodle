# 🚀 doodle — Quickstart Guide

This guide gets you from zero to published GitHub release in under 5 minutes.

---

## ⚠️ First: revoke the leaked token

If you previously pasted a GitHub token into any chat, **revoke it immediately**:

1. Go to https://github.com/settings/tokens
2. Find the leaked token (starts with `ghp_`)
3. Click **Delete**

A leaked token is a compromised credential. Revoke first, then proceed.

---

## 📋 What you'll need

- A GitHub account (free)
- A computer with `git`, `curl`, and `zip` installed (macOS and Linux have these by default; Windows users: use WSL or Git Bash)
- 5 minutes

---

## 🪜 Step-by-step

### 1. Generate a fresh GitHub token

1. Go to https://github.com/settings/tokens/new
2. **Note**: `doodle publish`
3. **Expiration**: 30 days
4. **Scopes**: tick ✅ `repo` (all sub-items)
5. Click **Generate token** at the bottom
6. **Copy the token** to your password manager — you won't see it again

> 💡 The token starts with `ghp_`. **Never paste it into a chat, email, or document.** Only paste it into terminal prompts where you see `Password:`.

### 2. Download and unzip doodle

If you have the zip file:
```bash
unzip doodle-v0.1-beta.zip
cd doodle
chmod +x publish.sh
```

### 3. Run the publish script

```bash
./publish.sh
```

It will prompt you for:
- Your fresh GitHub token (input hidden)
- Your GitHub username
- Your GitHub email (used for commit authorship — must match the email on your GitHub account)

Press Enter to confirm. The script will:

1. ✅ Verify your token works
2. ✅ Create the `doodle` repo on your GitHub account if it doesn't exist
3. ✅ Configure git with your name + email (so commits are authored as you)
4. ✅ Initialize git, commit all files, push to `main` branch
5. ✅ Create the `v0.1-beta` tag and push it
6. ✅ Build a release zip
7. ✅ Create a GitHub Release with the zip attached
8. ✅ Print the URLs to your repo and release

### 4. (Optional) Alternative: one-liner with env vars

If you prefer not to be prompted:

```bash
DOODLE_GH_TOKEN=ghp_your_new_token \
  GH_USER=your_username \
  GH_EMAIL=you@example.com \
  ./publish.sh
```

---

## ✅ Verify it worked

After the script finishes, you should see:

```
═══════════════════════════════════════════════════════════════
  ✓ doodle v0.1-beta is live on GitHub!
═══════════════════════════════════════════════════════════════

  Repo:    https://github.com/YOUR_USERNAME/doodle
  Release: https://github.com/YOUR_USERNAME/doodle/releases/tag/v0.1-beta
  Asset:   doodle-v0.1-beta.zip
```

Open those URLs in your browser to verify.

---

## 🔄 Future releases

Once the initial publish is done, future releases are even easier:

### Option A: Just push a new tag

```bash
# Make your changes, commit them
git add .
git commit -m "feat: add new feature"
git push

# Tag and push to trigger auto-release
git tag v0.2-beta
git push origin v0.2-beta
```

The included GitHub Actions workflow (`.github/workflows/release.yml`) will automatically:
- Lint all PHP files
- Verify the schema installs cleanly
- Build a release zip
- Generate SHA-256 and SHA-512 checksums
- Create a GitHub Release with the zip attached

### Option B: Re-run publish.sh with a new tag

```bash
RELEASE_TAG=v0.2-beta ./publish.sh
```

---

## 📱 Installing doodle on your phone (no APK needed!)

doodle is a **PWA** (Progressive Web App). Once you host it on a server:

1. Open your doodle URL in Chrome (Android) or Safari (iOS)
2. Sign in
3. **Android (Chrome)**: tap the three-dot menu → **Add to Home screen**
4. **iOS (Safari)**: tap the Share button → **Add to Home Screen**

doodle will install as a standalone app with its own icon, full-screen, and offline support for the app shell. No APK required.

---

## 🆘 Troubleshooting

### "Token verification failed"
- Make sure you copied the entire token (it's long)
- Make sure the token has `repo` scope
- Make sure the token hasn't expired

### "Repo already exists"
- The script handles this — it'll push to the existing repo
- If you want to start fresh, delete the repo first at `https://github.com/YOUR_USERNAME/doodle/settings` → scroll to bottom → **Delete this repository**

### "Permission denied (publickey)"
- This happens if git tries to use SSH instead of HTTPS
- The script uses HTTPS with the token embedded in the URL, so this shouldn't happen
- If it does, run: `git remote set-url origin https://github.com/YOUR_USERNAME/doodle.git`

### "fatal: refusing to merge unrelated histories"
- The repo on GitHub has commits that aren't in your local copy
- Run: `git push origin main --force-with-lease` (the script does this automatically)

### "Could not create release via API"
- The repo might be private (the script creates it as public)
- Your token might not have `repo` scope
- Check the error response printed by the script

### The release was created but the asset upload failed
- Upload manually at `https://github.com/YOUR_USERNAME/doodle/releases/edit/v0.1-beta`
- Drag the `doodle-v0.1-beta.zip` file into the "Attach binaries" area
- Click **Update release**

---

## 🔒 After publishing

- **Revoke the token** if you don't need it for future releases (https://github.com/settings/tokens)
- Or keep it for the GitHub Actions workflow (but you don't need a personal token for that — Actions uses `GITHUB_TOKEN` automatically)

---

## 📞 Need help?

- Open an issue: https://github.com/DeathLegionTeam/doodle/issues
- Read the full README: [README.md](./README.md)
- Full changelog: [CHANGELOG.md](./CHANGELOG.md)

---

**Built by Death Legion Team. GPL-3.0. 100% free.**
