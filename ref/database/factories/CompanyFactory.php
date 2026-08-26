<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Company> */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'company_address' => fake()->streetAddress().', France',
            'company_telephone' => '+33 1 '.fake()->numerify('## ## ## ##'),
            'company_email' => fake()->unique()->safeEmail(),
            'owner_name' => fake()->name(),
            'owner_mobile' => '+33 6 '.fake()->numerify('## ## ## ##'),
            'owner_email' => fake()->unique()->safeEmail(),
            'contact_name' => fake()->name(),
            'contact_mobile' => '+33 6 '.fake()->numerify('## ## ## ##'),
            'contact_email' => fake()->unique()->safeEmail(),
            'deactivated' => false,
        ];
    }

    public function deactivated(): static
    {
        return $this->state(fn () => ['deactivated' => true]);
    }
}
