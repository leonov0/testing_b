<?php

use App\Models\Company;
use Illuminate\Support\Facades\RateLimiter;

// F1 - passphrase login and the 401 gate (R7).

beforeEach(fn () => RateLimiter::clear('login:127.0.0.1'));

it('serves the login page to anyone', function () {
    $this->get('/login')->assertOk()->assertSee('Passphrase', false);
});

it('rejects a wrong passphrase and does not authenticate the session', function () {
    $this->post('/login', ['passphrase' => 'not-admin'])
        ->assertRedirect()
        ->assertSessionHasErrors('passphrase');

    expect(session()->get('admin_authenticated'))->toBeNull();
});

it('requires a passphrase', function () {
    $this->post('/login', [])->assertSessionHasErrors('passphrase');
});

it('authenticates the session with the admin passphrase', function () {
    $this->post('/login', ['passphrase' => 'admin'])->assertRedirect('/products');

    expect(session()->get('admin_authenticated'))->toBeTrue();
});

it('lets an authenticated session reach the management area', function () {
    $this->post('/login', ['passphrase' => 'admin']);

    $this->get('/products')->assertOk();
    $this->get('/companies')->assertOk();
});

it('answers 401, not a redirect, for every management route without a session', function () {
    $company = Company::factory()->create();

    foreach (managementRoutes((string) $company->id) as [$method, $uri]) {
        $this->json(strtoupper($method), $uri)->assertStatus(401);
    }
});

it('answers 401 for a management page requested as a document', function () {
    $this->get('/products')->assertStatus(401);
    $this->get('/companies')->assertStatus(401);
});

it('drops the session on logout', function () {
    $this->post('/login', ['passphrase' => 'admin']);
    $this->post('/logout')->assertRedirect('/login');

    $this->get('/products')->assertStatus(401);
});

it('keeps the public pages reachable without a session', function () {
    $this->get('/verify')->assertOk();
    $this->get('/products.json')->assertOk();
});
