<?php

// S8 - a failed request must not leak a stack trace, paths or SQL, even with APP_DEBUG on. json in, json out.

use App\Models\Product;

function assertNoInternals(string $body): void
{
    expect($body)->not->toContain('Stack trace')
        ->and($body)->not->toContain('#0 ')
        ->and($body)->not->toContain(base_path())
        ->and($body)->not->toContain('/vendor/')
        ->and($body)->not->toContain('Illuminate\\')
        ->and($body)->not->toContain('SQLSTATE')
        ->and($body)->not->toContain('PDOException')
        ->and($body)->not->toContain('vendor/laravel')
        ->and($body)->not->toContain('.php on line');
}

it('leaks nothing when a public product page is not found', function () {
    $response = $this->get('/01/9999999999999');

    $response->assertStatus(404);
    assertNoInternals($response->getContent());
});

it('leaks nothing when a single product endpoint is not found', function () {
    $response = $this->get('/products/9999999999999.json');

    $response->assertStatus(404);
    assertNoInternals($response->getContent());
});

it('answers JSON when a JSON request fails', function () {
    $this->getJson('/products/9999999999999.json')
        ->assertStatus(404)
        ->assertHeader('content-type', 'application/json');
});

it('leaks nothing when an unknown page is requested', function () {
    $response = $this->get('/this-page-does-not-exist');

    $response->assertStatus(404);
    assertNoInternals($response->getContent());
});

it('leaks nothing when a management route is refused', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    assertNoInternals($this->get('/products/3000123456789')->getContent());
    assertNoInternals($this->getJson('/products')->getContent());
});

it('leaks nothing when a management record is missing for an authenticated request', function () {
    $response = asAdmin()->get('/products/9999999999999');

    $response->assertStatus(404);
    assertNoInternals($response->getContent());
});

it('leaks nothing when an unsupported method is used', function () {
    $company = \App\Models\Company::factory()->create();

    $response = asAdmin()->delete('/companies/'.$company->id);

    $response->assertStatus(405);
    assertNoInternals($response->getContent());
});

it('leaks nothing when a submission fails validation', function () {
    $response = asAdmin()->from('/products/new')->post('/products', productPayload(['gtin' => 'not-a-gtin']));

    assertNoInternals($response->getContent());
});

it('leaks nothing when a search keyword breaks the query', function () {
    Product::factory()->create();

    assertNoInternals($this->get('/products.json?query='.urlencode("' UNION SELECT 1 --"))->getContent());
    assertNoInternals(asAdmin()->get('/products?query='.urlencode("' UNION SELECT 1 --"))->getContent());
});

it('leaks nothing when a visible product is refused deletion', function () {
    $product = Product::factory()->create();

    $response = asAdmin()->delete('/products/'.$product->gtin);

    assertNoInternals($response->getContent());
});

it('leaks no configuration value in a failure page', function () {
    $response = $this->get('/01/9999999999999');

    expect($response->getContent())
        ->not->toContain(config('app.key'))
        ->and($response->getContent())->not->toContain('APP_KEY');
});
