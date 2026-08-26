<?php

use App\Models\Product;

// S8 - a failed request never returns a stack trace, a file path or SQL.

dataset('failing requests', [
    'unknown product page' => ['/01/9999999999999'],
    'unknown product json' => ['/products/9999999999999.json'],
    'unknown route' => ['/no-such-page'],
    'malformed gtin json' => ['/products/not-a-gtin.json'],
]);

it('answers a failing request without leaking internals', function (string $uri) {
    Product::factory()->create();

    $body = $this->get($uri)->getContent();

    expect($body)->not->toContain('Stack trace')
        ->and($body)->not->toContain('vendor/laravel')
        ->and($body)->not->toContain(base_path())
        ->and($body)->not->toContain('SQLSTATE')
        ->and($body)->not->toContain('No query results');
})->with('failing requests');

it('answers a failed deletion without leaking internals', function () {
    $product = Product::factory()->create();

    $response = asAdmin()->delete('/products/'.$product->gtin);
    $response->assertStatus(409);

    expect($response->getContent())->not->toContain('Stack trace')
        ->and($response->getContent())->not->toContain(base_path());
});

it('answers an unauthenticated request without leaking internals', function () {
    $body = $this->get('/products')->getContent();

    expect($body)->not->toContain('Stack trace')
        ->and($body)->not->toContain(base_path());
});

it('keeps the JSON API answering JSON when it fails', function () {
    $this->getJson('/products/9999999999999.json')
        ->assertStatus(404)
        ->assertHeader('content-type', 'application/json')
        ->assertJsonStructure(['error']);
});
