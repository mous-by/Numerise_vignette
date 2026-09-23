<?php

namespace Tests\Feature\Motos;

use App\Models\ActivityLog;
use App\Models\Commissariat;
use App\Models\Moto;
use App\Models\Proprietaire;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Écran Motos (W8) : saisies par le commissaire (cahier §5 Cas 1, §9 Accueil), cloisonnées par commissariat
 * (BelongsToCommissariat), comme les propriétaires (W7). Le matricule est l'identifiant national unique de la
 * moto (cahier : jamais de châssis) : unique sur toute la base, contrairement au cloisonnement du reste de la
 * fiche.
 */
class MotoScreenTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        $this->superadmin = $this->superadmin();
    }

    private function actorWithProprietaire(): array
    {
        $chef = User::factory()->commissaire()->create();
        $proprietaire = Proprietaire::factory()->create(['commissariat_id' => $chef->commissariat_id]);

        return [$chef, $proprietaire];
    }

    public function test_the_screen_requires_the_view_permission(): void
    {
        $this->get('/motos')->assertRedirect('/login');
        $this->actingAs(User::factory()->mairie()->create())->get('/motos')->assertForbidden();
        $this->actingAs(User::factory()->commissaire()->create())->get('/motos')->assertOk()->assertSee('MOTOS');
    }

    public function test_it_follows_the_theme_template(): void
    {
        $this->actingAs(User::factory()->commissaire()->create())->get('/motos')
            ->assertSee('card-header card-header-brand', false)
            ->assertSee('modal fade', false);
    }

    public function test_the_create_moto_modal_offers_to_add_a_new_proprietaire_on_the_spot(): void
    {
        $this->actingAs(User::factory()->commissaire()->create())->get('/motos')
            ->assertSee('id="add-proprietaire-btn"', false)
            ->assertSee('id="createProprietaireFromMotoModal"', false);
    }

    public function test_the_search_endpoint_matches_by_plate_number_and_is_cloisonne(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();
        $mine = Moto::factory()->create(['commissariat_id' => $chef->commissariat_id, 'proprietaire_id' => $proprietaire->id, 'plate_number' => 'AB 1234 CD']);
        Moto::factory()->create(['plate_number' => 'AB 9999 CD']); // autre commissariat

        $this->actingAs($chef)->getJson('/motos/search?q=1234')
            ->assertOk()
            ->assertExactJson(['results' => [['id' => $mine->id, 'text' => "AB 1234 CD — {$proprietaire->fullName()}"]]]);
    }

    public function test_the_screen_accepts_a_new_proprietaire_query_parameter_without_error(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();

        $this->actingAs($chef)->get('/motos?new_proprietaire='.$proprietaire->id)
            ->assertOk()
            ->assertSee($proprietaire->fullName());
    }

    public function test_a_commissaire_only_sees_the_motos_of_his_own_commissariat(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();
        Moto::factory()->create(['commissariat_id' => $chef->commissariat_id, 'proprietaire_id' => $proprietaire->id, 'plate_number' => 'AB 0001 CD']);
        Moto::factory()->create(['plate_number' => 'ZZ 9999 ZZ']);

        $this->actingAs($chef)->get('/motos')->assertSee('AB 0001 CD')->assertDontSee('ZZ 9999 ZZ');
    }

    public function test_a_commissaire_creates_a_moto_for_a_proprietaire_of_his_own_commissariat(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();

        $this->actingAs($chef)->post('/motos', [
            'proprietaire_id' => $proprietaire->id, 'plate_number' => 'ab 1234 cd', 'color' => 'Rouge',
            'type_or_brand' => 'Sanili', 'vgt_year' => 2026,
        ])->assertRedirect('/motos')->assertSessionHas('status');

        $moto = Moto::sole();
        $this->assertSame('AB 1234 CD', $moto->plate_number);
        $this->assertSame($chef->commissariat_id, $moto->commissariat_id);
        $this->assertSame($proprietaire->id, $moto->proprietaire_id);
        $this->assertFalse($moto->has_sale_certificate);
        $this->assertSame(1, ActivityLog::where('action', 'motos.created')->count());
    }

    public function test_the_plate_number_is_unique_nationally_not_just_per_commissariat(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();
        Moto::factory()->create(['plate_number' => 'AB 1234 CD']); // autre commissariat

        $this->actingAs($chef)->from('/motos')->post('/motos', [
            'proprietaire_id' => $proprietaire->id, 'plate_number' => 'AB 1234 CD', 'color' => 'Rouge',
            'type_or_brand' => 'Sanili', 'vgt_year' => 2026,
        ])->assertSessionHasErrors('plate_number');

        // Moto::count() est cloisonné (BelongsToCommissariat, fail-closed) : la moto de l'autre commissariat,
        // invisible pour ce commissaire, reste néanmoins seule en base — acrossCommissariats() le confirme.
        $this->assertSame(1, Moto::acrossCommissariats()->count());
    }

    public function test_a_commissaire_cannot_assign_a_proprietaire_of_another_commissariat(): void
    {
        $chef = User::factory()->commissaire()->create();
        $other = Proprietaire::factory()->create(); // autre commissariat

        $this->actingAs($chef)->from('/motos')->post('/motos', [
            'proprietaire_id' => $other->id, 'plate_number' => 'AB 1234 CD', 'color' => 'Rouge',
            'type_or_brand' => 'Sanili', 'vgt_year' => 2026,
        ])->assertSessionHasErrors('proprietaire_id');

        $this->assertSame(0, Moto::count());
    }

    public function test_validation_requires_the_mandatory_fields(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->from('/motos')->post('/motos', [])
            ->assertSessionHasErrors(['proprietaire_id', 'plate_number', 'color', 'type_or_brand', 'vgt_year']);

        $this->assertSame(0, Moto::count());
    }

    public function test_the_sale_certificate_requires_seller_information_when_checked(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();

        $this->actingAs($chef)->from('/motos')->post('/motos', [
            'proprietaire_id' => $proprietaire->id, 'plate_number' => 'AB 1234 CD', 'color' => 'Rouge',
            'type_or_brand' => 'Sanili', 'vgt_year' => 2026, 'has_sale_certificate' => '1',
        ])->assertSessionHasErrors(['seller_first_name', 'seller_last_name', 'seller_phone', 'seller_address']);

        $this->assertSame(0, Moto::count());
    }

    public function test_the_witness_requires_witness_information_only_when_checked(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();

        $this->actingAs($chef)->post('/motos', [
            'proprietaire_id' => $proprietaire->id, 'plate_number' => 'AB 1234 CD', 'color' => 'Rouge',
            'type_or_brand' => 'Sanili', 'vgt_year' => 2026, 'has_sale_certificate' => '1',
            'seller_first_name' => 'Awa', 'seller_last_name' => 'Traoré', 'seller_phone' => '70 00 00 01', 'seller_address' => 'Bamako',
            'has_witness' => '1',
        ])->assertSessionHasErrors(['witness_first_name', 'witness_last_name', 'witness_phone', 'witness_address']);
    }

    public function test_a_witness_cannot_be_declared_without_a_sale_certificate(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();

        $this->actingAs($chef)->post('/motos', [
            'proprietaire_id' => $proprietaire->id, 'plate_number' => 'AB 1234 CD', 'color' => 'Rouge',
            'type_or_brand' => 'Sanili', 'vgt_year' => 2026, 'has_sale_certificate' => '0', 'has_witness' => '1',
            'witness_first_name' => 'Ali', 'witness_last_name' => 'Cissé', 'witness_phone' => '70 00 00 02', 'witness_address' => 'Bamako',
        ])->assertRedirect('/motos');

        $moto = Moto::sole();
        $this->assertFalse($moto->has_sale_certificate);
        $this->assertFalse($moto->has_witness);
        $this->assertNull($moto->witness_first_name);
    }

    public function test_a_complete_sale_certificate_with_witness_is_saved(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();

        $this->actingAs($chef)->post('/motos', [
            'proprietaire_id' => $proprietaire->id, 'plate_number' => 'AB 1234 CD', 'color' => 'Rouge',
            'type_or_brand' => 'Sanili', 'vgt_year' => 2026, 'has_sale_certificate' => '1',
            'seller_first_name' => 'Awa', 'seller_last_name' => 'Traoré', 'seller_phone' => '70 00 00 01', 'seller_address' => 'Bamako',
            'has_witness' => '1', 'witness_first_name' => 'Ali', 'witness_last_name' => 'Cissé', 'witness_phone' => '70 00 00 02', 'witness_address' => 'Bamako',
        ])->assertRedirect('/motos');

        $moto = Moto::sole();
        $this->assertTrue($moto->has_sale_certificate);
        $this->assertSame('Awa Traoré', $moto->sellerFullName());
        $this->assertTrue($moto->has_witness);
        $this->assertSame('Ali Cissé', $moto->witnessFullName());
        $this->assertSame('+22370000001', $moto->seller_phone);
    }

    public function test_updating_a_moto(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();
        $moto = Moto::factory()->create(['commissariat_id' => $chef->commissariat_id, 'proprietaire_id' => $proprietaire->id, 'color' => 'Rouge']);

        $this->actingAs($chef)->put("/motos/{$moto->id}", [
            'proprietaire_id' => $proprietaire->id, 'plate_number' => $moto->plate_number, 'color' => 'Bleu',
            'type_or_brand' => $moto->type_or_brand, 'vgt_year' => $moto->vgt_year,
        ])->assertRedirect('/motos');

        $this->assertSame('Bleu', $moto->fresh()->color);
        $this->assertSame(1, ActivityLog::where('action', 'motos.updated')->count());
    }

    public function test_updating_a_moto_keeps_its_own_plate_number_unique_check_from_failing(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();
        $moto = Moto::factory()->create(['commissariat_id' => $chef->commissariat_id, 'proprietaire_id' => $proprietaire->id, 'plate_number' => 'AB 1234 CD']);

        $this->actingAs($chef)->put("/motos/{$moto->id}", [
            'proprietaire_id' => $proprietaire->id, 'plate_number' => 'AB 1234 CD', 'color' => $moto->color,
            'type_or_brand' => $moto->type_or_brand, 'vgt_year' => $moto->vgt_year,
        ])->assertRedirect('/motos')->assertSessionHasNoErrors();
    }

    public function test_a_commissaire_cannot_update_a_moto_of_another_commissariat(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = Moto::factory()->create(); // autre commissariat

        // 404 : le cloisonnement fail-closed bloque même la résolution de la route (§4.7).
        $this->actingAs($chef)->put("/motos/{$moto->id}", ['color' => 'Bleu'])->assertNotFound();
    }

    public function test_deleting_a_moto_is_a_soft_delete(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();
        $moto = Moto::factory()->create(['commissariat_id' => $chef->commissariat_id, 'proprietaire_id' => $proprietaire->id]);

        $this->actingAs($chef)->delete("/motos/{$moto->id}")->assertRedirect('/motos');

        $this->assertSoftDeleted($moto);
        $this->assertSame(1, ActivityLog::where('action', 'motos.deleted')->count());
    }

    public function test_the_superadmin_cannot_create_since_it_has_no_commissariat(): void
    {
        $proprietaire = Proprietaire::factory()->create();

        // Le superadmin n'a aucun commissariat (§4.2) : la validation « proprietaire_id existe dans mon
        // commissariat » échoue déjà pour lui (commissariat_id = null ne matche jamais un propriétaire), avant
        // même d'atteindre BelongsToCommissariat/MissingInstitutionException au niveau du modèle.
        $this->actingAs($this->superadmin)->from('/motos')->post('/motos', [
            'proprietaire_id' => $proprietaire->id, 'plate_number' => 'AB 1234 CD', 'color' => 'Rouge',
            'type_or_brand' => 'Sanili', 'vgt_year' => 2026,
        ])->assertSessionHasErrors('proprietaire_id');

        $this->assertSame(0, Moto::acrossCommissariats()->count());
    }

    public function test_a_proprietaire_with_motos_cannot_be_hard_deleted(): void
    {
        [$chef, $proprietaire] = $this->actorWithProprietaire();
        Moto::factory()->create(['commissariat_id' => $chef->commissariat_id, 'proprietaire_id' => $proprietaire->id]);

        $this->expectException(QueryException::class);
        $proprietaire->forceDelete();
    }
}
