# Module B — Made in France products management: trainer package

Everything needed to run the test-only back-end module from
`../tasks/MODULE_B_backend_testing.md`.

The application under test is the real **WSC2024 Skill 17 Module B — Products Management**
(`../WSC2024_TP17_MB_actual_en.pdf`), rebuilt to that brief: companies, products, GTIN, the JSON API
with pagination and keyword search, the public bulk verification page and the public product page.
The competitor never writes application code — they write the test suite that proves it.

```
module_b/
  ref/         REF build - the specification-correct Laravel 12 application. The competitor never sees it.
  sut/         SUT build - what the competitor receives. REF + 16 seeded defects + 3 example tests + docs/spec.md
  solution/    model answer: tests/ (165 tests) + defects.manifest.json
  mutants/     16 one-change builds generated from REF, plus manifest.json
  tools/       verify.sh (marking harness), report.py, make_sut.py, make_mutants.py, package-competitor.sh
```

Stack: **PHP 8.3+ / Laravel 12 / Pest 3 / SQLite**. The test suite runs against an in-memory SQLite
database configured in `phpunit.xml`, so there is no database to provision.

## Handing the module to the competitor

```bash
bash tools/package-competitor.sh      # writes dist/XX_module_b_sut.zip
```

The zip contains `sut/` with `vendor/` included — the competition workstation has no internet. The
competitor writes only inside `tests/` and the eight deliverable files listed in the task document;
`app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `resources/` and `public/` are read only.

Checksum the read-only tree before and after the module:

```bash
cd sut && find app bootstrap config database routes resources public -type f \
  -exec shasum -a 256 {} + | sort > ../dist/handout.sha256
# ... after the module ...
cd <competitor-copy> && find app bootstrap config database routes resources public -type f \
  -exec shasum -a 256 {} + | sort | diff - ../dist/handout.sha256
```

Any difference means the source was edited → the module scores zero.

## Marking

```bash
bash tools/verify.sh <path-to-competitor-tests>     # e.g. .../XX_module_b/tests
bash tools/verify.sh <path> --fast                  # REF + SUT only, skips the mutants
bash tools/verify.sh                                # health check against the model answer
```

The harness stages a fresh copy of each build (APFS clone where available), drops the suite under
assessment into `tests/`, runs Pest with `--log-junit`, and prints three tables:

| Run | Meaning | What the marker reads |
|---|---|---|
| **REF** | the suite against the correct implementation | every failure is a **false positive**: −0.25 marks each |
| **SUT** | the suite against the delivered build | how many of the 16 seeded defects the suite detects |
| **MUT** | the suite against 16 one-change builds | how deep the assertions are; a surviving mutant scores nothing |

The model answer scores REF 165/165, SUT 16/16, MUT 16/16.

## The 16 seeded defects in SUT

Full detail, including the test names that prove each one, is in `solution/defects.manifest.json`.

| ID | Kind | Where | What is wrong |
|---|---|---|---|
| B-D01 | functional | `Gtin::isValidFormat()` | a 12 digit code passes validation |
| B-D02 | functional | `Company::deactivate()` | deactivation no longer hides the company's products |
| B-D03 | functional | `ProductApiController` | 20 products per page instead of 10 |
| B-D04 | functional | `ProductApiController` | next and previous page links swapped |
| B-D05 | functional | `Product::scopeMatching()` | the keyword search ignores the French fields |
| B-D06 | functional | `Product::isDeletable()` | a visible product can be deleted |
| B-D07 | functional | `PublicController::verify()` | the "All valid" banner appears when only some codes are valid |
| B-D08 | functional | `PublicController::product()` | the public page ignores `?lang=` |
| B-S01 | security | `Product::scopeMatching()` | the keyword is concatenated into raw SQL |
| B-S02 | security | `Product` + `ProductController` | `is_hidden` becomes client-writable |
| B-S03 | security | `RequireAdminSession` | the gate redirects instead of answering 401 |
| B-S04 | security | `StoreProductRequest` | any file is accepted as a product image |
| B-S05 | security | `AuthController` | no rate limiting, plain string passphrase compare |
| B-S06 | security | `ProductApiController::show()` | hidden products are served by the JSON API |
| B-S07 | security | product Blade views | name and description rendered with `{!! !!}` |
| B-S08 | security | `bootstrap/app.php` | failures leak exception messages, paths and traces |

## The 16 mutants

Listed in `mutants/manifest.json`. Regenerate after any change to REF:

```bash
python3 tools/make_mutants.py
```

They cover both GTIN length boundaries, bulk-input trimming and blank-line filtering, the search
field list, the visible scope, the delete rule, the deactivation cascade and its reactivation
counterpart, the page size, the query string on pagination links, two JSON shape details, the
"All valid" logic, the language switch, and the admin gate.

## Keeping the package consistent

`sut/` is generated from `ref/` — if you change REF, re-derive rather than editing both by hand:

```bash
python3 tools/make_sut.py && python3 tools/make_mutants.py && bash tools/verify.sh
```

`make_sut.py` refreshes `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `resources/`,
`public/` and the root config files from REF, then re-applies the 16 defect patches. It never touches
`sut/tests/`, `sut/docs/` or `sut/vendor/`.

## Running the working application

`ref/` is the working build — the specification-correct one. `sut/` is deliberately defective and is
only ever handed to a competitor.

```bash
bash tools/run.sh                    # one command: seeds, links storage, serves, prints the URLs
# or by hand:
cd ref && php artisan migrate:fresh --seed && php artisan storage:link && php artisan serve
```

| Page | What it shows |
|---|---|
| `/verify` | public bulk GTIN verification |
| `/products.json` | the public JSON API with its pagination envelope |
| `/01/03000123456789` | the public product page (add `?lang=fr` for French) |
| `/login` | admin login, passphrase `admin` |
| `/products`, `/companies` | the management area, once signed in |

## Preflight: is the module actually completable?

```bash
bash tools/preflight.sh          # fast: everything except the mutant stage
bash tools/preflight.sh --full   # adds the mutant stage
```

Nine steps: the handout has every promised file, boots, seeds and passes its example tests; the
handout runs over HTTP; the **working build answers exactly the statuses the specification
documents**; the passphrase login can be passed; all eight deliverable paths are writable; a coverage
driver is present (a warning, not a failure — the task has a documented fallback); every seeded
defect maps to a rule that exists in `docs/spec.md` and to a real test in the model answer; and a
suite written from the specification alone finds every defect without a single false positive.

Current result: **52 checks pass, 0 fail**, one warning (no Xdebug/PCOV on this machine).
