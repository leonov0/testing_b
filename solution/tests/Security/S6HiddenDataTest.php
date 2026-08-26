<?php

use App\Models\Company;
use App\Models\Product;

// S6 - a hidden product is invisible to the public, everywhere.

function hiddenProduct(): Product
{
    return Product::factory()->hidden()->create([
        'name_en' => 'Withdrawn Batch Juice',
        'name_fr' => 'Jus du lot retire',
        'description_en' => 'Withdrawn from sale.',
    ]);
}

it('is absent from the API listing', function () {
    Product::factory()->count(2)->create();
    $hidden = hiddenProduct();

    $body = $this->getJson('/products.json')->getContent();

    expect($body)->not->toContain($hidden->gtin)
        ->and($body)->not->toContain('Withdrawn Batch Juice');
});

it('is absent from a keyword search of the API', function () {
    hiddenProduct();

    expect($this->getJson('/products.json?query=Withdrawn')->json('data'))->toBe([]);
});

it('is not counted in the pagination totals', function () {
    Product::factory()->count(10)->create();
    Product::factory()->count(5)->hidden()->create();

    $this->getJson('/products.json')->assertJsonPath('pagination.total_pages', 1);
});

it('answers 404 on the single product endpoint', function () {
    $this->getJson('/products/'.hiddenProduct()->gtin.'.json')->assertStatus(404);
});

it('answers 404 on the public product page', function () {
    $this->get('/01/'.hiddenProduct()->gtin)->assertStatus(404);
});

it('verifies as not valid on the bulk page', function () {
    $this->post('/verify', ['gtins' => hiddenProduct()->gtin])
        ->assertOk()
        ->assertSee('Not valid')
        ->assertDontSee('All valid');
});

it('hides every product of a deactivated company from the public API', function () {
    $company = Company::factory()->create();
    $products = Product::factory()->count(3)->for($company)->create();
    $company->deactivate();

    $body = $this->getJson('/products.json')->getContent();

    $products->each(fn (Product $product) => expect($body)->not->toContain($product->gtin));
});

it('does not expose internal columns through the public API', function () {
    Product::factory()->create(['image_path' => 'product-images/secret-name.jpg']);

    $body = $this->getJson('/products.json')->getContent();

    foreach (['image_path', 'product-images/secret-name.jpg', 'is_hidden', 'company_id', 'created_at'] as $internal) {
        expect($body)->not->toContain($internal);
    }
});
