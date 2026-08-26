<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $euroExpo = Company::factory()->create([
            'company_name' => 'Euro Expo',
            'company_address' => 'Boulevard de l\'Europe, 69680 Chassieu, France',
            'company_telephone' => '+33 1 41 56 78 00',
            'company_email' => 'mail.customerservice.hdq@example.com',
            'owner_name' => 'Benjamin Smith',
            'owner_mobile' => '+33 6 12 34 56 78',
            'owner_email' => 'b.smith@example.com',
            'contact_name' => 'Marie Dubois',
            'contact_mobile' => '+33 6 98 76 54 32',
            'contact_email' => 'm.dubois@example.com',
        ]);

        Product::factory()->for($euroExpo)->create([
            'gtin' => '03000123456789',
            'name_en' => 'Organic Apple Juice',
            'name_fr' => 'Jus de pomme biologique',
            'description_en' => 'Our organic apple juice is pressed from 100% fresh organic apples, with no added sugars or preservatives.',
            'description_fr' => 'Notre jus de pomme biologique est pressé à partir de 100% de pommes biologiques fraîches, sans sucre ajouté ni conservateurs.',
            'brand' => 'Green Orchard',
            'country_of_origin' => 'France',
            'weight_gross' => 1.1,
            'weight_net' => 1.0,
            'weight_unit' => 'L',
        ]);

        $others = Company::factory()->count(4)->create();

        foreach ($others as $index => $company) {
            Product::factory()->count(6)->for($company)->create();

            if ($index === 3) {
                Product::factory()->count(2)->hidden()->for($company)->create();
            }
        }

        Company::factory()->deactivated()->create(['company_name' => 'Fermé SARL'])
            ->products()->saveMany(Product::factory()->count(2)->hidden()->make());
    }
}
