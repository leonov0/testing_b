<?php

use App\Models\Company;
use App\Models\Product;
use Database\Factories\ProductFactory;

// S2 - a submitted form cannot write a field it does not own.

function payloadFor(Company $company, array $overrides = []): array
{
    return array_merge([
        'company_id' => $company->id,
        'gtin' => ProductFactory::gtin(),
        'name_en' => 'Organic Apple Juice',
        'name_fr' => 'Jus de pomme biologique',
        'description_en' => 'Pressed from organic apples.',
        'description_fr' => 'Presse de pommes biologiques.',
        'brand' => 'Green Orchard',
        'country_of_origin' => 'France',
        'weight_gross' => 1.1,
        'weight_net' => 1.0,
        'weight_unit' => 'L',
    ], $overrides);
}

it('ignores an is_hidden flag submitted with a new product', function () {
    $company = Company::factory()->create();

    asAdmin()->post('/products', payloadFor($company, ['is_hidden' => true]))->assertRedirect();

    expect(Product::query()->latest('id')->first()->is_hidden)->toBeFalse();
});

it('ignores an is_hidden flag submitted with an update', function () {
    $product = Product::factory()->hidden()->create();

    asAdmin()->put('/products/'.$product->gtin, payloadFor($product->company, [
        'gtin' => $product->gtin,
        'is_hidden' => false,
    ]))->assertRedirect();

    expect($product->fresh()->is_hidden)->toBeTrue();
});

it('ignores an id submitted with a new product', function () {
    $company = Company::factory()->create();

    asAdmin()->post('/products', payloadFor($company, ['id' => 4242]))->assertRedirect();

    expect(Product::query()->where('id', 4242)->exists())->toBeFalse();
});

it('ignores timestamps submitted with a new product', function () {
    $company = Company::factory()->create();

    asAdmin()->post('/products', payloadFor($company, ['created_at' => '1999-01-01 00:00:00']))->assertRedirect();

    expect(Product::query()->latest('id')->first()->created_at->year)->toBeGreaterThan(2000);
});

it('ignores a deactivated flag submitted with a company form', function () {
    asAdmin()->post('/companies', [
        'company_name' => 'Euro Expo',
        'company_address' => 'Chassieu, France',
        'company_telephone' => '+33 1 41 56 78 00',
        'company_email' => 'mail@example.com',
        'owner_name' => 'Benjamin Smith',
        'owner_mobile' => '+33 6 12 34 56 78',
        'owner_email' => 'b.smith@example.com',
        'contact_name' => 'Marie Dubois',
        'contact_mobile' => '+33 6 98 76 54 32',
        'contact_email' => 'm.dubois@example.com',
        'deactivated' => true,
    ])->assertRedirect();

    expect(Company::query()->latest('id')->first()->deactivated)->toBeFalse();
});
