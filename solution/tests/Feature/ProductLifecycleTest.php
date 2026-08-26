<?php

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// F6 / F7 - hiding, deleting and images (R3).

it('hides a product', function () {
    $product = Product::factory()->create();

    asAdmin()->post('/products/'.$product->gtin.'/hide')->assertRedirect();

    expect($product->fresh()->is_hidden)->toBeTrue();
});

it('unhides a product', function () {
    $product = Product::factory()->hidden()->create();

    asAdmin()->post('/products/'.$product->gtin.'/unhide')->assertRedirect();

    expect($product->fresh()->is_hidden)->toBeFalse();
});

it('refuses to delete a visible product', function () {
    $product = Product::factory()->create();

    asAdmin()->delete('/products/'.$product->gtin)->assertStatus(409);

    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

it('deletes a hidden product permanently', function () {
    $product = Product::factory()->hidden()->create();

    asAdmin()->delete('/products/'.$product->gtin)->assertRedirect('/products');

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

it('stores an uploaded product image outside the source tree', function () {
    Storage::fake('public');
    $product = Product::factory()->create();

    asAdmin()->put('/products/'.$product->gtin, array_merge($product->only([
        'company_id', 'gtin', 'name_en', 'name_fr', 'brand', 'country_of_origin',
        'weight_gross', 'weight_net', 'weight_unit',
    ]), ['image' => UploadedFile::fake()->image('bottle.jpg')]), ['Accept' => 'application/json'])
        ->assertRedirect();

    $stored = $product->fresh()->image_path;
    expect($stored)->not->toBeNull();
    Storage::disk('public')->assertExists($stored);
});

it('rejects a file that is not an image', function () {
    Storage::fake('public');
    $product = Product::factory()->create();

    asAdmin()->put('/products/'.$product->gtin, array_merge($product->only([
        'company_id', 'gtin', 'name_en', 'name_fr', 'brand', 'country_of_origin',
        'weight_gross', 'weight_net', 'weight_unit',
    ]), ['image' => UploadedFile::fake()->create('payload.php', 8, 'application/x-php')]))
        ->assertSessionHasErrors(['image']);

    expect($product->fresh()->image_path)->toBeNull();
});

it('removes an uploaded image and falls back to the placeholder', function () {
    Storage::fake('public');
    $product = Product::factory()->create(['image_path' => 'product-images/bottle.jpg']);
    Storage::disk('public')->put('product-images/bottle.jpg', 'x');

    asAdmin()->post('/products/'.$product->gtin.'/remove-image')->assertRedirect();

    expect($product->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing('product-images/bottle.jpg');
    asAdmin()->get('/products/'.$product->gtin)->assertSee('product-placeholder', false);
});
