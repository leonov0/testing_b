<?php

// S2 - a form must not be able to set is_hidden, id, timestamps or the company deactivated flag.

use App\Models\Company;
use App\Models\Product;

it('does not let a product submission hide the product', function () {
    asAdmin()->post('/products', productPayload(['gtin' => '3000123456789', 'is_hidden' => 1]));

    $product = Product::query()->firstWhere('gtin', '3000123456789');

    expect($product)->not->toBeNull()
        ->and($product->is_hidden)->toBeFalse();
});

it('does not let a product update hide the product', function () {
    $product = Product::factory()->create(['gtin' => '3000123456789']);

    asAdmin()->put('/products/'.$product->gtin, productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'is_hidden' => 1,
    ]));

    expect($product->fresh()->is_hidden)->toBeFalse();
});

it('does not let a product update unhide a hidden product', function () {
    $product = Product::factory()->hidden()->create(['gtin' => '3000123456789']);

    asAdmin()->put('/products/'.$product->gtin, productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'is_hidden' => 0,
    ]));

    expect($product->fresh()->is_hidden)->toBeTrue();
});

it('does not let a product submission choose its own id', function () {
    asAdmin()->post('/products', productPayload(['gtin' => '3000123456789', 'id' => 987654]));

    expect(Product::query()->firstWhere('gtin', '3000123456789')->id)->not->toBe(987654);
});

it('does not let a product submission set its timestamps', function () {
    asAdmin()->post('/products', productPayload([
        'gtin' => '3000123456789',
        'created_at' => '1999-01-01 00:00:00',
        'updated_at' => '1999-01-01 00:00:00',
    ]));

    $product = Product::query()->firstWhere('gtin', '3000123456789');

    expect($product->created_at->year)->not->toBe(1999)
        ->and($product->updated_at->year)->not->toBe(1999);
});

it('does not let a product submission set the image path directly', function () {
    asAdmin()->post('/products', productPayload([
        'gtin' => '3000123456789',
        'image_path' => '../../../etc/passwd',
    ]));

    expect(Product::query()->firstWhere('gtin', '3000123456789')->image_path)->toBeNull();
});

it('does not let a company submission deactivate the company', function () {
    asAdmin()->post('/companies', companyPayload(['deactivated' => 1]));

    expect(Company::query()->firstWhere('company_name', 'Fromagerie Test')->deactivated)->toBeFalse();
});

it('does not let a company update deactivate the company', function () {
    $company = Company::factory()->create();

    asAdmin()->put('/companies/'.$company->id, companyPayload(['deactivated' => 1]));

    expect($company->fresh()->deactivated)->toBeFalse();
});

it('does not let a company update reactivate a deactivated company', function () {
    $company = Company::factory()->deactivated()->create();

    asAdmin()->put('/companies/'.$company->id, companyPayload(['deactivated' => 0]));

    expect($company->fresh()->deactivated)->toBeTrue();
});

it('does not let a company submission choose its own id', function () {
    asAdmin()->post('/companies', companyPayload(['id' => 987654]));

    expect(Company::query()->firstWhere('company_name', 'Fromagerie Test')->id)->not->toBe(987654);
});

it('keeps a hidden product hidden across an ordinary edit', function () {
    $product = Product::factory()->hidden()->create(['gtin' => '3000123456789']);

    asAdmin()->put('/products/'.$product->gtin, productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'name_en' => 'Renamed Butter',
    ]));

    $fresh = $product->fresh();

    expect($fresh->name_en)->toBe('Renamed Butter')
        ->and($fresh->is_hidden)->toBeTrue();
});
