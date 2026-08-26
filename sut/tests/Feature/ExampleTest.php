<?php

/*
 * Example feature test - style reference. Do not delete, skip or weaken it.
 *
 * The suite runs against an in-memory SQLite database (phpunit.xml). Use the model
 * factories in database/factories to build the data a test needs.
 */

use App\Models\Product;

it('serves the public products API without a session', function () {
    Product::factory()->count(2)->create();

    $this->getJson('/products.json')
        ->assertOk()
        ->assertJsonStructure(['data', 'pagination' => ['current_page', 'total_pages', 'per_page']]);
});
