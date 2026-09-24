<?php

namespace Tests\Feature\DemandesVgt;

use App\Enums\VgtCardTemplate;
use App\Models\DemandeVgt;
use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Modèle de carte VGT par mairie (W13, App\Enums\VgtCardTemplate) — PROPOSITION TECHNIQUE, le cahier ne décrit
 * aucun visuel (voir CLAUDE.md §5). Réglage gardé par la permission `mairies.update` déjà du manifeste, pas
 * d'écran Mairies dédié (W2, pas encore construit).
 */
class CardTemplateTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    public function test_officiel_is_the_default_template_for_a_new_mairie(): void
    {
        // Le défaut vit dans la colonne SQL (DEFAULT 'officiel'), pas dans la factory : fresh() relit la ligne
        // plutôt que l'instance en mémoire juste après create(), qui ne connaît que ce qui lui a été assigné.
        $mairie = Mairie::factory()->create();

        $this->assertSame(VgtCardTemplate::Officiel, $mairie->fresh()->card_template);
    }

    public function test_the_admin_national_changes_a_mairies_card_template(): void
    {
        $admin = User::factory()->adminNational()->create();
        $mairie = Mairie::factory()->create();

        $this->actingAs($admin)->put("/mairies/{$mairie->id}/carte-vgt-modele", ['card_template' => 'premium'])
            ->assertRedirect('/demandes-vgt')->assertSessionHas('status');

        $this->assertSame(VgtCardTemplate::Premium, $mairie->fresh()->card_template);
    }

    public function test_a_commissaire_cannot_change_a_mairies_card_template(): void
    {
        $chef = User::factory()->commissaire()->create();
        $mairie = Mairie::factory()->create();

        $this->actingAs($chef)->put("/mairies/{$mairie->id}/carte-vgt-modele", ['card_template' => 'premium'])->assertForbidden();

        $this->assertSame(VgtCardTemplate::Officiel, $mairie->fresh()->card_template);
    }

    public function test_the_printed_card_follows_the_mairies_chosen_template(): void
    {
        $admin = User::factory()->adminNational()->create();
        $mairieModel = Mairie::factory()->template(VgtCardTemplate::Minimaliste)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'payee']);

        $response = $this->actingAs($admin)->get('/demandes-vgt');

        $response->assertSee('Courier New', false); // marqueur du modèle « minimaliste »
        $response->assertDontSee('République du Mali'); // marqueur du modèle « officiel », non choisi ici
    }
}
