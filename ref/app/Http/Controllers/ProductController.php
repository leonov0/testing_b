<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Product management (admin only).
 */
class ProductController extends Controller
{
    public const IMAGE_DISK = 'public';

    public function index(Request $request): View
    {
        $query = Product::query()->with('company')->matching($request->query('query'));

        return view('products.index', [
            'products' => $query->orderBy('name_en')->get(),
            'keyword' => $request->query('query'),
        ]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product,
            'companies' => Company::query()->orderBy('company_name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = new Product;
        $product->fill($request->safe()->except('image'));
        $product->is_hidden = false;
        $this->storeImage($request, $product);
        $product->save();

        return redirect('/products/'.$product->gtin);
    }

    public function show(Product $product): View
    {
        return view('products.show', ['product' => $product->load('company')]);
    }

    public function edit(Product $product): View
    {
        return view('products.form', [
            'product' => $product,
            'companies' => Company::query()->orderBy('company_name')->get(),
        ]);
    }

    public function update(StoreProductRequest $request, Product $product): RedirectResponse
    {
        $product->fill($request->safe()->except('image'));
        $this->storeImage($request, $product);
        $product->save();

        return redirect('/products/'.$product->gtin);
    }

    public function hide(Product $product): RedirectResponse
    {
        $product->hide();

        return redirect('/products/'.$product->gtin);
    }

    public function unhide(Product $product): RedirectResponse
    {
        $product->unhide();

        return redirect('/products/'.$product->gtin);
    }

    /** Only a hidden product may be deleted (R3). */
    public function destroy(Product $product): RedirectResponse
    {
        if (! $product->isDeletable()) {
            abort(409);
        }

        $this->removeImage($product);
        $product->delete();

        return redirect('/products');
    }

    public function removeImageAction(Product $product): RedirectResponse
    {
        $this->removeImage($product);
        $product->save();

        return redirect('/products/'.$product->gtin);
    }

    private function storeImage(StoreProductRequest $request, Product $product): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $this->removeImage($product);
        $product->image_path = $request->file('image')->store('product-images', self::IMAGE_DISK);
    }

    private function removeImage(Product $product): void
    {
        if ($product->image_path) {
            Storage::disk(self::IMAGE_DISK)->delete($product->image_path);
            $product->image_path = null;
        }
    }
}
