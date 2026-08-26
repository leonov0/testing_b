<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $gross = fake()->randomFloat(2, 0.5, 5);

        return [
            'company_id' => Company::factory(),
            'gtin' => self::gtin(),
            'name_en' => fake()->words(3, true),
            'name_fr' => fake()->words(3, true),
            'description_en' => fake()->sentence(12),
            'description_fr' => fake()->sentence(12),
            'brand' => fake()->company(),
            'country_of_origin' => 'France',
            'weight_gross' => $gross,
            'weight_net' => round($gross * 0.9, 2),
            'weight_unit' => fake()->randomElement(['kg', 'L', 'g']),
            'image_path' => null,
            'is_hidden' => false,
        ];
    }

    /** A unique 13 digit GTIN. */
    public static function gtin(int $digits = 13): string
    {
        return substr(str_pad((string) fake()->unique()->numberBetween(1, 999999999999999), $digits, '0', STR_PAD_LEFT), 0, $digits);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_hidden' => true]);
    }
}
