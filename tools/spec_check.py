#!/usr/bin/env python3
"""Checks that every seeded defect is findable from the specification alone.

A competitor only ever reads docs/spec.md. If a defect breaks a rule the specification never states,
no honest test suite can find it and the module is unfair. This script cross-checks the defect
manifest against the specification, and optionally against the model answer:

  * every defect names a rule id (R1.., S1..) in its `spec` field   (skip with --no-rules)
  * every rule id it names actually appears in docs/spec.md
  * every defect lists at least one `detectedBy` test fragment
  * with --tests DIR: every fragment really occurs in the model answer's test sources

    python3 tools/spec_check.py solution/defects.manifest.json sut/docs/spec.md [--tests DIR] [--no-rules]
"""
import argparse
import json
import pathlib
import re
import sys

RULE_PATTERN = re.compile(r'\b([RS]\d{1,2})\b')


def load_test_sources(directory: pathlib.Path) -> str:
    parts = []
    for path in sorted(directory.rglob('*')):
        if path.is_file() and path.suffix in {'.php', '.js', '.ts'}:
            parts.append(path.read_text(errors='ignore'))
    return '\n'.join(parts)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('manifest')
    parser.add_argument('spec')
    parser.add_argument('--tests', default=None)
    parser.add_argument('--no-rules', action='store_true')
    args = parser.parse_args()

    manifest = json.loads(pathlib.Path(args.manifest).read_text())
    spec_path = pathlib.Path(args.spec)
    spec = spec_path.read_text()
    documented = set(RULE_PATTERN.findall(spec))
    sources = load_test_sources(pathlib.Path(args.tests)) if args.tests else None

    problems = []
    referenced = set()

    for defect in manifest['defects']:
        rules = set(RULE_PATTERN.findall(defect.get('spec', '')))

        if not args.no_rules:
            if not rules:
                problems.append(f"{defect['id']}: its `spec` field names no rule id (R.. or S..)")
            referenced |= rules
            for rule in sorted(rules):
                if rule not in documented:
                    problems.append(f"{defect['id']}: rule {rule} does not appear in {spec_path.name}")

        fragments = defect.get('detectedBy') or []
        if not fragments:
            problems.append(f"{defect['id']}: no test fragment listed in detectedBy")

        if sources is not None:
            for fragment in fragments:
                if fragment not in sources:
                    problems.append(f"{defect['id']}: detectedBy fragment not found in the model answer: \"{fragment}\"")

    for problem in problems:
        print(f'  spec-check: {problem}', file=sys.stderr)

    if not args.no_rules:
        untested = sorted(rule for rule in documented if rule not in referenced)
        if untested:
            print(f"  spec-check: rules with no seeded defect (fine, just so you know): {', '.join(untested)}")

    return 1 if problems else 0


if __name__ == '__main__':
    sys.exit(main())
