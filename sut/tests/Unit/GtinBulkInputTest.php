<?php

// U2 - Gtin::splitBulkInput(). one row per line, same order, duplicates kept, blank lines and spaces dropped. bad lines stay so we can show them back.

use App\Services\Gtin;

it('splits codes separated by unix line breaks', function () {
    expect(Gtin::splitBulkInput("3000123456789\n3000123456790"))
        ->toBe(['3000123456789', '3000123456790']);
});

it('splits codes separated by windows line breaks', function () {
    expect(Gtin::splitBulkInput("3000123456789\r\n3000123456790"))
        ->toBe(['3000123456789', '3000123456790']);
});

it('splits codes separated by carriage returns alone', function () {
    expect(Gtin::splitBulkInput("3000123456789\r3000123456790"))
        ->toBe(['3000123456789', '3000123456790']);
});

it('trims spaces surrounding each code', function () {
    expect(Gtin::splitBulkInput("  3000123456789  \n\t3000123456790\t"))
        ->toBe(['3000123456789', '3000123456790']);
});

it('trims a single code submitted with surrounding spaces', function () {
    expect(Gtin::splitBulkInput('   3000123456789   '))->toBe(['3000123456789']);
});

it('drops blank lines between codes', function () {
    expect(Gtin::splitBulkInput("3000123456789\n\n3000123456790"))
        ->toBe(['3000123456789', '3000123456790']);
});

it('drops whitespace only lines', function () {
    expect(Gtin::splitBulkInput("3000123456789\n   \n\t\n3000123456790"))
        ->toBe(['3000123456789', '3000123456790']);
});

it('drops leading and trailing blank lines', function () {
    expect(Gtin::splitBulkInput("\n\n3000123456789\n\n"))->toBe(['3000123456789']);
});

it('keeps the submitted order', function () {
    expect(Gtin::splitBulkInput("3000000000003\n1000000000001\n2000000000002"))
        ->toBe(['3000000000003', '1000000000001', '2000000000002']);
});

it('keeps duplicate codes as separate entries', function () {
    expect(Gtin::splitBulkInput("3000123456789\n3000123456789"))
        ->toBe(['3000123456789', '3000123456789']);
});

it('keeps entries that are not valid GTIN codes so they can be reported', function () {
    expect(Gtin::splitBulkInput("not-a-gtin\n300012345678\n3000123456789"))
        ->toBe(['not-a-gtin', '300012345678', '3000123456789']);
});

it('returns an empty list for an empty string', function () {
    expect(Gtin::splitBulkInput(''))->toBe([]);
});

it('returns an empty list for null', function () {
    expect(Gtin::splitBulkInput(null))->toBe([]);
});

it('returns an empty list for whitespace only input', function () {
    expect(Gtin::splitBulkInput("   \n\t\n  "))->toBe([]);
});

it('returns a list indexed from zero after dropping blank lines', function () {
    expect(array_keys(Gtin::splitBulkInput("\n3000123456789\n\n3000123456790")))->toBe([0, 1]);
});
