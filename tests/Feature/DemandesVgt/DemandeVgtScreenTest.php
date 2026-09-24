<?php

namespace Tests\Feature\DemandesVgt;

use App\Enums\DemandeVgtStatus;
use App\Models\ActivityLog;
use App\Models\DemandeVgt;
use App\Models\Mairie;
use App\Models\Moto;
use App\Models\Proprietaire;
use App\Models\TarifVgt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Écran Demandes VGT (W11) : déposée par le commissaire, validée ou rejetée par la mairie de retrait choisie.
 * Ni BelongsToCommissariat ni BelongsToMairie seuls : visible des deux institutions (DemandeVgt::scopeVisibleTo).
 * Workflow et majoration : PROPOSITION TECHNIQUE (voir App\Models\DemandeVgt).
 */
class DemandeVgtScreenTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    private function motoFor(User $chef): Moto
    {
        $proprietaire = Proprietaire::factory()->create(['commissariat_id' => $chef->commissariat_id]);

        return Moto::factory()->create([
            'commissariat_id' => $chef->commissariat_id, 'proprietaire_id' => $proprietaire->id, 'type_or_brand' => 'Sanili',
        ]);
    }

    public function test_the_screen_requires_the_view_permission(): void
    {
        $this->get('/demandes-vgt')->assertRedirect('/login');
        $this->actingAs(User::factory()->commissaire()->create())->get('/demandes-vgt')->assertOk()->assertSee('DEMANDES VGT');
        $this->actingAs(User::factory()->mairie()->create())->get('/demandes-vgt')->assertOk();
    }

    public function test_it_follows_the_theme_template(): void
    {
        $this->actingAs(User::factory()->commissaire()->create())->get('/demandes-vgt')
            ->assertSee('card-header card-header-brand', false)
            ->assertSee('modal fade', false);
    }

    public function test_a_commissaire_creates_a_demande_for_the_current_year_at_the_base_tariff(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->motoFor($chef);
        $mairie = Mairie::factory()->create();
        TarifVgt::create(['type_or_brand' => 'Sanili', 'amount' => 6000]);

        $this->actingAs($chef)->post('/demandes-vgt', [
            'moto_id' => $moto->id, 'mairie_id' => $mairie->id, 'vgt_year' => now()->year,
            'contact_phone' => '70 00 00 01', 'merchant_code' => 'MC-1234',
        ])->assertRedirect('/demandes-vgt')->assertSessionHas('status');

        $demande = DemandeVgt::sole();
        $this->assertSame($chef->commissariat_id, $demande->commissariat_id);
        $this->assertSame($mairie->id, $demande->mairie_id);
        $this->assertSame(DemandeVgtStatus::EnAttente, $demande->status);
        $this->assertSame(6000, $demande->base_amount);
        $this->assertFalse($demande->is_late);
        $this->assertSame(0, $demande->surcharge_amount);
        $this->assertSame(6000, $demande->totalAmount());
        // TarifVgt partage le même module d'audit (« demandes-vgt ») : on distingue par subject_type, comme
        // l'écran Audit (W3) le fait déjà pour toute paire d'actions de même nom.
        $this->assertSame(1, ActivityLog::where('action', 'demandes-vgt.created')->where('subject_type', DemandeVgt::class)->count());
    }

    public function test_a_past_year_request_gets_the_late_surcharge(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->motoFor($chef);
        $mairie = Mairie::factory()->create();

        $this->actingAs($chef)->post('/demandes-vgt', [
            'moto_id' => $moto->id, 'mairie_id' => $mairie->id, 'vgt_year' => now()->year - 1,
            'contact_phone' => '70 00 00 01',
        ])->assertRedirect('/demandes-vgt');

        $demande = DemandeVgt::sole();
        $this->assertTrue($demande->is_late);
        $this->assertSame((int) config('vgt.late_surcharge_amount'), $demande->surcharge_amount);
        $this->assertSame($demande->base_amount + $demande->surcharge_amount, $demande->totalAmount());
    }

    public function test_an_unknown_genre_gets_the_default_tariff_created_on_the_fly(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->motoFor($chef); // genre "Sanili", jamais tarifé
        $mairie = Mairie::factory()->create();

        $this->actingAs($chef)->post('/demandes-vgt', [
            'moto_id' => $moto->id, 'mairie_id' => $mairie->id, 'vgt_year' => now()->year, 'contact_phone' => '70 00 00 01',
        ])->assertRedirect('/demandes-vgt');

        $this->assertSame(TarifVgt::DEFAULT_AMOUNT, DemandeVgt::sole()->base_amount);
        $this->assertSame(TarifVgt::DEFAULT_AMOUNT, TarifVgt::where('type_or_brand', 'Sanili')->value('amount'));
    }

    public function test_a_commissaire_and_the_chosen_mairie_both_see_the_same_demande(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->motoFor($chef);
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['commissariat_id' => $chef->commissariat_id, 'mairie_id' => $mairieModel->id, 'moto_id' => $moto->id]);

        $this->actingAs($chef)->get('/demandes-vgt')->assertSee($moto->plate_number);
        $this->actingAs($agent)->get('/demandes-vgt')->assertSee($moto->plate_number);
    }

    public function test_a_commissaire_does_not_see_another_commissariats_demande_to_a_different_mairie(): void
    {
        $chef = User::factory()->commissaire()->create();
        DemandeVgt::factory()->create(); // autre commissariat, autre mairie

        $this->actingAs($chef)->get('/demandes-vgt')->assertDontSee('AB 0000 CD');
    }

    public function test_a_mairie_agent_validates_a_pending_demande(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id]);

        $this->actingAs($agent)->put("/demandes-vgt/{$demande->id}/decision", ['decision' => 'validee'])
            ->assertRedirect('/demandes-vgt')->assertSessionHas('status');

        $this->assertSame(DemandeVgtStatus::Validee, $demande->fresh()->status);
        $this->assertSame(1, ActivityLog::where('action', 'demandes-vgt.updated')->where('subject_type', DemandeVgt::class)->count());
    }

    public function test_rejecting_a_demande_requires_a_reason(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id]);

        $this->actingAs($agent)->from('/demandes-vgt')->put("/demandes-vgt/{$demande->id}/decision", ['decision' => 'rejetee'])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame(DemandeVgtStatus::EnAttente, $demande->fresh()->status);
    }

    public function test_a_mairie_agent_cannot_validate_another_mairies_demande(): void
    {
        $agent = User::factory()->mairie()->create();
        $demande = DemandeVgt::factory()->create(); // autre mairie

        $this->actingAs($agent)->put("/demandes-vgt/{$demande->id}/decision", ['decision' => 'validee'])->assertForbidden();
    }

    public function test_a_mairie_agent_cannot_validate_an_already_decided_demande(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee']);

        $this->actingAs($agent)->put("/demandes-vgt/{$demande->id}/decision", ['decision' => 'rejetee', 'rejection_reason' => 'x'])->assertForbidden();
    }

    public function test_a_mairie_agent_confirms_the_payment_of_a_validated_demande(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee']);

        $this->actingAs($agent)->put("/demandes-vgt/{$demande->id}/paiement", ['payment_confirmed_at' => now()->format('Y-m-d')])
            ->assertRedirect('/demandes-vgt')->assertSessionHas('status');

        $demande->refresh();
        $this->assertSame(DemandeVgtStatus::Payee, $demande->status);
        $this->assertNotNull($demande->payment_confirmed_at);
        $this->assertSame(1, ActivityLog::where('action', 'demandes-vgt.updated')->where('subject_type', DemandeVgt::class)->count());
    }

    public function test_a_mairie_agent_cannot_confirm_payment_of_a_pending_demande(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'en_attente']);

        $this->actingAs($agent)->put("/demandes-vgt/{$demande->id}/paiement", ['payment_confirmed_at' => now()->format('Y-m-d')])->assertForbidden();
    }

    public function test_a_mairie_agent_cannot_confirm_another_mairies_payment(): void
    {
        $agent = User::factory()->mairie()->create();
        $demande = DemandeVgt::factory()->create(['status' => 'validee']); // autre mairie

        $this->actingAs($agent)->put("/demandes-vgt/{$demande->id}/paiement", ['payment_confirmed_at' => now()->format('Y-m-d')])->assertForbidden();
    }

    public function test_the_payment_date_cannot_be_in_the_future(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee']);

        $this->actingAs($agent)->from('/demandes-vgt')->put("/demandes-vgt/{$demande->id}/paiement", ['payment_confirmed_at' => now()->addDay()->format('Y-m-d')])
            ->assertSessionHasErrors('payment_confirmed_at');
    }

    public function test_a_commissaire_cannot_confirm_payment(): void
    {
        $chef = User::factory()->commissaire()->create();
        $demande = DemandeVgt::factory()->create(['commissariat_id' => $chef->commissariat_id, 'status' => 'validee']);

        $this->actingAs($chef)->put("/demandes-vgt/{$demande->id}/paiement", ['payment_confirmed_at' => now()->format('Y-m-d')])->assertForbidden();
    }

    public function test_a_mairie_agent_confirms_the_retrait_of_a_paid_demande(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'payee']);

        $this->actingAs($agent)->put("/demandes-vgt/{$demande->id}/retrait", ['retrait_date' => now()->format('Y-m-d')])
            ->assertRedirect('/demandes-vgt')->assertSessionHas('status');

        $demande->refresh();
        $this->assertSame(DemandeVgtStatus::Retiree, $demande->status);
        $this->assertNotNull($demande->retrait_date);
        $this->assertSame(1, ActivityLog::where('action', 'demandes-vgt.updated')->where('subject_type', DemandeVgt::class)->count());
    }

    public function test_a_mairie_agent_cannot_confirm_retrait_of_an_unpaid_demande(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee']);

        $this->actingAs($agent)->put("/demandes-vgt/{$demande->id}/retrait", ['retrait_date' => now()->format('Y-m-d')])->assertForbidden();
    }

    public function test_a_mairie_agent_cannot_confirm_another_mairies_retrait(): void
    {
        $agent = User::factory()->mairie()->create();
        $demande = DemandeVgt::factory()->create(['status' => 'payee']); // autre mairie

        $this->actingAs($agent)->put("/demandes-vgt/{$demande->id}/retrait", ['retrait_date' => now()->format('Y-m-d')])->assertForbidden();
    }

    public function test_the_retrait_date_cannot_be_in_the_future(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'payee']);

        $this->actingAs($agent)->from('/demandes-vgt')->put("/demandes-vgt/{$demande->id}/retrait", ['retrait_date' => now()->addDay()->format('Y-m-d')])
            ->assertSessionHasErrors('retrait_date');
    }

    public function test_a_commissaire_cannot_confirm_retrait(): void
    {
        $chef = User::factory()->commissaire()->create();
        $demande = DemandeVgt::factory()->create(['commissariat_id' => $chef->commissariat_id, 'status' => 'payee']);

        $this->actingAs($chef)->put("/demandes-vgt/{$demande->id}/retrait", ['retrait_date' => now()->format('Y-m-d')])->assertForbidden();
    }

    public function test_a_commissaire_can_correct_and_resubmit_a_rejected_demande(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->motoFor($chef);
        $mairieModel = Mairie::factory()->create();
        $demande = DemandeVgt::factory()->create([
            'commissariat_id' => $chef->commissariat_id, 'mairie_id' => $mairieModel->id, 'moto_id' => $moto->id,
            'status' => 'rejetee', 'rejection_reason' => 'Formulaire incomplet',
        ]);

        $this->actingAs($chef)->put("/demandes-vgt/{$demande->id}", [
            'moto_id' => $moto->id, 'mairie_id' => $mairieModel->id, 'vgt_year' => now()->year, 'contact_phone' => '70 00 00 02',
        ])->assertRedirect('/demandes-vgt');

        $demande->refresh();
        $this->assertSame(DemandeVgtStatus::EnAttente, $demande->status);
        $this->assertNull($demande->rejection_reason);
        $this->assertSame('+22370000002', $demande->contact_phone);
    }

    public function test_a_commissaire_cannot_edit_a_pending_demande(): void
    {
        $chef = User::factory()->commissaire()->create();
        $moto = $this->motoFor($chef);
        $demande = DemandeVgt::factory()->create(['commissariat_id' => $chef->commissariat_id, 'moto_id' => $moto->id, 'status' => 'en_attente']);

        $this->actingAs($chef)->put("/demandes-vgt/{$demande->id}", ['moto_id' => $moto->id, 'mairie_id' => $demande->mairie_id, 'vgt_year' => now()->year, 'contact_phone' => '70 00 00 01'])
            ->assertForbidden();
    }

    public function test_the_superadmin_cannot_create_since_it_has_no_commissariat(): void
    {
        $superadmin = $this->superadmin();
        $moto = Moto::factory()->create();
        $mairie = Mairie::factory()->create();

        // La validation (« moto de mon commissariat ») échoue déjà pour lui (commissariat_id = null ne matche
        // jamais), avant même d'atteindre la garde explicite du contrôleur — comme Moto et Declaration (W8, W9).
        $this->actingAs($superadmin)->from('/demandes-vgt')->post('/demandes-vgt', [
            'moto_id' => $moto->id, 'mairie_id' => $mairie->id, 'vgt_year' => now()->year, 'contact_phone' => '70 00 00 01',
        ])->assertSessionHasErrors('moto_id');

        $this->assertSame(0, DemandeVgt::count());
    }

    public function test_the_admin_national_manages_tarifs(): void
    {
        $admin = User::factory()->adminNational()->create();

        $this->actingAs($admin)->post('/tarifs-vgt', ['type_or_brand' => 'Yamaha', 'amount' => 9000])
            ->assertRedirect('/demandes-vgt')->assertSessionHas('status');

        $tarif = TarifVgt::sole();
        $this->assertSame('Yamaha', $tarif->type_or_brand);

        $this->actingAs($admin)->put("/tarifs-vgt/{$tarif->id}", ['amount' => 9500])->assertRedirect('/demandes-vgt');
        $this->assertSame(9500, $tarif->fresh()->amount);
    }

    public function test_a_commissaire_cannot_manage_tarifs(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->post('/tarifs-vgt', ['type_or_brand' => 'Yamaha', 'amount' => 9000])->assertForbidden();
    }
}
