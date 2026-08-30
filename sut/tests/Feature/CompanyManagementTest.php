<?php

// F2 - company create / edit / list.

use App\Models\Company;
use App\Models\Product;

it('lists the active companies', function () {
    Company::factory()->create(['company_name' => 'Active Cheese Co']);
    Company::factory()->deactivated()->create(['company_name' => 'Dormant Cheese Co']);

    asAdmin()->get('/companies')
        ->assertOk()
        ->assertSee('Active Cheese Co')
        ->assertDontSee('Dormant Cheese Co');
});

it('lists the deactivated companies separately', function () {
    Company::factory()->create(['company_name' => 'Active Cheese Co']);
    Company::factory()->deactivated()->create(['company_name' => 'Dormant Cheese Co']);

    asAdmin()->get('/companies/deactivated')
        ->assertOk()
        ->assertSee('Dormant Cheese Co')
        ->assertDontSee('Active Cheese Co');
});

it('shows a company with the products it owns', function () {
    $company = Company::factory()->create(['company_name' => 'Fromagerie Test']);
    Product::factory()->for($company)->create(['name_en' => 'Salted Butter Block']);
    Product::factory()->create(['name_en' => 'Other Company Jam']);

    asAdmin()->get('/companies/'.$company->id)
        ->assertOk()
        ->assertSee('Fromagerie Test')
        ->assertSee('Salted Butter Block')
        ->assertDontSee('Other Company Jam');
});

it('shows the hidden products of a company on its management page', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->hidden()->create(['name_en' => 'Hidden Butter Block']);

    asAdmin()->get('/companies/'.$company->id)
        ->assertOk()
        ->assertSee('Hidden Butter Block');
});

it('serves the new company form', function () {
    asAdmin()->get('/companies/new')->assertOk();
});

it('creates a company from a complete submission', function () {
    asAdmin()->post('/companies', companyPayload())->assertRedirect();

    $this->assertDatabaseHas('companies', [
        'company_name' => 'Fromagerie Test',
        'company_email' => 'company@example.test',
        'owner_name' => 'Owner Test',
        'contact_email' => 'contact@example.test',
    ]);
});

it('creates a company that is active', function () {
    asAdmin()->post('/companies', companyPayload());

    expect(Company::query()->firstWhere('company_name', 'Fromagerie Test')->deactivated)->toBeFalse();
});

it('refuses a company submission missing a required field', function (string $field) {
    asAdmin()->from('/companies/new')
        ->post('/companies', companyPayload([$field => '']))
        ->assertSessionHasErrors($field);

    expect(Company::query()->count())->toBe(0);
})->with([
    'company_name', 'company_address', 'company_telephone', 'company_email',
    'owner_name', 'owner_mobile', 'owner_email',
    'contact_name', 'contact_mobile', 'contact_email',
]);

it('refuses a company submission whose email address is malformed', function (string $field) {
    asAdmin()->from('/companies/new')
        ->post('/companies', companyPayload([$field => 'not-an-email']))
        ->assertSessionHasErrors($field);

    expect(Company::query()->count())->toBe(0);
})->with(['company_email', 'owner_email', 'contact_email']);

it('serves the edit form of an existing company', function () {
    $company = Company::factory()->create(['company_name' => 'Fromagerie Test']);

    asAdmin()->get('/companies/'.$company->id.'/edit')
        ->assertOk()
        ->assertSee('Fromagerie Test');
});

it('updates a company', function () {
    $company = Company::factory()->create(['company_name' => 'Old Name']);

    asAdmin()->put('/companies/'.$company->id, companyPayload(['company_name' => 'New Name']))
        ->assertRedirect();

    expect($company->fresh()->company_name)->toBe('New Name');
});

it('refuses an update that empties a required field', function () {
    $company = Company::factory()->create(['company_name' => 'Old Name']);

    asAdmin()->from('/companies/'.$company->id.'/edit')
        ->put('/companies/'.$company->id, companyPayload(['company_name' => '']))
        ->assertSessionHasErrors('company_name');

    expect($company->fresh()->company_name)->toBe('Old Name');
});

it('exposes no delete route for a company', function () {
    $company = Company::factory()->create();

    asAdmin()->delete('/companies/'.$company->id)->assertStatus(405);

    $this->assertDatabaseHas('companies', ['id' => $company->id]);
});
