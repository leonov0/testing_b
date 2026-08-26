#!/usr/bin/env python3
"""Reads the JUnit XML produced by tools/verify.sh (Pest --log-junit) and prints the marking table."""
import json
import pathlib
import sys
import xml.etree.ElementTree as ET


def load_junit(path):
    """Returns (total, failed, [failing test names])."""
    tree = ET.parse(path)
    root = tree.getroot()
    total = 0
    failing = []
    for case in root.iter('testcase'):
        total += 1
        problems = list(case.findall('failure')) + list(case.findall('error'))
        if problems:
            name = f"{case.get('class') or case.get('classname') or ''}::{case.get('name')}"
            failing.append(name.strip(':'))
    return total, len(failing), failing


def load_json(path):
    with open(path) as handle:
        return json.load(handle)


def report_ref(result_path):
    total, failed, failing = load_junit(result_path)
    print(f'  tests: {total}   passed: {total - failed}   failed: {failed}')
    if failed == 0:
        print('  no false positives - every assertion holds on the correct implementation')
        return 0
    print('  FALSE POSITIVES (these tests claim a defect that does not exist):')
    for name in failing:
        print(f'    - {name}')
    print(f'  deduction: {failed} x 0.25 mark')
    return 1


def report_sut(result_path, manifest_path):
    total, failed, failing = load_junit(result_path)
    manifest = load_json(manifest_path)
    print(f'  tests: {total}   passed: {total - failed}   failed: {failed}')

    width = max(len(d['id']) for d in manifest['defects'])
    detected = 0
    for defect in manifest['defects']:
        hits = [name for name in failing if any(frag in name for frag in defect['detectedBy'])]
        detected += 1 if hits else 0
        print(f'  {"DETECTED" if hits else "MISSED  "} {defect["id"]:<{width}} '
              f'[{defect["kind"]:<10}|{defect["severity"]:<7}] {defect["area"]}')
        if hits:
            print(f'           proven by: {hits[0]}')
        else:
            print(f'           expected: {defect["spec"]}')
    print(f'  seeded defects detected: {detected}/{len(manifest["defects"])}')
    return 0 if detected == len(manifest['defects']) else 1


def report_mutants(results_dir, manifest_path):
    manifest = load_json(manifest_path)
    results_dir = pathlib.Path(results_dir)
    caught = 0
    for mutant in manifest:
        path = results_dir / f'{mutant["id"]}.xml'
        if not path.exists():
            print(f'  ERROR    {mutant["id"]} - no result file')
            continue
        _, failed, failing = load_junit(path)
        if failed:
            caught += 1
            print(f'  CAUGHT   {mutant["id"]} {mutant["description"]}')
            print(f'           killed by: {failing[0]}')
        else:
            print(f'  SURVIVED {mutant["id"]} {mutant["description"]}')
            print(f'           no test in the suite reacts to this change ({mutant["file"]})')
    print(f'  mutants caught: {caught}/{len(manifest)}')
    return 0 if caught == len(manifest) else 1


def main():
    mode = sys.argv[1]
    if mode == 'ref':
        return report_ref(sys.argv[2])
    if mode == 'sut':
        return report_sut(sys.argv[2], sys.argv[3])
    if mode == 'mutants':
        return report_mutants(sys.argv[2], sys.argv[3])
    print(f'unknown mode: {mode}', file=sys.stderr)
    return 2


if __name__ == '__main__':
    sys.exit(main())
