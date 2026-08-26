<?php

use App\Models\Company;
use App\Models\Product;
use Database\Factories\ProductFactory;

// F4 / F5 - product listing, search and creation (R1, R6).

function productPayload(array $overrides = []): array
{
    $company = $overrides['company'] ?? Company::factory()->create();
    unset($overrides['company']);

    return array_merge([
        'company_id' => $company->id,
        'gtin' => ProductFactory::gtin(),
        'name_en' => 'Organic Apple Juice',
        'name_fr' => 'Jus de pomme biologique',
        'description_en' => 'Pressed from organic apples.',
        'description_fr' => 'Pressé à partir de pommes biologiques.',
        'brand' => 'Green Orchard',
        'country_of_origin' => 'France',
        'weight_gross' => 1.1,
        'weight_net' => 1.0,
        'weight_unit' => 'L',
    ], $overrides);
}

it('lists every product, hidden ones included, for the admin', function () {
    Product::factory()->create(['name_en' => 'Visible Juice']);
    Product::factory()->hidden()->create(['name_en' => 'Hidden Juice']);

    asAdmin()->get('/products')
        ->assertOk()
        ->assertSee('Visible Juice')
        ->assertSee('Hidden Juice');
});

it('opens a product by its GTIN', function () {
    $product = Product::factory()->create(['gtin' => '03000123456789', 'name_en' => 'Organic Apple Juice']);

    asAdmin()->get('/products/'.$product->gtin)
        ->assertOk()
        ->assertSee('Organic Apple Juice')
        ->assertSee('03000123456789');
});

it('answers 404 for an unknown GTIN', function () {
    asAdmin()->get('/products/9999999999999')->assertStatus(404);
});

it('searches the english name', function () {
    Product::factory()->create(['name_en' => 'Organic Apple Juice', 'name_fr' => 'x', 'description_en' => 'y', 'description_fr' => 'z']);
    Product::factory()->create(['name_en' => 'Sea Salt', 'name_fr' => 'x', 'description_en' => 'y', 'description_fr' => 'z']);

    asAdmin()->get('/products?query=Apple')->assertOk()->assertSee('Organic Apple Juice')->assertDontSee('Sea Salt');
});

it('searches each of the four translated fields', function () {
    $neutral = [
        'name_en' => 'Neutral name',
        'name_fr' => 'Nom neutre',
        'description_en' => 'Neutral description',
        'description_fr' => 'Description neutre',
    ];

    $marked = [
        'name_en' => 'Marker Apple',
        'name_fr' => 'Marqueur Pomme',
        'description_en' => 'Contains marker orchards',
        'description_fr' => 'Contient marqueur vergers',
    ];

    $created = [];
    foreach ($marked as $field => $value) {
        $created[$field] = Product::factory()->create(array_merge($neutral, [
            $field => $value,
            'gtin' => ProductFactory::gtin(),
        ]));
    }

    // Each keyword lives in exactly one field of exactly one product.
    foreach (['Apple' => 'name_en', 'Pomme' => 'name_fr', 'orchards' => 'description_en', 'vergers' => 'description_fr'] as $keyword => $field) {
        $body = asAdmin()->get('/products?query='.$keyword)->assertOk()->getContent();

        expect($body)->toContain($created[$field]->gtin)
            ->and(substr_count($body, '<li>'))->toBe(1);
    }
});

it('matches the keyword case-insensitively', function () {
    Product::factory()->create(['name_en' => 'Organic Apple Juice']);

    asAdmin()->get('/products?query=apple')->assertOk()->assertSee('Organic Apple Juice');
});

it('creates a product with a 13 digit GTIN', function () {
    asAdmin()->post('/products', productPayload(['gtin' => '3000123456789']))->assertRedirect();

    $this->assertDatabaseHas('products', ['gtin' => '3000123456789', 'is_hidden' => false]);
});

it('creates a product with a 14 digit GTIN', function () {
    asAdmin()->post('/products', productPayload(['gtin' => '03000123456789']))->assertRedirect();

    $this->assertDatabaseHas('products', ['gtin' => '03000123456789']);
});

it('rejects a GTIN that is not 13 or 14 digits', function () {
    foreach (['300012345678', '030001234567890', 'ABCDEFGHIJKLM', ''] as $gtin) {
        asAdmin()->post('/products', productPayload(['gtin' => $gtin]))
            ->assertSessionHasErrors(['gtin']);
    }

    expect(Product::query()->count())->toBe(0);
});

it('rejects a duplicate GTIN', function () {
    $existing = Product::factory()->create();

    asAdmin()->post('/products', productPayload(['gtin' => $existing->gtin]))
        ->assertSessionHasErrors(['gtin']);

    expect(Product::query()->count())->toBe(1);
});

it('requires the mandatory product fields', function () {
    asAdmin()->post('/products', [])
        ->assertSessionHasErrors([
            'company_id', 'gtin', 'name_en', 'name_fr', 'brand',
            'country_of_origin', 'weight_gross', 'weight_net', 'weight_unit',
        ]);
});

it('rejects a weight that is zero or negative', function () {
    asAdmin()->post('/products', productPayload(['weight_gross' => 0]))->assertSessionHasErrors(['weight_gross']);
    asAdmin()->post('/products', productPayload(['weight_net' => -1]))->assertSessionHasErrors(['weight_net']);
});

it('rejects a net weight larger than the gross weight', function () {
    asAdmin()->post('/products', productPayload(['weight_gross' => 1.0, 'weight_net' => 1.5]))
        ->assertSessionHasErrors(['weight_net']);
});

it('rejects a company that does not exist', function () {
    asAdmin()->post('/products', productPayload(['company_id' => 999999]))
        ->assertSessionHasErrors(['company_id']);
});

it('updates a product and keeps its own GTIN valid', function () {
    $product = Product::factory()->create();

    asAdmin()->put('/products/'.$product->gtin, productPayload([
        'gtin' => $product->gtin,
        'company_id' => $product->company_id,
        'name_en' => 'Renamed Juice',
    ]))->assertRedirect();

    expect($product->fresh()->name_en)->toBe('Renamed Juice');
});
