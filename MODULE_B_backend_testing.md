# Test Project — Module B: Made in France Products Management — Back-end Test Suite

Web Technologies (Skill 17) — test-only training module
Duration: **3 hours** · Stack: **PHP 8.3+ / Laravel 12 / Pest 3 / SQLite (in-memory for the suite)**
Application under test: **WSC2024 Skill 17, Module B — Products Management**
(`WSC2024_TP17_MB_actual_en.pdf`)
Maps to WSOS: Section 5 Back-end development (40%), Section 2 (5%), Section 1 (5%)

---

## Introduction

The made-in-France products management system from the Lyon 2024 competition has been built and
delivered. It manages companies and their products, publishes a JSON API, and serves two public
pages: bulk GTIN verification and the single product page. It was never tested.

You are hired **as the tester, not as the developer**. You receive the running application and its
specification. Your deliverable is an **automated test suite**, a **test plan**, and a
**defect report**.

**You must not modify the application source code.** Not one line. If you find a defect, you prove it
with a failing test and you write it in the defect report — you do not fix it. The source tree is
checksummed before and after the module; a modified checksum scores zero for the whole module.

Your tests assert **the specification in `docs/spec.md`**, never the behaviour you observe in the
running application. Where the application disagrees with the specification, your test is supposed to
go red. That red test is the product you are being paid for.

---

## What you receive

`dl.worldskills.org/XX_module_b_sut.zip` contains:

```
app/            <- application source. READ ONLY.
bootstrap/  config/  database/  routes/  resources/  public/   <- READ ONLY.
tests/          <- YOUR WORK GOES HERE. Three example tests as a style reference.
vendor/         <- installed, Pest included. No internet access is available.
docs/spec.md    <- the specification under test
phpunit.xml     <- the suite runs against an in-memory SQLite database
```

`php artisan test` already runs; the three example tests pass. `php artisan migrate:fresh --seed`
then `php artisan serve` gives you the application in a browser; the admin passphrase is `admin`.

---

## Deliverables

| # | Deliverable | Location |
|---|---|---|
| 1 | Test plan | `test-plan.md` |
| 2 | Unit test suite | `tests/Unit/` |
| 3 | Feature test suite | `tests/Feature/` |
| 4 | Security test suite | `tests/Security/` |
| 5 | Defect report | `defects.md` |
| 6 | Coverage summary | `coverage.txt` |
| 7 | CI pipeline | `.github/workflows/ci.yml` |
| 8 | Runner instructions | `expert_readme.txt` |

---

## The specification under test

The full contract is `docs/spec.md`, taken from the Module B brief. In outline:

| Rule | Subject |
|---|---|
| R1 | GTIN format — any sequence of 13 or 14 digits, unique, validated server side |
| R2 | Deactivating a company marks every one of its products hidden |
| R3 | Companies are never deletable; a product is deletable only while hidden |
| R4 | `GET /products.json` — 10 per page, `pagination` object with next/prev links |
| R5 | Hidden products are invisible to the public: listing, single endpoint, product page, verification |
| R6 | Keyword search across name, name in French, description and description in French |
| R7 | Passphrase admin access; management functions answer **401** without a session |
| R8 | Public bulk GTIN verification page, one row per submitted line, "All valid" only when all are |
| R9 | Public product page in English or French with a matching `lang` attribute |

And a security contract, `S1`–`S8`: no SQL from user input, no client-writable server fields, the 401
gate holds and reveals nothing, image uploads are validated and stored under a generated path, the
passphrase form is rate limited and never echoed, hidden products never leak, stored text is escaped,
failures never leak internals.

---

## Part 1 — Test plan (`test-plan.md`)

Before writing code, write the plan. A table with one row per test case:

```
| ID | Area | Precondition | Steps / request | Expected result (spec ref) | Automated test name |
```

Requirements: at least 40 rows; covers R1–R9, every route, the validation rules and all eight
security requirements; each row names the specification rule it comes from and the test that
automates it.

## Part 2 — Unit tests (`tests/Unit/`)

The pure logic the application exposes, tested without the database or HTTP.

| ID | Subject | Minimum coverage of cases |
|---|---|---|
| U1 | `Gtin::isValidFormat()` | 13 and 14 digits valid, leading zeros kept; 12 and 15 digits, letters, inner and outer spaces, hyphens, plus sign, decimal point, empty string and non-string values invalid |
| U2 | `Gtin::splitBulkInput()` | unix and windows line breaks, trimming, blank lines dropped, order and duplicates kept, empty and null input, invalid entries preserved for reporting |
| U3 | `Product::toApiArray()` | both languages nested per field, the weight triple, the nested company, no internal columns |
| U4 | `Company::toApiArray()` | the documented key names, the nested owner and contact objects |

## Part 3 — Feature tests (`tests/Feature/`)

| ID | Area | Minimum coverage of cases |
|---|---|---|
| F1 | admin access | login page, wrong passphrase, missing passphrase, successful login, logout, **401 on every management route** for both JSON and document requests |
| F2 | companies | active listing, deactivated listing, company page with its products, create, per-field validation, update, no delete route |
| F3 | deactivation cascade | products hidden, other companies untouched, listings move, the API drops them, reactivation does not unhide |
| F4 | product listing and search | admin listing shows hidden products too, each of the four translated fields is searched, case-insensitivity |
| F5 | product creation | 13 and 14 digit GTINs accepted, invalid and duplicate GTINs rejected, every required field, weight rules, unknown company |
| F6 | product lifecycle | hide, unhide, delete refused while visible (409), delete allowed once hidden |
| F7 | images | valid upload stored, non-image rejected, oversize rejected, replacement removes the old file, removal falls back to the placeholder |
| F8 | products JSON | the documented envelope, 10 per page, page two differs, next/prev links and their nulls, query string preserved, keyword filter, hidden excluded from data and totals |
| F9 | single product JSON | the documented shape, 404 unknown, 404 hidden, 404 for a deactivated company's product |
| F10 | public pages | verification rows per line, "All valid" only when all are valid, hidden and unknown codes not valid, blank input; product page fields, language switch, `lang` attribute, placeholder image, 404 for hidden and unknown |

Use factories. Do not depend on seeded rows. Each test must pass or fail identically when run alone
or in a different order.

## Part 4 — Security tests (`tests/Security/`)

One group per requirement `S1`–`S8`, named after it. Each asserts the **secure** behaviour required by
the security contract. Where the application is insecure the test goes red — that is the finding.

Guidance: `S1` sends injection and wildcard payloads through both search entry points and asserts the
result set, the surviving table and the absence of any database error string; `S2` posts extra fields
and asserts the stored row did not take them; `S3` sweeps every management route unauthenticated and
asserts 401, identical status codes for existing and missing records, no record content in the body,
and no data change; `S4` uploads an executable, a double extension and an oversize file, and inspects
the generated path; `S5` drives the passphrase form; `S6` walks every public surface for a hidden
product and for internal column names; `S7` stores payloads and asserts the rendered output is
escaped; `S8` triggers failures and asserts the response carries no trace, path or SQL.

## Part 5 — Defect report (`defects.md`)

Every red test gets a row. The application contains seeded defects across the functional rules, the
API contract and the security contract — finding them is the point of the module. You are not told
how many.

```
| ID | Severity | Area (spec ref) | Steps to reproduce | Expected (spec) | Actual | Failing test |
```

Severity is `blocker` / `major` / `minor`. "Expected" quotes the specification. "Actual" is the
observed response or value, copied exactly. A row without a failing automated test scores nothing; a
failing test without a row scores nothing.

## Part 6 — Supporting deliverables

1. **Coverage** — `php artisan test --coverage`, and commit the summary as `coverage.txt`.
   `App\Services` and `App\Models` must reach ≥ 90% lines, `App\Http` ≥ 75%.
   Coverage needs a driver (Xdebug or PCOV). If the workstation has none, commit the suite
   inventory instead — `./vendor/bin/pest --list-tests > coverage.txt` — and say so in
   `expert_readme.txt`; the percentages then do not apply.
2. **CI** — `.github/workflows/ci.yml` running the three suites as separate steps and failing the job
   on a failing test.
3. **`expert_readme.txt`** — the exact commands to run each suite, the whole suite, and the coverage
   report, plus how to start the application and the admin passphrase.
4. **Git** — one commit per test area, meaningful messages. Not one commit at the end.

---

## Instructions to the Competitor

- **Do not modify anything outside `tests/` and the eight deliverable files.** `app/`, `bootstrap/`,
  `config/`, `database/`, `routes/`, `resources/` and `public/` are read only and are checksummed.
- Assert the specification, never the current behaviour. A test written to make a defect pass is worth
  less than no test at all — see the marking notes.
- Do not delete, skip or weaken the three provided example tests.
- Tests must be deterministic: no dependence on the seeder, on execution order, or on today's date.
  Give text fields explicit values when a test searches for a payload — a faker sentence can
  legitimately contain a quote or a percent sign.
- Every test name must state the behaviour it checks, readable without opening the file.

### How this module is assessed

Your suite is executed against **three builds** of the application. You never see two of them.

| Build | What it is | What your suite must do |
|---|---|---|
| **REF** | the specification-correct implementation | every one of your tests must **pass** |
| **SUT** | the build you were given, containing the seeded defects | your tests must **fail exactly on the defective behaviours** |
| **MUT** | 16 further builds, each with one small mutation applied | each mutation must make at least one of your tests fail |

- A test that fails on **REF** is a **false positive**: it costs marks, because it reports a defect
  that does not exist.
- A defect present in **SUT** that no test of yours catches scores nothing, even if you describe it in
  `defects.md`.
- The 16 mutations touch both GTIN length boundaries, bulk-input trimming and blank-line filtering,
  the searched field list, the visible scope, the delete rule, the deactivation cascade and its
  reactivation counterpart, the page size, the query string on pagination links, two JSON shape
  details, the "All valid" logic, the language switch, and the admin gate.

---

## Trainer package

The runnable package for this module is `../module_b/` — REF build, SUT build (the handout), the
model answer suite (165 tests), the 16 seeded defects, the 16 mutants and the marking harness.
See `../module_b/README.md`.

## Marking Summary

| # | Sub-Criteria | Marks |
|---|---|---|
| 1 | Test plan: coverage of the specification, traceability to rules and to test names | 2.00 |
| 2 | Unit tests U1–U4: pass on REF, mutations caught, determinism | 4.00 |
| 3 | Feature tests F1–F10: contract, validation, pagination, cascade, public pages | 7.00 |
| 4 | Security tests S1–S8: correct secure assertion per requirement | 5.00 |
| 5 | Defect report: seeded defects detected, reproduced and described | 4.00 |
| 6 | Coverage summary, CI pipeline, `expert_readme.txt`, git history | 1.50 |
| | **Total** | **23.50** |

### Expert marking notes

- Sub-criteria 2, 3 and 4 are measured by the REF and MUT runs, not by counting test files.
- Sub-criterion 5 is measured by the SUT run: a seeded defect counts only when a named failing test is
  mapped to a defect row.
- Deduct 0.25 per false positive (a test failing on REF), to a maximum of the sub-criterion's marks.
- Deduct from sub-criterion 3 if any test depends on the seeder or on execution order, or if the suite
  result changes between two consecutive runs.
- Any modification detected in the read-only folders scores the module zero.

## Other

- Provide `expert_readme.txt` even if you use the default commands.
- The assessment workstation runs the CLI and Firefox Developer Edition.
- `withSession(['admin_authenticated' => true])` is the session helper most suites end up needing;
  use `Storage::fake('public')` for anything that touches image uploads, and clear the rate limiter
  when a test depends on the login counter.
