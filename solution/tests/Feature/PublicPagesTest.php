<?php

use App\Models\Company;
use App\Models\Product;

// F10 / F11 - the public GTIN verification page and the public product page (R8, R9).

it('serves the verification form to anyone', function () {
    $this->get('/verify')->assertOk()->assertSee('GTIN', false);
});

it('reports every submitted code as valid and shows the all valid banner', function () {
    $first = Product::factory()->create();
    $second = Product::factory()->create();

    $response = $this->post('/verify', ['gtins' => $first->gtin."\n".$second->gtin]);

    $response->assertOk()->assertSee('All valid');
    expect(substr_count($response->getContent(), '>Valid<'))->toBe(2);
});

it('does not show the all valid banner when one code is unknown', function () {
    $product = Product::factory()->create();

    $this->post('/verify', ['gtins' => $product->gtin."\n9999999999999"])
        ->assertOk()
        ->assertDontSee('All valid')
        ->assertSee('Not valid');
});

it('treats a hidden product as not valid', function () {
    $hidden = Product::factory()->hidden()->create();

    $this->post('/verify', ['gtins' => $hidden->gtin])
        ->assertOk()
        ->assertDontSee('All valid')
        ->assertSee('Not valid');
});

it('treats a product of a deactivated company as not valid', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();
    $company->deactivate();

    $this->post('/verify', ['gtins' => $product->gtin])
        ->assertOk()
        ->assertSee('Not valid');
});

it('reports one row per submitted line, duplicates included', function () {
    $product = Product::factory()->create();

    $response = $this->post('/verify', ['gtins' => $product->gtin."\n".$product->gtin]);

    expect(substr_count($response->getContent(), 'data-gtin="'.$product->gtin.'"'))->toBe(2);
});

it('ignores blank lines and surrounding spaces', function () {
    $product = Product::factory()->create();

    $this->post('/verify', ['gtins' => "\n  ".$product->gtin."  \n\n"])
        ->assertOk()
        ->assertSee('All valid');
});

it('says nothing was submitted for an empty textarea', function () {
    $this->post('/verify', ['gtins' => ''])
        ->assertOk()
        ->assertDontSee('All valid')
        ->assertSee('No GTIN codes submitted.');
});

it('shows the public product page with every required field', function () {
    $company = Company::factory()->create(['company_name' => 'Euro Expo']);
    $product = Product::factory()->for($company)->create([
        'gtin' => '03000123456789',
        'name_en' => 'Organic Apple Juice',
        'description_en' => 'Pressed from organic apples.',
        'weight_gross' => 1.1,
        'weight_net' => 1.0,
        'weight_unit' => 'L',
    ]);

    $this->get('/01/'.$product->gtin)
        ->assertOk()
        ->assertSee('Euro Expo')
        ->assertSee('Organic Apple Juice')
        ->assertSee('03000123456789')
        ->assertSee('Pressed from organic apples.')
        ->assertSee('1.1 L')
        ->assertSee('1 L');
});

it('renders the english page with a matching lang attribute', function () {
    $product = Product::factory()->create();

    $this->get('/01/'.$product->gtin)->assertOk()->assertSee('<html lang="en">', false);
});

it('switches to french content and the french lang attribute', function () {
    $product = Product::factory()->create([
        'name_en' => 'Organic Apple Juice',
        'name_fr' => 'Jus de pomme biologique',
        'description_fr' => 'Pressé à partir de pommes biologiques.',
    ]);

    $this->get('/01/'.$product->gtin.'?lang=fr')
        ->assertOk()
        ->assertSee('<html lang="fr">', false)
        ->assertSee('Jus de pomme biologique')
        ->assertSee('Pressé à partir de pommes biologiques.');
});

it('falls back to english for an unknown language', function () {
    $product = Product::factory()->create(['name_en' => 'Organic Apple Juice']);

    $this->get('/01/'.$product->gtin.'?lang=de')
        ->assertOk()
        ->assertSee('<html lang="en">', false)
        ->assertSee('Organic Apple Juice');
});

it('shows the placeholder image when no image was uploaded', function () {
    $product = Product::factory()->create(['image_path' => null]);

    $this->get('/01/'.$product->gtin)->assertOk()->assertSee('product-placeholder', false);
});

it('answers 404 for a hidden product', function () {
    $hidden = Product::factory()->hidden()->create();

    $this->get('/01/'.$hidden->gtin)->assertStatus(404);
});

it('answers 404 for a GTIN that does not exist', function () {
    $this->get('/01/9999999999999')->assertStatus(404);
});
