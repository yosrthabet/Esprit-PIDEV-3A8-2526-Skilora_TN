#!/bin/bash
#
# push-to-main.sh - Push all local changes to main WITHOUT pulling
# Groups files into logical commits and pushes directly
#
# Usage: ./push-to-main.sh
#

set -e

REPO_URL="https://github.com/yosrthabet/Esprit-PIDEV-3A8-2526-Skilora_TN"
BRANCH="main"
REMOTE="origin"

echo "=========================================="
echo "  Push Local Changes to Main (No Pull)"
echo "=========================================="
echo ""

# Check if we're in a git repo
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "❌ Not a git repository!"
    exit 1
fi

# Show current status
echo "📊 Current Git Status:"
echo "----------------------"
git status --short | head -20
echo ""

# Count modified files
MODIFIED_COUNT=$(git status --short | wc -l | tr -d ' ')
echo "📁 Total modified/new files: $MODIFIED_COUNT"
echo ""

if [ "$MODIFIED_COUNT" -eq 0 ]; then
    echo "✅ No changes to commit. Everything is clean."
    exit 0
fi

echo "⚠️  WARNING: This will push directly to $BRANCH without pulling!"
echo "   Make sure your local version is the correct one."
echo ""
read -p "Continue? (y/N): " confirm
if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
    echo "❌ Aborted."
    exit 0
fi

echo ""
echo "📝 Grouping files into logical commits..."
echo ""

# Function to commit specific files with a message
commit_group() {
    local files="$1"
    local message="$2"
    
    if [ -n "$files" ]; then
        echo "  → Committing: $message"
        echo "    Files: $(echo "$files" | wc -w | tr -d ' ') files"
        git add $files
        git commit -m "$message" --quiet
        echo "    ✅ Committed"
    fi
}

# Group 1: Core configuration and infrastructure
echo "📦 Group 1: Configuration & Infrastructure"
CONFIG_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+(\.env|composer\.json|composer\.lock|package\.json|package-lock\.json|symfony\.lock|vite\.config|tsconfig|tailwind\.config|postcss\.config)" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$CONFIG_FILES" ]; then
    commit_group "$CONFIG_FILES" "chore(config): update core infrastructure and dependencies"
fi

# Group 2: Profile-related fixes (Experience, ProfileController, AI Service)
echo "📦 Group 2: Profile Module Fixes"
PROFILE_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+.*[Pp]rofile" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$PROFILE_FILES" ]; then
    commit_group "$PROFILE_FILES" "fix(profile): fix experience ordering and AI service integration"
fi

# Group 3: README and documentation
echo "📦 Group 3: Documentation"
README_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+(README|CHANGELOG|LICENSE|CONTRIBUTING|\.md)" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$README_FILES" ]; then
    commit_group "$README_FILES" "docs(readme): update project documentation"
fi

# Group 4: Entity and Repository changes
echo "📦 Group 4: Entities & Repositories"
ENTITY_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+.*src/(Entity|Repository)/" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$ENTITY_FILES" ]; then
    commit_group "$ENTITY_FILES" "refactor(entity): update entities and repositories"
fi

# Group 5: Controller changes
echo "📦 Group 5: Controllers"
CONTROLLER_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+.*src/Controller/" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$CONTROLLER_FILES" ]; then
    commit_group "$CONTROLLER_FILES" "fix(controller): update controller logic"
fi

# Group 6: Service changes
echo "📦 Group 6: Services"
SERVICE_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+.*src/Service/" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$SERVICE_FILES" ]; then
    commit_group "$SERVICE_FILES" "fix(service): update service layer and AI integration"
fi

# Group 7: Templates and views
echo "📦 Group 7: Templates & Views"
TEMPLATE_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+.*templates/" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$TEMPLATE_FILES" ]; then
    commit_group "$TEMPLATE_FILES" "fix(templates): update UI templates"
fi

# Group 8: Assets (CSS, JS, images)
echo "📦 Group 8: Assets"
ASSET_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+.*(assets/|public/|\.css|\.js|\.png|\.jpg|\.svg|\.woff)" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$ASSET_FILES" ]; then
    commit_group "$ASSET_FILES" "chore(assets): update frontend assets"
fi

# Group 9: Config files (yaml, xml in config/)
echo "📦 Group 9: Config Files"
CONFIG_YAML_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+.*config/" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$CONFIG_YAML_FILES" ]; then
    commit_group "$CONFIG_YAML_FILES" "chore(config): update configuration files"
fi

# Group 10: Migrations
echo "📦 Group 10: Database Migrations"
MIGRATION_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+.*migrations?/" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$MIGRATION_FILES" ]; then
    commit_group "$MIGRATION_FILES" "chore(migrations): add database migrations"
fi

# Group 11: Tests
echo "📦 Group 11: Tests"
TEST_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+.*tests?/" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$TEST_FILES" ]; then
    commit_group "$TEST_FILES" "test(unit): update test suite"
fi

# Group 12: Scripts and tooling
echo "📦 Group 12: Scripts & Tools"
SCRIPT_FILES=$(git status --short | grep -E "^\s*(M|A|\?\?)\s+.*\.sh|Makefile|docker|\.github/" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$SCRIPT_FILES" ]; then
    commit_group "$SCRIPT_FILES" "chore(tools): add scripts and tooling"
fi

# Group 13: Anything remaining
echo "📦 Group 13: Remaining Files"
REMAINING=$(git status --short | grep -E "^\s*(M|A|\?\?)" | awk '{print $2}' | tr '\n' ' ')
if [ -n "$REMAINING" ]; then
    commit_group "$REMAINING" "chore: update remaining files"
fi

echo ""
echo "📊 Commit Summary:"
echo "------------------"
git log --oneline HEAD~10..HEAD 2>/dev/null || git log --oneline -5
echo ""

# Show what's about to be pushed
echo "🚀 Ready to push to $BRANCH"
echo "   Remote: $REPO_URL"
echo ""

read -p "Push to $BRANCH? (y/N): " push_confirm
if [[ ! "$push_confirm" =~ ^[Yy]$ ]]; then
    echo "❌ Push aborted. Your commits are local only."
    echo "   To push later, run: git push origin $BRANCH"
    exit 0
fi

echo ""
echo "🚀 Pushing to $BRANCH..."

# Try regular push first
git push origin $BRANCH 2>&1 && {
    echo ""
    echo "✅ Successfully pushed to $BRANCH!"
    echo ""
    echo "📋 Pushed commits:"
    git log --oneline origin/$BRANCH..HEAD 2>/dev/null || echo "   (commits are now on remote)"
    exit 0
}

# If regular push fails, offer force-with-lease (safer than --force)
echo ""
echo "⚠️  Regular push failed. This usually means remote has commits that local doesn't."
echo ""
echo "Options:"
echo "  1. Force with lease (safe force push - only if no one else pushed)")
echo "  2. Abort and investigate manually"
echo ""
read -p "Choose (1/2): " force_choice

if [ "$force_choice" = "1" ]; then
    echo ""
    echo "🚀 Force pushing with lease..."
    git push origin $BRANCH --force-with-lease 2>&1 && {
        echo ""
        echo "✅ Force push successful!"
        exit 0
    } || {
        echo ""
        echo "❌ Force push failed. Someone may have pushed to $BRANCH recently."
        echo "   You'll need to resolve this manually."
        exit 1
    }
else
    echo "❌ Aborted. Changes are committed locally but not pushed."
    exit 0
fi
