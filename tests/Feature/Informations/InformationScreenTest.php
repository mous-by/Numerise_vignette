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
 * Écran Informations (W6) : publiées par les commissaires (description, images ou PDF — plusieurs de chaque,
 * PROPOSITION TECHNIQUE), lues par tous les rôles Web autorisés — liste publique par nature, pas cloisonnée par
 * commissariat (ARCHITECTURE §11).
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

    public function test_a_commissaire_publishes_several_images_and_pdfs_without_description(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->post('/informations', [
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            'documents' => [UploadedFile::fake()->create('c.pdf', 200, 'application/pdf')],
        ])->assertRedirect('/informations');

        $information = Information::acrossCommissariats()->with('files')->sole();
        $this->assertCount(2, $information->images);
        $this->assertCount(1, $information->documents);
        foreach ($information->files as $file) {
            Storage::disk('public')->assertExists($file->path);
        }
    }

    public function test_at_least_one_of_description_images_or_documents_is_required(): void
    {
        $chef = User::factory()->commissaire()->create();

        $this->actingAs($chef)->from('/informations')->post('/informations', [])
            ->assertRedirect('/informations')
            ->assertSessionHasErrors('description');

        $this->assertSame(0, Information::acrossCommissariats()->count());
    }

    public function test_more_than_five_images_is_refused(): void
    {
        $chef = User::factory()->commissaire()->create();
        $images = array_map(fn ($i) => UploadedFile::fake()->image("img{$i}.jpg"), range(1, 6));

        $this->actingAs($chef)->from('/informations')->post('/informations', ['images' => $images])
            ->assertSessionHasErrors('images');

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

    public function test_updating_adds_new_files_alongside_existing_ones(): void
    {
        $chef = User::factory()->commissaire()->create();
        $this->actingAs($chef)->post('/informations', ['images' => [UploadedFile::fake()->image('premiere.jpg')]]);
        $information = Information::acrossCommissariats()->sole();

        $this->actingAs($chef)->put("/informations/{$information->id}", [
            'description' => 'Mise à jour', 'images' => [UploadedFile::fake()->image('deuxieme.jpg')],
        ])->assertRedirect('/informations');

        $this->assertCount(2, $information->fresh()->images);
    }

    public function test_removing_a_specific_existing_file_keeps_the_others(): void
    {
        $chef = User::factory()->commissaire()->create();
        $this->actingAs($chef)->post('/informations', [
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]);
        $information = Information::acrossCommissariats()->with('images')->sole();
        $toRemove = $information->images->first();
        $kept = $information->images->last();

        $this->actingAs($chef)->put("/informations/{$information->id}", [
            'description' => 'Texte de secours', 'remove_files' => [$toRemove->id],
        ])->assertRedirect('/informations');

        Storage::disk('public')->assertMissing($toRemove->path);
        $this->assertCount(1, $information->fresh()->images);
        $this->assertSame($kept->id, $information->fresh()->images->first()->id);
    }

    public function test_removing_every_file_without_a_description_is_refused(): void
    {
        $chef = User::factory()->commissaire()->create();
        $this->actingAs($chef)->post('/informations', ['images' => [UploadedFile::fake()->image('seule.jpg')]]);
        $information = Information::acrossCommissariats()->with('images')->sole();
        $image = $information->images->first();

        $this->actingAs($chef)->from('/informations')->put("/informations/{$information->id}", [
            'remove_files' => [$image->id],
        ])->assertSessionHasErrors('description');

        $this->assertCount(1, $information->fresh()->images, 'Rien ne doit être supprimé si la validation échoue.');
    }

    public function test_deleting_removes_every_file_and_soft_deletes_the_row(): void
    {
        $chef = User::factory()->commissaire()->create();
        $this->actingAs($chef)->post('/informations', [
            'images' => [UploadedFile::fake()->image('a.jpg')],
            'documents' => [UploadedFile::fake()->create('b.pdf', 100, 'application/pdf')],
        ]);
        $information = Information::acrossCommissariats()->with('files')->sole();
        $paths = $information->files->pluck('path')->all();

        $this->actingAs($chef)->delete("/informations/{$information->id}")->assertRedirect('/informations');

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
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
