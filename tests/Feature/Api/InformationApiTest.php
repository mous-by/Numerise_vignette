<?php

namespace Tests\Feature\Api;

use App\Enums\InformationFileType;
use App\Models\Information;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Contrat §8 (informations, W6) : lecture publique, sans jeton, consommée par la police (M2) et la population (M5).
 * `image_urls`/`document_urls` sont des tableaux (plusieurs pièces jointes possibles, PROPOSITION TECHNIQUE).
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
            ->assertJsonStructure(['data' => [['id', 'commissaire_name', 'commissariat_name', 'description', 'image_urls', 'document_urls', 'published_at']], 'meta' => ['current_page', 'last_page']]);
    }

    public function test_no_token_is_required(): void
    {
        Information::factory()->create();

        $this->getJson('/api/v1/informations')->assertOk();
    }

    public function test_fields_without_a_file_are_empty_arrays_not_missing(): void
    {
        Information::factory()->create(['description' => 'Texte seul']);

        $this->getJson('/api/v1/informations')
            ->assertJsonPath('data.0.image_urls', [])
            ->assertJsonPath('data.0.document_urls', [])
            ->assertJsonPath('data.0.description', 'Texte seul');
    }

    public function test_several_images_and_documents_all_come_back_in_order(): void
    {
        $information = Information::factory()->create();
        $information->files()->createMany([
            ['type' => InformationFileType::Image, 'path' => 'informations/img-1.jpg', 'position' => 0],
            ['type' => InformationFileType::Image, 'path' => 'informations/img-2.jpg', 'position' => 1],
            ['type' => InformationFileType::Document, 'path' => 'informations/doc-1.pdf', 'position' => 0],
        ]);

        $response = $this->getJson('/api/v1/informations')->assertOk();

        $response->assertJsonCount(2, 'data.0.image_urls')
            ->assertJsonCount(1, 'data.0.document_urls')
            ->assertJsonPath('data.0.image_urls.0', fn ($url) => str_contains($url, 'img-1.jpg'))
            ->assertJsonPath('data.0.image_urls.1', fn ($url) => str_contains($url, 'img-2.jpg'));
    }
}
