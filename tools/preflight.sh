#!/usr/bin/env bash
#
# Module B preflight: proves the module can actually be handed out and completed.
#
#   bash tools/preflight.sh [--full]
#
# It answers the questions a competitor cannot ask on the day.
#
#   1  handout structure       every file the task document promises is there
#   2  handout boots           artisan runs, the database seeds, the shipped example tests pass
#   3  handout runs            the delivered build answers on every documented route
#   4  REF conforms            the working build answers exactly the documented statuses
#   5  login flow              the passphrase gate can be passed on the working build
#   6  deliverables writable   the eight deliverable paths can be created
#   7  coverage driver         Xdebug or PCOV present (warning only - the task has a fallback)
#   8  spec covers defects     every seeded defect points at a rule that exists in docs/spec.md
#   9  solvability             a suite written from the spec finds every defect (and with --full,
#                              kills every mutant)
#
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SUT="$ROOT/sut"
REF="$ROOT/ref"
FULL="${1:-}"

PASS=0
FAIL=0
WARN=0
SUT_PID=""
REF_PID=""
SUT_LOG="$(mktemp)"
REF_LOG="$(mktemp)"

pass() { printf '  \033[32mPASS\033[0m  %s\n' "$1"; PASS=$((PASS + 1)); }
fail() { printf '  \033[31mFAIL\033[0m  %s\n' "$1"; FAIL=$((FAIL + 1)); }
warn() { printf '  \033[33mWARN\033[0m  %s\n' "$1"; WARN=$((WARN + 1)); }
step() { printf '\n== %s\n' "$1"; }

cleanup() {
  [[ -n "$SUT_PID" ]] && kill "$SUT_PID" 2>/dev/null
  [[ -n "$REF_PID" ]] && kill "$REF_PID" 2>/dev/null
  rm -rf "$SUT/.preflight-scratch"
  rm -f "$SUT_LOG" "$REF_LOG"
}
trap cleanup EXIT

free_port() {
  python3 -c 'import socket;s=socket.socket();s.bind(("127.0.0.1",0));print(s.getsockname()[1]);s.close()'
}

# start_server <build-dir> <port> <log> <pid-var>
# Sets the named variable to the server pid. Never use a command substitution for this: the
# background server keeps the substitution's pipe open and the caller would hang.
start_server() {
  local dir="$1" port="$2" log="$3" pid_var="$4"
  ( cd "$dir" && php artisan serve --port="$port" > "$log" 2>&1 ) &
  printf -v "$pid_var" '%s' "$!"

  for _ in $(seq 1 15); do
    if [[ "$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:$port/verify")" != "000" ]]; then
      return 0
    fi
    sleep 1
  done
  return 1
}

status_of() { curl -s -o /dev/null -w '%{http_code}' "$1"; }

echo "Module B preflight"
echo "  handout : $SUT"
echo "  working : $REF"

# 1 ---------------------------------------------------------------------------------------
step "1 handout structure"
for path in artisan composer.json phpunit.xml docs/spec.md tests/Pest.php tests/TestCase.php \
            tests/Unit/ExampleTest.php tests/Feature/ExampleTest.php tests/Security/ExampleTest.php \
            vendor/bin/pest app/Services/Gtin.php .env; do
  [[ -e "$SUT/$path" ]] && pass "$path" || fail "$path is missing from the handout"
done

# 2 ---------------------------------------------------------------------------------------
step "2 handout boots"
( cd "$SUT" && php artisan --version >/dev/null 2>&1 ) \
  && pass "php artisan runs" || fail "php artisan does not run in the handout"
( cd "$SUT" && php artisan migrate:fresh --seed >/dev/null 2>&1 ) \
  && pass "database migrates and seeds" || fail "php artisan migrate:fresh --seed failed"
( cd "$SUT" && ./vendor/bin/pest >/dev/null 2>&1 ) \
  && pass "the shipped example tests pass" \
  || fail "the shipped example tests do not pass - the competitor would start on red"

# 3 ---------------------------------------------------------------------------------------
# The handout is deliberately defective, so only its reachability is asserted here.
step "3 handout runs"
SUT_PORT="$(free_port)"
if start_server "$SUT" "$SUT_PORT" "$SUT_LOG" SUT_PID; then
  pass "the handout serves HTTP (port $SUT_PORT)"
  for uri in / /verify /products.json /login /products /companies; do
    code="$(status_of "http://127.0.0.1:$SUT_PORT$uri")"
    [[ "$code" != "000" && "$code" != "500" ]] \
      && pass "handout answers $uri ($code)" \
      || fail "handout answers $uri with $code"
  done
else
  fail "the handout never answered on port $SUT_PORT"
  sed 's/^/        /' "$SUT_LOG" | head -10
fi

# 4 ---------------------------------------------------------------------------------------
step "4 the working build conforms to the documented statuses"
( cd "$REF" && php artisan migrate:fresh --seed >/dev/null 2>&1 ) \
  && pass "working build seeds" || fail "the working build failed to seed"
( cd "$REF" && php artisan storage:link >/dev/null 2>&1 ) || true

REF_PORT="$(free_port)"
if start_server "$REF" "$REF_PORT" "$REF_LOG" REF_PID; then
  pass "the working build serves HTTP (port $REF_PORT)"

  GTIN="$(cd "$REF" && php -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo App\Models\Product::query()->where("is_hidden", false)->value("gtin");' 2>/dev/null)"
  [[ -n "$GTIN" ]] && pass "a public GTIN is seeded ($GTIN)" || fail "no visible product was seeded"

  check() { # <uri> <expected> <label>
    local actual
    actual="$(status_of "http://127.0.0.1:$REF_PORT$1")"
    [[ "$actual" == "$2" ]] && pass "$3 -> $actual" || fail "$3 -> expected $2, got $actual"
  }

  check "/verify" 200 "GET /verify"
  check "/products.json" 200 "GET /products.json"
  check "/products/$GTIN.json" 200 "GET /products/{gtin}.json"
  check "/products/9999999999999.json" 404 "GET /products/{unknown}.json"
  check "/01/$GTIN" 200 "GET /01/{gtin}"
  check "/01/$GTIN?lang=fr" 200 "GET /01/{gtin}?lang=fr"
  check "/login" 200 "GET /login"
  check "/products" 401 "GET /products without a session"
  check "/companies" 401 "GET /companies without a session"
  check "/heritages/nope" 404 "GET an unknown path"

  curl -s "http://127.0.0.1:$REF_PORT/products.json" | grep -q '"pagination"' \
    && pass "the products API returns the documented envelope" \
    || fail "the products API does not return a pagination envelope"

  curl -s "http://127.0.0.1:$REF_PORT/01/$GTIN?lang=fr" | grep -q '<html lang="fr">' \
    && pass "the public product page switches to French" \
    || fail "the French product page does not carry lang=\"fr\""
else
  fail "the working build never answered on port $REF_PORT"
  sed 's/^/        /' "$REF_LOG" | head -10
fi

# 5 ---------------------------------------------------------------------------------------
step "5 login flow on the working build"
if [[ -n "${REF_PORT:-}" ]]; then
  JAR="$(mktemp)"
  TOKEN="$(curl -s -c "$JAR" "http://127.0.0.1:$REF_PORT/login" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')"
  [[ -n "$TOKEN" ]] && pass "the login form ships a CSRF token" || fail "no CSRF token on the login form"

  curl -s -b "$JAR" -c "$JAR" -o /dev/null -X POST -d "_token=$TOKEN&passphrase=admin" "http://127.0.0.1:$REF_PORT/login"
  for uri in /products /companies /companies/deactivated /products/new; do
    code="$(curl -s -b "$JAR" -o /dev/null -w '%{http_code}' "http://127.0.0.1:$REF_PORT$uri")"
    [[ "$code" == "200" ]] && pass "signed in: $uri -> 200" || fail "signed in: $uri -> $code"
  done
  rm -f "$JAR"
fi

# 6 ---------------------------------------------------------------------------------------
step "6 deliverables writable"
mkdir -p "$SUT/.preflight-scratch"
for deliverable in test-plan.md defects.md coverage.txt expert_readme.txt \
                   .github/workflows/ci.yml tests/Unit/Probe.php tests/Feature/Probe.php tests/Security/Probe.php; do
  target="$SUT/.preflight-scratch/$deliverable"
  mkdir -p "$(dirname "$target")" 2>/dev/null
  : > "$target" 2>/dev/null && pass "can create $deliverable" || fail "cannot create $deliverable"
done

# 7 ---------------------------------------------------------------------------------------
step "7 coverage driver"
if php -m | grep -qiE '^(xdebug|pcov)$'; then
  pass "a coverage driver is installed"
else
  warn "no Xdebug or PCOV: 'php artisan test --coverage' will not work on this machine"
  warn "  the task document allows './vendor/bin/pest --list-tests > coverage.txt' instead"
fi

# 8 ---------------------------------------------------------------------------------------
step "8 the specification covers every seeded defect"
python3 "$ROOT/tools/spec_check.py" "$ROOT/solution/defects.manifest.json" "$SUT/docs/spec.md" \
  --tests "$ROOT/solution/tests" \
  && pass "every seeded defect maps to a documented rule and to a real test" \
  || fail "a seeded defect is not covered by the specification or by the model answer"

# 9 ---------------------------------------------------------------------------------------
step "9 solvability"
VERIFY_ARGS=("$ROOT/solution/tests")
[[ "$FULL" != "--full" ]] && VERIFY_ARGS+=("--fast")
if bash "$ROOT/tools/verify.sh" "${VERIFY_ARGS[@]}" > "$ROOT/tools/.preflight-verify.log" 2>&1; then
  if [[ "$FULL" == "--full" ]]; then
    pass "a spec-derived suite: no false positives, every defect detected, every mutant killed"
  else
    pass "a spec-derived suite: no false positives, every defect detected (run --full for mutants)"
  fi
else
  fail "the model answer no longer proves the module solvable - see tools/.preflight-verify.log"
fi

# ------------------------------------------------------------------------------------------
printf '\n=========================================================\n'
printf 'passed: %s   failed: %s   warnings: %s\n' "$PASS" "$FAIL" "$WARN"
if [[ "$FAIL" -eq 0 ]]; then
  echo "PREFLIGHT OK - the module can be handed out and completed as written."
  exit 0
fi
echo "PREFLIGHT FAILED - fix the items above before handing the module out."
exit 1
