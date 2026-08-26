<?php

use App\Models\Product;
use Illuminate\Support\Facades\Schema;

// S1 - the search keyword never reaches the database as concatenated SQL.

dataset('injection payloads', [
    'quote' => ["'"],
    'or true' => ["' OR '1'='1"],
    'or one equals one' => ["1' OR 1=1 --"],
    'union select' => ["' UNION SELECT 1,2,3 --"],
    'drop table' => ["'; DROP TABLE products; --"],
    'comment terminator' => ["%' --"],
]);

beforeEach(function () {
    // Explicit text: a faker sentence could legitimately contain a quote or a percent sign.
    Product::factory()->count(4)->create([
        'name_en' => 'Organic Apple Juice',
        'name_fr' => 'Jus de pomme biologique',
        'description_en' => 'Pressed from organic apples.',
        'description_fr' => 'Presse de pommes biologiques.',
    ]);
});

it('treats an injection payload in the API keyword as an ordinary search term', function (string $payload) {
    $response = $this->getJson('/products.json?query='.urlencode($payload));

    $response->assertOk();
    expect($response->json('data'))->toBe([]);
})->with('injection payloads');

it('treats an injection payload in the admin search as an ordinary search term', function (string $payload) {
    asAdmin()->get('/products?query='.urlencode($payload))
        ->assertOk()
        ->assertSee('No products match.');
})->with('injection payloads');

it('keeps the products table intact after a destructive payload', function () {
    $this->getJson('/products.json?query='.urlencode("'; DROP TABLE products; --"));

    expect(Schema::hasTable('products'))->toBeTrue()
        ->and(Product::query()->count())->toBe(4);
});

it('never returns a database error message to the client', function (string $payload) {
    $body = $this->getJson('/products.json?query='.urlencode($payload))->getContent();

    expect($body)->not->toContain('SQLSTATE')
        ->and($body)->not->toContain('syntax error')
        ->and($body)->not->toContain('SQL');
})->with('injection payloads');

it('does not let a wildcard payload widen the result set', function () {
    // A raw LIKE with an unescaped % would match everything.
    $response = $this->getJson('/products.json?query='.urlencode('%'));

    expect($response->json('data'))->toBe([]);
});
