<?php

namespace Tests\Feature\DemandesVgt;

use App\Enums\VgtCardTemplate;
use App\Models\DemandeVgt;
use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        $this->actingAs($admin)->put("/mairies/{$mairie->id}/carte-vgt-modele", ['card_template' => 'jaune'])
            ->assertRedirect('/demandes-vgt')->assertSessionHas('status');

        $this->assertSame(VgtCardTemplate::Jaune, $mairie->fresh()->card_template);
    }

    public function test_a_commissaire_cannot_change_a_mairies_card_template(): void
    {
        $chef = User::factory()->commissaire()->create();
        $mairie = Mairie::factory()->create();

        $this->actingAs($chef)->put("/mairies/{$mairie->id}/carte-vgt-modele", ['card_template' => 'jaune'])->assertForbidden();

        $this->assertSame(VgtCardTemplate::Officiel, $mairie->fresh()->card_template);
    }

    public function test_the_print_dialog_offers_every_model_for_a_paid_demande(): void
    {
        $admin = User::factory()->adminNational()->create();
        $demande = DemandeVgt::factory()->create(['status' => 'payee']);

        $response = $this->actingAs($admin)->get('/demandes-vgt');

        foreach (VgtCardTemplate::cases() as $template) {
            $response->assertSee("/demandes-vgt/{$demande->id}/carte/{$template->value}?face=recto", false);
        }
    }

    public function test_each_model_renders_the_real_card_of_the_demande(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'payee']);

        foreach (VgtCardTemplate::cases() as $template) {
            $this->actingAs($agent)->get("/demandes-vgt/{$demande->id}/carte/{$template->value}")
                ->assertOk()->assertSee('data-model="'.$template->value.'"', false)->assertSee($demande->moto->plate_number)->assertSee(str_pad((string) $demande->id, 6, '0', STR_PAD_LEFT));
        }

        $this->actingAs($agent)->get("/demandes-vgt/{$demande->id}/carte/ancien")->assertSee('data-model="ancien"', false)->assertDontSee('data-model="officiel"', false);
    }

    public function test_the_card_is_unavailable_for_an_unknown_model_an_unpaid_demande_or_another_institution(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $paid = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'payee']);
        $pending = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee']);
        $foreign = DemandeVgt::factory()->create(['status' => 'payee']);

        $this->actingAs($agent)->get("/demandes-vgt/{$paid->id}/carte/inconnu")->assertNotFound();
        $this->actingAs($agent)->get("/demandes-vgt/{$pending->id}/carte/officiel")->assertNotFound();
        $this->actingAs($agent)->get("/demandes-vgt/{$foreign->id}/carte/officiel")->assertNotFound();
    }

    public function test_the_card_requires_the_view_permission(): void
    {
        $demande = DemandeVgt::factory()->create(['status' => 'payee']);

        $this->get("/demandes-vgt/{$demande->id}/carte/officiel")->assertRedirect('/login');
        $this->actingAs(User::factory()->police()->create())->get("/demandes-vgt/{$demande->id}/carte/officiel")->assertRedirect(); // police : canal API seulement, refusée sur le Web
    }

    public function test_the_default_logo_is_the_drawn_arms_when_the_mairie_has_none(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'payee']);

        $this->actingAs($agent)->get("/demandes-vgt/{$demande->id}/carte/officiel")->assertOk()
            ->assertSee('assets/images/vignette/arms-bamako.png', false);
    }

    public function test_the_admin_national_uploads_a_logo_that_replaces_the_default_on_every_model(): void
    {
        Storage::fake('public');
        $admin = User::factory()->adminNational()->create();
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'payee']);

        $this->actingAs($admin)->post("/mairies/{$mairieModel->id}/logo-vgt", ['logo' => UploadedFile::fake()->image('logo.png', 300, 300)])
            ->assertRedirect('/demandes-vgt')->assertSessionHas('status');

        $path = $mairieModel->fresh()->logo_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        foreach (VgtCardTemplate::cases() as $template) {
            $this->actingAs($agent)->get("/demandes-vgt/{$demande->id}/carte/{$template->value}")->assertOk()
                ->assertSee('storage/'.$path, false)->assertDontSee('assets/images/vignette/arms-', false);
        }
    }

    public function test_replacing_or_resetting_the_logo_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->adminNational()->create();
        $mairieModel = Mairie::factory()->create();

        $this->actingAs($admin)->post("/mairies/{$mairieModel->id}/logo-vgt", ['logo' => UploadedFile::fake()->image('a.png', 200, 200)]);
        $first = $mairieModel->fresh()->logo_path;
        $this->actingAs($admin)->post("/mairies/{$mairieModel->id}/logo-vgt", ['logo' => UploadedFile::fake()->image('b.jpg', 200, 200)]);
        $second = $mairieModel->fresh()->logo_path;

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->actingAs($admin)->delete("/mairies/{$mairieModel->id}/logo-vgt")->assertRedirect('/demandes-vgt');

        $this->assertNull($mairieModel->fresh()->logo_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_an_invalid_logo_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->adminNational()->create();
        $mairieModel = Mairie::factory()->create();

        foreach ([
            UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->image('big.png', 300, 300)->size(2048),
            UploadedFile::fake()->image('tiny.png', 40, 40),
        ] as $file) {
            $this->actingAs($admin)->from('/demandes-vgt')->post("/mairies/{$mairieModel->id}/logo-vgt", ['logo' => $file])->assertSessionHasErrors('logo');
        }

        $this->assertNull($mairieModel->fresh()->logo_path);
    }

    public function test_a_commissaire_cannot_change_a_mairies_logo(): void
    {
        Storage::fake('public');
        $chef = User::factory()->commissaire()->create();
        $mairieModel = Mairie::factory()->create();

        $this->actingAs($chef)->post("/mairies/{$mairieModel->id}/logo-vgt", ['logo' => UploadedFile::fake()->image('logo.png', 300, 300)])->assertForbidden();
        $this->actingAs($chef)->delete("/mairies/{$mairieModel->id}/logo-vgt")->assertForbidden();
    }

    public function test_the_monument_image_is_configurable_with_a_default_drawing(): void
    {
        Storage::fake('public');
        $admin = User::factory()->adminNational()->create();
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'payee']);

        $this->actingAs($agent)->get("/demandes-vgt/{$demande->id}/carte/officiel")->assertOk()
            ->assertSee('viewBox="0 0 100 150"', false)->assertDontSee('alt="Monument"', false);

        $this->actingAs($admin)->post("/mairies/{$mairieModel->id}/monument-vgt", ['monument' => UploadedFile::fake()->image('monument.jpg', 736, 1104)])
            ->assertRedirect('/demandes-vgt')->assertSessionHas('status');

        $path = $mairieModel->fresh()->monument_path;
        Storage::disk('public')->assertExists($path);

        foreach (['officiel', 'jaune'] as $model) {
            $this->actingAs($agent)->get("/demandes-vgt/{$demande->id}/carte/$model")->assertOk()
                ->assertSee('storage/'.$path, false)->assertSee('alt="Monument"', false)->assertDontSee('viewBox="0 0 100 150"', false);
        }

        $this->actingAs($admin)->delete("/mairies/{$mairieModel->id}/monument-vgt")->assertRedirect('/demandes-vgt');
        $this->assertNull($mairieModel->fresh()->monument_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_an_invalid_monument_image_or_an_unauthorised_user_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->adminNational()->create();
        $mairieModel = Mairie::factory()->create();

        $this->actingAs($admin)->from('/demandes-vgt')->post("/mairies/{$mairieModel->id}/monument-vgt", ['monument' => UploadedFile::fake()->create('m.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('monument');
        $this->actingAs($admin)->from('/demandes-vgt')->post("/mairies/{$mairieModel->id}/monument-vgt", ['monument' => UploadedFile::fake()->image('big.jpg', 800, 1200)->size(3072)])->assertSessionHasErrors('monument');
        $this->assertNull($mairieModel->fresh()->monument_path);

        $this->actingAs(User::factory()->commissaire()->create())->post("/mairies/{$mairieModel->id}/monument-vgt", ['monument' => UploadedFile::fake()->image('m.jpg', 300, 400)])->assertForbidden();
    }

    public function test_the_card_settings_are_also_reachable_from_the_mairies_screen(): void
    {
        $admin = User::factory()->adminNational()->create();
        $mairieModel = Mairie::factory()->create(['name' => 'Mairie de Test']);

        $this->actingAs($admin)->get('/mairies')->assertOk()
            ->assertSee('cardSettings-'.$mairieModel->id, false)
            ->assertSee('Carte VGT — Mairie de Test')
            ->assertSee(route('mairies.logo.update', $mairieModel), false)
            ->assertSee('name="back" value="mairies"', false);
    }

    public function test_the_settings_return_to_the_screen_they_were_used_from(): void
    {
        Storage::fake('public');
        $admin = User::factory()->adminNational()->create();
        $mairieModel = Mairie::factory()->create();

        $this->actingAs($admin)->put("/mairies/{$mairieModel->id}/carte-vgt-modele", ['card_template' => 'rose', 'back' => 'mairies'])->assertRedirect('/mairies');
        $this->actingAs($admin)->post("/mairies/{$mairieModel->id}/logo-vgt", ['logo' => UploadedFile::fake()->image('l.png', 200, 200), 'back' => 'mairies'])->assertRedirect('/mairies');
        $this->actingAs($admin)->delete("/mairies/{$mairieModel->id}/logo-vgt", ['back' => 'mairies'])->assertRedirect('/mairies');
        $this->actingAs($admin)->post("/mairies/{$mairieModel->id}/monument-vgt", ['monument' => UploadedFile::fake()->image('m.png', 200, 300), 'back' => 'mairies'])->assertRedirect('/mairies');
        $this->actingAs($admin)->delete("/mairies/{$mairieModel->id}/monument-vgt", ['back' => 'mairies'])->assertRedirect('/mairies');

        // Sans indication (ou avec une valeur inconnue), retour sur l'écran Demandes VGT : aucune redirection libre.
        $this->actingAs($admin)->put("/mairies/{$mairieModel->id}/carte-vgt-modele", ['card_template' => 'officiel', 'back' => 'https://exemple.test'])->assertRedirect('/demandes-vgt');
    }
}
