<?php

use App\Models\Company;
use App\Models\Product;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Security');

// unit tests need the app booted so eloquent casts work, but no db and no http here
pest()->extend(Tests\TestCase::class)->in('Unit');

// logged in admin session
function asAdmin(): Tests\TestCase
{
    return test()->withSession(['admin_authenticated' => true]);
}

function apiCompany(array $overrides = []): Company
{
    return (new Company)->forceFill(array_merge([
        'id' => 7,
        'company_name' => 'Fromagerie Test',
        'company_address' => '1 rue du Test, France',
        'company_telephone' => '+33 1 11 11 11 11',
        'company_email' => 'company@example.test',
        'owner_name' => 'Owner Test',
        'owner_mobile' => '+33 6 11 11 11 11',
        'owner_email' => 'owner@example.test',
        'contact_name' => 'Contact Test',
        'contact_mobile' => '+33 6 22 22 22 22',
        'contact_email' => 'contact@example.test',
        'deactivated' => false,
        'created_at' => '2024-01-01 00:00:00',
        'updated_at' => '2024-01-02 00:00:00',
    ], $overrides));
}

function apiProduct(array $overrides = []): Product
{
    $product = (new Product)->forceFill(array_merge([
        'id' => 42,
        'company_id' => 7,
        'gtin' => '3000123456789',
        'name_en' => 'Salted butter',
        'name_fr' => 'Beurre demi-sel',
        'description_en' => 'Churned in Brittany.',
        'description_fr' => 'Baratte en Bretagne.',
        'brand' => 'Maison Test',
        'country_of_origin' => 'France',
        'weight_gross' => 0.5,
        'weight_net' => 0.45,
        'weight_unit' => 'kg',
        'image_path' => 'product-images/secret.jpg',
        'is_hidden' => false,
        'created_at' => '2024-01-01 00:00:00',
        'updated_at' => '2024-01-02 00:00:00',
    ], $overrides));

    $product->setRelation('company', apiCompany());

    return $product;
}

function companyPayload(array $overrides = []): array
{
    return array_merge([
        'company_name' => 'Fromagerie Test',
        'company_address' => '1 rue du Test, Lyon',
        'company_telephone' => '+33 1 11 11 11 11',
        'company_email' => 'company@example.test',
        'owner_name' => 'Owner Test',
        'owner_mobile' => '+33 6 11 11 11 11',
        'owner_email' => 'owner@example.test',
        'contact_name' => 'Contact Test',
        'contact_mobile' => '+33 6 22 22 22 22',
        'contact_email' => 'contact@example.test',
    ], $overrides);
}

function productPayload(array $overrides = []): array
{
    return array_merge([
        'company_id' => Company::factory()->create()->id,
        'gtin' => ProductFactory::gtin(),
        'name_en' => 'Salted Butter',
        'name_fr' => 'Beurre demi-sel',
        'description_en' => 'Churned in Brittany.',
        'description_fr' => 'Baratte en Bretagne.',
        'brand' => 'Maison Test',
        'country_of_origin' => 'France',
        'weight_gross' => '0.500',
        'weight_net' => '0.450',
        'weight_unit' => 'kg',
    ], $overrides);
}

// all the admin routes, pointed at records that really exist
function managementRoutes(int $companyId, string $gtin): array
{
    return [
        ['get', '/companies'],
        ['get', '/companies/deactivated'],
        ['get', '/companies/new'],
        ['get', "/companies/{$companyId}"],
        ['get', "/companies/{$companyId}/edit"],
        ['post', '/companies'],
        ['put', "/companies/{$companyId}"],
        ['post', "/companies/{$companyId}/deactivate"],
        ['post', "/companies/{$companyId}/reactivate"],
        ['get', '/products'],
        ['get', '/products/new'],
        ['get', "/products/{$gtin}"],
        ['get', "/products/{$gtin}/edit"],
        ['post', '/products'],
        ['put', "/products/{$gtin}"],
        ['delete', "/products/{$gtin}"],
        ['post', "/products/{$gtin}/hide"],
        ['post', "/products/{$gtin}/unhide"],
        ['post', "/products/{$gtin}/remove-image"],
    ];
}
