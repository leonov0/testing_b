<?php

// F5 - adding a product + the field validation.

use App\Models\Company;
use App\Models\Product;
use Database\Factories\ProductFactory;

it('creates a product from a 13 digit GTIN', function () {
    asAdmin()->post('/products', productPayload(['gtin' => '3000123456789']))->assertRedirect();

    $this->assertDatabaseHas('products', ['gtin' => '3000123456789', 'name_en' => 'Salted Butter']);
});

it('creates a product from a 14 digit GTIN', function () {
    asAdmin()->post('/products', productPayload(['gtin' => '30001234567890']))->assertRedirect();

    $this->assertDatabaseHas('products', ['gtin' => '30001234567890']);
});

it('keeps the leading zeros of a submitted GTIN', function () {
    asAdmin()->post('/products', productPayload(['gtin' => '0000000000012']));

    $this->assertDatabaseHas('products', ['gtin' => '0000000000012']);
});

it('creates a product that is visible', function () {
    asAdmin()->post('/products', productPayload(['gtin' => '3000123456789']));

    expect(Product::query()->firstWhere('gtin', '3000123456789')->is_hidden)->toBeFalse();
});

it('refuses a GTIN of the wrong shape', function (string $gtin) {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload(['gtin' => $gtin]))
        ->assertSessionHasErrors('gtin');

    expect(Product::query()->count())->toBe(0);
})->with([
    '12 digits' => '300012345678',
    '15 digits' => '300012345678901',
    'letters' => '30001234567AB',
    'inner space' => '300012 3456789',
    'hyphen' => '300-0123456789',
    'plus sign' => '+3000123456789',
    'decimal point' => '300012345678.9',
    'empty' => '',
]);

it('refuses a GTIN that already belongs to another product', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    asAdmin()->from('/products/new')
        ->post('/products', productPayload(['gtin' => '3000123456789']))
        ->assertSessionHasErrors('gtin');

    expect(Product::query()->where('gtin', '3000123456789')->count())->toBe(1);
});

it('refuses a product submission missing a required field', function (string $field) {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload([$field => '']))
        ->assertSessionHasErrors($field);

    expect(Product::query()->count())->toBe(0);
})->with(['company_id', 'gtin', 'name_en', 'name_fr', 'brand', 'country_of_origin', 'weight_unit', 'weight_gross', 'weight_net']);

it('accepts a product without any description', function () {
    asAdmin()->post('/products', productPayload([
        'gtin' => '3000123456789',
        'description_en' => null,
        'description_fr' => null,
    ]))->assertRedirect();

    $this->assertDatabaseHas('products', ['gtin' => '3000123456789']);
});

it('refuses a non numeric weight', function (string $field) {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload([$field => 'heavy']))
        ->assertSessionHasErrors($field);

    expect(Product::query()->count())->toBe(0);
})->with(['weight_gross', 'weight_net']);

it('refuses a weight of zero', function (string $field) {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload([$field => '0']))
        ->assertSessionHasErrors($field);

    expect(Product::query()->count())->toBe(0);
})->with(['weight_gross', 'weight_net']);

it('refuses a negative weight', function (string $field) {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload([$field => '-1']))
        ->assertSessionHasErrors($field);

    expect(Product::query()->count())->toBe(0);
})->with(['weight_gross', 'weight_net']);

it('refuses a net weight heavier than the gross weight', function () {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload(['weight_gross' => '1.000', 'weight_net' => '1.500']))
        ->assertSessionHasErrors('weight_net');

    expect(Product::query()->count())->toBe(0);
});

it('accepts a net weight equal to the gross weight', function () {
    asAdmin()->post('/products', productPayload([
        'gtin' => '3000123456789',
        'weight_gross' => '1.000',
        'weight_net' => '1.000',
    ]))->assertRedirect();

    $this->assertDatabaseHas('products', ['gtin' => '3000123456789']);
});

it('refuses a product for a company that does not exist', function () {
    asAdmin()->from('/products/new')
        ->post('/products', productPayload(['company_id' => 999999]))
        ->assertSessionHasErrors('company_id');

    expect(Product::query()->count())->toBe(0);
});

it('serves the new product form', function () {
    asAdmin()->get('/products/new')->assertOk();
});

it('updates a product keeping its own GTIN', function () {
    $product = Product::factory()->create(['gtin' => '3000123456789', 'name_en' => 'Old Name']);

    asAdmin()->put('/products/'.$product->gtin, productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'name_en' => 'New Name',
    ]))->assertRedirect();

    expect($product->fresh()->name_en)->toBe('New Name');
});

it('refuses an update taking a GTIN that belongs to another product', function () {
    $product = Product::factory()->create(['gtin' => '3000123456789']);
    Product::factory()->create(['gtin' => '3000123456790']);

    asAdmin()->from('/products/'.$product->gtin.'/edit')
        ->put('/products/'.$product->gtin, productPayload([
            'company_id' => $product->company_id,
            'gtin' => '3000123456790',
        ]))
        ->assertSessionHasErrors('gtin');

    expect($product->fresh()->gtin)->toBe('3000123456789');
});
