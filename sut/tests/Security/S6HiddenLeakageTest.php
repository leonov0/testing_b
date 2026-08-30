<?php

// S6 - hidden products stay hidden everywhere, and the api never shows internal columns.

use App\Models\Company;
use App\Models\Product;

function hiddenProduct(): Product
{
    return Product::factory()->hidden()->create([
        'gtin' => '3000123456789',
        'name_en' => 'Confidential Hidden Butter',
        'name_fr' => 'Beurre Confidentiel Cache',
        'description_en' => 'Confidential hidden recipe',
        'description_fr' => 'Recette confidentielle cachee',
        'image_path' => 'product-images/confidential-file.jpg',
    ]);
}

it('never names a hidden product in the public listing', function () {
    hiddenProduct();
    Product::factory()->create(['gtin' => '3000123456790', 'name_en' => 'Public Butter']);

    $body = $this->getJson('/products.json')->assertOk()->getContent();

    expect($body)->not->toContain('Confidential Hidden Butter')
        ->and($body)->not->toContain('Beurre Confidentiel Cache')
        ->and($body)->not->toContain('Confidential hidden recipe')
        ->and($body)->not->toContain('3000123456789');
});

it('never names a hidden product in a keyword search of the public listing', function () {
    hiddenProduct();

    $body = $this->getJson('/products.json?query=Confidential')->assertOk()->getContent();

    expect($body)->not->toContain('Confidential Hidden Butter');
});

it('answers 404 on the single endpoint of a hidden product', function () {
    hiddenProduct();

    $response = $this->getJson('/products/3000123456789.json');

    $response->assertStatus(404);
    expect($response->getContent())->not->toContain('Confidential Hidden Butter');
});

it('answers 404 on the public page of a hidden product', function () {
    hiddenProduct();

    $response = $this->get('/01/3000123456789');

    $response->assertStatus(404);
    expect($response->getContent())->not->toContain('Confidential Hidden Butter');
});

it('reports a hidden product as not valid on the verification page', function () {
    hiddenProduct();

    $body = $this->post('/verify', ['gtins' => '3000123456789'])->assertOk()->getContent();

    expect(substr_count($body, '>Not valid<'))->toBe(1)
        ->and($body)->not->toContain('Confidential Hidden Butter');
});

it('never exposes the file path of a hidden product', function () {
    hiddenProduct();

    foreach (['/products.json', '/products/3000123456789.json'] as $path) {
        expect($this->get($path)->getContent())->not->toContain('confidential-file.jpg');
    }
});

it('exposes no internal column in the public listing', function () {
    Product::factory()->create(['image_path' => 'product-images/generated-name.jpg']);

    $body = $this->getJson('/products.json')->assertOk()->getContent();

    expect($body)->not->toContain('image_path')
        ->and($body)->not->toContain('is_hidden')
        ->and($body)->not->toContain('company_id')
        ->and($body)->not->toContain('created_at')
        ->and($body)->not->toContain('updated_at')
        ->and($body)->not->toContain('deactivated')
        ->and($body)->not->toContain('generated-name.jpg');
});

it('exposes no internal column on the single product endpoint', function () {
    Product::factory()->create(['gtin' => '3000123456789', 'image_path' => 'product-images/generated-name.jpg']);

    $body = $this->getJson('/products/3000123456789.json')->assertOk()->getContent();

    expect($body)->not->toContain('image_path')
        ->and($body)->not->toContain('is_hidden')
        ->and($body)->not->toContain('company_id')
        ->and($body)->not->toContain('created_at')
        ->and($body)->not->toContain('updated_at')
        ->and($body)->not->toContain('deactivated')
        ->and($body)->not->toContain('generated-name.jpg');
});

it('exposes no database identifier in the public listing', function () {
    Product::factory()->create();

    $body = $this->getJson('/products.json')->assertOk()->getContent();

    expect($body)->not->toContain('"id"');
});

it('keeps a product of a deactivated company out of every public surface', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create([
        'gtin' => '3000123456789',
        'name_en' => 'Confidential Dormant Butter',
    ]);

    $company->deactivate();

    expect($this->getJson('/products.json')->getContent())->not->toContain('Confidential Dormant Butter');
    $this->getJson('/products/3000123456789.json')->assertStatus(404);
    $this->get('/01/3000123456789')->assertStatus(404);
    expect(substr_count($this->post('/verify', ['gtins' => '3000123456789'])->getContent(), '>Not valid<'))->toBe(1);
});

it('does not let a public request ask for hidden products', function () {
    hiddenProduct();

    foreach ([
        '/products.json?is_hidden=1',
        '/products.json?hidden=1',
        '/products.json?visible=0',
    ] as $path) {
        expect($this->getJson($path)->getContent())->not->toContain('Confidential Hidden Butter');
    }
});
