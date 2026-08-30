<?php

// F8 - GET /products.json. data + pagination block, 10 per page, next/prev null at the ends.

use App\Models\Product;

it('answers with the documented envelope', function () {
    Product::factory()->create();

    $this->getJson('/products.json')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['name' => ['en', 'fr'], 'description' => ['en', 'fr'], 'gtin', 'brand',
                'countryOfOrigin', 'weight' => ['gross', 'net', 'unit'], 'company']],
            'pagination' => ['current_page', 'total_pages', 'per_page', 'next_page_url', 'prev_page_url'],
        ]);
});

it('exposes exactly the documented pagination keys', function () {
    Product::factory()->create();

    $pagination = $this->getJson('/products.json')->json('pagination');

    expect(array_keys($pagination))
        ->toBe(['current_page', 'total_pages', 'per_page', 'next_page_url', 'prev_page_url']);
});

it('reports a page size of ten', function () {
    Product::factory()->count(3)->create();

    expect($this->getJson('/products.json')->json('pagination.per_page'))->toBe(10);
});

it('serves ten products on a full page', function () {
    Product::factory()->count(12)->create();

    expect($this->getJson('/products.json')->json('data'))->toHaveCount(10);
});

it('counts the pages from a page size of ten', function () {
    Product::factory()->count(12)->create();

    $pagination = $this->getJson('/products.json')->json('pagination');

    expect($pagination['total_pages'])->toBe(2)
        ->and($pagination['current_page'])->toBe(1);
});

it('serves the remaining products on the second page', function () {
    Product::factory()->count(12)->create();

    $second = $this->getJson('/products.json?page=2')->assertOk()->json();

    expect($second['data'])->toHaveCount(2)
        ->and($second['pagination']['current_page'])->toBe(2);
});

it('serves different products on the second page', function () {
    Product::factory()->count(12)->create();

    $first = collect($this->getJson('/products.json')->json('data'))->pluck('gtin');
    $second = collect($this->getJson('/products.json?page=2')->json('data'))->pluck('gtin');

    expect($first->intersect($second))->toBeEmpty()
        ->and($first->merge($second)->unique())->toHaveCount(12);
});

it('has no previous page link on the first page', function () {
    Product::factory()->count(12)->create();

    expect($this->getJson('/products.json')->json('pagination.prev_page_url'))->toBeNull();
});

it('links to the second page from the first', function () {
    Product::factory()->count(12)->create();

    expect($this->getJson('/products.json')->json('pagination.next_page_url'))
        ->toContain('page=2');
});

it('has no next page link on the last page', function () {
    Product::factory()->count(12)->create();

    expect($this->getJson('/products.json?page=2')->json('pagination.next_page_url'))->toBeNull();
});

it('links back to the first page from the second', function () {
    Product::factory()->count(12)->create();

    expect($this->getJson('/products.json?page=2')->json('pagination.prev_page_url'))
        ->toContain('page=1');
});

it('has neither link when everything fits on one page', function () {
    Product::factory()->count(3)->create();

    $pagination = $this->getJson('/products.json')->json('pagination');

    expect($pagination['next_page_url'])->toBeNull()
        ->and($pagination['prev_page_url'])->toBeNull()
        ->and($pagination['total_pages'])->toBe(1);
});

it('keeps the query string on the next page link', function () {
    Product::factory()->count(12)->create(['name_en' => 'Salted Butter']);

    $next = $this->getJson('/products.json?query=Salted')->json('pagination.next_page_url');

    expect($next)->toContain('query=Salted')
        ->and($next)->toContain('page=2');
});

it('keeps the query string on the previous page link', function () {
    Product::factory()->count(12)->create(['name_en' => 'Salted Butter']);

    $prev = $this->getJson('/products.json?query=Salted&page=2')->json('pagination.prev_page_url');

    expect($prev)->toContain('query=Salted');
});

it('filters the listing by keyword', function () {
    $match = Product::factory()->create(['name_en' => 'Salted Butter', 'name_fr' => 'Aucun', 'description_en' => 'Aucun', 'description_fr' => 'Aucun']);
    Product::factory()->create(['name_en' => 'Strawberry Jam', 'name_fr' => 'Aucun', 'description_en' => 'Aucun', 'description_fr' => 'Aucun']);

    $data = $this->getJson('/products.json?query=Salted')->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['gtin'])->toBe($match->gtin);
});

it('counts only the matching products when a keyword is submitted', function () {
    Product::factory()->count(12)->create(['name_en' => 'Salted Butter', 'name_fr' => 'Aucun', 'description_en' => 'Aucun', 'description_fr' => 'Aucun']);
    Product::factory()->count(4)->create(['name_en' => 'Strawberry Jam', 'name_fr' => 'Aucun', 'description_en' => 'Aucun', 'description_fr' => 'Aucun']);

    $pagination = $this->getJson('/products.json?query=Salted')->json('pagination');

    expect($pagination['total_pages'])->toBe(2);
});

it('omits hidden products from the data', function () {
    $visible = Product::factory()->create();
    $hidden = Product::factory()->hidden()->create();

    $gtins = collect($this->getJson('/products.json')->json('data'))->pluck('gtin');

    expect($gtins)->toContain($visible->gtin)
        ->and($gtins)->not->toContain($hidden->gtin);
});

it('omits hidden products from the pagination totals', function () {
    Product::factory()->count(10)->create();
    Product::factory()->count(5)->hidden()->create();

    $pagination = $this->getJson('/products.json')->json('pagination');

    expect($pagination['total_pages'])->toBe(1)
        ->and($pagination['next_page_url'])->toBeNull();
});

it('serves the listing as JSON', function () {
    Product::factory()->create();

    $this->get('/products.json')
        ->assertOk()
        ->assertHeader('content-type', 'application/json');
});

it('serves the listing without a session', function () {
    Product::factory()->create();

    $this->getJson('/products.json')->assertOk();
});

it('answers an empty data list when no product exists', function () {
    $body = $this->getJson('/products.json')->assertOk()->json();

    expect($body['data'])->toBe([])
        ->and($body['pagination']['current_page'])->toBe(1);
});
