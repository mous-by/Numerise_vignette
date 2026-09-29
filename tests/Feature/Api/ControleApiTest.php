<?php

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Moto;
use App\Models\Proprietaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Contrôle d'une moto par la police en patrouille (W11, cahier §4, §8) : matricule → volée ou non, vignette à
 * jour ou non. Lecture nationale (§4.7, Moto::acrossCommissariats()) : une moto d'un autre commissariat doit
 * être trouvable.
 */
class ControleApiTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    private function api(string $method, string $uri, ?string $token = null): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        if ($token !== null) {
            $this->withToken($token);
        }

        return $this->json($method, $uri);
    }

    private function motoWith(array $attributes = []): Moto
    {
        $proprietaire = Proprietaire::factory()->create();

        return Moto::factory()->create(['proprietaire_id' => $proprietaire->id, 'commissariat_id' => $proprietaire->commissariat_id] + $attributes);
    }

    public function test_a_police_officer_controls_a_moto_by_plate_number(): void
    {
        $moto = $this->motoWith(['plate_number' => 'AB 1234 CD', 'vgt_year' => now()->year, 'is_stolen' => false]);
        $token = User::factory()->police()->create()->createToken('x')->plainTextToken;

        $this->api('GET', '/api/v1/controles?matricule=AB 1234 CD', $token)
            ->assertOk()
            ->assertExactJson(['matricule' => 'AB 1234 CD', 'volee' => false, 'vgt_a_jour' => true]);
    }

    public function test_it_finds_a_moto_registered_in_another_commissariat(): void
    {
        $moto = $this->motoWith(['plate_number' => 'AB 9999 CD', 'is_stolen' => true]);
        $token = User::factory()->police()->create()->createToken('x')->plainTextToken; // autre commissariat

        $this->api('GET', '/api/v1/controles?matricule=AB 9999 CD', $token)
            ->assertOk()
            ->assertJsonPath('volee', true);
    }

    public function test_a_stolen_moto_is_reported_as_such(): void
    {
        $this->motoWith(['plate_number' => 'VOLEE', 'is_stolen' => true]);
        $token = User::factory()->police()->create()->createToken('x')->plainTextToken;

        $this->api('GET', '/api/v1/controles?matricule=VOLEE', $token)->assertJsonPath('volee', true);
    }

    public function test_an_outdated_vgt_year_is_reported_as_not_current(): void
    {
        $this->motoWith(['plate_number' => 'PERIME', 'vgt_year' => now()->year - 1]);
        $token = User::factory()->police()->create()->createToken('x')->plainTextToken;

        $this->api('GET', '/api/v1/controles?matricule=PERIME', $token)->assertJsonPath('vgt_a_jour', false);
    }

    public function test_an_unknown_plate_number_returns_404(): void
    {
        $token = User::factory()->police()->create()->createToken('x')->plainTextToken;

        $this->api('GET', '/api/v1/controles?matricule=INCONNU', $token)->assertNotFound();
    }

    public function test_a_missing_matricule_returns_a_validation_error(): void
    {
        $token = User::factory()->police()->create()->createToken('x')->plainTextToken;

        $this->api('GET', '/api/v1/controles', $token)->assertStatus(422);
    }

    public function test_the_control_is_audited(): void
    {
        $this->motoWith(['plate_number' => 'AB 1234 CD']);
        $token = User::factory()->police()->create()->createToken('x')->plainTextToken;

        $this->api('GET', '/api/v1/controles?matricule=AB 1234 CD', $token)->assertOk();

        $this->assertSame(1, ActivityLog::where('action', 'controles.read')->count());
    }

    public function test_a_commissaire_cannot_use_the_api_channel(): void
    {
        $token = User::factory()->commissaire()->create()->createToken('x')->plainTextToken;

        $this->api('GET', '/api/v1/controles?matricule=AB 1234 CD', $token)
            ->assertForbidden()->assertJsonPath('code', 'channel_forbidden');
    }

    public function test_an_unauthenticated_request_is_rejected(): void
    {
        $this->api('GET', '/api/v1/controles?matricule=AB 1234 CD')->assertUnauthorized();
    }
}
