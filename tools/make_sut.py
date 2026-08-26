#!/usr/bin/env python3
"""Re-derives the SUT build (what the competitor receives) from the REF build.

SUT = REF application code + 16 seeded defects (8 functional, 8 security).
Everything the competitor keeps (tests/, docs/, .env) lives only in sut/.

Run after any change to ref/:
    python3 tools/make_sut.py && python3 tools/make_mutants.py && bash tools/verify.sh
"""
import pathlib
import shutil
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
REF = ROOT / 'ref'
SUT = ROOT / 'sut'

COPY_DIRS = ['app', 'bootstrap', 'config', 'database', 'routes', 'resources', 'public']
COPY_FILES = ['artisan', 'composer.json', 'composer.lock', 'phpunit.xml', 'vite.config.js', 'package.json', '.env', '.env.example']

DEFECTS = [
    # ---- functional ----------------------------------------------------------------
    ('B-D01', 'app/Services/Gtin.php',
     'a 12 digit code passes GTIN validation',
     "        return preg_match('/^\\d{13,14}$/', (string) $gtin) === 1;",
     "        return preg_match('/^\\d{12,14}$/', (string) $gtin) === 1;"),

    ('B-D02', 'app/Models/Company.php',
     'deactivating a company no longer hides its products',
     """        $this->forceFill(['deactivated' => true])->save();
        $this->products()->update(['is_hidden' => true]);""",
     """        $this->forceFill(['deactivated' => true])->save();"""),

    ('B-D03', 'app/Http/Controllers/ProductApiController.php',
     'the API returns 20 products per page instead of 10',
     '    public const PER_PAGE = 10;',
     '    public const PER_PAGE = 20;'),

    ('B-D04', 'app/Http/Controllers/ProductApiController.php',
     'the next and previous page links are swapped',
     """                'next_page_url' => $page->nextPageUrl(),
                'prev_page_url' => $page->previousPageUrl(),""",
     """                'next_page_url' => $page->previousPageUrl(),
                'prev_page_url' => $page->nextPageUrl(),"""),

    ('B-S01', 'app/Models/Product.php',
     'the keyword is concatenated straight into SQL',
     """        // Escape the LIKE wildcards so that "%" is searched for, not used as a wildcard.
        $escaped = str_replace(['\\\\', '%', '_'], ['\\\\\\\\', '\\\\%', '\\\\_'], trim($keyword));
        $like = '%'.$escaped.'%';

        return $query->where(function (Builder $inner) use ($like) {
            foreach (['name_en', 'name_fr', 'description_en', 'description_fr'] as $column) {
                $inner->orWhereRaw(
                    sprintf("LOWER(%s) LIKE LOWER(?) ESCAPE '\\\\'", $column),
                    [$like],
                );
            }
        });""",
     """        $term = trim($keyword);

        return $query->whereRaw(
            "(LOWER(name_en) LIKE LOWER('%".$term."%') OR LOWER(name_fr) LIKE LOWER('%".$term."%')"
            ." OR LOWER(description_en) LIKE LOWER('%".$term."%') OR LOWER(description_fr) LIKE LOWER('%".$term."%'))"
        );"""),

    # B-D05 is expressed on top of B-S01: both rewrite scopeMatching, so the order matters.
    ('B-D05', 'app/Models/Product.php',
     'the keyword search ignores the French fields',
     ("            \"(LOWER(name_en) LIKE LOWER('%\".$term.\"%') OR LOWER(name_fr) LIKE LOWER('%\".$term.\"%')\"\n"
      "            .\" OR LOWER(description_en) LIKE LOWER('%\".$term.\"%') OR LOWER(description_fr) LIKE LOWER('%\".$term.\"%'))\""),
     ("            \"(LOWER(name_en) LIKE LOWER('%\".$term.\"%')\"\n"
      "            .\" OR LOWER(description_en) LIKE LOWER('%\".$term.\"%'))\"")),

    ('B-D06', 'app/Models/Product.php',
     'a visible product can be deleted permanently',
     '        return $this->is_hidden === true;',
     '        return true;'),

    ('B-D07', 'app/Http/Controllers/PublicController.php',
     'the all valid banner appears when only some codes are valid',
     "            'allValid' => $results !== [] && collect($results)->every(fn (array $row) => $row['valid']),",
     "            'allValid' => $results !== [] && collect($results)->contains(fn (array $row) => $row['valid']),"),

    ('B-D08', 'app/Http/Controllers/PublicController.php',
     'the public product page ignores the language parameter',
     "        $language = $request->query('lang') === 'fr' ? 'fr' : 'en';",
     "        $language = 'en';"),

    # ---- security ------------------------------------------------------------------
    ('B-S02', 'app/Models/Product.php',
     'is_hidden became a client-writable attribute',
     """        'weight_unit',
    ];""",
     """        'weight_unit',
        'is_hidden',
    ];"""),

    ('B-S03', 'app/Http/Middleware/RequireAdminSession.php',
     'the management gate redirects instead of answering 401',
     """            return $request->expectsJson()
                ? response()->json(['error' => 'unauthenticated'], 401)
                : response()->view('errors.unauthenticated', [], 401);""",
     """            return redirect('/login');"""),

    ('B-S04', 'app/Http/Requests/StoreProductRequest.php',
     'any file is accepted as a product image',
     "            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],",
     "            'image' => ['sometimes', 'nullable', 'file'],"),

    ('B-S05', 'app/Http/Controllers/AuthController.php',
     'login has no rate limiting and does not regenerate the session',
     """        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()->withErrors(['passphrase' => 'Too many attempts. Try again later.'], 'default');
        }

        RateLimiter::hit($key, 60);

        if (! hash_equals(config('catalogue.admin_passphrase'), (string) $request->input('passphrase'))) {
            return back()->withErrors(['passphrase' => 'The passphrase is incorrect.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put(RequireAdminSession::SESSION_KEY, true);""",
     """        if (config('catalogue.admin_passphrase') !== (string) $request->input('passphrase')) {
            return back()->withErrors(['passphrase' => 'The passphrase is incorrect.']);
        }

        $request->session()->put(RequireAdminSession::SESSION_KEY, true);"""),

    ('B-S06', 'app/Http/Controllers/ProductApiController.php',
     'the single product endpoint serves hidden products',
     "        $product = Product::query()->with('company')->visible()->where('gtin', $gtin)->first();",
     "        $product = Product::query()->with('company')->where('gtin', $gtin)->first();"),

    ('B-S07', 'resources/views/public/product.blade.php',
     'the public product page renders the name and description unescaped',
     """    <h1>{{ $name }}</h1>""",
     """    <h1>{!! $name !!}</h1>"""),

    ('B-S08', 'bootstrap/app.php',
     'failures fall through to the framework renderer and leak internals',
     """        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($exception instanceof ValidationException) {
                return null; // Laravel already answers with the field errors.
            }

            $status = match (true) {
                $exception instanceof ModelNotFoundException,
                $exception instanceof NotFoundHttpException => 404,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };

            $code = match ($status) {
                401 => 'unauthenticated',
                403 => 'forbidden',
                404 => 'not_found',
                409 => 'conflict',
                default => $status >= 500 ? 'server_error' : 'request_failed',
            };

            if ($request->expectsJson() || $request->is('*.json')) {
                return response()->json(['error' => $code], $status);
            }

            return response()->view('errors.generic', ['status' => $status, 'code' => $code], $status);
        });""",
     """        //"""),
]

# The XSS defect must also reach the admin views.
EXTRA_DEFECT_EDITS = [
    # B-S02 only bites when the controller also stops filtering the request.
    ('B-S02', 'app/Http/Controllers/ProductController.php',
     """        $product = new Product;
        $product->fill($request->safe()->except('image'));
        $product->is_hidden = false;""",
     """        $product = new Product;
        $product->fill($request->except('image'));"""),
    ('B-S02', 'app/Http/Controllers/ProductController.php',
     "        $product->fill($request->safe()->except('image'));",
     "        $product->fill($request->except('image'));"),

    ('B-S07', 'resources/views/products/show.blade.php',
     "    <h2>{{ $product->name_en }}</h2>",
     "    <h2>{!! $product->name_en !!}</h2>"),
    ('B-S07', 'resources/views/products/show.blade.php',
     "    <p>{{ $product->description_en }}</p>",
     "    <p>{!! $product->description_en !!}</p>"),
]


def refresh_source():
    for name in COPY_DIRS:
        source = REF / name
        target = SUT / name
        if not source.exists():
            continue
        shutil.rmtree(target, ignore_errors=True)
        shutil.copytree(source, target)

    for name in COPY_FILES:
        source = REF / name
        if source.exists():
            shutil.copy2(source, SUT / name)


def apply(defect_id, rel, old, new, description=None):
    path = SUT / rel
    source = path.read_text()
    if old not in source:
        sys.exit(f'{defect_id}: anchor not found in {rel}')
    path.write_text(source.replace(old, new, 1))
    if description:
        print(f'{defect_id} {rel}: {description}')


def main():
    if not SUT.exists():
        sys.exit('sut/ is missing - it holds the competitor tests and docs, restore it first')
    refresh_source()

    for defect_id, rel, description, old, new in DEFECTS:
        apply(defect_id, rel, old, new, description)

    for defect_id, rel, old, new in EXTRA_DEFECT_EDITS:
        apply(defect_id, rel, old, new)

    print(f'{len(DEFECTS)} defects seeded into sut/')


if __name__ == '__main__':
    main()
