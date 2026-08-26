<?php

use App\Models\Company;
use App\Models\Product;

// U3 / U4 - the JSON shapes documented in the brief, built without touching the database.

function sampleCompany(): Company
{
    return new Company([
        'company_name' => 'Euro Expo',
        'company_address' => 'Boulevard de l\'Europe, 69680 Chassieu, France',
        'company_telephone' => '+33 1 41 56 78 00',
        'company_email' => 'mail.customerservice.hdq@example.com',
        'owner_name' => 'Benjamin Smith',
        'owner_mobile' => '+33 6 12 34 56 78',
        'owner_email' => 'b.smith@example.com',
        'contact_name' => 'Marie Dubois',
        'contact_mobile' => '+33 6 98 76 54 32',
        'contact_email' => 'm.dubois@example.com',
    ]);
}

function sampleProduct(): Product
{
    $product = new Product([
        'gtin' => '03000123456789',
        'name_en' => 'Organic Apple Juice',
        'name_fr' => 'Jus de pomme biologique',
        'description_en' => 'Pressed from organic apples.',
        'description_fr' => 'Pressé à partir de pommes biologiques.',
        'brand' => 'Green Orchard',
        'country_of_origin' => 'France',
        'weight_gross' => 1.1,
        'weight_net' => 1.0,
        'weight_unit' => 'L',
    ]);
    $product->setRelation('company', sampleCompany());

    return $product;
}

it('serialises a company with nested owner and contact objects', function () {
    expect(sampleCompany()->toApiArray())->toBe([
        'companyName' => 'Euro Expo',
        'companyAddress' => 'Boulevard de l\'Europe, 69680 Chassieu, France',
        'companyTelephone' => '+33 1 41 56 78 00',
        'companyEmail' => 'mail.customerservice.hdq@example.com',
        'owner' => [
            'name' => 'Benjamin Smith',
            'mobileNumber' => '+33 6 12 34 56 78',
            'email' => 'b.smith@example.com',
        ],
        'contact' => [
            'name' => 'Marie Dubois',
            'mobileNumber' => '+33 6 98 76 54 32',
            'email' => 'm.dubois@example.com',
        ],
    ]);
});

it('serialises a product with both languages nested per field', function () {
    $array = sampleProduct()->toApiArray();

    expect($array['name'])->toBe(['en' => 'Organic Apple Juice', 'fr' => 'Jus de pomme biologique'])
        ->and($array['description'])->toBe([
            'en' => 'Pressed from organic apples.',
            'fr' => 'Pressé à partir de pommes biologiques.',
        ])
        ->and($array['gtin'])->toBe('03000123456789')
        ->and($array['brand'])->toBe('Green Orchard')
        ->and($array['countryOfOrigin'])->toBe('France');
});

it('serialises the weight as gross, net and unit', function () {
    expect(sampleProduct()->toApiArray()['weight'])->toBe([
        'gross' => 1.1,
        'net' => 1.0,
        'unit' => 'L',
    ]);
});

it('nests the company inside the product output', function () {
    expect(sampleProduct()->toApiArray()['company'])->toBe(sampleCompany()->toApiArray());
});

it('never exposes internal columns in the product output', function () {
    $array = sampleProduct()->toApiArray();

    foreach (['id', 'company_id', 'is_hidden', 'image_path', 'created_at', 'updated_at'] as $internal) {
        expect($array)->not->toHaveKey($internal);
    }
});
