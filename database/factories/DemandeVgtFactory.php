<?php

namespace Database\Factories;

use App\Models\Commissariat;
use App\Models\DemandeVgt;
use App\Models\Mairie;
use App\Models\Moto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandeVgt>
 */
class DemandeVgtFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commissariat_id' => Commissariat::factory(),
            'mairie_id' => Mairie::factory(),
            'moto_id' => Moto::factory(),
            'vgt_year' => (int) date('Y'),
            'contact_phone' => '+2237'.fake()->unique()->numerify('#######'),
            'merchant_code' => fake()->optional()->bothify('MC-####??'),
            'status' => 'en_attente',
            'base_amount' => 6000,
            'is_late' => false,
            'surcharge_amount' => 0,
        ];
    }
}
