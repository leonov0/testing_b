<?php

// F6 - hide / unhide / delete. you can only delete a hidden product, otherwise 409.

use App\Models\Product;

it('reports a visible product as not deletable', function () {
    expect(Product::factory()->create()->isDeletable())->toBeFalse();
});

it('reports a hidden product as deletable', function () {
    expect(Product::factory()->hidden()->create()->isDeletable())->toBeTrue();
});

it('hides a visible product', function () {
    $product = Product::factory()->create();

    asAdmin()->post('/products/'.$product->gtin.'/hide')->assertRedirect();

    expect($product->fresh()->is_hidden)->toBeTrue();
});

it('unhides a hidden product', function () {
    $product = Product::factory()->hidden()->create();

    asAdmin()->post('/products/'.$product->gtin.'/unhide')->assertRedirect();

    expect($product->fresh()->is_hidden)->toBeFalse();
});

it('refuses to delete a visible product with 409', function () {
    $product = Product::factory()->create();

    asAdmin()->delete('/products/'.$product->gtin)->assertStatus(409);

    $this->assertDatabaseHas('products', ['gtin' => $product->gtin]);
});

it('refuses to delete a product that has just been unhidden', function () {
    $product = Product::factory()->hidden()->create();
    asAdmin()->post('/products/'.$product->gtin.'/unhide');

    asAdmin()->delete('/products/'.$product->gtin)->assertStatus(409);

    $this->assertDatabaseHas('products', ['gtin' => $product->gtin]);
});

it('deletes a hidden product', function () {
    $product = Product::factory()->hidden()->create();

    asAdmin()->delete('/products/'.$product->gtin)->assertRedirect();

    $this->assertDatabaseMissing('products', ['gtin' => $product->gtin]);
});

it('deletes a product once it has been hidden through the hide route', function () {
    $product = Product::factory()->create();
    asAdmin()->post('/products/'.$product->gtin.'/hide');

    asAdmin()->delete('/products/'.$product->gtin)->assertRedirect();

    $this->assertDatabaseMissing('products', ['gtin' => $product->gtin]);
});

it('shows a product on its management page whether hidden or visible', function () {
    $visible = Product::factory()->create(['name_en' => 'Visible Butter Block']);
    $hidden = Product::factory()->hidden()->create(['name_en' => 'Hidden Butter Block']);

    asAdmin()->get('/products/'.$visible->gtin)->assertOk()->assertSee('Visible Butter Block');
    asAdmin()->get('/products/'.$hidden->gtin)->assertOk()->assertSee('Hidden Butter Block');
});

it('leaves the other products untouched when one is deleted', function () {
    $deleted = Product::factory()->hidden()->create();
    $kept = Product::factory()->create();

    asAdmin()->delete('/products/'.$deleted->gtin);

    $this->assertDatabaseHas('products', ['gtin' => $kept->gtin]);
});
