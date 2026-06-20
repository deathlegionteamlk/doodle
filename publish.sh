#!/usr/bin/env bash
# ============================================================================
# doodle publisher — one-command release to GitHub
# ============================================================================
# Usage:
#   1. Revoke your old (leaked) token at https://github.com/settings/tokens
#   2. Generate a NEW token with `repo` scope at
#      https://github.com/settings/tokens/new
#   3. Run this script:
#
#         DOODLE_GH_TOKEN=ghp_your_new_token_here \
#           GH_USER=your_github_username \
#           GH_EMAIL=you@example.com \
#           ./publish.sh
#
#   Or just run ./publish.sh and it'll prompt you for everything.
#
# What it does:
#   - Configures git with your name + email (so commits are authored as you)
#   - Inits the repo, commits everything, pushes to main
#   - Creates a v0.1-beta tag and pushes it
#   - Calls the GitHub API to create a Release with the zip attached
# ============================================================================

set -euo pipefail

# Colors
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; BOLD='\033[1m'; NC='\033[0m'

log()  { echo -e "${BLUE}▶${NC} $*"; }
ok()   { echo -e "${GREEN}✓${NC} $*"; }
warn() { echo -e "${YELLOW}!${NC} $*"; }
die()  { echo -e "${RED}✗${NC} $*" >&2; exit 1; }

# --- Collect inputs ---------------------------------------------------------
: "${DOODLE_GH_TOKEN:=${GH_TOKEN:-}}"
: "${GH_USER:=}"
: "${GH_EMAIL:=}"
: "${REPO_NAME:=doodle}"
: "${RELEASE_TAG:=v0.1-beta}"
: "${RELEASE_TITLE:=doodle v0.1-beta}"
: "${BRANCH:=main}"

if [[ -z "$DOODLE_GH_TOKEN" ]]; then
  echo -e "${BOLD}Enter your fresh GitHub token (input hidden):${NC}"
  read -rs DOODLE_GH_TOKEN
  [[ -z "$DOODLE_GH_TOKEN" ]] && die "Token is required."
fi

if [[ -z "$GH_USER" ]]; then
  echo -e "${BOLD}Enter your GitHub username:${NC}"
  read -r GH_USER
  [[ -z "$GH_USER" ]] && die "Username is required."
fi

if [[ -z "$GH_EMAIL" ]]; then
  echo -e "${BOLD}Enter your GitHub email (for commit authorship):${NC}"
  read -r GH_EMAIL
  [[ -z "$GH_EMAIL" ]] && die "Email is required."
fi

echo ""
echo -e "${BOLD}Summary:${NC}"
echo "  GitHub user:    $GH_USER"
echo "  Repo name:      $REPO_NAME"
echo "  Branch:         $BRANCH"
echo "  Release tag:    $RELEASE_TAG"
echo "  Release title:  $RELEASE_TITLE"
echo ""
echo -e "${BOLD}Press Enter to continue, Ctrl+C to abort.${NC}"
read -r

# --- Verify dependencies ----------------------------------------------------
command -v git  >/dev/null 2>&1 || die "git is not installed. Install it first: https://git-scm.com/downloads"
command -v curl >/dev/null 2>&1 || die "curl is not installed."
command -v zip  >/dev/null 2>&1 || die "zip is not installed."

# --- Verify we're in the doodle directory ----------------------------------
[[ -f "./index.php" && -f "./install.php" && -f "./config/config.php" ]] \
  || die "Run this from inside the doodle/ project directory (must contain index.php, install.php, config/)."

# --- Verify the token works + get the user's GitHub login -------------------
log "Verifying GitHub token..."
RESPONSE=$(curl -sS -H "Authorization: token $DOODLE_GH_TOKEN" \
  -H "Accept: application/vnd.github+json" \
  https://api.github.com/user)
GH_LOGIN=$(echo "$RESPONSE" | grep -m1 '"login"' | sed -E 's/.*"login": "([^"]+)".*/\1/')

if [[ -z "$GH_LOGIN" || "$GH_LOGIN" == "null" ]]; then
  die "Token verification failed. Either the token is invalid/expired, or it lacks the 'user' scope. Response:
$RESPONSE"
fi
ok "Token valid. Authenticated as: $GH_LOGIN"

# Use the GitHub login if the user-provided username doesn't match
if [[ "$GH_LOGIN" != "$GH_USER" ]]; then
  warn "Note: token is for '$GH_LOGIN' but you said '$GH_USER'. Using '$GH_LOGIN'."
  GH_USER="$GH_LOGIN"
fi

# --- Check if repo exists; create if not -----------------------------------
log "Checking if repo $GH_USER/$REPO_NAME exists..."
REPO_CHECK=$(curl -sS -o /dev/null -w "%{http_code}" \
  -H "Authorization: token $DOODLE_GH_TOKEN" \
  -H "Accept: application/vnd.github+json" \
  https://api.github.com/repos/$GH_USER/$REPO_NAME)

if [[ "$REPO_CHECK" == "404" ]]; then
  log "Repo doesn't exist. Creating $GH_USER/$REPO_NAME as public..."
  curl -sS -X POST \
    -H "Authorization: token $DOODLE_GH_TOKEN" \
    -H "Accept: application/vnd.github+json" \
    https://api.github.com/user/repos \
    -d "{\"name\":\"$REPO_NAME\",\"description\":\"doodle — open-source LMS with AI assistant. By Death Legion Team. GPL-3.0.\",\"public\":true,\"has_issues\":true,\"has_projects\":true,\"has_wiki\":true}" \
    > /dev/null
  ok "Repo created: https://github.com/$GH_USER/$REPO_NAME"
elif [[ "$REPO_CHECK" == "200" ]]; then
  ok "Repo already exists."
else
  die "Unexpected response checking repo: HTTP $REPO_CHECK"
fi

# --- Configure git ----------------------------------------------------------
log "Configuring git..."
git config user.name  "$GH_USER"
git config user.email "$GH_EMAIL"

# Use the token in the remote URL so we don't need to type it again
REMOTE_URL="https://x-access-token:${DOODLE_GH_TOKEN}@github.com/$GH_USER/$REPO_NAME.git"

# --- Init / commit / push ---------------------------------------------------
if [[ ! -d ".git" ]]; then
  log "Initializing git repo..."
  git init -q
fi

log "Staging files (respecting .gitignore)..."
git add -A

if git diff --cached --quiet; then
  warn "No changes to commit. Using existing HEAD."
else
  log "Committing as $GH_USER <$GH_EMAIL>..."
  git commit -q -m "doodle $RELEASE_TAG — open-source LMS with AI assistant

Built by Death Legion Team. GPL-3.0. PHP 8+.

Features:
- Full LMS: courses, modules, lessons, quizzes, assignments, gradebook, forum
- AI Learning Assistant (pure PHP, no external API needed)
- Gamification: XP, levels, badges, streaks, leaderboard
- Spaced-repetition flashcards (SM-2) with AI auto-generation
- Direct messaging, calendar, certificates, polls, learning paths
- Analytics dashboard, REST API, dark mode

100% free, 100% open source."
fi

log "Renaming branch to $BRANCH..."
git branch -M "$BRANCH" 2>/dev/null || true

log "Setting remote origin..."
git remote remove origin 2>/dev/null || true
git remote add origin "$REMOTE_URL"

log "Pushing to GitHub..."
git push -u origin "$BRANCH" --force-with-lease 2>&1 | sed 's/x-access-token:[^@]*@/***@/g'
ok "Code pushed to https://github.com/$GH_USER/$REPO_NAME"

# --- Create tag -------------------------------------------------------------
log "Creating tag $RELEASE_TAG..."
if git rev-parse "$RELEASE_TAG" >/dev/null 2>&1; then
  warn "Tag $RELEASE_TAG already exists locally. Skipping creation."
else
  git tag -a "$RELEASE_TAG" -m "doodle $RELEASE_TAG"
fi

log "Pushing tag..."
git push origin "$RELEASE_TAG" 2>&1 | sed 's/x-access-token:[^@]*@/***@/g'
ok "Tag pushed."

# --- Build the release zip --------------------------------------------------
ZIP_NAME="doodle-${RELEASE_TAG}.zip"
log "Building release zip: $ZIP_NAME"
# Clean any leftovers
rm -f "$ZIP_NAME"
# Make sure sensitive files are excluded
zip -r "$ZIP_NAME" . \
  -x "*.git*" \
  -x "*.installed" \
  -x "*.v2_migrated" \
  -x "database/*.sqlite*" \
  -x "public/uploads/avatars/*" \
  -x "public/uploads/covers/*" \
  -x "public/uploads/lessons/*" \
  -x "public/uploads/submissions/*" \
  -x "node_modules/*" \
  -x "vendor/*" \
  -x "*.log" \
  > /dev/null
ok "Zip built: $(du -h "$ZIP_NAME" | cut -f1)"

# --- Create the GitHub Release ----------------------------------------------
log "Creating GitHub Release $RELEASE_TAG..."

# Read release body from CHANGELOG.md if it exists, otherwise use a default
if [[ -f "CHANGELOG.md" ]]; then
  # Extract the section for this release
  RELEASE_BODY=$(awk "/^## \[$RELEASE_TAG\]/{flag=1} /^## \[/ && !/^## \[$RELEASE_TAG\]/{if(flag) exit} flag" CHANGELOG.md)
  [[ -z "$RELEASE_BODY" ]] && RELEASE_BODY=$(head -100 CHANGELOG.md)
else
  RELEASE_BODY="doodle $RELEASE_TAG — open-source LMS with AI assistant. By Death Legion Team. GPL-3.0."
fi

# Escape for JSON
RELEASE_BODY_JSON=$(printf '%s' "$RELEASE_BODY" | python3 -c 'import json,sys; print(json.dumps(sys.stdin.read()))')

CREATE_RESP=$(curl -sS -X POST \
  -H "Authorization: token $DOODLE_GH_TOKEN" \
  -H "Accept: application/vnd.github+json" \
  https://api.github.com/repos/$GH_USER/$REPO_NAME/releases \
  -d "{\"tag_name\":\"$RELEASE_TAG\",\"name\":\"$RELEASE_TITLE\",\"body\":$RELEASE_BODY_JSON,\"draft\":false,\"prerelease\":true}")

RELEASE_ID=$(echo "$CREATE_RESP" | grep -m1 '"id"' | sed -E 's/.*"id": ([0-9]+).*/\1/')
UPLOAD_URL=$(echo "$CREATE_RESP" | grep -m1 '"upload_url"' | sed -E 's/.*"upload_url": "([^"]+)".*/\1/' | sed 's/{?name,label}//')

if [[ -z "$RELEASE_ID" ]]; then
  warn "Could not create release via API. The tag is pushed, so you can manually create the release at:"
  echo "    https://github.com/$GH_USER/$REPO_NAME/releases/new?tag=$RELEASE_TAG"
  echo "Then attach: $ZIP_NAME"
  die "API response: $CREATE_RESP"
fi
ok "Release created (ID: $RELEASE_ID)"

# --- Upload the zip as a release asset --------------------------------------
log "Uploading $ZIP_NAME as release asset..."
UPLOAD_RESP=$(curl -sS -X POST \
  -H "Authorization: token $DOODLE_GH_TOKEN" \
  -H "Accept: application/vnd.github+json" \
  -H "Content-Type: application/zip" \
  --data-binary @"$ZIP_NAME" \
  "${UPLOAD_URL}?name=${ZIP_NAME}")

ASSET_NAME=$(echo "$UPLOAD_RESP" | grep -m1 '"name"' | sed -E 's/.*"name": "([^"]+)".*/\1/')

if [[ -z "$ASSET_NAME" ]]; then
  warn "Could not upload asset automatically. Upload manually at:"
  echo "    https://github.com/$GH_USER/$REPO_NAME/releases/upload?asset=$ZIP_NAME"
  die "Upload response: $UPLOAD_RESP"
fi
ok "Asset uploaded: $ASSET_NAME"

# --- Done -------------------------------------------------------------------
echo ""
echo -e "${GREEN}${BOLD}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}${BOLD}  ✓ doodle $RELEASE_TAG is live on GitHub!${NC}"
echo -e "${GREEN}${BOLD}═══════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  Repo:    ${BLUE}https://github.com/$GH_USER/$REPO_NAME${NC}"
echo -e "  Release: ${BLUE}https://github.com/$GH_USER/$REPO_NAME/releases/tag/$RELEASE_TAG${NC}"
echo -e "  Asset:   $ZIP_NAME"
echo ""
echo -e "${YELLOW}! Reminder: revoke this token after publishing if you don't need it.${NC}"
echo -e "${YELLOW}  https://github.com/settings/tokens${NC}"
echo ""
