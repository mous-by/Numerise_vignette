<?php

namespace Database\Factories;

use App\Enums\VgtCardTemplate;
use App\Models\Mairie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mairie>
 */
class MairieFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Mairie de '.fake()->unique()->city(),
            'code' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function template(VgtCardTemplate $template): static
    {
        return $this->state(fn () => ['card_template' => $template->value]);
    }
}
