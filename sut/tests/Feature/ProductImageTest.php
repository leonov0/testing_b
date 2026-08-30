<?php

// F7 - product images. max 2 MB, the app makes the path, replacing an image deletes the old file.

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('stores an uploaded image', function () {
    asAdmin()->post('/products', productPayload([
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('butter.jpg', 200, 200),
    ]))->assertRedirect();

    $product = Product::query()->firstWhere('gtin', '3000123456789');

    expect($product->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($product->image_path);
});

it('stores the uploaded image under a generated path in the image directory', function () {
    asAdmin()->post('/products', productPayload([
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('butter.jpg', 200, 200),
    ]));

    $path = Product::query()->firstWhere('gtin', '3000123456789')->image_path;

    expect($path)->toStartWith('product-images/')
        ->and($path)->not->toContain('butter')
        ->and($path)->not->toContain('..');
});

it('refuses a file that is not an image', function () {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload([
            'gtin' => '3000123456789',
            'image' => UploadedFile::fake()->create('payload.php', 8, 'text/x-php'),
        ]))
        ->assertSessionHasErrors('image');

    expect(Product::query()->count())->toBe(0);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('refuses an image heavier than 2 MB', function () {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload([
            'gtin' => '3000123456789',
            'image' => UploadedFile::fake()->image('huge.jpg')->size(4096),
        ]))
        ->assertSessionHasErrors('image');

    expect(Product::query()->count())->toBe(0);
});

it('accepts an image well under 2 MB', function () {
    asAdmin()->post('/products', productPayload([
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('butter.jpg')->size(512),
    ]))->assertRedirect();

    $this->assertDatabaseHas('products', ['gtin' => '3000123456789']);
});

it('removes the previous file when the image is replaced', function () {
    $product = Product::factory()->create(['gtin' => '3000123456789']);
    asAdmin()->put('/products/'.$product->gtin, productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('first.jpg', 200, 200),
    ]));
    $first = $product->fresh()->image_path;

    asAdmin()->put('/products/'.$product->gtin, productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('second.jpg', 200, 200),
    ]));
    $second = $product->fresh()->image_path;

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

it('keeps the stored image when a product is updated without a new file', function () {
    $product = Product::factory()->create(['gtin' => '3000123456789']);
    asAdmin()->put('/products/'.$product->gtin, productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('first.jpg', 200, 200),
    ]));
    $path = $product->fresh()->image_path;

    asAdmin()->put('/products/'.$product->gtin, productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'name_en' => 'Renamed Butter',
    ]));

    expect($product->fresh()->image_path)->toBe($path);
    Storage::disk('public')->assertExists($path);
});

it('removes the image on request and deletes the file', function () {
    $product = Product::factory()->create(['gtin' => '3000123456789']);
    asAdmin()->put('/products/'.$product->gtin, productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'image' => UploadedFile::fake()->image('first.jpg', 200, 200),
    ]));
    $path = $product->fresh()->image_path;

    asAdmin()->post('/products/'.$product->gtin.'/remove-image')->assertRedirect();

    expect($product->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('falls back to the placeholder image on the public page when no image is uploaded', function () {
    $product = Product::factory()->create(['image_path' => null]);

    $this->get('/01/'.$product->gtin)
        ->assertOk()
        ->assertSee('product-placeholder', false);
});

it('shows the uploaded image on the public page', function () {
    $product = Product::factory()->create(['image_path' => 'product-images/generated-name.jpg']);

    $this->get('/01/'.$product->gtin)
        ->assertOk()
        ->assertSee('product-images/generated-name.jpg', false)
        ->assertDontSee('product-placeholder', false);
});

it('deletes the stored image when the product is deleted', function () {
    $product = Product::factory()->hidden()->create(['gtin' => '3000123456789']);
    Storage::disk('public')->put('product-images/generated-name.jpg', 'x');
    $product->forceFill(['image_path' => 'product-images/generated-name.jpg'])->save();

    asAdmin()->delete('/products/'.$product->gtin);

    Storage::disk('public')->assertMissing('product-images/generated-name.jpg');
});
