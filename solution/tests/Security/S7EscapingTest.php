<?php

use App\Models\Company;
use App\Models\Product;

// S7 - stored free text is escaped wherever it is rendered.

const XSS = '<script>alert("xss")</script>';

it('escapes a product name in the admin listing', function () {
    Product::factory()->create(['name_en' => XSS]);

    $body = asAdmin()->get('/products')->assertOk()->getContent();

    expect($body)->not->toContain('<script>alert')
        ->and($body)->toContain('&lt;script&gt;');
});

it('escapes a product name and description on the admin product page', function () {
    $product = Product::factory()->create(['name_en' => XSS, 'description_en' => XSS]);

    $body = asAdmin()->get('/products/'.$product->gtin)->assertOk()->getContent();

    expect($body)->not->toContain('<script>alert')
        ->and($body)->toContain('&lt;script&gt;');
});

it('escapes a company name on the company page', function () {
    $company = Company::factory()->create(['company_name' => '<img src=x onerror=alert(1)>']);

    $body = asAdmin()->get('/companies/'.$company->id)->assertOk()->getContent();

    expect($body)->not->toContain('<img src=x onerror')
        ->and($body)->toContain('&lt;img');
});

it('escapes the product name on the public product page', function () {
    $product = Product::factory()->create(['name_en' => XSS]);

    $body = $this->get('/01/'.$product->gtin)->assertOk()->getContent();

    expect($body)->not->toContain('<script>alert')
        ->and($body)->toContain('&lt;script&gt;');
});

it('escapes the french description on the public product page', function () {
    $product = Product::factory()->create(['description_fr' => XSS]);

    $body = $this->get('/01/'.$product->gtin.'?lang=fr')->assertOk()->getContent();

    expect($body)->not->toContain('<script>alert');
});

it('escapes the submitted codes echoed back by the verification page', function () {
    $body = $this->post('/verify', ['gtins' => XSS])->assertOk()->getContent();

    expect($body)->not->toContain('<script>alert')
        ->and($body)->toContain('&lt;script&gt;');
});

it('escapes the keyword echoed back by the admin search box', function () {
    $body = asAdmin()->get('/products?query='.urlencode('"><script>alert(1)</script>'))
        ->assertOk()
        ->getContent();

    expect($body)->not->toContain('<script>alert');
});

it('serves the JSON API as json, never as a document the browser renders', function () {
    Product::factory()->create(['name_en' => XSS]);

    $this->getJson('/products.json')
        ->assertOk()
        ->assertHeader('content-type', 'application/json');
});
