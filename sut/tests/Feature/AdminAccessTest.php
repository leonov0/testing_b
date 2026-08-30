<?php

// F1 - login with a passphrase. admin stuff must answer 401 when there is no session, not a redirect and not a 404.

use App\Models\Company;
use App\Models\Product;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('login:127.0.0.1');
});

it('shows the passphrase form on the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Passphrase', false)
        ->assertSee('name="passphrase"', false);
});

it('does not authenticate a wrong passphrase', function () {
    $this->post('/login', ['passphrase' => 'not-the-passphrase'])
        ->assertSessionMissing('admin_authenticated');
});

it('reports an error for a wrong passphrase', function () {
    $this->from('/login')
        ->post('/login', ['passphrase' => 'not-the-passphrase'])
        ->assertSessionHasErrors('passphrase');
});

it('does not authenticate a missing passphrase', function () {
    $this->from('/login')
        ->post('/login', [])
        ->assertSessionHasErrors('passphrase')
        ->assertSessionMissing('admin_authenticated');
});

it('does not authenticate an empty passphrase', function () {
    $this->from('/login')
        ->post('/login', ['passphrase' => ''])
        ->assertSessionMissing('admin_authenticated');
});

it('authenticates the correct passphrase', function () {
    $this->post('/login', ['passphrase' => config('catalogue.admin_passphrase')])
        ->assertRedirect()
        ->assertSessionHas('admin_authenticated', true);
});

it('opens the management area once the passphrase is accepted', function () {
    $this->post('/login', ['passphrase' => config('catalogue.admin_passphrase')]);

    $this->get('/products')->assertOk();
});

it('closes the session on logout', function () {
    $this->post('/login', ['passphrase' => config('catalogue.admin_passphrase')]);

    $this->post('/logout')
        ->assertRedirect('/login')
        ->assertSessionMissing('admin_authenticated');
});

it('locks the management area again after logout', function () {
    $this->post('/login', ['passphrase' => config('catalogue.admin_passphrase')]);
    $this->post('/logout');

    $this->get('/products')->assertStatus(401);
});

it('answers 401 on every management route for a document request', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    foreach (managementRoutes($company->id, $product->gtin) as [$method, $path]) {
        $this->{$method}($path)->assertStatus(401, "{$method} {$path} must answer 401");
    }
});

it('answers 401 on every management route for a JSON request', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    foreach (managementRoutes($company->id, $product->gtin) as [$method, $path]) {
        $this->{$method.'Json'}($path)->assertStatus(401, "{$method} {$path} must answer 401 as JSON");
    }
});

it('never redirects an unauthenticated management request to the login page', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create();

    foreach (managementRoutes($company->id, $product->gtin) as [$method, $path]) {
        $response = $this->{$method}($path);

        expect($response->getStatusCode())->not->toBeIn([301, 302, 303, 307, 308],
            "{$method} {$path} must not redirect");
    }
});

it('keeps the public surfaces open without a session', function () {
    Product::factory()->create();

    $this->get('/verify')->assertOk();
    $this->get('/products.json')->assertOk();
    $this->get('/login')->assertOk();
});
