<?php

/*
 * Example unit test - style reference. Do not delete, skip or weaken it.
 */

use App\Services\Gtin;

it('accepts a 13 digit GTIN', function () {
    expect(Gtin::isValidFormat('3000123456789'))->toBeTrue();
});
