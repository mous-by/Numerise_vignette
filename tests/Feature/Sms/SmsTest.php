<?php

namespace Tests\Feature\Sms;

use App\Enums\SmsEvent;
use App\Enums\SmsStatus;
use App\Jobs\SendSmsJob;
use App\Models\ActivityLog;
use App\Models\DemandeVgt;
use App\Models\Mairie;
use App\Models\Moto;
use App\Models\Proprietaire;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Sms\SmsDriver;
use App\Services\Sms\SmsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * SMS automatiques (W14) : moto retrouvée, paiement confirmé, demande reçue. Pilote `log` (aucun opérateur
 * choisi, point ouvert client) ; l'envoi passe par la file d'attente (synchrone en test).
 */
class SmsTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
    }

    public function test_confirming_a_payment_texts_the_contact_of_the_demande(): void
    {
        $mairieModel = Mairie::factory()->create(['name' => 'Mairie de Kalaban']);
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee', 'contact_phone' => '+22370001111', 'vgt_year' => 2026]);

        $this->actingAs($agent)->put("/demandes-vgt/{$demande->id}/paiement", ['payment_confirmed_at' => now()->format('Y-m-d')])->assertRedirect('/demandes-vgt');

        $sms = SmsMessage::sole();
        $this->assertSame(SmsEvent::PaiementConfirme, $sms->event);
        $this->assertSame('+22370001111', $sms->phone);
        $this->assertStringContainsString('2026', $sms->message);
        $this->assertStringContainsString('Mairie de Kalaban', $sms->message);
        $this->assertSame(SmsStatus::Sent, $sms->status);
        $this->assertSame('log', $sms->driver);
        $this->assertNotNull($sms->sent_at);
    }

    public function test_registering_a_found_moto_texts_its_owner(): void
    {
        $chef = User::factory()->commissaire()->create();
        $proprietaire = Proprietaire::factory()->create(['commissariat_id' => $chef->commissariat_id, 'phone' => '+22370002222']);
        $moto = Moto::factory()->create(['commissariat_id' => $chef->commissariat_id, 'proprietaire_id' => $proprietaire->id, 'plate_number' => 'AB 1234 CD']);
        $moto->forceFill(['is_stolen' => true])->save(); // une moto n'est « retrouvée » que si elle était signalée volée

        $this->actingAs($chef)->post('/motos-retrouvees', ['moto_id' => $moto->id, 'found_at' => now()->format('Y-m-d'), 'location' => 'Pont des Martyrs'])
            ->assertRedirect('/motos-retrouvees');

        $sms = SmsMessage::sole();
        $this->assertSame(SmsEvent::MotoRetrouvee, $sms->event);
        $this->assertSame('+22370002222', $sms->phone);
        $this->assertStringContainsString('AB 1234 CD', $sms->message);
        $this->assertStringContainsString('Pont des Martyrs', $sms->message);
    }

    public function test_a_population_request_is_acknowledged_by_sms(): void
    {
        $proprietaire = Proprietaire::factory()->create(['phone' => '+22370003333']);
        Moto::factory()->create(['commissariat_id' => $proprietaire->commissariat_id, 'proprietaire_id' => $proprietaire->id, 'plate_number' => 'ZZ 9999 ZZ', 'vgt_year' => 2020]);

        $this->postJson('/api/v1/demandes-vgt', ['matricule' => 'ZZ 9999 ZZ', 'phone' => '70 00 33 33', 'mairie_id' => Mairie::factory()->create()->id, 'vgt_year' => (int) date('Y')])->assertCreated();

        $sms = SmsMessage::sole();
        $this->assertSame(SmsEvent::DemandeRecue, $sms->event);
        $this->assertSame('+22370003333', $sms->phone);
        $this->assertStringContainsString('VGT-'.date('Y').'-', $sms->message);
    }

    public function test_the_sms_is_sent_through_the_queue(): void
    {
        Queue::fake();
        $mairieModel = Mairie::factory()->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee', 'contact_phone' => '+22370001111']);

        $this->actingAs(User::factory()->mairie($mairieModel)->create())->put("/demandes-vgt/{$demande->id}/paiement", ['payment_confirmed_at' => now()->format('Y-m-d')]);

        Queue::assertPushed(SendSmsJob::class);
        $this->assertSame(SmsStatus::Queued, SmsMessage::sole()->status);
    }

    public function test_a_failing_driver_marks_the_message_failed_without_breaking_the_action(): void
    {
        $this->app->bind(SmsManager::class, fn () => new class extends SmsManager
        {
            public function driver(?string $name = null): SmsDriver
            {
                return new class implements SmsDriver
                {
                    public function name(): string
                    {
                        return 'panne';
                    }

                    public function send(string $phone, string $message): string
                    {
                        throw new RuntimeException('Opérateur injoignable');
                    }
                };
            }
        });
        $mairieModel = Mairie::factory()->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee', 'contact_phone' => '+22370001111']);

        try {
            $this->actingAs(User::factory()->mairie($mairieModel)->create())->put("/demandes-vgt/{$demande->id}/paiement", ['payment_confirmed_at' => now()->format('Y-m-d')]);
        } catch (RuntimeException) {
            // la file synchrone remonte l'exception du job ; en production elle est réessayée, jamais vue de l'utilisateur
        }

        $this->assertSame('payee', $demande->fresh()->status->value, "Le paiement reste confirmé même si l'SMS échoue.");
        $sms = SmsMessage::sole();
        $this->assertSame(SmsStatus::Failed, $sms->status);
        $this->assertSame('Opérateur injoignable', $sms->error);
    }

    public function test_nothing_is_sent_when_sms_are_disabled_or_the_number_is_unusable(): void
    {
        $mairieModel = Mairie::factory()->create();
        $agent = User::factory()->mairie($mairieModel)->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee', 'contact_phone' => 'pas-un-numero']);
        $this->actingAs($agent)->put("/demandes-vgt/{$demande->id}/paiement", ['payment_confirmed_at' => now()->format('Y-m-d')]);
        $this->assertSame(0, SmsMessage::count());

        config(['sms.enabled' => false]);
        $other = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee', 'contact_phone' => '+22370001111']);
        $this->actingAs($agent)->put("/demandes-vgt/{$other->id}/paiement", ['payment_confirmed_at' => now()->format('Y-m-d')]);
        $this->assertSame(0, SmsMessage::count());
    }

    public function test_the_simulated_driver_only_writes_to_the_log(): void
    {
        Log::spy();
        $mairieModel = Mairie::factory()->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee', 'contact_phone' => '+22370001111']);

        $this->actingAs(User::factory()->mairie($mairieModel)->create())->put("/demandes-vgt/{$demande->id}/paiement", ['payment_confirmed_at' => now()->format('Y-m-d')]);

        Log::shouldHaveReceived('info')->with('SMS simulé', \Mockery::type('array'))->once();
    }

    public function test_the_audit_never_stores_the_number_or_the_text_of_a_sms(): void
    {
        $mairieModel = Mairie::factory()->create();
        $demande = DemandeVgt::factory()->create(['mairie_id' => $mairieModel->id, 'status' => 'validee', 'contact_phone' => '+22370001111']);

        $this->actingAs(User::factory()->mairie($mairieModel)->create())->put("/demandes-vgt/{$demande->id}/paiement", ['payment_confirmed_at' => now()->format('Y-m-d')]);

        $logs = ActivityLog::where('subject_type', SmsMessage::class)->orWhere('module', 'sms_messages')->get();
        $this->assertNotEmpty($logs);
        foreach ($logs as $log) {
            $this->assertStringNotContainsString('+22370001111', json_encode([$log->old_values, $log->new_values]));
            $this->assertStringNotContainsString('retirez votre carte', mb_strtolower(json_encode([$log->old_values, $log->new_values])));
        }
    }

    public function test_the_journal_is_reserved_to_the_national_supervision(): void
    {
        SmsMessage::create(['event' => SmsEvent::PaiementConfirme, 'phone' => '+22370001111', 'message' => 'Texte du SMS', 'status' => SmsStatus::Sent]);

        $this->get('/sms')->assertRedirect('/login');
        $this->actingAs(User::factory()->adminNational()->create())->get('/sms')->assertOk()->assertSee('JOURNAL DES SMS')->assertSee('Texte du SMS')->assertSee('Envoi simulé');
        $this->actingAs($this->superadmin())->get('/sms')->assertOk();
        $this->actingAs(User::factory()->commissaire()->create())->get('/sms')->assertForbidden();
        $this->actingAs(User::factory()->mairie()->create())->get('/sms')->assertForbidden();
    }
}
