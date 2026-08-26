#!/usr/bin/env bash
#
# Module B marking harness.
#
#   bash tools/verify.sh [path-to-tests] [--fast]
#
# Runs one candidate test suite (default: the model answer in solution/tests) against:
#   REF  - the specification-correct build   -> every test must pass
#   SUT  - the build the competitor received -> the 14 seeded defects must be detected
#   MUT  - 14 one-change builds              -> each must make at least one test fail
#
# --fast skips the mutant runs.
#
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SUITE="${1:-$ROOT/solution/tests}"
FAST="${2:-}"
if [[ "$SUITE" == "--fast" ]]; then SUITE="$ROOT/solution/tests"; FAST="--fast"; fi

if [[ ! -d "$SUITE" ]]; then
  echo "suite not found: $SUITE" >&2
  exit 2
fi

WORK="$(mktemp -d "${TMPDIR:-/tmp}/wsc-module-b.XXXXXX")"
RESULTS="$WORK/results"
mkdir -p "$RESULTS"
trap 'rm -rf "$WORK"' EXIT

# Fast copy: APFS clone when available, plain copy otherwise.
copy_tree() {
  cp -Rc "$1" "$2" 2>/dev/null || cp -R "$1" "$2"
}

# stage <label> <source-build> [mutant-dir]
stage() {
  local label="$1" source="$2" mutant="${3:-}"
  local dir="$WORK/$label"
  rm -rf "$dir"
  copy_tree "$source" "$dir"
  rm -rf "$dir/tests" "$dir/storage/framework/cache" "$dir/storage/logs"
  mkdir -p "$dir/storage/framework/cache/data" "$dir/storage/framework/sessions" \
           "$dir/storage/framework/views" "$dir/storage/app/private" "$dir/storage/logs" \
           "$dir/bootstrap/cache"
  copy_tree "$SUITE" "$dir/tests"
  if [[ -n "$mutant" ]]; then
    copy_tree "$mutant/." "$dir/"
  fi
  ( cd "$dir" && ./vendor/bin/pest --log-junit "$RESULTS/$label.xml" >"$RESULTS/$label.log" 2>&1 )
}

echo "suite under assessment: $SUITE"
echo

echo "== REF run (every test must pass) ============================================"
stage ref "$ROOT/ref"
python3 "$ROOT/tools/report.py" ref "$RESULTS/ref.xml"
REF_STATUS=$?
echo

echo "== SUT run (seeded defects must be detected) ================================="
stage sut "$ROOT/sut"
python3 "$ROOT/tools/report.py" sut "$RESULTS/sut.xml" "$ROOT/solution/defects.manifest.json"
SUT_STATUS=$?
echo

if [[ "$FAST" == "--fast" ]]; then
  echo "(mutant runs skipped: --fast)"
  exit $(( REF_STATUS || SUT_STATUS ))
fi

echo "== MUT runs (each mutant must break at least one test) ======================="
MUT_IDS=$(python3 -c "import json,sys; print(' '.join(m['id'] for m in json.load(open(sys.argv[1]))))" "$ROOT/mutants/manifest.json")
for id in $MUT_IDS; do
  stage "$id" "$ROOT/ref" "$ROOT/mutants/$id"
done
python3 "$ROOT/tools/report.py" mutants "$RESULTS" "$ROOT/mutants/manifest.json"
MUT_STATUS=$?

echo
echo "============================================================================="
[[ $REF_STATUS -eq 0 ]] && echo "REF     : OK - no false positives" || echo "REF     : FAILED - the suite reports defects that do not exist"
[[ $SUT_STATUS -eq 0 ]] && echo "SUT     : OK - every seeded defect detected" || echo "SUT     : INCOMPLETE - see the table above"
[[ $MUT_STATUS -eq 0 ]] && echo "MUTANTS : OK - all mutants caught" || echo "MUTANTS : INCOMPLETE - see the table above"

exit $(( REF_STATUS || SUT_STATUS || MUT_STATUS ))
