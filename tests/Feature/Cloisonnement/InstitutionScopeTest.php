<?php

namespace Tests\Feature\Cloisonnement;

use App\Exceptions\MissingInstitutionException;
use App\Models\Commissariat;
use App\Models\Concerns\BelongsToCommissariat;
use App\Models\Concerns\BelongsToMairie;
use App\Models\Mairie;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/** Modèle de test cloisonné par commissariat, adossé à la table users (aucune DDL dans les tests). */
class ScopedByCommissariat extends Model
{
    use BelongsToCommissariat;

    protected $table = 'users';

    protected $guarded = [];
}

class ScopedByMairie extends Model
{
    use BelongsToMairie;

    protected $table = 'users';

    protected $guarded = [];
}

/**
 * D8 : cloisonnement fail-closed, l'absence d'institution ne signifie jamais « tout voir ».
 */
class InstitutionScopeTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private Commissariat $a;

    private Commissariat $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->a = Commissariat::factory()->create();
        $this->b = Commissariat::factory()->create();
        User::factory()->police($this->a)->count(2)->create();
        User::factory()->police($this->b)->count(3)->create();
    }

    public function test_a_user_of_an_institution_only_sees_its_own_rows(): void
    {
        $this->actingAs(User::factory()->commissaire($this->a)->create());

        $this->assertSame(3, ScopedByCommissariat::count()); // 2 police + le commissaire lui-même
        $this->assertSame([$this->a->id], ScopedByCommissariat::pluck('commissariat_id')->unique()->values()->all());
    }

    public function test_national_roles_see_everything(): void
    {
        foreach ([$this->superadmin(), User::factory()->adminNational()->create()] as $user) {
            $this->actingAs($user);
            $this->assertSame(User::count(), ScopedByCommissariat::count());
        }
    }

    public function test_an_account_without_institution_sees_nothing_fail_closed(): void
    {
        $this->actingAs(User::factory()->population()->create());
        $this->assertSame(0, ScopedByCommissariat::count());
        $this->assertSame(0, ScopedByMairie::count());
    }

    public function test_a_commissariat_user_sees_nothing_in_a_mairie_scoped_model(): void
    {
        $mairie = Mairie::factory()->create();
        User::factory()->mairie($mairie)->create();
        $this->actingAs(User::factory()->police($this->a)->create());

        $this->assertSame(0, ScopedByMairie::count());
    }

    public function test_an_agent_mairie_only_sees_their_mairie(): void
    {
        $mine = Mairie::factory()->create();
        User::factory()->mairie($mine)->count(2)->create();
        User::factory()->mairie(Mairie::factory()->create())->count(4)->create();
        $this->actingAs(User::factory()->mairie($mine)->create());

        $this->assertSame(3, ScopedByMairie::count());
    }

    public function test_an_unauthenticated_request_sees_nothing(): void
    {
        $this->assertSame(0, ScopedByCommissariat::count());
    }

    public function test_the_explicit_bypass_is_available_and_findable(): void
    {
        $this->actingAs(User::factory()->police($this->a)->create());

        $this->assertSame(User::whereNotNull('commissariat_id')->count(), ScopedByCommissariat::acrossCommissariats()->whereNotNull('commissariat_id')->count());
    }

    public function test_the_commissariat_is_filled_from_the_author_on_creation(): void
    {
        $this->actingAs(User::factory()->commissaire($this->a)->create());

        $row = ScopedByCommissariat::create(['name' => 'X', 'phone' => '+22370000777', 'password' => 'not-a-hash']);

        $this->assertSame($this->a->id, $row->commissariat_id);
    }

    public function test_creating_without_any_institution_throws_instead_of_violating_the_database(): void
    {
        // Un compte sans institution (superadmin, admin national) contourne la permission (Gate::before, D16)
        // mais pas cette contrainte : logique métier, jamais atteinte en usage normal.
        $this->actingAs($this->superadmin());

        $this->expectException(MissingInstitutionException::class);
        ScopedByCommissariat::create(['name' => 'X', 'phone' => '+22370000778', 'password' => 'not-a-hash']);
    }

    public function test_creating_without_any_institution_throws_for_mairie_scoped_models_too(): void
    {
        $this->actingAs($this->superadmin());

        $this->expectException(MissingInstitutionException::class);
        ScopedByMairie::create(['name' => 'Y', 'phone' => '+22370000779', 'password' => 'not-a-hash']);
    }

    public function test_user_visible_to_is_fail_closed(): void
    {
        $commissaire = User::factory()->commissaire($this->a)->create();
        $admin = User::factory()->adminNational()->create();
        $superadmin = $this->superadmin();

        $this->assertSame(User::count(), User::visibleTo($superadmin)->count());
        $this->assertFalse(User::visibleTo($admin)->whereKey($superadmin->id)->exists(), 'L\'admin national ne voit pas les superadmins.');
        $this->assertTrue(User::visibleTo($admin)->whereKey($commissaire->id)->exists());
        $this->assertSame(2, User::visibleTo($commissaire)->count(), 'Le commissaire voit uniquement la police de son commissariat.');
        $this->assertSame(0, User::visibleTo(User::factory()->police($this->a)->create())->count());
        $this->assertSame(0, User::visibleTo(User::factory()->population()->create())->count());
    }
}
