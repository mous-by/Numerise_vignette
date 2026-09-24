<?php

namespace Tests\Feature\Api;

use App\Enums\ActivityChannel;
use App\Models\ActivityLog;
use App\Models\DemandeVgt;
use App\Models\Mairie;
use App\Models\Moto;
use App\Models\MotoRetrouvee;
use App\Models\Proprietaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Contrat §8 (D32) : routes publiques de la population, sans compte ni jeton — motos retrouvées (M5), mairies,
 * demande de VGT et suivi (M4). Identification : matricule + téléphone enregistré du propriétaire.
 */
class PublicPopulationApiTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        RateLimiter::clear('ip:127.0.0.1');
    }

    private function registeredMoto(int $vgtYear = 2020): Moto
    {
        $proprietaire = Proprietaire::factory()->create(['phone' => '+22370001234']);

        return Moto::factory()->create([
            'commissariat_id' => $proprietaire->commissariat_id, 'proprietaire_id' => $proprietaire->id,
            'plate_number' => 'AB 1234 CD', 'type_or_brand' => 'Sanili', 'vgt_year' => $vgtYear,
        ]);
    }

    private function payload(array $override = []): array
    {
        return array_merge(['matricule' => 'ab 1234 cd', 'phone' => '70 00 12 34', 'mairie_id' => Mairie::factory()->create()->id, 'vgt_year' => (int) date('Y')], $override);
    }

    public function test_the_found_motos_are_public_and_carry_no_personal_data(): void
    {
        $moto = $this->registeredMoto();
        $found = MotoRetrouvee::factory()->create(['commissariat_id' => $moto->commissariat_id, 'moto_id' => $moto->id, 'location' => 'Pont des Martyrs', 'recovered' => false]);
        MotoRetrouvee::factory()->create(['recovered' => true]); // déjà récupérée : jamais listée

        $response = $this->getJson('/api/v1/motos-retrouvees')->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $found->id)->assertJsonPath('data.0.matricule', 'AB 1234 CD')
            ->assertJsonPath('data.0.lieu', 'Pont des Martyrs')
            ->assertJsonStructure(['data' => [['id', 'matricule', 'genre', 'couleur', 'lieu', 'date_arret', 'commissariat']], 'meta' => ['current_page', 'last_page']]);
        $this->assertStringNotContainsString('+22370001234', $response->getContent());
        $this->assertStringNotContainsString(Proprietaire::withoutGlobalScopes()->value('last_name'), $response->getContent());
    }

    public function test_the_active_mairies_are_listed_for_the_choice_of_the_withdrawal_office(): void
    {
        $active = Mairie::factory()->create(['name' => 'Mairie Active']);
        Mairie::factory()->inactive()->create(['name' => 'Mairie Fermée']);

        $this->getJson('/api/v1/mairies')->assertOk()->assertJsonFragment(['id' => $active->id, 'nom' => 'Mairie Active'])->assertDontSee('Mairie Fermée');
    }

    public function test_an_owner_requests_a_vgt_without_an_account(): void
    {
        $moto = $this->registeredMoto();
        $payload = $this->payload();

        $this->postJson('/api/v1/demandes-vgt', $payload)->assertCreated()
            ->assertJsonPath('data.statut.code', 'en_attente')->assertJsonPath('data.annee', (int) date('Y'))
            ->assertJsonPath('data.mairie.id', $payload['mairie_id'])
            ->assertJsonStructure(['data' => ['reference', 'annee', 'statut', 'montant' => ['base', 'majoration', 'total'], 'mairie', 'cree_le']]);

        $demande = DemandeVgt::sole();
        $this->assertSame($moto->commissariat_id, $demande->commissariat_id);
        $this->assertSame('+22370001234', $demande->contact_phone);
        $this->assertSame(0, $demande->surcharge_amount);

        $log = ActivityLog::where('action', 'demandes-vgt.created')->where('subject_type', DemandeVgt::class)->sole();
        $this->assertNull($log->user_id);
        $this->assertSame(ActivityChannel::Api, $log->channel);
    }

    public function test_a_past_year_gets_the_late_surcharge(): void
    {
        $this->registeredMoto(2020);

        $this->postJson('/api/v1/demandes-vgt', $this->payload(['vgt_year' => (int) date('Y') - 1]))->assertCreated()
            ->assertJsonPath('data.montant.majoration', (int) config('vgt.late_surcharge_amount'));
    }

    public function test_a_wrong_phone_and_an_unknown_plate_get_the_same_answer(): void
    {
        $this->registeredMoto();

        $wrongPhone = $this->postJson('/api/v1/demandes-vgt', $this->payload(['phone' => '70 99 99 99']))->assertStatus(422);
        $unknownPlate = $this->postJson('/api/v1/demandes-vgt', $this->payload(['matricule' => 'ZZ 0000 ZZ']))->assertStatus(422);

        $this->assertSame($wrongPhone->json('message'), $unknownPlate->json('message'));
        $this->assertSame(0, DemandeVgt::count());
    }

    public function test_an_up_to_date_vignette_cannot_be_requested_again(): void
    {
        $this->registeredMoto((int) date('Y'));

        $this->postJson('/api/v1/demandes-vgt', $this->payload())->assertStatus(422)->assertJsonValidationErrors('vgt_year');
    }

    public function test_a_second_open_request_for_the_same_year_is_refused(): void
    {
        $this->registeredMoto();
        $payload = $this->payload();

        $this->postJson('/api/v1/demandes-vgt', $payload)->assertCreated();
        $this->postJson('/api/v1/demandes-vgt', $payload)->assertStatus(409);
        $this->assertSame(1, DemandeVgt::count());
    }

    public function test_the_request_is_validated(): void
    {
        $this->registeredMoto();

        $this->postJson('/api/v1/demandes-vgt', [])->assertStatus(422)->assertJsonValidationErrors(['matricule', 'phone', 'mairie_id', 'vgt_year']);
        $this->postJson('/api/v1/demandes-vgt', $this->payload(['mairie_id' => Mairie::factory()->inactive()->create()->id]))->assertStatus(422)->assertJsonValidationErrors('mairie_id');
        $this->postJson('/api/v1/demandes-vgt', $this->payload(['vgt_year' => (int) date('Y') + 5]))->assertStatus(422)->assertJsonValidationErrors('vgt_year');
    }

    public function test_the_follow_up_shows_the_status_of_the_owners_requests_only(): void
    {
        $moto = $this->registeredMoto();
        $mairie = Mairie::factory()->create();
        DemandeVgt::factory()->create(['moto_id' => $moto->id, 'commissariat_id' => $moto->commissariat_id, 'mairie_id' => $mairie->id, 'status' => 'rejetee', 'rejection_reason' => 'Pièce manquante', 'vgt_year' => 2024]);
        DemandeVgt::factory()->create(['status' => 'payee']); // une autre moto : jamais visible

        $response = $this->postJson('/api/v1/demandes-vgt/suivi', ['matricule' => 'AB 1234 CD', 'phone' => '70 00 12 34'])->assertOk();

        $response->assertJsonCount(1, 'data')->assertJsonPath('data.0.statut.code', 'rejetee')->assertJsonPath('data.0.motif_rejet', 'Pièce manquante')
            ->assertJsonPath('matricule', 'AB 1234 CD')->assertJsonPath('vgt_a_jour', false);
        $this->assertStringNotContainsString('+22370001234', $response->getContent());

        $this->postJson('/api/v1/demandes-vgt/suivi', ['matricule' => 'AB 1234 CD', 'phone' => '70 99 99 99'])->assertStatus(422);
    }

    public function test_the_write_routes_are_rate_limited_per_plate(): void
    {
        $this->registeredMoto();

        foreach (range(1, 10) as $ignored) {
            $this->postJson('/api/v1/demandes-vgt/suivi', ['matricule' => 'AB 1234 CD', 'phone' => '70 99 99 99']);
            RateLimiter::clear('ip:127.0.0.1');
        }

        $this->postJson('/api/v1/demandes-vgt/suivi', ['matricule' => 'AB 1234 CD', 'phone' => '70 00 12 34'])->assertStatus(429);
    }

    public function test_the_write_routes_are_rate_limited_per_ip(): void
    {
        foreach (range(1, 6) as $index) {
            $this->postJson('/api/v1/demandes-vgt/suivi', ['matricule' => "ZZ $index", 'phone' => '70 99 99 99']);
        }

        $this->postJson('/api/v1/demandes-vgt/suivi', ['matricule' => 'ZZ 7', 'phone' => '70 99 99 99'])->assertStatus(429);
    }
}
