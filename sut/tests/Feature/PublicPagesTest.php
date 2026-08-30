<?php

// F10 - the two public pages.

use App\Models\Company;
use App\Models\Product;

// how many result rows are on the verify page
function rowCount(string $content): int
{
    return substr_count($content, '>Valid<') + substr_count($content, '>Not valid<');
}

it('serves the verification form', function () {
    $this->get('/verify')
        ->assertOk()
        ->assertSee('name="gtins"', false);
});

it('reports one row per submitted line', function () {
    Product::factory()->create(['gtin' => '3000123456789']);
    Product::factory()->create(['gtin' => '3000123456790']);

    $content = $this->post('/verify', ['gtins' => "3000123456789\n3000123456790\n9999999999999"])
        ->assertOk()
        ->getContent();

    expect(rowCount($content))->toBe(3);
});

it('keeps duplicate submissions as separate rows', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    $content = $this->post('/verify', ['gtins' => "3000123456789\n3000123456789"])->getContent();

    expect(rowCount($content))->toBe(2)
        ->and(substr_count($content, '>Valid<'))->toBe(2);
});

it('ignores blank lines and surrounding spaces', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    $content = $this->post('/verify', ['gtins' => "\n  3000123456789  \n\n   \n"])->getContent();

    expect(rowCount($content))->toBe(1)
        ->and(substr_count($content, '>Valid<'))->toBe(1);
});

it('reports a registered visible code as valid', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    $content = $this->post('/verify', ['gtins' => '3000123456789'])->getContent();

    expect(substr_count($content, '>Valid<'))->toBe(1)
        ->and(substr_count($content, '>Not valid<'))->toBe(0);
});

it('reports an unregistered code as not valid', function () {
    $content = $this->post('/verify', ['gtins' => '9999999999999'])->getContent();

    expect(substr_count($content, '>Not valid<'))->toBe(1);
});

it('reports a hidden product code as not valid', function () {
    Product::factory()->hidden()->create(['gtin' => '3000123456789']);

    $content = $this->post('/verify', ['gtins' => '3000123456789'])->getContent();

    expect(substr_count($content, '>Not valid<'))->toBe(1)
        ->and(substr_count($content, '>Valid<'))->toBe(0);
});

it('reports the code of a deactivated company product as not valid', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['gtin' => '3000123456789']);
    $company->deactivate();

    $content = $this->post('/verify', ['gtins' => '3000123456789'])->getContent();

    expect(substr_count($content, '>Not valid<'))->toBe(1);
});

it('reports a malformed code as not valid', function () {
    $content = $this->post('/verify', ['gtins' => 'not-a-gtin'])->getContent();

    expect(substr_count($content, '>Not valid<'))->toBe(1);
});

it('shows the all valid banner when every submitted code is valid', function () {
    Product::factory()->create(['gtin' => '3000123456789']);
    Product::factory()->create(['gtin' => '3000123456790']);

    $this->post('/verify', ['gtins' => "3000123456789\n3000123456790"])
        ->assertOk()
        ->assertSee('All valid');
});

it('hides the all valid banner when one submitted code is not valid', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    $this->post('/verify', ['gtins' => "3000123456789\n9999999999999"])
        ->assertOk()
        ->assertDontSee('All valid');
});

it('hides the all valid banner when the only valid code is accompanied by a hidden one', function () {
    Product::factory()->create(['gtin' => '3000123456789']);
    Product::factory()->hidden()->create(['gtin' => '3000123456790']);

    $this->post('/verify', ['gtins' => "3000123456789\n3000123456790"])
        ->assertDontSee('All valid');
});

it('hides the all valid banner when no submitted code is valid', function () {
    $this->post('/verify', ['gtins' => "9999999999999\n8888888888888"])
        ->assertDontSee('All valid');
});

it('reports that nothing was submitted for an empty submission', function () {
    $response = $this->post('/verify', ['gtins' => '']);

    $response->assertOk()->assertDontSee('All valid');
    expect(rowCount($response->getContent()))->toBe(0);
    $response->assertSee('No GTIN codes submitted.');
});

it('reports that nothing was submitted for whitespace only input', function () {
    $response = $this->post('/verify', ['gtins' => "  \n\n\t"]);

    $response->assertOk()->assertDontSee('All valid');
    expect(rowCount($response->getContent()))->toBe(0);
});

it('verifies without a session', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    $this->post('/verify', ['gtins' => '3000123456789'])->assertOk();
});

it('shows the product details on the public product page', function () {
    $company = Company::factory()->create(['company_name' => 'Fromagerie Test']);
    $product = Product::factory()->for($company)->create([
        'gtin' => '3000123456789',
        'name_en' => 'Salted Butter',
        'description_en' => 'Churned in Brittany.',
        'weight_gross' => 1.25,
        'weight_net' => 0.75,
        'weight_unit' => 'kg',
    ]);

    $this->get('/01/3000123456789')
        ->assertOk()
        ->assertSee('Fromagerie Test')
        ->assertSee('Salted Butter')
        ->assertSee('3000123456789')
        ->assertSee('Churned in Brittany.')
        ->assertSee('1.25')
        ->assertSee('0.75')
        ->assertSee('kg');
});

it('shows the English text and declares English by default', function () {
    Product::factory()->create([
        'gtin' => '3000123456789',
        'name_en' => 'Salted Butter',
        'name_fr' => 'Beurre demi-sel',
        'description_en' => 'Churned in Brittany.',
        'description_fr' => 'Baratte en Bretagne.',
    ]);

    $this->get('/01/3000123456789')
        ->assertOk()
        ->assertSee('Salted Butter')
        ->assertSee('Churned in Brittany.')
        ->assertSee('<html lang="en"', false);
});

it('shows the English text when English is requested', function () {
    Product::factory()->create([
        'gtin' => '3000123456789',
        'name_en' => 'Salted Butter',
        'name_fr' => 'Beurre demi-sel',
        'description_en' => 'Churned in Brittany.',
        'description_fr' => 'Baratte en Bretagne.',
    ]);

    $this->get('/01/3000123456789?lang=en')
        ->assertOk()
        ->assertSee('Salted Butter')
        ->assertSee('Churned in Brittany.')
        ->assertSee('<html lang="en"', false);
});

it('shows the French text when French is requested', function () {
    Product::factory()->create([
        'gtin' => '3000123456789',
        'name_en' => 'Salted Butter',
        'name_fr' => 'Beurre demi-sel',
        'description_en' => 'Churned in Brittany.',
        'description_fr' => 'Baratte en Bretagne.',
    ]);

    $this->get('/01/3000123456789?lang=fr')
        ->assertOk()
        ->assertSee('Beurre demi-sel')
        ->assertSee('Baratte en Bretagne.')
        ->assertDontSee('Churned in Brittany.');
});

it('declares French in the html lang attribute when French is requested', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    $this->get('/01/3000123456789?lang=fr')
        ->assertOk()
        ->assertSee('<html lang="fr"', false);
});

it('falls back to English for an unknown language', function () {
    Product::factory()->create([
        'gtin' => '3000123456789',
        'name_en' => 'Salted Butter',
        'name_fr' => 'Beurre demi-sel',
        'description_en' => 'Churned in Brittany.',
        'description_fr' => 'Baratte en Bretagne.',
    ]);

    $this->get('/01/3000123456789?lang=de')
        ->assertOk()
        ->assertSee('Salted Butter')
        ->assertSee('<html lang="en"', false);
});

it('answers 404 on the public page of an unknown GTIN', function () {
    $this->get('/01/9999999999999')->assertStatus(404);
});

it('answers 404 on the public page of a hidden product', function () {
    Product::factory()->hidden()->create(['gtin' => '3000123456789']);

    $this->get('/01/3000123456789')->assertStatus(404);
});

it('serves the public product page without a session', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    $this->get('/01/3000123456789')->assertOk();
});

it('is mobile friendly through a viewport declaration', function () {
    Product::factory()->create(['gtin' => '3000123456789']);

    $this->get('/01/3000123456789')->assertSee('name="viewport"', false);
});
