<?php

namespace Database\Factories;

use App\Models\Commissariat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commissariat>
 */
class CommissariatFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Commissariat de '.fake()->unique()->city(),
            'code' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
