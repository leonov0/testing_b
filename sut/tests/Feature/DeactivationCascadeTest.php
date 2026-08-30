<?php

// F3 - deactivating a company hides all its products. reactivate only clears the company flag, products stay hidden.

use App\Models\Company;
use App\Models\Product;

it('hides every product of the company when the model deactivates it', function () {
    $company = Company::factory()->create();
    $products = Product::factory()->count(3)->for($company)->create();

    $company->deactivate();

    foreach ($products as $product) {
        expect($product->fresh()->is_hidden)->toBeTrue();
    }
});

it('marks the company deactivated when the model deactivates it', function () {
    $company = Company::factory()->create();

    $company->deactivate();

    expect($company->fresh()->deactivated)->toBeTrue();
});

it('hides every product of the company through the deactivate route', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    asAdmin()->post('/companies/'.$company->id.'/deactivate')->assertRedirect();

    expect($product->fresh()->is_hidden)->toBeTrue()
        ->and($company->fresh()->deactivated)->toBeTrue();
});

it('leaves the products of other companies visible', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create();
    $untouched = Product::factory()->create();

    $company->deactivate();

    expect($untouched->fresh()->is_hidden)->toBeFalse()
        ->and($untouched->company->fresh()->deactivated)->toBeFalse();
});

it('moves the company from the active listing to the deactivated listing', function () {
    $company = Company::factory()->create(['company_name' => 'Fromagerie Test']);

    $company->deactivate();

    asAdmin()->get('/companies')->assertDontSee('Fromagerie Test');
    asAdmin()->get('/companies/deactivated')->assertSee('Fromagerie Test');
});

it('drops the products of a deactivated company from the public API', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    $company->deactivate();

    $body = $this->getJson('/products.json')->assertOk()->json();

    expect($body['data'])->toBe([]);
});

it('answers 404 on the single product endpoint of a deactivated company', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    $company->deactivate();

    $this->getJson('/products/'.$product->gtin.'.json')->assertStatus(404);
});

it('answers 404 on the public page of a deactivated company product', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    $company->deactivate();

    $this->get('/01/'.$product->gtin)->assertStatus(404);
});

it('keeps the products hidden when the company is reactivated', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    $company->deactivate();
    $company->reactivate();

    expect($product->fresh()->is_hidden)->toBeTrue()
        ->and($company->fresh()->deactivated)->toBeFalse();
});

it('keeps the products hidden when the company is reactivated through its route', function () {
    $company = Company::factory()->deactivated()->create();
    $product = Product::factory()->for($company)->hidden()->create();

    asAdmin()->post('/companies/'.$company->id.'/reactivate')->assertRedirect();

    expect($product->fresh()->is_hidden)->toBeTrue();
});

it('returns the company to the active listing when it is reactivated', function () {
    $company = Company::factory()->deactivated()->create(['company_name' => 'Fromagerie Test']);

    asAdmin()->post('/companies/'.$company->id.'/reactivate');

    asAdmin()->get('/companies')->assertSee('Fromagerie Test');
    asAdmin()->get('/companies/deactivated')->assertDontSee('Fromagerie Test');
});

it('leaves an already hidden product hidden when the company is deactivated', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->hidden()->create();

    $company->deactivate();

    expect($product->fresh()->is_hidden)->toBeTrue();
});
