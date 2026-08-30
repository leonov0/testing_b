<?php

// S1 - the search word must never become SQL. quotes, OR 1=1, UNION, DROP TABLE and % are just text.

use App\Models\Product;

function injectionPayloads(): array
{
    return [
        'single quote' => "'",
        'always true' => "' OR '1'='1",
        'union select' => "' UNION SELECT name_en, gtin, brand FROM products --",
        'drop table' => "'; DROP TABLE products; --",
        'comment out' => "admin'--",
        'boolean tail' => "x' OR 1=1 --",
    ];
}

function seedTwoDistinctProducts(): array
{
    return [
        Product::factory()->create([
            'gtin' => '3000123456789',
            'name_en' => 'Salted Butter', 'name_fr' => 'Beurre Sale',
            'description_en' => 'Churned in Brittany', 'description_fr' => 'Baratte en Bretagne',
        ]),
        Product::factory()->create([
            'gtin' => '3000123456790',
            'name_en' => 'Strawberry Jam', 'name_fr' => 'Confiture Fraise',
            'description_en' => 'Cooked in Provence', 'description_fr' => 'Cuite en Provence',
        ]),
    ];
}

it('returns no product for an injection payload through the JSON API', function (string $payload) {
    seedTwoDistinctProducts();

    $response = $this->getJson('/products.json?query='.urlencode($payload));

    $response->assertOk();
    expect($response->json('data'))->toBe([]);
})->with(injectionPayloads());

it('returns no product for an injection payload through the management listing', function (string $payload) {
    seedTwoDistinctProducts();

    asAdmin()->get('/products?query='.urlencode($payload))
        ->assertOk()
        ->assertDontSee('Salted Butter')
        ->assertDontSee('Strawberry Jam');
})->with(injectionPayloads());

it('leaves the products table intact after an injection payload', function (string $payload) {
    seedTwoDistinctProducts();

    $this->getJson('/products.json?query='.urlencode($payload));
    asAdmin()->get('/products?query='.urlencode($payload));

    expect(Product::query()->count())->toBe(2);
})->with(injectionPayloads());

it('surfaces no database error for an injection payload through the JSON API', function (string $payload) {
    seedTwoDistinctProducts();

    $body = $this->getJson('/products.json?query='.urlencode($payload))->getContent();

    expect($body)->not->toContain('SQLSTATE')
        ->and($body)->not->toContain('SQL')
        ->and($body)->not->toContain('syntax error')
        ->and($body)->not->toContain('PDO');
})->with(injectionPayloads());

it('surfaces no database error for an injection payload through the management listing', function (string $payload) {
    seedTwoDistinctProducts();

    $body = asAdmin()->get('/products?query='.urlencode($payload))->getContent();

    expect($body)->not->toContain('SQLSTATE')
        ->and($body)->not->toContain('syntax error')
        ->and($body)->not->toContain('PDO');
})->with(injectionPayloads());

it('treats a percent sign in the keyword as ordinary text', function () {
    $literal = Product::factory()->create([
        'gtin' => '3000123456789',
        'name_en' => 'One Hundred % Pure', 'name_fr' => 'Aucun',
        'description_en' => 'Aucun', 'description_fr' => 'Aucun',
    ]);
    Product::factory()->create([
        'gtin' => '3000123456790',
        'name_en' => 'Salted Butter', 'name_fr' => 'Aucun',
        'description_en' => 'Aucun', 'description_fr' => 'Aucun',
    ]);

    $data = $this->getJson('/products.json?query='.urlencode('%'))->assertOk()->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['gtin'])->toBe($literal->gtin);
});

it('treats an underscore in the keyword as ordinary text', function () {
    $literal = Product::factory()->create([
        'gtin' => '3000123456789',
        'name_en' => 'snake_case butter', 'name_fr' => 'Aucun',
        'description_en' => 'Aucun', 'description_fr' => 'Aucun',
    ]);
    Product::factory()->create([
        'gtin' => '3000123456790',
        'name_en' => 'Salted Butter', 'name_fr' => 'Aucun',
        'description_en' => 'Aucun', 'description_fr' => 'Aucun',
    ]);

    $data = $this->getJson('/products.json?query='.urlencode('_'))->assertOk()->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['gtin'])->toBe($literal->gtin);
});

it('treats a wildcard keyword as ordinary text in the management listing', function () {
    Product::factory()->create([
        'gtin' => '3000123456789',
        'name_en' => 'Salted Butter', 'name_fr' => 'Aucun',
        'description_en' => 'Aucun', 'description_fr' => 'Aucun',
    ]);

    asAdmin()->get('/products?query='.urlencode('%'))
        ->assertOk()
        ->assertDontSee('Salted Butter');
});

it('does not widen the result set with an always true payload', function () {
    seedTwoDistinctProducts();
    Product::factory()->hidden()->create(['gtin' => '3000123456791', 'name_en' => 'Hidden Butter']);

    $body = $this->getJson('/products.json?query='.urlencode("' OR '1'='1"))->getContent();

    expect($this->getJson('/products.json?query='.urlencode("' OR '1'='1"))->json('data'))->toBe([])
        ->and($body)->not->toContain('Hidden Butter');
});

it('keeps the companies table intact after a drop table payload', function () {
    seedTwoDistinctProducts();

    $this->getJson('/products.json?query='.urlencode("'; DROP TABLE companies; --"));

    expect(\App\Models\Company::query()->count())->toBe(2);
});
