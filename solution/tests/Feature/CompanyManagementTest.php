<?php

use App\Models\Company;
use App\Models\Product;

// F2 - company listing, viewing and editing.

function companyPayload(array $overrides = []): array
{
    return array_merge([
        'company_name' => 'Euro Expo',
        'company_address' => 'Boulevard de l\'Europe, 69680 Chassieu, France',
        'company_telephone' => '+33 1 41 56 78 00',
        'company_email' => 'mail@example.com',
        'owner_name' => 'Benjamin Smith',
        'owner_mobile' => '+33 6 12 34 56 78',
        'owner_email' => 'b.smith@example.com',
        'contact_name' => 'Marie Dubois',
        'contact_mobile' => '+33 6 98 76 54 32',
        'contact_email' => 'm.dubois@example.com',
    ], $overrides);
}

it('lists the active companies', function () {
    Company::factory()->create(['company_name' => 'Active Co']);
    Company::factory()->deactivated()->create(['company_name' => 'Closed Co']);

    asAdmin()->get('/companies')
        ->assertOk()
        ->assertSee('Active Co')
        ->assertDontSee('Closed Co');
});

it('lists the deactivated companies separately', function () {
    Company::factory()->create(['company_name' => 'Active Co']);
    Company::factory()->deactivated()->create(['company_name' => 'Closed Co']);

    asAdmin()->get('/companies/deactivated')
        ->assertOk()
        ->assertSee('Closed Co')
        ->assertDontSee('Active Co');
});

it('shows a company with its products', function () {
    $company = Company::factory()->create(['company_name' => 'Euro Expo']);
    Product::factory()->for($company)->create(['name_en' => 'Organic Apple Juice']);

    asAdmin()->get('/companies/'.$company->id)
        ->assertOk()
        ->assertSee('Euro Expo')
        ->assertSee('Organic Apple Juice');
});

it('creates a company from the form', function () {
    asAdmin()->post('/companies', companyPayload())->assertRedirect();

    $this->assertDatabaseHas('companies', [
        'company_name' => 'Euro Expo',
        'owner_email' => 'b.smith@example.com',
        'deactivated' => false,
    ]);
});

it('requires every company field', function () {
    asAdmin()->post('/companies', [])
        ->assertSessionHasErrors([
            'company_name', 'company_address', 'company_telephone', 'company_email',
            'owner_name', 'owner_mobile', 'owner_email',
            'contact_name', 'contact_mobile', 'contact_email',
        ]);
});

it('rejects a malformed email on any of the three addresses', function () {
    foreach (['company_email', 'owner_email', 'contact_email'] as $field) {
        asAdmin()->post('/companies', companyPayload([$field => 'not-an-email']))
            ->assertSessionHasErrors([$field]);
    }
});

it('updates an existing company', function () {
    $company = Company::factory()->create();

    asAdmin()->put('/companies/'.$company->id, companyPayload(['company_name' => 'Renamed SARL']))
        ->assertRedirect();

    expect($company->fresh()->company_name)->toBe('Renamed SARL');
});

it('offers no way to delete a company', function () {
    $company = Company::factory()->create();

    asAdmin()->delete('/companies/'.$company->id)->assertStatus(405);

    $this->assertDatabaseHas('companies', ['id' => $company->id]);
});
