<?php

// F4 - product list and search. search looks at name and description in both languages, case does not matter.

use App\Models\Product;

it('shows visible and hidden products in the management listing', function () {
    Product::factory()->create(['name_en' => 'Visible Butter Block']);
    Product::factory()->hidden()->create(['name_en' => 'Hidden Butter Block']);

    asAdmin()->get('/products')
        ->assertOk()
        ->assertSee('Visible Butter Block')
        ->assertSee('Hidden Butter Block');
});

it('marks a hidden product as hidden in the management listing', function () {
    Product::factory()->hidden()->create(['name_en' => 'Hidden Butter Block']);

    asAdmin()->get('/products')->assertSee('Hidden');
});

it('finds a product by its English name', function () {
    Product::factory()->create(['name_en' => 'Salted Butter', 'name_fr' => 'Aucun', 'description_en' => 'Aucun', 'description_fr' => 'Aucun']);
    Product::factory()->create(['name_en' => 'Strawberry Jam', 'name_fr' => 'Aucun', 'description_en' => 'Aucun', 'description_fr' => 'Aucun']);

    asAdmin()->get('/products?query=Salted')
        ->assertSee('Salted Butter')
        ->assertDontSee('Strawberry Jam');
});

it('finds a product by its French name', function () {
    Product::factory()->create(['name_en' => 'Nothing Here', 'name_fr' => 'Beurre demi-sel', 'description_en' => 'Nothing', 'description_fr' => 'Rien']);
    Product::factory()->create(['name_en' => 'Strawberry Jam', 'name_fr' => 'Confiture', 'description_en' => 'Nothing', 'description_fr' => 'Rien']);

    asAdmin()->get('/products?query=demi-sel')
        ->assertSee('Nothing Here')
        ->assertDontSee('Strawberry Jam');
});

it('finds a product by its English description', function () {
    Product::factory()->create(['name_en' => 'Anonymous One', 'name_fr' => 'Anonyme', 'description_en' => 'Churned in Brittany', 'description_fr' => 'Rien']);
    Product::factory()->create(['name_en' => 'Anonymous Two', 'name_fr' => 'Anonyme', 'description_en' => 'Nothing at all', 'description_fr' => 'Rien']);

    asAdmin()->get('/products?query=Brittany')
        ->assertSee('Anonymous One')
        ->assertDontSee('Anonymous Two');
});

it('finds a product by its French description', function () {
    Product::factory()->create(['name_en' => 'Anonymous One', 'name_fr' => 'Anonyme', 'description_en' => 'Nothing', 'description_fr' => 'Baratte en Bretagne']);
    Product::factory()->create(['name_en' => 'Anonymous Two', 'name_fr' => 'Anonyme', 'description_en' => 'Nothing', 'description_fr' => 'Rien du tout']);

    asAdmin()->get('/products?query=Bretagne')
        ->assertSee('Anonymous One')
        ->assertDontSee('Anonymous Two');
});

it('matches the keyword case-insensitively in the English name', function () {
    Product::factory()->create(['name_en' => 'Salted Butter', 'name_fr' => 'Aucun', 'description_en' => 'Aucun', 'description_fr' => 'Aucun']);

    asAdmin()->get('/products?query=sAlTeD')->assertSee('Salted Butter');
});

it('matches the keyword case-insensitively in the French description', function () {
    Product::factory()->create(['name_en' => 'Anonymous One', 'name_fr' => 'Anonyme', 'description_en' => 'Nothing', 'description_fr' => 'Baratte en Bretagne']);

    asAdmin()->get('/products?query=BRETAGNE')->assertSee('Anonymous One');
});

it('matches a keyword found in the middle of a field', function () {
    Product::factory()->create(['name_en' => 'Extra Salted Butter Block', 'name_fr' => 'Aucun', 'description_en' => 'Aucun', 'description_fr' => 'Aucun']);

    asAdmin()->get('/products?query=Salted')->assertSee('Extra Salted Butter Block');
});

it('lists every product when no keyword is submitted', function () {
    Product::factory()->create(['name_en' => 'Salted Butter']);
    Product::factory()->create(['name_en' => 'Strawberry Jam']);

    asAdmin()->get('/products')
        ->assertSee('Salted Butter')
        ->assertSee('Strawberry Jam');
});

it('lists every product when the keyword is blank', function () {
    Product::factory()->create(['name_en' => 'Salted Butter']);
    Product::factory()->create(['name_en' => 'Strawberry Jam']);

    asAdmin()->get('/products?query=')
        ->assertSee('Salted Butter')
        ->assertSee('Strawberry Jam');
});

it('reports no match for a keyword that matches nothing', function () {
    Product::factory()->create(['name_en' => 'Salted Butter', 'name_fr' => 'Aucun', 'description_en' => 'Aucun', 'description_fr' => 'Aucun']);

    asAdmin()->get('/products?query=zzzzznothingzzzzz')->assertDontSee('Salted Butter');
});

it('searches hidden products in the management listing too', function () {
    Product::factory()->hidden()->create(['name_en' => 'Hidden Salted Butter', 'name_fr' => 'Aucun', 'description_en' => 'Aucun', 'description_fr' => 'Aucun']);

    asAdmin()->get('/products?query=Salted')->assertSee('Hidden Salted Butter');
});

it('searches the French fields through the JSON API too', function () {
    $found = Product::factory()->create(['name_en' => 'Anonymous One', 'name_fr' => 'Beurre demi-sel', 'description_en' => 'Nothing', 'description_fr' => 'Rien']);
    Product::factory()->create(['name_en' => 'Anonymous Two', 'name_fr' => 'Confiture', 'description_en' => 'Nothing', 'description_fr' => 'Rien']);

    $data = $this->getJson('/products.json?query=demi-sel')->assertOk()->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['gtin'])->toBe($found->gtin);
});

it('searches the French description through the JSON API too', function () {
    $found = Product::factory()->create(['name_en' => 'Anonymous One', 'name_fr' => 'Anonyme', 'description_en' => 'Nothing', 'description_fr' => 'Baratte en Bretagne']);
    Product::factory()->create(['name_en' => 'Anonymous Two', 'name_fr' => 'Anonyme', 'description_en' => 'Nothing', 'description_fr' => 'Rien']);

    $data = $this->getJson('/products.json?query=Bretagne')->assertOk()->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['gtin'])->toBe($found->gtin);
});
