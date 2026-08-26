<?php

use App\Models\Company;
use App\Models\Product;

// S3 - the management area is closed to anyone without the passphrase session.

it('answers 401 for every management route, JSON or document', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->create();

    foreach (managementRoutes((string) $company->id, $product->gtin) as [$method, $uri]) {
        $this->json(strtoupper($method), $uri)->assertStatus(401);
        $this->call(strtoupper($method), $uri)->assertStatus(401);
    }
});

it('does not reveal whether a record exists through the status code', function () {
    $product = Product::factory()->create();

    $existing = $this->get('/products/'.$product->gtin)->getStatusCode();
    $missing = $this->get('/products/9999999999999')->getStatusCode();

    expect($existing)->toBe(401)->and($missing)->toBe(401);
});

it('does not leak record content in the unauthenticated response', function () {
    $product = Product::factory()->create(['name_en' => 'Secret Prototype Juice']);

    $body = $this->get('/products/'.$product->gtin)->getContent();

    expect($body)->not->toContain('Secret Prototype Juice');
});

it('does not change any data through an unauthenticated write', function () {
    $product = Product::factory()->create();

    $this->post('/products/'.$product->gtin.'/hide');
    $this->delete('/products/'.$product->gtin);

    expect($product->fresh())->not->toBeNull()
        ->and($product->fresh()->is_hidden)->toBeFalse();
});

it('closes the session on logout so the management area locks again', function () {
    $this->post('/login', ['passphrase' => 'admin']);
    $this->get('/products')->assertOk();

    $this->post('/logout');

    $this->get('/products')->assertStatus(401);
});
