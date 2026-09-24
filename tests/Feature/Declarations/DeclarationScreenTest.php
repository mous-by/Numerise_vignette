<?php

namespace Tests\Feature\Declarations;

use App\Enums\DeclarationType;
use App\Models\ActivityLog;
use App\Models\Declaration;
use App\Models\Moto;
use App\Models\Proprietaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Écran Déclarations (W9) : vol, braquage ou autre (cahier §5 Cas 2, §9 Déclaration), cloisonnées par
 * commissariat (BelongsToCommissariat), comme les propriétaires et les motos (W7, W8). Une déclaration de vol
 * ou de braquage marque la moto liée « Volée » (Moto::recalculateStolenStatus()).
 */
class DeclarationScreenTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        $this->superadmin = $this->superadmin();
    }

    private function actorWithMoto(): array
    {
        $chef = User::factory()->commissaire()->create();
        $proprietaire = Proprietaire::factory()->create(['commissariat_id' => $chef->commissariat_id]);
        // fresh() : la colonne is_stolen a un défaut au niveau du schéma, absent de l'instance tant qu'on ne
        // recharge pas depuis la base (Moto::create() ne connaît que les attributs qu'on lui a passés).
        $moto = Moto::factory()->create(['commissariat_id' => $chef->commissariat_id, 'proprietaire_id' => $proprietaire->id])->fresh();

        return [$chef, $moto];
    }

    public function test_the_screen_requires_the_view_permission(): void
    {
        $this->get('/declarations')->assertRedirect('/login');
        $this->actingAs(User::factory()->mairie()->create())->get('/declarations')->assertForbidden();
        $this->actingAs(User::factory()->commissaire()->create())->get('/declarations')->assertOk()->assertSee('DÉCLARATIONS');
    }

    public function test_it_follows_the_theme_template(): void
    {
        $this->actingAs(User::factory()->commissaire()->create())->get('/declarations')
            ->assertSee('card-header card-header-brand', false)
            ->assertSee('modal fade', false);
    }

    public function test_a_commissaire_only_sees_the_declarations_of_his_own_commissariat(): void
    {
        [$chef, $moto] = $this->actorWithMoto();
        Declaration::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id, 'location' => 'Ici visible']);
        Declaration::factory()->create(['location' => 'Ailleurs invisible']); // autre commissariat

        $this->actingAs($chef)->get('/declarations')->assertSee('Ici visible')->assertDontSee('Ailleurs invisible');
    }

    public function test_a_theft_declaration_marks_the_moto_as_stolen(): void
    {
        [$chef, $moto] = $this->actorWithMoto();
        $this->assertFalse($moto->is_stolen);

        $this->actingAs($chef)->post('/declarations', [
            'moto_id' => $moto->id, 'type' => 'vol', 'location' => 'Kalaban Coura',
            'occurred_at' => now()->subDay()->format('Y-m-d'), 'description' => 'Moto volée devant le domicile.',
        ])->assertRedirect('/declarations')->assertSessionHas('status');

        $declaration = Declaration::sole();
        $this->assertSame(DeclarationType::Vol, $declaration->type);
        $this->assertSame($chef->commissariat_id, $declaration->commissariat_id);
        $this->assertTrue($moto->fresh()->is_stolen);
        $this->assertSame(1, ActivityLog::where('action', 'declarations.created')->count());
    }

    public function test_a_braquage_declaration_also_marks_the_moto_as_stolen(): void
    {
        [$chef, $moto] = $this->actorWithMoto();

        $this->actingAs($chef)->post('/declarations', [
            'moto_id' => $moto->id, 'type' => 'braquage', 'location' => 'Sogoniko',
            'occurred_at' => now()->subDay()->format('Y-m-d'), 'description' => 'Braquage à main armée.',
        ])->assertRedirect('/declarations');

        $this->assertTrue($moto->fresh()->is_stolen);
    }

    public function test_an_other_declaration_does_not_mark_the_moto_as_stolen(): void
    {
        [$chef, $moto] = $this->actorWithMoto();

        $this->actingAs($chef)->post('/declarations', [
            'moto_id' => $moto->id, 'type' => 'autre', 'location' => 'Badalabougou',
            'occurred_at' => now()->subDay()->format('Y-m-d'), 'description' => 'Accident sans vol.',
        ])->assertRedirect('/declarations');

        $this->assertFalse($moto->fresh()->is_stolen);
    }

    public function test_validation_requires_the_mandatory_fields(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->from('/declarations')->post('/declarations', [])
            ->assertSessionHasErrors(['moto_id', 'type', 'location', 'occurred_at', 'description']);

        $this->assertSame(0, Declaration::count());
    }

    public function test_the_occurred_at_date_cannot_be_in_the_future(): void
    {
        [$chef, $moto] = $this->actorWithMoto();

        $this->actingAs($chef)->from('/declarations')->post('/declarations', [
            'moto_id' => $moto->id, 'type' => 'vol', 'location' => 'Kalaban Coura',
            'occurred_at' => now()->addDay()->format('Y-m-d'), 'description' => 'Test.',
        ])->assertSessionHasErrors('occurred_at');
    }

    public function test_a_commissaire_cannot_declare_for_a_moto_of_another_commissariat(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = Moto::factory()->create(); // autre commissariat

        $this->actingAs($chef)->from('/declarations')->post('/declarations', [
            'moto_id' => $moto->id, 'type' => 'vol', 'location' => 'Kalaban Coura',
            'occurred_at' => now()->subDay()->format('Y-m-d'), 'description' => 'Test.',
        ])->assertSessionHasErrors('moto_id');

        $this->assertSame(0, Declaration::count());
    }

    public function test_changing_the_type_away_from_theft_unmarks_the_moto(): void
    {
        [$chef, $moto] = $this->actorWithMoto();
        $declaration = Declaration::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id, 'type' => 'vol']);
        $moto->recalculateStolenStatus();
        $this->assertTrue($moto->fresh()->is_stolen);

        $this->actingAs($chef)->put("/declarations/{$declaration->id}", [
            'moto_id' => $moto->id, 'type' => 'autre', 'location' => $declaration->location,
            'occurred_at' => $declaration->occurred_at->format('Y-m-d'), 'description' => $declaration->description,
        ])->assertRedirect('/declarations');

        $this->assertFalse($moto->fresh()->is_stolen);
        $this->assertSame(1, ActivityLog::where('action', 'declarations.updated')->count());
    }

    public function test_deleting_the_last_theft_declaration_unmarks_the_moto(): void
    {
        [$chef, $moto] = $this->actorWithMoto();
        $declaration = Declaration::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id, 'type' => 'vol']);
        $moto->recalculateStolenStatus();
        $this->assertTrue($moto->fresh()->is_stolen);

        $this->actingAs($chef)->delete("/declarations/{$declaration->id}")->assertRedirect('/declarations');

        $this->assertSoftDeleted($declaration);
        $this->assertFalse($moto->fresh()->is_stolen);
        $this->assertSame(1, ActivityLog::where('action', 'declarations.deleted')->count());
    }

    public function test_the_moto_stays_stolen_if_another_active_theft_declaration_remains(): void
    {
        [$chef, $moto] = $this->actorWithMoto();
        $declaration = Declaration::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id, 'type' => 'vol']);
        Declaration::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id, 'type' => 'braquage']);
        $moto->recalculateStolenStatus();

        $this->actingAs($chef)->delete("/declarations/{$declaration->id}")->assertRedirect('/declarations');

        $this->assertTrue($moto->fresh()->is_stolen);
    }

    public function test_a_commissaire_cannot_update_a_declaration_of_another_commissariat(): void
    {
        $chef = User::factory()->commissaire()->create();
        $declaration = Declaration::factory()->create(); // autre commissariat

        // 404 : le cloisonnement fail-closed bloque même la résolution de la route (§4.7).
        $this->actingAs($chef)->put("/declarations/{$declaration->id}", ['location' => 'X'])->assertNotFound();
    }

    public function test_the_superadmin_cannot_create_since_it_has_no_commissariat(): void
    {
        $moto = Moto::factory()->create();

        $this->actingAs($this->superadmin)->from('/declarations')->post('/declarations', [
            'moto_id' => $moto->id, 'type' => 'vol', 'location' => 'X',
            'occurred_at' => now()->subDay()->format('Y-m-d'), 'description' => 'Y',
        ])->assertSessionHasErrors('moto_id');

        $this->assertSame(0, Declaration::acrossCommissariats()->count());
    }

    public function test_the_list_shows_type_chips_and_a_sheet_per_declaration(): void
    {
        $chef = User::factory()->commissaire()->create();
        $owner = Proprietaire::factory()->create(['commissariat_id' => $chef->commissariat_id]);
        $moto = Moto::factory()->create(['commissariat_id' => $chef->commissariat_id, 'proprietaire_id' => $owner->id]);
        $declaration = Declaration::create([
            'commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id, 'type' => 'vol',
            'location' => 'Marché de Bamako', 'occurred_at' => '2026-09-01', 'description' => 'Volée devant le domicile.',
        ]);

        $this->actingAs($chef)->get('/declarations')->assertOk()
            ->assertSee('declarations-filter', false)
            ->assertSee('data-token="type-braquage"', false)
            ->assertSee('ficheDeclaration-'.$declaration->id, false)
            ->assertSee('Volée devant le domicile.');
    }
}
