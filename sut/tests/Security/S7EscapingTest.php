<?php

// S7 - stored text is escaped everywhere it is printed. json api answers with application/json.

use App\Models\Company;
use App\Models\Product;

const XSS_PAYLOAD = '<script>alert("xss")</script>';

it('escapes a product name on the public product page', function () {
    Product::factory()->create(['gtin' => '3000123456789', 'name_en' => XSS_PAYLOAD]);

    $this->get('/01/3000123456789')
        ->assertOk()
        ->assertDontSee('<script>alert', false)
        ->assertSee(XSS_PAYLOAD);
});

it('escapes a product description on the public product page', function () {
    Product::factory()->create(['gtin' => '3000123456789', 'description_en' => XSS_PAYLOAD]);

    $this->get('/01/3000123456789')
        ->assertOk()
        ->assertDontSee('<script>alert', false)
        ->assertSee(XSS_PAYLOAD);
});

it('escapes a French product name on the public product page', function () {
    Product::factory()->create(['gtin' => '3000123456789', 'name_fr' => XSS_PAYLOAD]);

    $this->get('/01/3000123456789?lang=fr')
        ->assertOk()
        ->assertDontSee('<script>alert', false);
});

it('escapes a French product description on the public product page', function () {
    Product::factory()->create(['gtin' => '3000123456789', 'description_fr' => XSS_PAYLOAD]);

    $this->get('/01/3000123456789?lang=fr')
        ->assertOk()
        ->assertDontSee('<script>alert', false);
});

it('escapes a product name on the management product page', function () {
    $product = Product::factory()->create(['gtin' => '3000123456789', 'name_en' => XSS_PAYLOAD]);

    asAdmin()->get('/products/3000123456789')
        ->assertOk()
        ->assertDontSee('<script>alert', false);
});

it('escapes a product description on the management product page', function () {
    Product::factory()->create(['gtin' => '3000123456789', 'description_en' => XSS_PAYLOAD]);

    asAdmin()->get('/products/3000123456789')
        ->assertOk()
        ->assertDontSee('<script>alert', false);
});

it('escapes a product name in the management listing', function () {
    Product::factory()->create(['name_en' => XSS_PAYLOAD]);

    asAdmin()->get('/products')
        ->assertOk()
        ->assertDontSee('<script>alert', false);
});

it('escapes a company name in the company listing', function () {
    Company::factory()->create(['company_name' => XSS_PAYLOAD]);

    asAdmin()->get('/companies')
        ->assertOk()
        ->assertDontSee('<script>alert', false);
});

it('escapes a company name on the company page', function () {
    $company = Company::factory()->create(['company_name' => XSS_PAYLOAD]);

    asAdmin()->get('/companies/'.$company->id)
        ->assertOk()
        ->assertDontSee('<script>alert', false);
});

it('escapes a submitted GTIN code on the verification page', function () {
    $this->post('/verify', ['gtins' => XSS_PAYLOAD])
        ->assertOk()
        ->assertDontSee('<script>alert', false);
});

it('escapes the submitted text of the verification textarea', function () {
    $this->post('/verify', ['gtins' => '</textarea><script>alert("xss")</script>'])
        ->assertOk()
        ->assertDontSee('<script>alert', false)
        ->assertDontSee('</textarea><script>', false);
});

it('escapes the search keyword echoed by the management listing', function () {
    Product::factory()->create();

    asAdmin()->get('/products?query='.urlencode('"><script>alert("xss")</script>'))
        ->assertOk()
        ->assertDontSee('<script>alert', false);
});

it('escapes a company name shown on a product management page', function () {
    $company = Company::factory()->create(['company_name' => XSS_PAYLOAD]);
    Product::factory()->for($company)->create(['gtin' => '3000123456789']);

    asAdmin()->get('/products/3000123456789')
        ->assertOk()
        ->assertDontSee('<script>alert', false);
});

it('escapes a company name shown on the public product page', function () {
    $company = Company::factory()->create(['company_name' => XSS_PAYLOAD]);
    Product::factory()->for($company)->create(['gtin' => '3000123456789']);

    $this->get('/01/3000123456789')
        ->assertOk()
        ->assertDontSee('<script>alert', false);
});

it('serves the JSON API as application/json', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    $this->get('/products.json')->assertHeader('content-type', 'application/json');
    $this->get('/products/3000123456789.json')->assertHeader('content-type', 'application/json');
});

it('serves stored markup as JSON data rather than as a document', function () {
    Product::factory()->create(['gtin' => '3000123456789', 'name_en' => XSS_PAYLOAD]);

    $response = $this->get('/products.json');

    $response->assertHeader('content-type', 'application/json');
    expect($response->json('data.0.name.en'))->toBe(XSS_PAYLOAD);
});
