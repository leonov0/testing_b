# Made in France — products management: specification under test

Module B. The application under test implements **WorldSkills Competition 2024, Skill 17,
Module B — Products Management** (`WSC2024_TP17_MB_actual_en.pdf`). This document is the contract
your tests assert. Where the delivered application disagrees with it, your test is supposed to go
red, and the finding belongs in `defects.md`.

The application source (`app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `resources/`,
`public/`) is **read only**. You write only inside `tests/` and the deliverable files listed in the
task document.

Run the suite with `php artisan test` or `./vendor/bin/pest`. Tests use an in-memory SQLite database
(`phpunit.xml`), so there is no database to provision.

---

## 1. Domain

An office administrator manages **companies** and the **made-in-France products** they own. Every
product carries a **GTIN** (Global Trade Item Number). Two pages are public: bulk GTIN verification
and the single product page. The JSON API is public as well.

Factories: `Company::factory()`, `Company::factory()->deactivated()`, `Product::factory()`,
`Product::factory()->hidden()`, and `Database\Factories\ProductFactory::gtin()` for a fresh valid code.

## 2. Business rules

**R1 — GTIN format.** `App\Services\Gtin::isValidFormat(mixed): bool`
Simplified per the brief: any sequence of **13 or 14 digits**, and unique across products. No check
digit is computed. Anything else is invalid — 12 or 15 digits, letters, spaces (leading, trailing or
inside), hyphens, a plus sign, a decimal point, an empty string, a non-string non-integer value.
Validation happens **server side**, after form submission.

**R2 — Deactivating a company.** `App\Models\Company::deactivate()`
Marks the company deactivated **and marks every one of its products hidden**. Deactivated companies
appear in their own listing and disappear from the active listing. `reactivate()` clears the company
flag only — the products stay hidden until they are unhidden one by one.

**R3 — Deletion rules.**
A company can never be deleted through the web interface (`DELETE /companies/{id}` answers `405`).
A product can be deleted permanently **only while it is hidden**; deleting a visible product answers
`409`. `App\Models\Product::isDeletable()` carries the rule.

**R4 — Products JSON pagination.** `GET /products.json`
`{"data": [...], "pagination": {"current_page", "total_pages", "per_page", "next_page_url", "prev_page_url"}}`
`per_page` is **10**. `next_page_url` is null on the last page, `prev_page_url` is null on the first.
Both links carry the current query string.

**R5 — Hidden products are not public.**
A hidden product is absent from `products.json`, is not counted in the pagination totals, answers
`404` on `GET /products/{gtin}.json`, answers `404` on the public product page, and verifies as
**not valid** on the bulk verification page.

**R6 — Keyword search.** `GET /products.json?query=KEYWORD` and `GET /products?query=KEYWORD`
Matches a product whose **name**, **name in French**, **description** or **description in French**
contains the keyword, case-insensitively. `%` and `_` in the keyword are searched for literally, not
treated as wildcards.

**R7 — Admin access.**
`GET /login` shows a passphrase form; the passphrase is `admin`. Management functions answer **401**
without an authenticated session — never a redirect, and never a 404 that would reveal whether a
record exists. `POST /logout` closes the session.

**R8 — Public bulk GTIN verification.** `GET|POST /verify`
A textarea takes GTIN codes separated by line breaks. Each submitted line becomes one result row, in
order, duplicates included; blank lines and surrounding spaces are ignored. A code is **valid** when
it exists in the database and is not hidden. When every submitted code is valid, and only then, an
`All valid` banner appears above the results. An empty submission reports that nothing was submitted.

**R9 — Public product page.** `GET /01/{GTIN}` (the `01` segment is static)
Mobile-friendly page showing company name, product name, GTIN, description, product image, gross
weight with unit and net content weight with unit. The visitor chooses English or French with
`?lang=en|fr`; the `<html lang>` attribute matches the chosen language, and an unknown value falls
back to English. A product without an uploaded image shows the placeholder image.

## 3. Routes

| Method | Path | Access |
|---|---|---|
| GET | `/verify`, POST `/verify` | public |
| GET | `/01/{gtin}` | public |
| GET | `/products.json`, `/products/{gtin}.json` | public |
| GET/POST | `/login`, POST `/logout` | public |
| GET | `/companies`, `/companies/deactivated`, `/companies/new`, `/companies/{id}`, `/companies/{id}/edit` | admin |
| POST | `/companies`, `/companies/{id}/deactivate`, `/companies/{id}/reactivate` | admin |
| PUT | `/companies/{id}` | admin |
| GET | `/products`, `/products/new`, `/products/{gtin}`, `/products/{gtin}/edit` | admin |
| POST | `/products`, `/products/{gtin}/hide`, `/products/{gtin}/unhide`, `/products/{gtin}/remove-image` | admin |
| PUT/DELETE | `/products/{gtin}` | admin |

## 4. Validation

**Company** — every field required: `company_name`, `company_address`, `company_telephone`,
`company_email`, `owner_name`, `owner_mobile`, `owner_email`, `contact_name`, `contact_mobile`,
`contact_email`. The three email fields must be valid addresses.

**Product** — `company_id` required and existing; `gtin` required, valid per R1, unique (ignoring the
product being edited); `name_en`, `name_fr`, `brand`, `country_of_origin`, `weight_unit` required;
`description_en`, `description_fr` optional; `weight_gross` and `weight_net` required, numeric,
greater than zero, and the net weight must not exceed the gross weight; `image` optional, an image
file of at most 2 MB.

Server-set fields — `is_hidden`, `id`, `created_at`, `updated_at`, and the company `deactivated`
flag — are never writable through a submitted form.

## 5. Security contract

| ID | Requirement |
|---|---|
| S1 | The search keyword never reaches the database as concatenated SQL. `'`, `' OR '1'='1`, `' UNION SELECT ...`, `'; DROP TABLE products; --` and `%` behave as ordinary search text: they never widen the result set, never damage a table, and never surface a database error. |
| S2 | A submitted form cannot write `is_hidden`, `id`, timestamps, or the company `deactivated` flag. |
| S3 | Every management route answers `401` without a session, for both JSON and document requests; the status code does not reveal whether the record exists, and no record content appears in the body. An unauthenticated write changes nothing. |
| S4 | Product image uploads accept images only, at most 2 MB. The stored path is generated by the application — never the client file name — and always resolves inside the image directory. Replacing an image removes the previous file. |
| S5 | The passphrase is never echoed back, and the configured passphrase never appears in a page. Repeated wrong passphrases are rate limited, and a correct passphrase does not authenticate while the limiter is tripped. |
| S6 | A hidden product is invisible to the public everywhere (see R5), and the API never exposes internal columns — `image_path`, `is_hidden`, `company_id`, timestamps or stored file paths. |
| S7 | Stored free text — product name, description, company name, submitted GTIN codes, the search keyword — is escaped wherever it is rendered. The JSON API is served as `application/json`. |
| S8 | A failed request never returns a stack trace, a framework path, a base path or SQL, whatever `APP_DEBUG` says. A failing JSON request answers JSON. |

## 6. Determinism rules for the suite

- Use the factories; never depend on rows created by the seeder.
- Give text fields explicit values when a test searches for a payload — a faker sentence can
  legitimately contain a quote or a percent sign.
- The session helper the suite usually needs: `test()->withSession(['admin_authenticated' => true])`.
- Use `Storage::fake('public')` for anything that touches image uploads.
- Clear the rate limiter (`RateLimiter::clear('login:127.0.0.1')`) when a test depends on the counter.
