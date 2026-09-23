<?php

namespace Database\Factories;

use App\Models\Commissariat;
use App\Models\Moto;
use App\Models\MotoRetrouvee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MotoRetrouvee>
 */
class MotoRetrouveeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commissariat_id' => Commissariat::factory(),
            'moto_id' => Moto::factory(),
            'found_at' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'location' => fake()->streetAddress(),
            'recovered' => false,
            'recovered_at' => null,
        ];
    }
}
