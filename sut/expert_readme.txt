Module B - back end test suite
==============================
How to run it on the assessment machine.

WHAT IS HERE
------------
  test-plan.md              the test plan, 162 rows, linked to spec rules and test names
  tests/Unit/               U1-U4  - plain logic, no db, no http
  tests/Feature/            F1-F10 - http + sqlite in memory
  tests/Security/           S1-S8  - one file per security rule
  defects.md                the defect report, 16 defects, each with the test that catches it
  coverage.txt              coverage summary, see COVERAGE below
  .github/workflows/ci.yml  CI, the three suites as separate steps
  expert_readme.txt         this file

Nothing else was touched. app/, bootstrap/, config/, database/, routes/, resources/ and public/
are the same bytes as in the handout.

WHAT YOU NEED
-------------
  PHP 8.3+ with the sqlite3 and pdo_sqlite extensions, plus the vendor/ folder from the handout.
  No internet and no database server needed: phpunit.xml points the suite at an sqlite database
  in memory.

HOW TO RUN
----------
  Everything:
      cd <this directory>
      ./vendor/bin/pest
      # or: php artisan test

  One suite at a time:
      ./vendor/bin/pest --testsuite Unit
      ./vendor/bin/pest --testsuite Feature
      ./vendor/bin/pest --testsuite Security

  One file, or one test:
      ./vendor/bin/pest tests/Feature/ProductsJsonTest.php
      ./vendor/bin/pest --filter="it reports a page size of ten"

  JUnit report (what the marking harness reads):
      ./vendor/bin/pest --log-junit results.xml

  Order does not matter. Every test builds its own rows with the factories and runs on
  RefreshDatabase, so one file, the whole suite, or the suites in any order give the same result.
  Nothing depends on the seeder or on today's date.

COVERAGE
--------
  This machine has no coverage driver (php -m shows no xdebug and no pcov), so
  `php artisan test --coverage` cannot give percentages. Part 6.1 of the task document allows a
  fallback, so coverage.txt holds the list of tests instead:

      ./vendor/bin/pest --list-tests > coverage.txt

  That means the thresholds (App\Services and App\Models >= 90%, App\Http >= 75%) do not apply
  here. On a machine that has a driver the real report is:

      php artisan test --coverage
      # or, for the per file table:
      XDEBUG_MODE=coverage ./vendor/bin/pest --coverage

STARTING THE APP
----------------
      php artisan migrate:fresh --seed
      php artisan storage:link
      php artisan serve            # http://127.0.0.1:8000

  Admin passphrase: admin      (config/catalogue.php, ADMIN_PASSPHRASE in .env)

  Pages worth opening by hand:
      /verify                  public bulk GTIN check
      /products.json           the public JSON api and its pagination block
      /01/<gtin>               the public product page (add ?lang=fr for French)
      /login                   admin login
      /products, /companies    the admin area, after login

WHAT THE RESULT MEANS
---------------------
  86 of the 352 tests fail on the delivered build. Every failure is a finding: it maps to a row of
  defects.md and nothing is left over. D-01 to D-08 are functional and api, S-01 to S-08 are
  security.

  The red tests are the deliverable. They check docs/spec.md, never the behaviour of the delivered
  build, and no test was softened to make a defect pass.
