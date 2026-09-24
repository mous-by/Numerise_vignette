<?php

namespace Tests\Feature\Web;

use App\Models\DemandeVgt;
use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Tableau de bord réel des modules métier : tâches à traiter par rôle, parcours des demandes VGT, chiffres,
 * toujours limités à ce que l'utilisateur peut voir (cloisonnement, §4.7).
 */
class DashboardBusinessTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        config(['dashboard.mock' => false]);
    }

    public function test_a_mairie_agent_sees_its_own_tasks_and_pipeline_only(): void
    {
        $mairie = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairie)->create();
        DemandeVgt::factory()->count(2)->create(['mairie_id' => $mairie->id, 'status' => 'en_attente']);
        DemandeVgt::factory()->create(['mairie_id' => $mairie->id, 'status' => 'validee']);
        DemandeVgt::factory()->count(3)->create(['mairie_id' => $mairie->id, 'status' => 'payee']);
        DemandeVgt::factory()->count(5)->create(['status' => 'en_attente']); // autre mairie : jamais comptée

        $page = $this->actingAs($agent)->get('/')->assertOk()
            ->assertSee('Bonjour')->assertSee('Demandes à valider')->assertSee('Paiements à confirmer')->assertSee('Cartes à remettre')
            ->assertSee('PARCOURS DES DEMANDES VGT')->assertSee('6 demande(s)')->assertSee('DERNIÈRES DEMANDES VGT')
            ->assertDontSee('Demandes rejetées');

        $page->assertSee('statut=en_attente', false)->assertSee('statut=payee', false);
    }

    public function test_a_commissaire_sees_the_rejected_demandes_to_correct(): void
    {
        $chef = User::factory()->commissaire()->create();
        DemandeVgt::factory()->count(2)->create(['commissariat_id' => $chef->commissariat_id, 'status' => 'rejetee']);

        $this->actingAs($chef)->get('/')->assertOk()
            ->assertSee('Demandes rejetées')->assertSee('À corriger et resoumettre')
            ->assertDontSee('Cartes à remettre')->assertDontSee('Demandes à valider');
    }

    public function test_the_task_counts_are_real_numbers(): void
    {
        $mairie = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairie)->create();
        DemandeVgt::factory()->count(4)->create(['mairie_id' => $mairie->id, 'status' => 'payee']);

        $this->actingAs($agent)->get('/')->assertOk()->assertSeeInOrder(['nv-task-count">4<', 'Cartes à remettre'], false);
    }

    public function test_the_encaisse_ce_mois_card_sums_confirmed_payments_for_the_mairie(): void
    {
        $mairie = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairie)->create();
        DemandeVgt::factory()->create(['mairie_id' => $mairie->id, 'status' => 'payee', 'base_amount' => 6000, 'surcharge_amount' => 1000, 'payment_confirmed_at' => now()]);
        DemandeVgt::factory()->create(['mairie_id' => $mairie->id, 'status' => 'retiree', 'base_amount' => 6000, 'payment_confirmed_at' => now()]);
        DemandeVgt::factory()->create(['mairie_id' => $mairie->id, 'status' => 'validee', 'base_amount' => 6000]); // non payée

        $this->actingAs($agent)->get('/')->assertOk()->assertSee('Encaissé ce mois')->assertSee('13 000 FCFA');
    }

    public function test_the_list_prefilters_by_status_from_the_dashboard_link(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->get('/demandes-vgt?statut=rejetee')->assertOk()->assertSee('data-key="rejetee"', false)->assertSee('URLSearchParams', false);
    }

    public function test_the_sidebar_groups_entries_by_domain_and_badges_pending_demandes(): void
    {
        $chef = User::factory()->commissaire()->create();
        DemandeVgt::factory()->count(3)->create(['commissariat_id' => $chef->commissariat_id, 'status' => 'rejetee']);
        DemandeVgt::factory()->create(['commissariat_id' => $chef->commissariat_id, 'status' => 'payee']); // pas une tâche du commissaire

        $this->actingAs($chef)->get('/')->assertOk()
            ->assertSeeInOrder(['Registre motos', 'Demandes VGT', 'Bientôt disponible', 'Modules à venir'])
            ->assertSee('nv-badge', false)->assertSee('title="3 à traiter"', false);
    }

    public function test_the_sidebar_has_no_badge_when_nothing_is_pending(): void
    {
        $this->actingAs(User::factory()->commissaire()->create())->get('/')->assertOk()->assertDontSee('nv-badge', false);
    }

    public function test_the_registre_screens_share_one_sidebar_link_and_a_tab_bar(): void
    {
        $chef = User::factory()->commissaire()->create();

        $home = $this->actingAs($chef)->get('/')->assertOk();
        $this->assertSame(1, substr_count($home->getContent(), 'href="'.route('motos.index').'"'), 'Un seul lien vers le registre dans le menu.');
        $home->assertDontSee('href="'.route('declarations.index').'"', false)->assertDontSee('href="'.route('motos-retrouvees.index').'"', false);

        foreach (['proprietaires', 'motos', 'declarations', 'motos-retrouvees'] as $screen) {
            $this->actingAs($chef)->get("/$screen")->assertOk()
                ->assertSee('nv-tabs', false)->assertSee('Propriétaires')->assertSee('Déclarations')->assertSee('Motos retrouvées');
        }
    }

    public function test_the_tab_bar_only_lists_screens_the_role_may_open(): void
    {
        $admin = User::factory()->adminNational()->create();

        $this->actingAs($admin)->get('/demandes-vgt')->assertOk()->assertDontSee('nv-tabs', false);
    }

    public function test_retrait_and_paiements_are_not_listed_as_upcoming_modules_anymore(): void
    {
        $page = $this->actingAs($this->superadmin())->get('/')->assertOk();

        $page->assertDontSee('href="'.route('modules.show', 'retraits-vgt').'"', false)->assertDontSee('href="'.route('modules.show', 'paiements').'"', false);
    }
}
