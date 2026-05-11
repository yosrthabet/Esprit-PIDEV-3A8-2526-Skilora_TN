#!/usr/bin/env bash
# Skilora Production Route Checker
# Tests all public GET routes on skilora.dev
set -euo pipefail

BASE="https://skilora.dev"
PASS=0
FAIL=0
WARN=0
ERRORS=()

check() {
    local label="$1"
    local url="$2"
    local expect="${3:-200}"

    code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$url" 2>/dev/null || echo "000")

    if [ "$code" = "$expect" ]; then
        printf "  ✅  %-40s %s → %s\n" "$label" "$code" "OK"
        PASS=$((PASS + 1))
    elif [ "$code" = "302" ] || [ "$code" = "301" ]; then
        printf "  🔄  %-40s %s → redirect (expected %s)\n" "$label" "$code" "$expect"
        WARN=$((WARN + 1))
    elif [ "$code" = "000" ]; then
        printf "  ❌  %-40s %s → TIMEOUT/UNREACHABLE\n" "$label" "$code"
        FAIL=$((FAIL + 1))
        ERRORS+=("$label: timeout")
    else
        printf "  ❌  %-40s %s → expected %s\n" "$label" "$code" "$expect"
        FAIL=$((FAIL + 1))
        ERRORS+=("$label: got $code expected $expect")
    fi
}

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║         SKILORA PRODUCTION ROUTE CHECKER                    ║"
echo "║         Target: $BASE                          ║"
echo "║         Time:   $(date '+%Y-%m-%d %H:%M:%S')                        ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# ─── PUBLIC PAGES (no auth needed) ───
echo "── Public Pages ──"
check "Homepage"                    "$BASE/"
check "About"                       "$BASE/about"
check "Pricing"                     "$BASE/pricing"
check "Careers"                     "$BASE/careers"
check "Case Studies"                "$BASE/case-studies"
check "Health Check"                "$BASE/health"
check "Offline"                     "$BASE/offline"
check "Login"                       "$BASE/login"
check "Register"                    "$BASE/register"
check "Forgot Password"            "$BASE/forgot-password"

echo ""
echo "── Static Assets ──"
check "CSS"                         "$BASE/css/app.css"
check "JS"                          "$BASE/assets/app.js"
check "Hero Video"                  "$BASE/videos/hero-scroll-opt.mp4"
check "Service Worker"              "$BASE/service-worker.js"
check "Manifest"                    "$BASE/manifest.json"

echo ""
echo "── Auth-Protected (expect 302 redirect to /login) ──"
check "Dashboard"                   "$BASE/dashboard"                302
check "Workspace"                   "$BASE/workspace"                302
check "Profile"                     "$BASE/profile"                  302
check "Settings"                    "$BASE/settings"                 302
check "Notifications"               "$BASE/notifications"            302
check "Chatbot"                     "$BASE/chatbot"                  302

echo ""
echo "── Finance (expect 302) ──"
check "Wallet"                      "$BASE/finance/wallet"           302
check "Invoices"                    "$BASE/finance/invoices"         302
check "Export Transactions"            "$BASE/finance/export/transactions" 302

echo ""
echo "── Community (expect 302) ──"
check "Community Feed"              "$BASE/community"                302
check "Community Blog"              "$BASE/community/blog"           302

echo ""
echo "── Recruitment (expect 302) ──"
check "Job Offers"                    "$BASE/hire-offers"              302
check "Active Offers"                "$BASE/active-offers"            302
check "Applications"                "$BASE/applications"             302

echo ""
echo "── Formation (expect 302) ──"
check "Formations List"             "$BASE/formations"               200
check "Trainer Formations"          "$BASE/trainer/formations"       302

echo ""
echo "── Support (expect 302) ──"
check "Support"                     "$BASE/support"                  302

echo ""
echo "── API / Services (no auth, expect various) ──"
check "Mercure Hub"                 "$BASE/.well-known/mercure"      502  # 502 expected until Mercure service is deployed
check "ML Recruitment"              "$BASE/recruitment/ml"           302
check "ML Formation"                "$BASE/formation/ml"             302

echo ""
echo "── SSL & Headers ──"
# Check SSL
ssl_code=$(curl -sI https://skilora.dev 2>&1 | head -1 | awk '{print $2}')
if [ "$ssl_code" = "200" ]; then
    printf "  ✅  %-40s SSL OK\n" "HTTPS"
    PASS=$((PASS + 1))
else
    printf "  ❌  %-40s SSL FAIL (%s)\n" "HTTPS" "$ssl_code"
    FAIL=$((FAIL + 1))
fi

# Check HTTP→HTTPS redirect
http_code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "http://skilora.dev" 2>/dev/null || echo "000")
if [ "$http_code" = "301" ] || [ "$http_code" = "302" ]; then
    printf "  ✅  %-40s HTTP→HTTPS redirect (%s)\n" "HTTP Redirect" "$http_code"
    PASS=$((PASS + 1))
else
    printf "  ❌  %-40s No redirect (%s)\n" "HTTP Redirect" "$http_code"
    FAIL=$((FAIL + 1))
fi

# Security headers
headers=$(curl -sI https://skilora.dev 2>&1)
for h in "X-Frame-Options" "X-Content-Type-Options"; do
    if echo "$headers" | grep -qi "$h"; then
        printf "  ✅  %-40s Present\n" "$h"
        PASS=$((PASS + 1))
    else
        printf "  ⚠️  %-40s Missing\n" "$h"
        WARN=$((WARN + 1))
    fi
done

echo ""
echo "══════════════════════════════════════════════════════════════"
echo "  Results: ✅ $PASS passed | 🔄 $WARN redirects/warnings | ❌ $FAIL failed"
echo "══════════════════════════════════════════════════════════════"

if [ ${#ERRORS[@]} -gt 0 ]; then
    echo ""
    echo "  Failed routes:"
    for e in "${ERRORS[@]}"; do
        echo "    → $e"
    done
fi

echo ""
exit $FAIL
