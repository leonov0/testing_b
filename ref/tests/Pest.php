<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Security');

/** Authenticates the session the way the passphrase login does. */
function asAdmin(): Tests\TestCase
{
    return test()->withSession(['admin_authenticated' => true]);
}

/** Every management route, as [method, uri] pairs, for authorization sweeps. */
function managementRoutes(string $companyId = '1', string $gtin = '03000123456789'): array
{
    return [
        ['get', '/companies'],
        ['get', '/companies/deactivated'],
        ['get', '/companies/new'],
        ['post', '/companies'],
        ['get', "/companies/{$companyId}"],
        ['get', "/companies/{$companyId}/edit"],
        ['put', "/companies/{$companyId}"],
        ['post', "/companies/{$companyId}/deactivate"],
        ['post', "/companies/{$companyId}/reactivate"],
        ['get', '/products'],
        ['get', '/products/new'],
        ['post', '/products'],
        ['get', "/products/{$gtin}"],
        ['get', "/products/{$gtin}/edit"],
        ['put', "/products/{$gtin}"],
        ['post', "/products/{$gtin}/hide"],
        ['post', "/products/{$gtin}/unhide"],
        ['post', "/products/{$gtin}/remove-image"],
        ['delete', "/products/{$gtin}"],
    ];
}
