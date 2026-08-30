<?php

// S4 - image upload. images only, max 2 MB, path comes from the app not the file name, old file removed on replace.

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('refuses an executable script uploaded as a product image', function () {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload([
            'gtin' => '3000123456789',
            'image' => UploadedFile::fake()->create('shell.php', 4, 'application/x-httpd-php'),
        ]))
        ->assertSessionHasErrors('image');

    expect(Product::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('refuses an html file uploaded as a product image', function () {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload([
            'gtin' => '3000123456789',
            'image' => UploadedFile::fake()->create('page.html', 4, 'text/html'),
        ]))
        ->assertSessionHasErrors('image');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('refuses a script disguised with a double extension', function () {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload([
            'gtin' => '3000123456789',
            'image' => UploadedFile::fake()->create('portrait.jpg.php', 4, 'application/x-httpd-php'),
        ]))
        ->assertSessionHasErrors('image');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('refuses a file heavier than 2 MB', function () {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload([
            'gtin' => '3000123456789',
            'image' => UploadedFile::fake()->image('huge.jpg')->size(4096),
        ]))
        ->assertSessionHasErrors('image');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('never stores an uploaded file under the name the client submitted', function () {
    asAdmin()->post('/products', productPayload([
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('client-chosen-name.jpg', 100, 100),
    ]));

    $path = Product::query()->firstWhere('gtin', '3000123456789')?->image_path;

    expect($path)->not->toBeNull()
        ->and($path)->not->toContain('client-chosen-name');
});

it('stores an uploaded image inside the image directory', function () {
    asAdmin()->post('/products', productPayload([
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('butter.jpg', 100, 100),
    ]));

    $path = Product::query()->firstWhere('gtin', '3000123456789')?->image_path;

    expect($path)->toStartWith('product-images/')
        ->and($path)->not->toContain('..')
        ->and(substr_count((string) $path, '/'))->toBe(1);
    Storage::disk('public')->assertExists($path);
});

it('does not let a traversing file name escape the image directory', function () {
    asAdmin()->post('/products', productPayload([
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('../../escape.jpg', 100, 100),
    ]));

    $product = Product::query()->firstWhere('gtin', '3000123456789');

    if ($product?->image_path) {
        $absolute = Storage::disk('public')->path($product->image_path);
        $directory = Storage::disk('public')->path('product-images');

        expect($product->image_path)->not->toContain('..')
            ->and(str_starts_with($absolute, rtrim($directory, '/').'/'))->toBeTrue();
    }

    expect(Storage::disk('public')->allFiles())
        ->each(fn ($file) => $file->toStartWith('product-images/'));
});

it('does not keep the previous file when an image is replaced', function () {
    $product = Product::factory()->create(['gtin' => '3000123456789']);

    asAdmin()->put('/products/3000123456789', productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('first.jpg', 100, 100),
    ]));
    $first = $product->fresh()->image_path;

    asAdmin()->put('/products/3000123456789', productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('second.jpg', 100, 100),
    ]));

    Storage::disk('public')->assertMissing($first);
    expect(Storage::disk('public')->allFiles())->toHaveCount(1);
});

it('stores nothing when the submission is rejected for another reason', function () {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload([
            'gtin' => 'not-a-gtin',
            'image' => UploadedFile::fake()->image('butter.jpg', 100, 100),
        ]))
        ->assertSessionHasErrors('gtin');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});
