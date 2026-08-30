<?php

// U4 - Company::toApiArray(). key names, owner and contact nested.

it('exposes exactly the documented top level keys', function () {
    expect(array_keys(apiCompany()->toApiArray()))
        ->toBe(['companyName', 'companyAddress', 'companyTelephone', 'companyEmail', 'owner', 'contact']);
});

it('reports the company identity under its documented key names', function () {
    $array = apiCompany()->toApiArray();

    expect($array['companyName'])->toBe('Fromagerie Test')
        ->and($array['companyAddress'])->toBe('1 rue du Test, France')
        ->and($array['companyTelephone'])->toBe('+33 1 11 11 11 11')
        ->and($array['companyEmail'])->toBe('company@example.test');
});

it('nests the owner as a name, mobile number and email object', function () {
    expect(apiCompany()->toApiArray()['owner'])->toBe([
        'name' => 'Owner Test',
        'mobileNumber' => '+33 6 11 11 11 11',
        'email' => 'owner@example.test',
    ]);
});

it('nests the contact as a name, mobile number and email object', function () {
    expect(apiCompany()->toApiArray()['contact'])->toBe([
        'name' => 'Contact Test',
        'mobileNumber' => '+33 6 22 22 22 22',
        'email' => 'contact@example.test',
    ]);
});

it('exposes no internal column', function () {
    $encoded = json_encode(apiCompany()->toApiArray());

    expect($encoded)->not->toContain('deactivated')
        ->and($encoded)->not->toContain('created_at')
        ->and($encoded)->not->toContain('updated_at')
        ->and($encoded)->not->toContain('"id"');
});

it('does not leak the deactivated flag of a deactivated company', function () {
    expect(apiCompany(['deactivated' => true])->toApiArray())->not->toHaveKey('deactivated');
});
