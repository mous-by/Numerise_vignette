<?php

namespace Database\Factories;

use App\Enums\DeclarationType;
use App\Models\Commissariat;
use App\Models\Declaration;
use App\Models\Moto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Declaration>
 */
class DeclarationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commissariat_id' => Commissariat::factory(),
            'moto_id' => Moto::factory(),
            'type' => DeclarationType::Vol->value,
            'location' => fake()->streetAddress(),
            'occurred_at' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'description' => fake()->sentence(12),
        ];
    }
}
