<?php

namespace Tests\Feature\Vgt;

use App\Models\DemandeVgt;
use App\Models\Mairie;
use App\Models\Moto;
use App\Models\Proprietaire;
use App\Models\User;
use App\Services\Vgt\VignetteVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Vérification publique d'une vignette par son QR Code (PROPOSITION TECHNIQUE) : adresse signée, aucune donnée personnelle.
 */
class VignetteVerificationTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    private function demande(string $status, ?int $year = null, string $plate = 'AB 1234 CD'): DemandeVgt
    {
        $proprietaire = Proprietaire::factory()->create(['first_name' => 'Awa', 'last_name' => 'Traore'] + ($plate === 'AB 1234 CD' ? ['phone' => '+22370001234'] : []));
        $moto = Moto::factory()->create(['commissariat_id' => $proprietaire->commissariat_id, 'proprietaire_id' => $proprietaire->id, 'plate_number' => $plate, 'type_or_brand' => 'Sanili']);

        return DemandeVgt::factory()->create([
            'commissariat_id' => $moto->commissariat_id, 'moto_id' => $moto->id, 'status' => $status,
            'vgt_year' => $year ?? (int) date('Y'), 'mairie_id' => Mairie::firstOrCreate(['name' => 'Mairie de Kalaban'])->id,
        ]);
    }

    private function url(DemandeVgt $demande): string
    {
        return app(VignetteVerifier::class)->url($demande);
    }

    public function test_a_paid_vignette_of_the_current_year_is_valid_without_any_login(): void
    {
        $demande = $this->demande('payee');

        $this->get($this->url($demande))->assertOk()
            ->assertSee('Vignette valide')->assertSee('AB 1234 CD')->assertSee('Sanili')->assertSee('Mairie de Kalaban')
            ->assertSee('31/12/'.date('Y'))->assertSee('VGT-'.date('Y').'-'.str_pad((string) $demande->id, 6, '0', STR_PAD_LEFT));
    }

    public function test_a_withdrawn_vignette_is_valid_too(): void
    {
        $this->get($this->url($this->demande('retiree')))->assertOk()->assertSee('Vignette valide');
    }

    public function test_a_past_year_vignette_is_expired(): void
    {
        $this->get($this->url($this->demande('retiree', (int) date('Y') - 1)))->assertOk()->assertSee('Vignette expirée');
    }

    public function test_an_unpaid_request_is_not_a_delivered_vignette(): void
    {
        foreach (['en_attente', 'validee', 'rejetee'] as $index => $status) {
            $this->get($this->url($this->demande($status, null, "ZZ 000{$index} ZZ")))->assertOk()->assertSee('Vignette non délivrée')->assertDontSee('Vignette valide');
        }
    }

    public function test_the_page_never_shows_personal_data(): void
    {
        $response = $this->get($this->url($this->demande('payee')))->assertOk();

        foreach (['Awa', 'Traore', '+22370001234', '70 00 12 34'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }
    }

    public function test_a_wrong_or_missing_signature_is_not_recognised(): void
    {
        $demande = $this->demande('payee');
        $reference = app(VignetteVerifier::class)->reference($demande);

        $this->get("/verifier/{$reference}")->assertOk()->assertSee('Vignette non reconnue')->assertDontSee('AB 1234 CD');
        $this->get("/verifier/{$reference}?s=0000000000000000")->assertOk()->assertSee('Vignette non reconnue')->assertDontSee('AB 1234 CD');
    }

    public function test_a_guessed_reference_is_not_recognised_even_with_a_valid_looking_format(): void
    {
        $demande = $this->demande('payee');
        $other = 'VGT-'.date('Y').'-'.str_pad((string) ($demande->id + 1), 6, '0', STR_PAD_LEFT);
        $signatureOfTheFirst = app(VignetteVerifier::class)->signature(app(VignetteVerifier::class)->reference($demande));

        $this->get("/verifier/{$other}?s={$signatureOfTheFirst}")->assertOk()->assertSee('Vignette non reconnue');
        $this->get('/verifier/pas-une-reference?s=abc')->assertOk()->assertSee('Vignette non reconnue');
    }

    public function test_a_valid_signature_for_an_unknown_vignette_is_not_recognised(): void
    {
        $reference = 'VGT-'.date('Y').'-000999';
        $signature = app(VignetteVerifier::class)->signature($reference);

        $this->get("/verifier/{$reference}?s={$signature}")->assertOk()->assertSee('Vignette non reconnue');
    }

    public function test_the_printed_card_qr_code_points_to_the_verification_page(): void
    {
        $mairieModel = Mairie::factory()->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'payee']);

        $this->actingAs(User::factory()->mairie($mairieModel)->create())->get("/demandes-vgt/{$demande->id}/carte/officiel")->assertOk()
            ->assertSee('data-qr="'.e($this->url($demande)).'"', false);
    }
}
