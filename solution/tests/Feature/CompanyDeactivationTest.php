<?php

use App\Models\Company;
use App\Models\Product;

// F3 - R2 deactivating a company hides all of its products.

it('hides every product of the company when it is deactivated', function () {
    $company = Company::factory()->create();
    $products = Product::factory()->count(3)->for($company)->create();

    asAdmin()->post('/companies/'.$company->id.'/deactivate')->assertRedirect();

    expect($company->fresh()->deactivated)->toBeTrue();
    $products->each(fn (Product $product) => expect($product->fresh()->is_hidden)->toBeTrue());
});

it('leaves the products of other companies untouched', function () {
    $company = Company::factory()->create();
    Product::factory()->count(2)->for($company)->create();
    $other = Product::factory()->create();

    asAdmin()->post('/companies/'.$company->id.'/deactivate');

    expect($other->fresh()->is_hidden)->toBeFalse();
});

it('removes the hidden products from the public API', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    asAdmin()->post('/companies/'.$company->id.'/deactivate');

    $this->getJson('/products.json')->assertOk()->assertJsonPath('pagination.current_page', 1);
    expect(collect($this->getJson('/products.json')->json('data'))->pluck('gtin'))
        ->not->toContain($product->gtin);
    $this->getJson('/products/'.$product->gtin.'.json')->assertStatus(404);
});

it('moves the company to the deactivated listing', function () {
    $company = Company::factory()->create(['company_name' => 'Fermé SARL']);

    asAdmin()->post('/companies/'.$company->id.'/deactivate');

    asAdmin()->get('/companies/deactivated')->assertSee('Fermé SARL');
    asAdmin()->get('/companies')->assertDontSee('Fermé SARL');
});

it('does not unhide the products when the company is reactivated', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    asAdmin()->post('/companies/'.$company->id.'/deactivate');
    asAdmin()->post('/companies/'.$company->id.'/reactivate');

    expect($company->fresh()->deactivated)->toBeFalse()
        ->and($product->fresh()->is_hidden)->toBeTrue();
});
