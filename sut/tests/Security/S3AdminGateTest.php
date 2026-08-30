<?php

// S3 - every admin route gives 401 with no session, same code for real and fake ids, and nothing from the record in the body.

use App\Models\Company;
use App\Models\Product;

it('answers 401 on every management route without a session', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    foreach (managementRoutes($company->id, $product->gtin) as [$method, $path]) {
        $this->{$method}($path)->assertStatus(401, "{$method} {$path} must answer 401");
        $this->{$method.'Json'}($path)->assertStatus(401, "{$method} {$path} must answer 401 as JSON");
    }
});

it('answers the same status for a record that exists and one that does not', function () {
    $product = Product::factory()->create(['gtin' => '3000123456789']);
    $company = Company::factory()->create();

    expect($this->get('/products/3000123456789')->getStatusCode())
        ->toBe($this->get('/products/9999999999999')->getStatusCode())
        ->and($this->get('/companies/'.$company->id)->getStatusCode())
        ->toBe($this->get('/companies/987654')->getStatusCode());
});

it('answers the same status for an existing and a missing record as JSON', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    expect($this->getJson('/products/3000123456789')->getStatusCode())
        ->toBe($this->getJson('/products/9999999999999')->getStatusCode());
});

it('reveals no record content in the body of an unauthenticated request', function () {
    $company = Company::factory()->create(['company_name' => 'Confidential Fromagerie']);
    $product = Product::factory()->for($company)->create([
        'gtin' => '3000123456789',
        'name_en' => 'Confidential Butter',
        'description_en' => 'Confidential recipe',
    ]);

    foreach ([
        '/products/3000123456789',
        '/products/3000123456789/edit',
        '/products',
        '/companies/'.$company->id,
        '/companies/'.$company->id.'/edit',
        '/companies',
    ] as $path) {
        $body = $this->get($path)->getContent();

        expect($body)->not->toContain('Confidential Butter')
            ->and($body)->not->toContain('Confidential Fromagerie')
            ->and($body)->not->toContain('Confidential recipe');
    }
});

it('reveals no hidden product content to an unauthenticated management request', function () {
    Product::factory()->hidden()->create(['gtin' => '3000123456789', 'name_en' => 'Confidential Hidden Butter']);

    expect($this->get('/products')->getContent())->not->toContain('Confidential Hidden Butter');
});

it('creates nothing on an unauthenticated company submission', function () {
    $this->post('/companies', companyPayload())->assertStatus(401);

    expect(Company::query()->count())->toBe(0);
});

it('creates nothing on an unauthenticated product submission', function () {
    $this->post('/products', productPayload(['gtin' => '3000123456789']))->assertStatus(401);

    expect(Product::query()->where('gtin', '3000123456789')->count())->toBe(0);
});

it('changes nothing on an unauthenticated company update', function () {
    $company = Company::factory()->create(['company_name' => 'Original Name']);

    $this->put('/companies/'.$company->id, companyPayload(['company_name' => 'Injected Name']))
        ->assertStatus(401);

    expect($company->fresh()->company_name)->toBe('Original Name');
});

it('changes nothing on an unauthenticated company deactivation', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    $this->post('/companies/'.$company->id.'/deactivate')->assertStatus(401);

    expect($company->fresh()->deactivated)->toBeFalse()
        ->and($product->fresh()->is_hidden)->toBeFalse();
});

it('changes nothing on an unauthenticated product update', function () {
    $product = Product::factory()->create(['gtin' => '3000123456789', 'name_en' => 'Original Name']);

    $this->put('/products/'.$product->gtin, productPayload([
        'company_id' => $product->company_id,
        'gtin' => '3000123456789',
        'name_en' => 'Injected Name',
    ]))->assertStatus(401);

    expect($product->fresh()->name_en)->toBe('Original Name');
});

it('hides nothing on an unauthenticated hide request', function () {
    $product = Product::factory()->create();

    $this->post('/products/'.$product->gtin.'/hide')->assertStatus(401);

    expect($product->fresh()->is_hidden)->toBeFalse();
});

it('unhides nothing on an unauthenticated unhide request', function () {
    $product = Product::factory()->hidden()->create();

    $this->post('/products/'.$product->gtin.'/unhide')->assertStatus(401);

    expect($product->fresh()->is_hidden)->toBeTrue();
});

it('deletes nothing on an unauthenticated delete request', function () {
    $product = Product::factory()->hidden()->create();

    $this->delete('/products/'.$product->gtin)->assertStatus(401);

    $this->assertDatabaseHas('products', ['gtin' => $product->gtin]);
});

it('removes no image on an unauthenticated remove image request', function () {
    $product = Product::factory()->create(['image_path' => 'product-images/generated-name.jpg']);

    $this->post('/products/'.$product->gtin.'/remove-image')->assertStatus(401);

    expect($product->fresh()->image_path)->toBe('product-images/generated-name.jpg');
});

it('does not accept a forged session flag from the request', function () {
    $this->get('/products?admin_authenticated=1')->assertStatus(401);
    $this->post('/products', productPayload(['admin_authenticated' => 1]))->assertStatus(401);
});
