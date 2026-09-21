<?php

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Commissariat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * API mobile (police) : connexion par téléphone, jeton Bearer par appareil, sans session ni CSRF (D12, D26).
 */
class AuthApiTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private const PASSWORD = 'Secret-Pass-1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    /** Une requête isolée : les gardes sont réinitialisés pour ne pas réutiliser l'utilisateur de l'appel précédent. */
    private function api(string $method, string $uri, array $data = [], ?string $token = null): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        if ($token !== null) {
            $this->withToken($token);
        }

        return $this->json($method, $uri, $data);
    }

    private function login(string $phone = '70 00 00 01', string $password = self::PASSWORD, string $device = 'tecno-spark'): TestResponse
    {
        return $this->api('POST', '/api/v1/auth/login', ['phone' => $phone, 'password' => $password, 'device_name' => $device]);
    }

    private function police(array $attributes = []): User
    {
        return User::factory()->police()->create(['phone' => '+22370000001', 'password' => self::PASSWORD] + $attributes);
    }

    public function test_the_health_endpoint_is_public(): void
    {
        $this->api('GET', '/api/v1/health')->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_a_police_officer_logs_in_with_their_phone_and_gets_a_bearer_token(): void
    {
        $commissariat = Commissariat::factory()->create(['name' => 'Commissariat de Test']);
        $police = User::factory()->police($commissariat)->create(['phone' => '+22370000001', 'password' => self::PASSWORD, 'name' => 'Agent Test']);

        $response = $this->login()
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('abilities', ['*'])
            ->assertJsonPath('password_change_required', false)
            ->assertJsonPath('user.name', 'Agent Test')
            ->assertJsonPath('user.phone', '+22370000001')
            ->assertJsonPath('user.role.name', 'police')
            ->assertJsonPath('user.institution.type', 'commissariat')
            ->assertJsonPath('user.institution.name', 'Commissariat de Test');

        $this->assertNotEmpty($response->json('token'));
        $this->assertEmpty($response->baseResponse->headers->getCookies(), 'L\'API est sans session : aucun cookie.');
        $this->assertNotNull($police->fresh()->last_login_at);
        $log = ActivityLog::where('action', 'auth.login')->firstOrFail();
        $this->assertSame('api', $log->channel->value);
    }

    public function test_the_token_gives_access_to_me_with_role_institution_and_permissions(): void
    {
        $this->police();
        $token = $this->login()->json('token');

        $this->api('GET', '/api/v1/auth/me', token: $token)
            ->assertOk()
            ->assertJsonPath('user.role.label', 'Police')
            ->assertJsonPath('user.permissions', [])
            ->assertJsonPath('user.must_change_password', false);
    }

    public function test_the_phone_is_accepted_in_common_writings(): void
    {
        $this->police();

        foreach (['70 00 00 01', '+223 70 00 00 01', '0022370000001', '70000001'] as $format) {
            $this->login($format)->assertOk();
        }
    }

    public function test_the_token_is_stored_with_a_stable_alias_and_a_30_day_expiry(): void
    {
        $this->police();
        $expiresAt = $this->login()->json('expires_at');

        $row = DB::table('personal_access_tokens')->first();
        $this->assertSame('user', $row->tokenable_type);
        $this->assertSame('tecno-spark', $row->name);
        $this->assertSame('["*"]', $row->abilities);
        $this->assertEqualsWithDelta(now()->addMinutes(43200)->timestamp, strtotime($expiresAt), 5);
        $this->assertNotSame($this->login()->json('token'), $row->token, 'Seul le hachage est stocké.');
    }

    public function test_the_login_validates_its_fields_in_json(): void
    {
        $this->api('POST', '/api/v1/auth/login', ['phone' => '70000001', 'password' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors(['device_name']);
        $this->api('POST', '/api/v1/auth/login', [])
            ->assertStatus(422)->assertJsonValidationErrors(['phone', 'password', 'device_name']);
    }

    public function test_a_wrong_password_is_refused_and_audited_on_the_api_channel(): void
    {
        $this->police();

        $this->login('70 00 00 01', 'faux-mot-de-passe-1')->assertStatus(422)->assertJsonValidationErrors(['phone']);

        $log = ActivityLog::where('action', 'auth.login_failed')->firstOrFail();
        $this->assertSame('api', $log->channel->value);
        $this->assertSame('+22370000001', $log->new_values['phone']);
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_five_failures_lock_the_api_login_with_a_429(): void
    {
        $this->police();

        for ($i = 0; $i < 5; $i++) {
            $this->login('70 00 00 01', 'faux-mot-de-passe-1')->assertStatus(422);
        }

        $this->login()->assertStatus(429)->assertJsonValidationErrors(['phone']);
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_web_roles_cannot_use_the_mobile_api(): void
    {
        User::factory()->commissaire()->create(['phone' => '+22370000002', 'password' => self::PASSWORD]);

        $this->login('70 00 00 02')->assertStatus(422)->assertJsonValidationErrors(['phone']);
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
        $this->assertSame('channel', ActivityLog::where('action', 'auth.login_blocked')->firstOrFail()->new_values['reason']);
    }

    public function test_a_token_of_a_web_role_is_refused_on_every_api_route(): void
    {
        $commissaire = User::factory()->commissaire()->create();
        $token = $commissaire->createToken('x')->plainTextToken;

        $this->api('GET', '/api/v1/auth/me', token: $token)->assertForbidden()->assertJsonPath('code', 'channel_forbidden');
    }

    public function test_deactivated_accounts_and_institutions_cannot_log_in(): void
    {
        User::factory()->police()->inactive()->create(['phone' => '+22370000003', 'password' => self::PASSWORD]);
        User::factory()->police(Commissariat::factory()->inactive()->create())->create(['phone' => '+22370000004', 'password' => self::PASSWORD]);

        $this->login('70 00 00 03')->assertStatus(422);
        $this->login('70 00 00 04')->assertStatus(422);
    }

    public function test_deactivating_the_account_or_the_institution_cuts_a_live_token_immediately(): void
    {
        $commissariat = Commissariat::factory()->create();
        $police = User::factory()->police($commissariat)->create(['phone' => '+22370000001', 'password' => self::PASSWORD]);
        $token = $this->login()->json('token');
        $this->api('GET', '/api/v1/auth/me', token: $token)->assertOk();

        $commissariat->update(['is_active' => false]);

        $this->api('GET', '/api/v1/auth/me', token: $token)->assertForbidden()->assertJsonPath('code', 'account_disabled');
        $this->assertSame(0, $police->tokens()->count(), 'Le jeton est supprimé.');
    }

    public function test_a_temporary_password_only_allows_changing_it(): void
    {
        $this->police(['must_change_password' => true]);
        $login = $this->login()->assertOk()->assertJsonPath('abilities', ['password:change'])->assertJsonPath('password_change_required', true);
        $restricted = $login->json('token');

        $this->api('GET', '/api/v1/auth/me', token: $restricted)->assertForbidden()->assertJsonPath('code', 'password_change_required');

        $this->api('PUT', '/api/v1/auth/password', ['current_password' => self::PASSWORD, 'password' => 'court1', 'password_confirmation' => 'court1'], $restricted)
            ->assertStatus(422)->assertJsonValidationErrors(['password']);
        $this->api('PUT', '/api/v1/auth/password', ['current_password' => 'incorrect-1', 'password' => 'Nouveau-Secret-9', 'password_confirmation' => 'Nouveau-Secret-9'], $restricted)
            ->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        $changed = $this->api('PUT', '/api/v1/auth/password', ['current_password' => self::PASSWORD, 'password' => 'Nouveau-Secret-9', 'password_confirmation' => 'Nouveau-Secret-9'], $restricted)
            ->assertOk()->assertJsonPath('abilities', ['*'])->assertJsonPath('password_change_required', false);

        $this->api('GET', '/api/v1/auth/me', token: $restricted)->assertUnauthorized(); // ancien jeton révoqué
        $this->api('GET', '/api/v1/auth/me', token: $changed->json('token'))->assertOk();
        $this->assertSame(1, ActivityLog::where('action', 'auth.password_changed')->count());
        $this->login('70 00 00 01', 'Nouveau-Secret-9')->assertOk();
    }

    public function test_logging_out_revokes_the_token(): void
    {
        $this->police();
        $token = $this->login()->json('token');

        $this->api('POST', '/api/v1/auth/logout', token: $token)->assertOk();

        $this->api('GET', '/api/v1/auth/me', token: $token)->assertUnauthorized();
        $this->assertSame(1, ActivityLog::where('action', 'auth.logout')->count());
    }

    public function test_one_token_per_device(): void
    {
        $police = $this->police();

        $first = $this->login(device: 'telephone-a')->json('token');
        $this->login(device: 'telephone-a')->assertOk();
        $this->assertSame(1, $police->tokens()->count(), 'Se reconnecter sur le même appareil remplace le jeton.');
        $this->api('GET', '/api/v1/auth/me', token: $first)->assertUnauthorized();

        $this->login(device: 'telephone-b')->assertOk();
        $this->assertSame(2, $police->tokens()->count());
    }

    public function test_changing_the_password_revokes_the_tokens_of_every_other_device(): void
    {
        $police = $this->police();
        $a = $this->login(device: 'a')->json('token');
        $b = $this->login(device: 'b')->json('token');

        $this->api('PUT', '/api/v1/auth/password', ['current_password' => self::PASSWORD, 'password' => 'Nouveau-Secret-9', 'password_confirmation' => 'Nouveau-Secret-9'], $a)->assertOk();

        $this->api('GET', '/api/v1/auth/me', token: $b)->assertUnauthorized();
        $this->assertSame(1, $police->tokens()->count());
    }

    public function test_a_token_expires_after_30_days(): void
    {
        $this->police();
        $token = $this->login()->json('token');
        $this->api('GET', '/api/v1/auth/me', token: $token)->assertOk();

        $this->travel(31)->days();

        $this->api('GET', '/api/v1/auth/me', token: $token)->assertUnauthorized();
    }

    public function test_missing_or_invalid_tokens_get_a_french_json_401_never_html(): void
    {
        $this->api('GET', '/api/v1/auth/me')->assertUnauthorized()->assertJsonPath('message', 'Non authentifié.');
        $this->api('GET', '/api/v1/auth/me', token: 'n-importe-quoi')->assertUnauthorized();

        // Même sans l'en-tête Accept, l'API répond en JSON.
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->get('/api/v1/auth/me')->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
    }

    public function test_the_spa_cookie_mode_does_not_exist(): void
    {
        $this->get('/sanctum/csrf-cookie')->assertNotFound();
        $this->assertSame([], config('sanctum.guard'));
        $this->assertSame([], config('sanctum.stateful'));
    }
}
