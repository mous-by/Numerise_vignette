<?php

namespace Tests\Feature\Api;

use App\Models\Information;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Contrat §8 (informations, W6) : lecture publique, sans jeton, consommée par la police (M2) et la population (M5).
 */
class InformationApiTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    public function test_the_endpoint_is_public_and_returns_every_commissariat(): void
    {
        $old = Information::factory()->create(['description' => 'Ancienne', 'published_at' => now()->subDays(2)]);
        $recent = Information::factory()->create(['description' => 'Récente', 'published_at' => now()]);

        $response = $this->getJson('/api/v1/informations')->assertOk();

        $response->assertJsonPath('data.0.id', $recent->id)
            ->assertJsonPath('data.1.id', $old->id)
            ->assertJsonPath('data.0.commissaire_name', $recent->commissaire->name)
            ->assertJsonPath('data.0.commissariat_name', $recent->commissariat->name)
            ->assertJsonStructure(['data' => [['id', 'commissaire_name', 'commissariat_name', 'description', 'image_url', 'document_url', 'published_at']], 'meta' => ['current_page', 'last_page']]);
    }

    public function test_no_token_is_required(): void
    {
        Information::factory()->create();

        $this->getJson('/api/v1/informations')->assertOk();
    }

    public function test_fields_without_a_file_are_null_not_missing(): void
    {
        $information = Information::factory()->create(['description' => 'Texte seul']);

        $this->getJson('/api/v1/informations')
            ->assertJsonPath('data.0.image_url', null)
            ->assertJsonPath('data.0.document_url', null)
            ->assertJsonPath('data.0.description', 'Texte seul');
    }
}
