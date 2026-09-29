<?php

namespace Database\Factories;

use App\Models\TarifVgt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TarifVgt>
 */
class TarifVgtFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type_or_brand' => fake()->unique()->randomElement(['Sanili', 'Sanya', 'Djakarta', 'Yamaha', 'Sagitar']),
            'amount' => fake()->numberBetween(5000, 15000),
        ];
    }
}
