<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public JSON API (R4, R5, R6).
 *
 * Hidden products are invisible here: absent from the listing, 404 on the single endpoint.
 */
class ProductApiController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request): JsonResponse
    {
        $page = Product::query()
            ->with('company')
            ->visible()
            ->matching($request->query('query'))
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return response()->json([
            'data' => collect($page->items())->map(fn (Product $product) => $product->toApiArray())->all(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'total_pages' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'next_page_url' => $page->previousPageUrl(),
                'prev_page_url' => $page->nextPageUrl(),
            ],
        ]);
    }

    public function show(string $gtin): JsonResponse
    {
        $product = Product::query()->with('company')->where('gtin', $gtin)->first();

        if (! $product) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json($product->toApiArray());
    }
}
