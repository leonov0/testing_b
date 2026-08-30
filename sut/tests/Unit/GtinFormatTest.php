<?php

// U1 - Gtin::isValidFormat(). only 13 or 14 digits pass, everything else fails. no check digit.

use App\Services\Gtin;

it('accepts a 13 digit code', function () {
    expect(Gtin::isValidFormat('3000123456789'))->toBeTrue();
});

it('accepts a 14 digit code', function () {
    expect(Gtin::isValidFormat('30001234567890'))->toBeTrue();
});

it('accepts a 13 digit code whose leading zeros are significant', function () {
    expect(Gtin::isValidFormat('0000000000012'))->toBeTrue();
});

it('accepts a 14 digit code whose leading zeros are significant', function () {
    expect(Gtin::isValidFormat('00000000000012'))->toBeTrue();
});

it('accepts an integer of 13 digits', function () {
    expect(Gtin::isValidFormat(3000123456789))->toBeTrue();
});

it('rejects a 12 digit code as one digit too short', function () {
    expect(Gtin::isValidFormat('300012345678'))->toBeFalse();
});

it('rejects an 11 digit code', function () {
    expect(Gtin::isValidFormat('30001234567'))->toBeFalse();
});

it('rejects a 15 digit code as one digit too long', function () {
    expect(Gtin::isValidFormat('300012345678901'))->toBeFalse();
});

it('rejects a code containing letters', function () {
    expect(Gtin::isValidFormat('30001234567AB'))->toBeFalse();
});

it('rejects a code that is entirely letters', function () {
    expect(Gtin::isValidFormat('abcdefghijklm'))->toBeFalse();
});

it('rejects a code with a leading space', function () {
    expect(Gtin::isValidFormat(' 3000123456789'))->toBeFalse();
});

it('rejects a code with a trailing space', function () {
    expect(Gtin::isValidFormat('3000123456789 '))->toBeFalse();
});

it('rejects a code with an inner space', function () {
    expect(Gtin::isValidFormat('300012 3456789'))->toBeFalse();
});

it('rejects a code containing a hyphen', function () {
    expect(Gtin::isValidFormat('300-0123456789'))->toBeFalse();
});

it('rejects a code carrying a plus sign', function () {
    expect(Gtin::isValidFormat('+3000123456789'))->toBeFalse();
});

it('rejects a code carrying a decimal point', function () {
    expect(Gtin::isValidFormat('3000123456789.0'))->toBeFalse();
});

it('rejects a decimal number of the right digit count', function () {
    expect(Gtin::isValidFormat('300012345678.9'))->toBeFalse();
});

it('rejects the empty string', function () {
    expect(Gtin::isValidFormat(''))->toBeFalse();
});

it('rejects a string of spaces', function () {
    expect(Gtin::isValidFormat('             '))->toBeFalse();
});

it('rejects null', function () {
    expect(Gtin::isValidFormat(null))->toBeFalse();
});

it('rejects a float', function () {
    expect(Gtin::isValidFormat(3000123456789.0))->toBeFalse();
});

it('rejects a boolean', function () {
    expect(Gtin::isValidFormat(true))->toBeFalse();
});

it('rejects an array', function () {
    expect(Gtin::isValidFormat(['3000123456789']))->toBeFalse();
});
