<?php

namespace Tests\Feature\Web;

use App\Models\ActivityLog;
use App\Models\Commissariat;
use App\Models\Mairie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

class DashboardAndModulesTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    public function test_the_superadmin_supervision_shows_real_socle_figures_and_the_single_superadmin_alert(): void
    {
        Commissariat::factory()->count(2)->create();
        Mairie::factory()->create();
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)->get('/')
            ->assertOk()
            ->assertSee('Supervision plateforme')
            ->assertSee('Vue d\'ensemble de la plateforme')
            ->assertSee('Un seul superadmin actif')
            ->assertDontSee('Données fictives');
    }

    public function test_no_alert_when_two_superadmins_are_active(): void
    {
        $this->superadmin();
        $second = $this->superadmin();

        $this->actingAs($second)->get('/')->assertOk()->assertDontSee('Un seul superadmin actif');
    }

    public function test_mock_mode_fills_the_whole_dashboard_with_clearly_labelled_fictitious_data(): void
    {
        config(['dashboard.mock' => true]);

        $this->actingAs($this->superadmin())->get('/')
            ->assertOk()
            ->assertSee('Données fictives')
            ->assertSee('Propriétaires')
            ->assertSee('Motos enregistrées')
            ->assertSee('Demandes VGT en attente')
            ->assertSee('Commissariat du 1er Arrondissement')
            ->assertSee('DERNIÈRES ACTIONS');
    }

    public function test_mock_data_is_role_specific(): void
    {
        config(['dashboard.mock' => true]);
        $commissariat = Commissariat::factory()->create();

        $this->actingAs(User::factory()->commissaire($commissariat)->create())->get('/')
            ->assertOk()->assertSee('Déclarations de vol ouvertes')->assertSee('DERNIÈRES DEMANDES VGT')->assertDontSee('DERNIÈRES ACTIONS');

        $this->actingAs(User::factory()->mairie()->create())->get('/')
            ->assertOk()->assertSee('Cartes à remettre')->assertSee('RETRAITS DU JOUR');

        $this->actingAs(User::factory()->adminNational()->create())->get('/')
            ->assertOk()->assertSee('COMMISSARIATS')->assertDontSee('DERNIÈRES ACTIONS');
    }

    public function test_mock_data_is_never_shown_in_production(): void
    {
        config(['dashboard.mock' => true]);
        $this->app['env'] = 'production';

        $this->actingAs($this->superadmin())->get('/')->assertOk()->assertDontSee('Données fictives')->assertDontSee('Commissariat du 1er Arrondissement');
    }

    public function test_real_mode_shows_only_what_the_role_may_see(): void
    {
        $commissariat = Commissariat::factory()->create();
        User::factory()->police($commissariat)->count(2)->create();
        $chef = User::factory()->commissaire($commissariat)->create();

        $this->actingAs($chef)->get('/')
            ->assertOk()
            ->assertSee('Utilisateurs actifs')
            ->assertDontSee('Commissariats')
            ->assertDontSee('Données fictives');

        // Un agent de mairie n'a aucune permission du socle : pas de chiffres utilisateurs, mais ses tâches VGT.
        $this->actingAs(User::factory()->mairie()->create())->get('/')->assertOk()->assertSee('Bonjour')->assertSee('À traiter maintenant')->assertDontSee('Utilisateurs actifs');
    }

    public function test_the_sidebar_lists_every_planned_module_for_national_roles(): void
    {
        $expected = collect(config('planned_modules'))->except(config('sidebar.embedded_planned'))->pluck('label');
        $this->assertGreaterThanOrEqual(9, $expected->count());

        // Superadmin : voit tout, y compris les modules déjà implémentés (via leur vraie route désormais, plus
        // la fiche « à venir » — même libellé visible dans les deux cas), sauf « Contrôle de police » : réservé
        // au canal API (police, D31/§4.3), aucune entrée Web même pour le superadmin (navigation => []).
        $page = $this->actingAs($this->superadmin())->get('/')->assertOk()->assertSee('Modules à venir');
        foreach ($expected->reject(fn ($label) => $label === 'Contrôle de police') as $label) {
            $page->assertSee($label);
        }

        // Admin national : pareil, sauf Propriétaires, Motos, Déclarations et Motos retrouvées — réservés au
        // commissaire par défaut (données personnelles des citoyens, pas une supervision nationale par défaut
        // comme les institutions, W7 à W10) — et sauf Contrôle de police (API seulement, aucune permission
        // possible côté Web). Demandes VGT reste visible : `view` lui est donné en plus de `manage_tarifs`,
        // sinon le bouton « Tarifs », imbriqué dans cet écran, serait inatteignable.
        $adminPage = $this->actingAs(User::factory()->adminNational()->create())->get('/')->assertOk()->assertSee('Modules à venir');
        $reserved = ['Propriétaires', 'Motos', 'Déclarations', 'Motos retrouvées', 'Contrôle de police'];
        foreach ($expected->reject(fn ($label) => in_array($label, $reserved, true)) as $label) {
            $adminPage->assertSee($label);
        }
        foreach (['Propriétaires', 'Déclarations', 'Contrôle de police'] as $label) {
            $adminPage->assertDontSee($label);
        }
        // Pas de assertDontSee('Motos') : « Motos retrouvées » (réservé au commissaire aussi, mais déjà exclu
        // du foreach ci-dessus) le contiendrait.
    }

    public function test_each_web_role_sees_the_modules_the_cahier_gives_it(): void
    {
        $chef = User::factory()->commissaire()->create();
        $this->actingAs($chef)->get('/')->assertSee('Propriétaires')->assertSee('Motos retrouvées')->assertSee('Demandes VGT')->assertDontSee('QR Code')->assertDontSee('Contrôle de police');

        $agent = User::factory()->mairie()->create();
        $this->actingAs($agent)->get('/')->assertSee('Cartes à remettre')->assertSee('Demandes VGT')->assertDontSee('Propriétaires')->assertDontSee('Motos retrouvées');
    }

    public function test_a_planned_module_page_is_a_sheet_with_open_points_and_no_data(): void
    {
        $this->actingAs($this->superadmin())->get('/modules/paiements')
            ->assertOk()
            ->assertSee('À venir')
            ->assertSee('code marchand de l')
            ->assertSee('config/modules/paiements.php');
    }

    public function test_a_planned_module_hidden_from_a_role_is_a_404(): void
    {
        $agent = User::factory()->mairie()->create();

        $this->get('/modules/qr-code')->assertRedirect('/login'); // invité, avant toute authentification
        $this->actingAs($agent)->get('/modules/qr-code')->assertNotFound();
        $this->actingAs($agent)->get('/modules/n-existe-pas')->assertNotFound();
    }

    public function test_a_module_leaves_the_planned_list_when_its_manifest_exists(): void
    {
        $superadmin = $this->superadmin();
        config(['modules.qr-code' => ['label' => 'QR Code', 'permissions' => ['view' => 'Voir'], 'roles' => []]]);

        $this->actingAs($superadmin)->get('/modules/qr-code')->assertNotFound();
        $this->actingAs($superadmin)->get('/modules/sms')->assertOk();
    }

    /** Le HTML de la sidebar seule (le corps de la page peut contenir les mêmes mots). */
    private function sidebar(string $html): string
    {
        preg_match('#<div class="sidebar-wrapper".*?</ul>\s*</div>#s', $html, $match);

        return $match[0] ?? '';
    }

    public function test_the_settings_entry_replaces_roles_and_sits_below_every_other_menu(): void
    {
        $sidebar = $this->sidebar($this->actingAs($this->superadmin())->get('/')->getContent());

        $this->assertStringContainsString('Paramètres', $sidebar);
        $this->assertStringNotContainsString('>Rôles<', $sidebar, 'L\'entrée « Rôles » est devenue « Paramètres ».');

        // Sous « Tableau de bord », sous tous les modules à venir, en dernier.
        $lastPlanned = collect(config('planned_modules'))->pluck('label')->map(fn ($label) => strrpos($sidebar, $label))->max();
        $this->assertGreaterThan($lastPlanned, strrpos($sidebar, 'Paramètres'));
        $this->assertGreaterThan(strpos($sidebar, 'Modules à venir'), strpos($sidebar, 'Paramètres'));
        $this->assertNotSame('', $sidebar);
    }

    public function test_permissions_and_assignment_are_not_in_the_sidebar_but_in_the_settings_menu(): void
    {
        $superadmin = $this->superadmin();

        $sidebar = $this->sidebar($this->actingAs($superadmin)->get('/')->getContent());
        $this->assertStringNotContainsString('Attribution', $sidebar);
        $this->assertStringNotContainsString('bx-shield-alt-2', $sidebar);
        $this->assertStringNotContainsString('bx-user-check', $sidebar);
        // Audit et Système non plus (W3, W4) : ils s'ouvrent depuis Paramètres.
        $this->assertStringNotContainsString('bx-list-check', $sidebar);
        $this->assertStringNotContainsString('bx-server', $sidebar);

        // Ils restent accessibles depuis la zone Paramètres.
        $page = $this->get('/roles')->assertOk()->assertSee('PARAMÈTRES');
        $page->assertSee('Permissions')->assertSee('Attribution des permissions')->assertSee('Rôles')
            ->assertSee(route('audit.index'))->assertSee(route('system.index'));
    }

    public function test_audit_and_system_are_reached_from_the_settings_menu_and_show_it(): void
    {
        $superadmin = $this->superadmin();

        foreach (['/audit' => 'audit.index', '/system' => 'system.index'] as $uri => $route) {
            $this->actingAs($superadmin)->get($uri)->assertOk()
                ->assertSee('PARAMÈTRES')
                ->assertSee('list-group-item list-group-item-action active', false)
                ->assertSee(route($route));
        }
    }

    public function test_settings_stays_highlighted_on_every_settings_page(): void
    {
        $superadmin = $this->superadmin();

        foreach (['/roles', '/permissions', '/user-permissions', '/audit', '/system'] as $uri) {
            $sidebar = $this->sidebar($this->actingAs($superadmin)->get($uri)->getContent());
            $this->assertMatchesRegularExpression('#mm-active[^>]*>\s*<div class="parent-icon"><i class=\'bx bx-cog\'#', $sidebar, "Paramètres doit être actif sur $uri");
        }
    }

    public function test_the_settings_entry_is_hidden_from_roles_without_access(): void
    {
        $sidebar = $this->sidebar($this->actingAs(User::factory()->adminNational()->create())->get('/')->getContent());

        $this->assertStringNotContainsString('Paramètres', $sidebar);
    }

    public function test_the_profile_shows_role_and_institution(): void
    {
        $commissariat = Commissariat::factory()->create(['name' => 'Commissariat de Test']);
        $chef = User::factory()->commissaire($commissariat)->create(['name' => 'Chef Test']);

        $this->actingAs($chef)->get('/profile')->assertOk()->assertSee('Chef Test')->assertSee('Commissaire')->assertSee('Commissariat de Test');
    }

    public function test_pages_never_reference_the_internet(): void
    {
        $login = $this->get('/login')->getContent();
        $pages = $login.$this->actingAs($this->superadmin())->get('/')->getContent().$this->get('/profile')->getContent().$this->get('/roles')->getContent().$this->get('/permissions')->getContent();

        preg_match_all('#(?:src|href|action)=["\'](https?://[^"\']+)#', $pages, $matches);
        // asset() et route() produisent des URL absolues sur le serveur lui-même : seules les URL vers un AUTRE hôte sont interdites.
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $external = array_values(array_unique(array_filter($matches[1], fn ($url) => parse_url($url, PHP_URL_HOST) !== $appHost)));
        $this->assertSame([], $external, 'Ressource externe dans une page (D15).');
        foreach (['fonts.googleapis', 'unpkg.com', 'jsdelivr', 'cdnjs', 'code.jquery.com'] as $cdn) {
            $this->assertStringNotContainsString($cdn, $pages);
        }
    }

    public function test_the_audit_table_of_the_supervision_lists_real_entries(): void
    {
        $superadmin = $this->superadmin(['name' => 'Moustapha Test']);
        $this->actingAs($superadmin);
        User::factory()->adminNational()->create(['name' => 'Cible Test']);

        $this->get('/')->assertOk()->assertSee('DERNIÈRES ACTIONS')->assertSee('Cible Test');
        $this->assertGreaterThan(0, ActivityLog::count());
    }
}
