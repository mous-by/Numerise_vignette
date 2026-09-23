<?php

namespace Database\Factories;

use App\Models\Commissariat;
use App\Models\Information;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Information>
 */
class InformationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $commissariat = Commissariat::factory()->create();

        return [
            'commissariat_id' => $commissariat->id,
            'commissaire_id' => User::factory()->commissaire($commissariat)->create()->id,
            'description' => fake()->sentence(12),
            'published_at' => now(),
        ];
    }
}
