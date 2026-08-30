<?php

// F9 - GET /products/{gtin}.json

use App\Models\Company;
use App\Models\Product;

it('serves the documented product shape', function () {
    $company = Company::factory()->create(['company_name' => 'Fromagerie Test']);
    $product = Product::factory()->for($company)->create([
        'gtin' => '3000123456789',
        'name_en' => 'Salted Butter',
        'name_fr' => 'Beurre demi-sel',
        'description_en' => 'Churned in Brittany.',
        'description_fr' => 'Baratte en Bretagne.',
        'brand' => 'Maison Test',
        'country_of_origin' => 'France',
        'weight_unit' => 'kg',
    ]);

    $this->getJson('/products/3000123456789.json')
        ->assertOk()
        ->assertJsonPath('gtin', '3000123456789')
        ->assertJsonPath('name.en', 'Salted Butter')
        ->assertJsonPath('name.fr', 'Beurre demi-sel')
        ->assertJsonPath('description.en', 'Churned in Brittany.')
        ->assertJsonPath('description.fr', 'Baratte en Bretagne.')
        ->assertJsonPath('brand', 'Maison Test')
        ->assertJsonPath('countryOfOrigin', 'France')
        ->assertJsonPath('weight.unit', 'kg')
        ->assertJsonPath('company.companyName', 'Fromagerie Test');
});

it('exposes exactly the documented top level keys', function () {
    $product = Product::factory()->create(['gtin' => '3000123456789']);

    $body = $this->getJson('/products/3000123456789.json')->assertOk()->json();

    expect(array_keys($body))
        ->toBe(['name', 'description', 'gtin', 'brand', 'countryOfOrigin', 'weight', 'company']);
});

it('reports the gross and net weight of the product', function () {
    Product::factory()->create(['gtin' => '3000123456789', 'weight_gross' => 1.25, 'weight_net' => 0.75, 'weight_unit' => 'kg']);

    $weight = $this->getJson('/products/3000123456789.json')->json('weight');

    expect((float) $weight['gross'])->toBe(1.25)
        ->and((float) $weight['net'])->toBe(0.75)
        ->and($weight['unit'])->toBe('kg');
});

it('nests the owning company with its owner and contact', function () {
    $company = Company::factory()->create(['owner_name' => 'Owner Test', 'contact_name' => 'Contact Test']);
    Product::factory()->for($company)->create(['gtin' => '3000123456789']);

    $this->getJson('/products/3000123456789.json')
        ->assertJsonPath('company.owner.name', 'Owner Test')
        ->assertJsonPath('company.contact.name', 'Contact Test');
});

it('answers 404 for a GTIN that is not registered', function () {
    $this->getJson('/products/9999999999999.json')->assertStatus(404);
});

it('answers 404 for a hidden product', function () {
    $product = Product::factory()->hidden()->create(['gtin' => '3000123456789']);

    $this->getJson('/products/3000123456789.json')->assertStatus(404);
});

it('answers 404 for a product of a deactivated company', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create(['gtin' => '3000123456789']);
    $company->deactivate();

    $this->getJson('/products/3000123456789.json')->assertStatus(404);
});

it('serves a single product without a session', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    $this->getJson('/products/3000123456789.json')->assertOk();
});

it('serves a single product as JSON', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    $this->get('/products/3000123456789.json')
        ->assertOk()
        ->assertHeader('content-type', 'application/json');
});

it('answers a JSON body when the product is unknown', function () {
    $this->get('/products/9999999999999.json')
        ->assertStatus(404)
        ->assertHeader('content-type', 'application/json');
});
