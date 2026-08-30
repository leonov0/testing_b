<?php

// U3 - Product::toApiArray(). both languages per field, weight triple, nested company, no internal columns.

use App\Models\Company;
use App\Models\Product;

it('nests the product name in both languages', function () {
    expect(apiProduct()->toApiArray()['name'])
        ->toBe(['en' => 'Salted butter', 'fr' => 'Beurre demi-sel']);
});

it('nests the product description in both languages', function () {
    expect(apiProduct()->toApiArray()['description'])
        ->toBe(['en' => 'Churned in Brittany.', 'fr' => 'Baratte en Bretagne.']);
});

it('reports the weight as a gross, net and unit triple', function () {
    expect(apiProduct()->toApiArray()['weight'])
        ->toBe(['gross' => 0.5, 'net' => 0.45, 'unit' => 'kg']);
});

it('reports the GTIN, brand and country of origin', function () {
    $array = apiProduct()->toApiArray();

    expect($array['gtin'])->toBe('3000123456789')
        ->and($array['brand'])->toBe('Maison Test')
        ->and($array['countryOfOrigin'])->toBe('France');
});

it('nests the owning company', function () {
    expect(apiProduct()->toApiArray()['company'])->toBe(apiCompany()->toApiArray());
});

it('keeps a null description in both languages rather than dropping the key', function () {
    $array = apiProduct(['description_en' => null, 'description_fr' => null])->toApiArray();

    expect($array)->toHaveKey('description')
        ->and($array['description'])->toBe(['en' => null, 'fr' => null]);
});

it('exposes exactly the documented top level keys', function () {
    expect(array_keys(apiProduct()->toApiArray()))
        ->toBe(['name', 'description', 'gtin', 'brand', 'countryOfOrigin', 'weight', 'company']);
});

it('exposes no internal column', function () {
    $encoded = json_encode(apiProduct()->toApiArray());

    expect($encoded)->not->toContain('image_path')
        ->and($encoded)->not->toContain('is_hidden')
        ->and($encoded)->not->toContain('company_id')
        ->and($encoded)->not->toContain('created_at')
        ->and($encoded)->not->toContain('updated_at')
        ->and($encoded)->not->toContain('secret.jpg');
});

it('does not leak the hidden flag of a hidden product', function () {
    $array = apiProduct(['is_hidden' => true])->toApiArray();

    expect($array)->not->toHaveKey('is_hidden');
});
