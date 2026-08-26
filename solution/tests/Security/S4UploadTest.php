<?php

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// S4 - product image uploads are validated and stored under a generated path.

beforeEach(fn () => Storage::fake('public'));

function updatePayload(Product $product, array $extra = []): array
{
    return array_merge($product->only([
        'company_id', 'gtin', 'name_en', 'name_fr', 'brand', 'country_of_origin',
        'weight_gross', 'weight_net', 'weight_unit',
    ]), $extra);
}

it('rejects an executable disguised as an upload', function () {
    $product = Product::factory()->create();

    asAdmin()->put('/products/'.$product->gtin, updatePayload($product, [
        'image' => UploadedFile::fake()->create('shell.php', 8, 'application/x-php'),
    ]))->assertSessionHasErrors(['image']);

    expect($product->fresh()->image_path)->toBeNull();
});

it('rejects an image with a double extension', function () {
    $product = Product::factory()->create();

    asAdmin()->put('/products/'.$product->gtin, updatePayload($product, [
        'image' => UploadedFile::fake()->create('shell.php.jpg', 8, 'text/plain'),
    ]))->assertSessionHasErrors(['image']);
});

it('rejects an image larger than 2 MB', function () {
    $product = Product::factory()->create();

    asAdmin()->put('/products/'.$product->gtin, updatePayload($product, [
        'image' => UploadedFile::fake()->image('huge.jpg')->size(2049),
    ]))->assertSessionHasErrors(['image']);
});

it('never stores the client supplied file name as the path', function () {
    $product = Product::factory()->create();

    asAdmin()->put('/products/'.$product->gtin, updatePayload($product, [
        'image' => UploadedFile::fake()->image('../../evil name.jpg'),
    ]))->assertRedirect();

    $stored = $product->fresh()->image_path;

    expect($stored)->not->toBeNull()
        ->and($stored)->not->toContain('..')
        ->and($stored)->not->toContain(' ')
        ->and($stored)->toStartWith('product-images/');
});

it('keeps the stored file inside the image directory', function () {
    $product = Product::factory()->create();

    asAdmin()->put('/products/'.$product->gtin, updatePayload($product, [
        'image' => UploadedFile::fake()->image('bottle.jpg'),
    ]))->assertRedirect();

    $stored = $product->fresh()->image_path;
    $root = rtrim(Storage::disk('public')->path(''), DIRECTORY_SEPARATOR);
    $full = realpath(Storage::disk('public')->path($stored));

    expect($full)->toStartWith($root.DIRECTORY_SEPARATOR);
});

it('replaces the previous file instead of leaving it behind', function () {
    $product = Product::factory()->create();

    asAdmin()->put('/products/'.$product->gtin, updatePayload($product, [
        'image' => UploadedFile::fake()->image('first.jpg'),
    ]));
    $first = $product->fresh()->image_path;

    asAdmin()->put('/products/'.$product->gtin, updatePayload($product, [
        'image' => UploadedFile::fake()->image('second.jpg'),
    ]));

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($product->fresh()->image_path);
});
