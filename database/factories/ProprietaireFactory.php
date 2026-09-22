<?php

namespace Database\Factories;

use App\Enums\Genre;
use App\Models\Commissariat;
use App\Models\Proprietaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proprietaire>
 */
class ProprietaireFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commissariat_id' => Commissariat::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(Genre::cases())->value,
            'address' => fake()->streetAddress(),
            'phone' => '+2237'.fake()->unique()->numerify('#######'),
            'emergency_contact' => null,
        ];
    }
}
