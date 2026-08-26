<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Gtin;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Public facing pages (R8, R9): bulk GTIN verification and the single product page.
 */
class PublicController extends Controller
{
    public function verifyForm(): View
    {
        return view('public.verify', ['results' => null, 'input' => '']);
    }

    public function verify(Request $request): View
    {
        $codes = Gtin::splitBulkInput($request->input('gtins'));

        $registered = Product::query()
            ->visible()
            ->whereIn('gtin', $codes)
            ->pluck('gtin')
            ->all();

        $results = collect($codes)->map(fn (string $code) => [
            'gtin' => $code,
            'valid' => in_array($code, $registered, true),
        ])->all();

        return view('public.verify', [
            'results' => $results,
            'input' => (string) $request->input('gtins'),
            'allValid' => $results !== [] && collect($results)->contains(fn (array $row) => $row['valid']),
        ]);
    }

    /**
     * Public product page. `$prefix` is the static "01" segment from the brief.
     * A hidden product is not public.
     */
    public function product(Request $request, string $prefix, string $gtin): View
    {
        $product = Product::query()->with('company')->visible()->where('gtin', $gtin)->firstOrFail();
        $language = $request->query('lang') === 'fr' ? 'fr' : 'en';

        return view('public.product', [
            'product' => $product,
            'language' => $language,
        ]);
    }
}
