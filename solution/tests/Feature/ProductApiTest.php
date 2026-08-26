<?php

use App\Models\Company;
use App\Models\Product;

// F8 / F9 - the public JSON API (R4, R5, R6).

it('is reachable without authentication', function () {
    Product::factory()->count(2)->create();

    $this->getJson('/products.json')->assertOk();
});

it('returns the documented envelope', function () {
    Product::factory()->count(3)->create();

    $this->getJson('/products.json')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['name' => ['en', 'fr'], 'description' => ['en', 'fr'], 'gtin', 'brand',
                'countryOfOrigin', 'weight' => ['gross', 'net', 'unit'],
                'company' => ['companyName', 'companyAddress', 'companyTelephone', 'companyEmail',
                    'owner' => ['name', 'mobileNumber', 'email'],
                    'contact' => ['name', 'mobileNumber', 'email']]]],
            'pagination' => ['current_page', 'total_pages', 'per_page', 'next_page_url', 'prev_page_url'],
        ]);
});

it('paginates ten products per page', function () {
    Product::factory()->count(25)->create();

    $this->getJson('/products.json')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('pagination.per_page', 10)
        ->assertJsonPath('pagination.current_page', 1)
        ->assertJsonPath('pagination.total_pages', 3);
});

it('links to the next page and not to a previous one on page one', function () {
    Product::factory()->count(25)->create();

    $response = $this->getJson('/products.json');

    expect($response->json('pagination.next_page_url'))->toContain('page=2')
        ->and($response->json('pagination.prev_page_url'))->toBeNull();
});

it('links back and stops linking forward on the last page', function () {
    Product::factory()->count(25)->create();

    $response = $this->getJson('/products.json?page=3');

    expect($response->json('pagination.current_page'))->toBe(3)
        ->and($response->json('pagination.next_page_url'))->toBeNull()
        ->and($response->json('pagination.prev_page_url'))->toContain('page=2');
});

it('serves a different set of products on page two', function () {
    Product::factory()->count(25)->create();

    $first = collect($this->getJson('/products.json?page=1')->json('data'))->pluck('gtin');
    $second = collect($this->getJson('/products.json?page=2')->json('data'))->pluck('gtin');

    expect($second)->toHaveCount(10)
        ->and($first->intersect($second))->toBeEmpty();
});

it('never lists a hidden product', function () {
    Product::factory()->count(3)->create();
    $hidden = Product::factory()->hidden()->create();

    $response = $this->getJson('/products.json');

    expect(collect($response->json('data'))->pluck('gtin'))->not->toContain($hidden->gtin);
    expect($response->json('pagination.total_pages'))->toBe(1);
});

it('filters the listing by keyword across both languages', function () {
    Product::factory()->create(['name_en' => 'Organic Apple Juice', 'name_fr' => 'a', 'description_en' => 'b', 'description_fr' => 'c']);
    Product::factory()->create(['name_en' => 'a', 'name_fr' => 'Jus de pomme biologique', 'description_en' => 'b', 'description_fr' => 'c']);
    Product::factory()->create(['name_en' => 'Sea Salt', 'name_fr' => 'Sel de mer', 'description_en' => 'b', 'description_fr' => 'c']);

    expect($this->getJson('/products.json?query=Apple')->json('data'))->toHaveCount(1);
    expect($this->getJson('/products.json?query=biologique')->json('data'))->toHaveCount(1);
    expect($this->getJson('/products.json?query=b')->json('data'))->toHaveCount(3);
});

it('keeps the keyword on the pagination links', function () {
    Product::factory()->count(15)->create(['description_en' => 'shared keyword']);

    $next = $this->getJson('/products.json?query=shared')->json('pagination.next_page_url');

    expect($next)->toContain('query=shared');
});

it('returns a single product with the documented shape', function () {
    $company = Company::factory()->create(['company_name' => 'Euro Expo']);
    $product = Product::factory()->for($company)->create([
        'gtin' => '03000123456789',
        'name_en' => 'Organic Apple Juice',
        'name_fr' => 'Jus de pomme biologique',
        'weight_gross' => 1.1,
        'weight_net' => 1.0,
        'weight_unit' => 'L',
    ]);

    $response = $this->getJson('/products/'.$product->gtin.'.json')
        ->assertOk()
        ->assertJsonPath('gtin', '03000123456789')
        ->assertJsonPath('name.en', 'Organic Apple Juice')
        ->assertJsonPath('name.fr', 'Jus de pomme biologique')
        ->assertJsonPath('weight.unit', 'L')
        ->assertJsonPath('company.companyName', 'Euro Expo');

    // JSON renders 1.0 as 1, so compare the weights numerically.
    expect((float) $response->json('weight.gross'))->toBe(1.1)
        ->and((float) $response->json('weight.net'))->toBe(1.0);
});

it('answers 404 for a GTIN that does not exist', function () {
    $this->getJson('/products/9999999999999.json')->assertStatus(404);
});

it('answers 404 for a hidden product', function () {
    $hidden = Product::factory()->hidden()->create();

    $this->getJson('/products/'.$hidden->gtin.'.json')->assertStatus(404);
});

it('answers 404 for a product of a deactivated company', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();
    $company->deactivate();

    $this->getJson('/products/'.$product->gtin.'.json')->assertStatus(404);
});
