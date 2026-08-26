#!/usr/bin/env python3
"""Generates the mutant set for Module B from the REF build.

Each mutant is one small behavioural change written as a full replacement file under
mutants/<ID>/<path relative to the build>. tools/verify.sh overlays one at a time onto REF.
"""
import json
import pathlib
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
REF = ROOT / 'ref'
OUT = ROOT / 'mutants'

MUTANTS = [
    ('M01', 'app/Services/Gtin.php',
     'a 14 digit GTIN is rejected: only 13 digits pass',
     "'/^\\d{13,14}$/'",
     "'/^\\d{13}$/'"),

    ('M02', 'app/Services/Gtin.php',
     'a 12 digit GTIN is accepted',
     "'/^\\d{13,14}$/'",
     "'/^\\d{12,14}$/'"),

    ('M03', 'app/Services/Gtin.php',
     'bulk input lines are no longer trimmed',
     '            ->map(fn (string $line) => trim($line))',
     '            ->map(fn (string $line) => $line)'),

    ('M04', 'app/Services/Gtin.php',
     'blank lines survive the bulk input parsing',
     "            ->filter(fn (string $line) => $line !== '')\n",
     ''),

    ('M05', 'app/Models/Product.php',
     'the keyword search drops the French description',
     "            foreach (['name_en', 'name_fr', 'description_en', 'description_fr'] as $column) {",
     "            foreach (['name_en', 'name_fr', 'description_en'] as $column) {"),

    ('M06', 'app/Models/Product.php',
     'the visible scope stops filtering hidden products',
     "        return $query->where('is_hidden', false);",
     '        return $query;'),

    ('M07', 'app/Models/Product.php',
     'a hidden product is reported as not deletable',
     '        return $this->is_hidden === true;',
     '        return false;'),

    ('M08', 'app/Models/Company.php',
     'deactivation no longer cascades to the products',
     "        $this->products()->update(['is_hidden' => true]);\n",
     ''),

    ('M09', 'app/Models/Company.php',
     'reactivating a company unhides all of its products again',
     "        $this->forceFill(['deactivated' => false])->save();",
     "        $this->forceFill(['deactivated' => false])->save();\n        $this->products()->update(['is_hidden' => false]);"),

    ('M10', 'app/Http/Controllers/ProductApiController.php',
     'the API paginates five products per page',
     '    public const PER_PAGE = 10;',
     '    public const PER_PAGE = 5;'),

    ('M11', 'app/Http/Controllers/ProductApiController.php',
     'the pagination links no longer carry the query string',
     '            ->paginate(self::PER_PAGE)\n            ->withQueryString();',
     '            ->paginate(self::PER_PAGE);'),

    ('M12', 'app/Models/Product.php',
     'the API drops the weight unit',
     """            'weight' => [
                'gross' => $this->weight_gross,
                'net' => $this->weight_net,
                'unit' => $this->weight_unit,
            ],""",
     """            'weight' => [
                'gross' => $this->weight_gross,
                'net' => $this->weight_net,
            ],"""),

    ('M13', 'app/Models/Product.php',
     'the API flattens the product name to English only',
     """            'name' => [
                'en' => $this->name_en,
                'fr' => $this->name_fr,
            ],""",
     """            'name' => $this->name_en,"""),

    ('M14', 'app/Http/Controllers/PublicController.php',
     'the all valid banner appears when any code is valid',
     "collect($results)->every(fn (array $row) => $row['valid'])",
     "collect($results)->contains(fn (array $row) => $row['valid'])"),

    ('M15', 'app/Http/Controllers/PublicController.php',
     'the public product page always renders English',
     "        $language = $request->query('lang') === 'fr' ? 'fr' : 'en';",
     "        $language = 'en';"),

    ('M16', 'app/Http/Middleware/RequireAdminSession.php',
     'the management gate lets everyone through',
     "        if ($request->session()->get(self::SESSION_KEY) !== true) {",
     '        if (false) {'),
]


def main():
    manifest = []
    for mutant_id, rel, description, old, new in MUTANTS:
        source = (REF / rel).read_text()
        if old not in source:
            sys.exit(f'{mutant_id}: anchor not found in {rel}')
        target = OUT / mutant_id / rel
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(source.replace(old, new, 1))
        manifest.append({'id': mutant_id, 'file': rel, 'description': description})
        print(f'{mutant_id} -> {target.relative_to(ROOT)}')

    (OUT / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
    print(f'{len(manifest)} mutants written')


if __name__ == '__main__':
    main()
