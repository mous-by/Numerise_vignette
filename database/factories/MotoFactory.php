<?php

namespace Database\Factories;

use App\Models\Commissariat;
use App\Models\Moto;
use App\Models\Proprietaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Moto>
 */
class MotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commissariat_id' => Commissariat::factory(),
            'proprietaire_id' => Proprietaire::factory(),
            'plate_number' => strtoupper(fake()->unique()->bothify('?? #### ??')),
            'color' => fake()->safeColorName(),
            'type_or_brand' => fake()->randomElement(['Sanili', 'Sanya', 'Djakarta', 'Yamaha', 'Sagitar']),
            'vgt_year' => (int) date('Y'),
            'has_sale_certificate' => false,
            'has_witness' => false,
        ];
    }

    public function withSaleCertificate(): static
    {
        return $this->state(fn () => [
            'has_sale_certificate' => true,
            'seller_first_name' => fake()->firstName(),
            'seller_last_name' => fake()->lastName(),
            'seller_phone' => '+2237'.fake()->numerify('#######'),
            'seller_address' => fake()->streetAddress(),
        ]);
    }
}
