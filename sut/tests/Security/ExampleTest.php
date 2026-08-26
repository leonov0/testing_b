<?php

/*
 * Example security test - style reference. Do not delete, skip or weaken it.
 *
 * A security test asserts the SECURE behaviour required by the security contract in
 * docs/spec.md. If the delivered application is insecure, this kind of test goes red -
 * that red test is the finding, and it belongs in defects.md.
 */

use App\Models\Product;

it('does not expose internal columns through the public API', function () {
    Product::factory()->create();

    $body = $this->getJson('/products.json')->getContent();

    expect($body)->not->toContain('created_at')
        ->and($body)->not->toContain('image_path');
});
