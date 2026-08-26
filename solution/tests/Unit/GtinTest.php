<?php

use App\Services\Gtin;

// U1 - R1 GTIN format: any sequence of 13 or 14 digits.

dataset('valid gtins', [
    'thirteen digits' => ['3000123456789'],
    'fourteen digits' => ['03000123456789'],
    'leading zeros kept' => ['00000000000001'],
    'all nines, thirteen' => ['9999999999999'],
    'all zeros, thirteen' => ['0000000000000'],
]);

dataset('invalid gtins', [
    'twelve digits' => ['300012345678'],
    'fifteen digits' => ['030001234567890'],
    'empty string' => [''],
    'letters' => ['30001234567AB'],
    'spaces inside' => ['3000 12345 6789'],
    'leading space' => [' 3000123456789'],
    'trailing space' => ['3000123456789 '],
    'hyphens' => ['3000-1234-56789'],
    'plus sign' => ['+3000123456789'],
    'decimal point' => ['3000123456789.0'],
]);

it('accepts a code of 13 or 14 digits', function (string $gtin) {
    expect(Gtin::isValidFormat($gtin))->toBeTrue();
})->with('valid gtins');

it('rejects anything that is not 13 or 14 digits', function (string $gtin) {
    expect(Gtin::isValidFormat($gtin))->toBeFalse();
})->with('invalid gtins');

it('rejects a non string, non integer value', function () {
    expect(Gtin::isValidFormat(null))->toBeFalse()
        ->and(Gtin::isValidFormat(['3000123456789']))->toBeFalse()
        ->and(Gtin::isValidFormat(3.5))->toBeFalse();
});

it('accepts an integer of the right length', function () {
    expect(Gtin::isValidFormat(3000123456789))->toBeTrue();
});

// U2 - bulk verification input parsing.

it('splits the textarea on unix line breaks', function () {
    expect(Gtin::splitBulkInput("3000123456789\n4000123456789"))
        ->toBe(['3000123456789', '4000123456789']);
});

it('splits the textarea on windows line breaks', function () {
    expect(Gtin::splitBulkInput("3000123456789\r\n4000123456789"))
        ->toBe(['3000123456789', '4000123456789']);
});

it('trims each line and drops the empty ones', function () {
    expect(Gtin::splitBulkInput("  3000123456789  \n\n\n 4000123456789\n"))
        ->toBe(['3000123456789', '4000123456789']);
});

it('keeps duplicates and the submitted order', function () {
    expect(Gtin::splitBulkInput("4000123456789\n3000123456789\n4000123456789"))
        ->toBe(['4000123456789', '3000123456789', '4000123456789']);
});

it('returns an empty list for empty or missing input', function () {
    expect(Gtin::splitBulkInput(''))->toBe([])
        ->and(Gtin::splitBulkInput("\n\n  \n"))->toBe([])
        ->and(Gtin::splitBulkInput(null))->toBe([]);
});

it('keeps invalid entries so that they can be reported back', function () {
    expect(Gtin::splitBulkInput("not-a-gtin\n3000123456789"))
        ->toBe(['not-a-gtin', '3000123456789']);
});
