<?php

namespace Tests\Feature\MotosRetrouvees;

use App\Models\ActivityLog;
use App\Models\Moto;
use App\Models\MotoRetrouvee;
use App\Models\Proprietaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Écran Motos retrouvées (W10) : cahier §5 Cas 2, §9 Moto retrouvée. À la différence de W7/W8/W9, la moto
 * retrouvée n'appartient pas forcément au commissariat qui l'enregistre (§4.7, acrossCommissariats) : une moto
 * volée peut être retrouvée ailleurs.
 */
class MotoRetrouveeScreenTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    private function stolenMoto(?int $commissariatId = null): Moto
    {
        $proprietaire = Proprietaire::factory()->create($commissariatId ? ['commissariat_id' => $commissariatId] : []);

        return Moto::factory()->create([
            'commissariat_id' => $proprietaire->commissariat_id,
            'proprietaire_id' => $proprietaire->id,
            'is_stolen' => true,
        ])->fresh();
    }

    public function test_the_screen_requires_the_view_permission(): void
    {
        $this->get('/motos-retrouvees')->assertRedirect('/login');
        $this->actingAs(User::factory()->mairie()->create())->get('/motos-retrouvees')->assertForbidden();
        $this->actingAs(User::factory()->commissaire()->create())->get('/motos-retrouvees')->assertOk()->assertSee('MOTOS RETROUVÉES');
    }

    public function test_it_follows_the_theme_template(): void
    {
        $this->actingAs(User::factory()->commissaire()->create())->get('/motos-retrouvees')
            ->assertSee('card-header card-header-brand', false)
            ->assertSee('modal fade', false);
    }

    public function test_a_commissaire_registers_a_moto_stolen_in_another_commissariat_as_found(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->stolenMoto(); // volée, enregistrée dans un AUTRE commissariat

        $this->actingAs($chef)->post('/motos-retrouvees', [
            'moto_id' => $moto->id, 'location' => 'Sogoniko', 'found_at' => now()->subDay()->format('Y-m-d'),
        ])->assertRedirect('/motos-retrouvees')->assertSessionHas('status');

        $motoRetrouvee = MotoRetrouvee::sole();
        $this->assertSame($chef->commissariat_id, $motoRetrouvee->commissariat_id);
        $this->assertSame($moto->id, $motoRetrouvee->moto_id);
        $this->assertFalse($moto->fresh()->is_stolen);
        $this->assertSame(1, ActivityLog::where('action', 'motos-retrouvees.created')->count());
    }

    public function test_a_commissaire_only_sees_the_finds_recorded_by_his_own_commissariat(): void
    {
        $chef = User::factory()->commissaire()->create();
        $mine = $this->stolenMoto();
        MotoRetrouvee::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $mine->id, 'location' => 'Ici visible']);

        $other = $this->stolenMoto();
        MotoRetrouvee::factory()->create(['moto_id' => $other->id, 'location' => 'Ailleurs invisible']); // autre commissariat trouveur

        $this->actingAs($chef)->get('/motos-retrouvees')->assertSee('Ici visible')->assertDontSee('Ailleurs invisible');
    }

    public function test_the_owner_name_displays_even_when_the_moto_belongs_to_another_commissariat(): void
    {
        $chef = User::factory()->commissaire()->create();
        $proprietaire = Proprietaire::factory()->create();
        $moto = Moto::factory()->create(['commissariat_id' => $proprietaire->commissariat_id, 'proprietaire_id' => $proprietaire->id, 'is_stolen' => true]);
        MotoRetrouvee::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id]);

        // $proprietaire est l'instance déjà en mémoire (pas une relecture scopée) : fullName() ne déclenche
        // aucune requête, donc aucun risque de cloisonnement sur le mauvais commissariat.
        // Sans le bypass explicite du cloisonnement sur moto()/proprietaire(), ce nom serait silencieusement absent.
        $this->actingAs($chef)->get('/motos-retrouvees')->assertSee($moto->plate_number)->assertSee($proprietaire->fullName());
    }

    public function test_a_moto_not_currently_stolen_is_rejected(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = Moto::factory()->create(); // is_stolen = false

        $this->actingAs($chef)->from('/motos-retrouvees')->post('/motos-retrouvees', [
            'moto_id' => $moto->id, 'location' => 'Sogoniko', 'found_at' => now()->subDay()->format('Y-m-d'),
        ])->assertSessionHasErrors('moto_id');

        $this->assertSame(0, MotoRetrouvee::acrossCommissariats()->count());
    }

    public function test_validation_requires_the_mandatory_fields(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->from('/motos-retrouvees')->post('/motos-retrouvees', [])
            ->assertSessionHasErrors(['moto_id', 'location', 'found_at']);
    }

    public function test_the_found_at_date_cannot_be_in_the_future(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->stolenMoto();

        $this->actingAs($chef)->from('/motos-retrouvees')->post('/motos-retrouvees', [
            'moto_id' => $moto->id, 'location' => 'Sogoniko', 'found_at' => now()->addDay()->format('Y-m-d'),
        ])->assertSessionHasErrors('found_at');
    }

    public function test_updating_the_location(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->stolenMoto();
        $motoRetrouvee = MotoRetrouvee::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id, 'location' => 'Ancien lieu']);

        $this->actingAs($chef)->put("/motos-retrouvees/{$motoRetrouvee->id}", [
            'location' => 'Nouveau lieu', 'found_at' => $motoRetrouvee->found_at->format('Y-m-d'),
        ])->assertRedirect('/motos-retrouvees');

        $this->assertSame('Nouveau lieu', $motoRetrouvee->fresh()->location);
        $this->assertSame(1, ActivityLog::where('action', 'motos-retrouvees.updated')->count());
    }

    public function test_marking_recovered_requires_a_recovery_date(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->stolenMoto();
        $motoRetrouvee = MotoRetrouvee::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id]);

        $this->actingAs($chef)->from('/motos-retrouvees')->put("/motos-retrouvees/{$motoRetrouvee->id}", [
            'location' => $motoRetrouvee->location, 'found_at' => $motoRetrouvee->found_at->format('Y-m-d'), 'recovered' => '1',
        ])->assertSessionHasErrors('recovered_at');
    }

    public function test_the_recovery_date_cannot_precede_the_found_date(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->stolenMoto();
        $motoRetrouvee = MotoRetrouvee::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id, 'found_at' => now()->subDays(2)]);

        $this->actingAs($chef)->from('/motos-retrouvees')->put("/motos-retrouvees/{$motoRetrouvee->id}", [
            'location' => $motoRetrouvee->location, 'found_at' => $motoRetrouvee->found_at->format('Y-m-d'),
            'recovered' => '1', 'recovered_at' => now()->subDays(3)->format('Y-m-d'),
        ])->assertSessionHasErrors('recovered_at');
    }

    public function test_marking_recovered_is_saved(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->stolenMoto();
        $motoRetrouvee = MotoRetrouvee::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id, 'found_at' => now()->subDays(2)]);

        $this->actingAs($chef)->put("/motos-retrouvees/{$motoRetrouvee->id}", [
            'location' => $motoRetrouvee->location, 'found_at' => $motoRetrouvee->found_at->format('Y-m-d'),
            'recovered' => '1', 'recovered_at' => now()->subDay()->format('Y-m-d'),
        ])->assertRedirect('/motos-retrouvees');

        $this->assertTrue($motoRetrouvee->fresh()->recovered);
        $this->assertNotNull($motoRetrouvee->fresh()->recovered_at);
    }

    public function test_the_search_stolen_endpoint_is_national_and_requires_the_create_permission(): void
    {
        $chef = User::factory()->commissaire()->create();
        $mine = $this->stolenMoto($chef->commissariat_id);
        $elsewhere = $this->stolenMoto();
        Moto::factory()->create(['plate_number' => 'ZZ 0000 ZZ', 'is_stolen' => false]); // pas volée : absente

        $response = $this->actingAs($chef)->getJson('/motos/search-stolen')->assertOk()->json('results');
        $ids = array_column($response, 'id');

        $this->assertContains($mine->id, $ids);
        $this->assertContains($elsewhere->id, $ids);

        $this->actingAs(User::factory()->mairie()->create())->getJson('/motos/search-stolen')->assertForbidden();
    }

    public function test_a_commissaire_cannot_update_a_find_recorded_by_another_commissariat(): void
    {
        $chef = User::factory()->commissaire()->create();
        $motoRetrouvee = MotoRetrouvee::factory()->create(['moto_id' => $this->stolenMoto()->id]); // autre commissariat trouveur

        // 404 : le cloisonnement fail-closed bloque même la résolution de la route (§4.7).
        $this->actingAs($chef)->put("/motos-retrouvees/{$motoRetrouvee->id}", ['location' => 'X'])->assertNotFound();
    }

    public function test_the_list_shows_status_chips_and_a_sheet_per_find(): void
    {
        $chef = User::factory()->commissaire()->create();
        $owner = Proprietaire::factory()->create(['commissariat_id' => $chef->commissariat_id]);
        $moto = Moto::factory()->create(['commissariat_id' => $chef->commissariat_id, 'proprietaire_id' => $owner->id]);
        $find = MotoRetrouvee::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id, 'location' => 'Pont des Martyrs']);

        $this->actingAs($chef)->get('/motos-retrouvees')->assertOk()
            ->assertSee('motos-retrouvees-filter', false)
            ->assertSee('data-token="recuperee"', false)
            ->assertSee('ficheMotoRetrouvee-'.$find->id, false)
            ->assertSee('Pont des Martyrs');
    }
}
