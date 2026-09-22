<?php

namespace Tests\Feature\Informations;

use App\Models\ActivityLog;
use App\Models\Information;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Écran Informations (W6) : publiées par les commissaires (description, image ou PDF), lues par tous les rôles
 * Web autorisés — liste publique par nature, pas cloisonnée par commissariat (ARCHITECTURE §11).
 */
class InformationScreenTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncPermissions();
        $this->superadmin = $this->superadmin();
        Storage::fake('public');
    }

    public function test_the_screen_requires_the_view_permission(): void
    {
        $this->get('/informations')->assertRedirect('/login');

        // Commissaire, mairie et admin national ont tous la vue par défaut (supervision nationale incluse) : pour
        // vérifier le blocage, on retire la permission par défaut au niveau du rôle lui-même (le seul niveau où
        // c'est possible, §4.4).
        Role::findByName('mairie', 'web')->revokePermissionTo('informations.view');
        $this->actingAs(User::factory()->mairie()->create())->get('/informations')->assertForbidden();

        $this->actingAs(User::factory()->commissaire()->create())->get('/informations')->assertOk()->assertSee('INFORMATIONS');
    }

    public function test_it_follows_the_theme_template(): void
    {
        $this->actingAs(User::factory()->commissaire()->create())->get('/informations')
            ->assertSee('card-header card-header-brand', false)
            ->assertSee('modal fade', false);
    }

    public function test_a_mairie_agent_can_only_view_not_publish(): void
    {
        $agent = User::factory()->mairie()->create();

        $this->actingAs($agent)->get('/informations')->assertOk()->assertDontSee('id="createInformationModal"', false);
        $this->actingAs($agent)->post('/informations', ['description' => 'Interdit'])->assertForbidden();
    }

    public function test_a_commissaire_publishes_a_description_only(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->post('/informations', ['description' => 'Vol de moto signalé secteur gare.'])
            ->assertRedirect('/informations')
            ->assertSessionHas('status');

        $information = Information::acrossCommissariats()->sole();
        $this->assertSame('Vol de moto signalé secteur gare.', $information->description);
        $this->assertSame($chef->id, $information->commissaire_id);
        $this->assertSame($chef->commissariat_id, $information->commissariat_id);
        $this->assertSame(1, ActivityLog::where('action', 'informations.created')->count());
    }

    public function test_a_commissaire_publishes_an_image_and_a_pdf_without_description(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->post('/informations', [
            'image' => UploadedFile::fake()->image('avis.jpg'),
            'document' => UploadedFile::fake()->create('avis.pdf', 200, 'application/pdf'),
        ])->assertRedirect('/informations');

        $information = Information::acrossCommissariats()->sole();
        Storage::disk('public')->assertExists($information->image_path);
        Storage::disk('public')->assertExists($information->document_path);
    }

    public function test_at_least_one_of_description_image_or_document_is_required(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->from('/informations')->post('/informations', [])
            ->assertRedirect('/informations')
            ->assertSessionHasErrors('description');

        $this->assertSame(0, Information::acrossCommissariats()->count());
    }

    public function test_the_list_shows_every_commissariat_not_just_the_actors_own(): void
    {
        $a = Information::factory()->create(['description' => 'Info A']);
        $b = Information::factory()->create(['description' => 'Info B']);
        $agent = User::factory()->mairie()->create();

        $this->actingAs($agent)->get('/informations')->assertSee('Info A')->assertSee('Info B');
    }

    public function test_only_the_author_institution_can_update_or_delete(): void
    {
        $chef = User::factory()->commissaire()->create();
        $information = Information::factory()->create(); // autre commissariat

        // 404, pas 403 : la liste (index) contourne volontairement le cloisonnement (acrossCommissariats()), mais
        // pas la résolution de route de ces actions d'écriture — l'information d'un autre commissariat ne se
        // résout donc même pas avant la Policy, cohérent avec le fail-closed (D8).
        $this->actingAs($chef)->put("/informations/{$information->id}", ['description' => 'Modifié'])->assertNotFound();
        $this->actingAs($chef)->delete("/informations/{$information->id}")->assertNotFound();
    }

    public function test_the_author_institution_updates_replaces_the_image_and_deletes_the_old_one(): void
    {
        $chef = User::factory()->commissaire()->create();
        $first = UploadedFile::fake()->image('premiere.jpg');
        $this->actingAs($chef)->post('/informations', ['image' => $first]);
        $information = Information::acrossCommissariats()->sole();
        $oldPath = $information->image_path;

        $this->actingAs($chef)->put("/informations/{$information->id}", [
            'description' => 'Mise à jour', 'image' => UploadedFile::fake()->image('nouvelle.jpg'),
        ])->assertRedirect('/informations');

        $information->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($information->image_path);
        $this->assertNotSame($oldPath, $information->image_path);
    }

    public function test_removing_the_image_without_replacing_it_keeps_the_description_valid(): void
    {
        $chef = User::factory()->commissaire()->create();
        $this->actingAs($chef)->post('/informations', ['image' => UploadedFile::fake()->image('seule.jpg')]);
        $information = Information::acrossCommissariats()->sole();
        $path = $information->image_path;

        $this->actingAs($chef)->put("/informations/{$information->id}", [
            'description' => 'Texte de secours', 'remove_image' => '1',
        ])->assertRedirect('/informations');

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($information->fresh()->image_path);
    }

    public function test_deleting_removes_the_files_and_soft_deletes_the_row(): void
    {
        $chef = User::factory()->commissaire()->create();
        $this->actingAs($chef)->post('/informations', ['image' => UploadedFile::fake()->image('a.jpg')]);
        $information = Information::acrossCommissariats()->sole();
        $path = $information->image_path;

        $this->actingAs($chef)->delete("/informations/{$information->id}")->assertRedirect('/informations');

        Storage::disk('public')->assertMissing($path);
        $this->assertSoftDeleted($information);
        $this->assertSame(1, ActivityLog::where('action', 'informations.deleted')->count());
    }

    public function test_the_superadmin_moderates_any_information(): void
    {
        $information = Information::factory()->create();

        $this->actingAs($this->superadmin)->delete("/informations/{$information->id}")->assertRedirect('/informations');
        $this->assertSoftDeleted($information);
    }
}
