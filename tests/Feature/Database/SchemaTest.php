<?php

namespace Tests\Feature\Database;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Le schéma du socle correspond exactement au dictionnaire de données de CLAUDE.md (§4.1).
 * Ces tests tournent sur MariaDB (db_numerise_vignette_test) : le CHECK et les clés y sont réellement appliqués.
 */
class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_foundation_has_exactly_the_validated_tables(): void
    {
        $tables = collect(Schema::getTables(schema: DB::getDatabaseName()))->pluck('name')->reject(fn ($name) => $name === 'migrations')->sort()->values()->all();

        $expected = [
            'activity_logs', 'cache', 'cache_locks', 'commissariats', 'declarations', 'failed_jobs', 'job_batches',
            'jobs', 'mairies', 'model_has_permissions', 'model_has_roles', 'motos', 'permissions',
            'personal_access_tokens', 'proprietaires', 'role_has_permissions', 'roles', 'sessions', 'users',
        ];

        $this->assertSame($expected, $tables);
    }

    public function test_users_have_no_email_and_there_is_no_password_reset_table(): void
    {
        foreach (['email', 'email_verified_at', 'photo', 'username'] as $column) {
            $this->assertFalse(Schema::hasColumn('users', $column), "users.$column ne doit pas exister (D5)");
        }
        $this->assertFalse(Schema::hasTable('password_reset_tokens'));
    }

    public function test_users_table_has_the_validated_columns(): void
    {
        $expected = [
            'id', 'name', 'phone', 'password', 'is_active', 'must_change_password', 'password_changed_at',
            'commissariat_id', 'mairie_id', 'last_login_at', 'remember_token', 'created_at', 'updated_at', 'deleted_at',
        ];

        $this->assertSame($expected, Schema::getColumnListing('users'));
    }

    public function test_a_user_cannot_belong_to_both_a_commissariat_and_a_mairie(): void
    {
        $commissariat = $this->commissariat('Commissariat A');
        $mairie = $this->mairie('Mairie A');

        $this->expectException(QueryException::class);
        $this->insertUser('both', ['commissariat_id' => $commissariat, 'mairie_id' => $mairie]);
    }

    public function test_a_user_can_belong_to_one_institution_or_none(): void
    {
        $this->insertUser('none');
        $this->insertUser('commissariat_only', ['commissariat_id' => $this->commissariat('Commissariat A')]);
        $this->insertUser('mairie_only', ['mairie_id' => $this->mairie('Mairie A')]);

        $this->assertSame(3, DB::table('users')->count());
    }

    public function test_phone_is_the_required_unique_login_identifier(): void
    {
        $this->insertUser('a', ['phone' => '+22370000001']);
        $this->insertUser('b'); // numéro généré, distinct

        try {
            $this->insertUser('c', ['phone' => '+22370000001']);
            $this->fail('unicité du numéro');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(QueryException::class);
        DB::table('users')->insert(['name' => 'sans numéro', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()]); // NOT NULL
    }

    public function test_an_institution_with_users_cannot_be_deleted(): void
    {
        $commissariat = $this->commissariat('Commissariat A');
        $this->insertUser('police_1', ['commissariat_id' => $commissariat]);

        $this->expectException(QueryException::class);
        DB::table('commissariats')->where('id', $commissariat)->delete();
    }

    public function test_institution_names_are_unique(): void
    {
        $this->mairie('Mairie A');

        $this->expectException(QueryException::class);
        $this->mairie('mairie a');
    }

    public function test_a_model_can_only_have_one_role(): void
    {
        $now = now();
        $roleA = DB::table('roles')->insertGetId(['name' => 'police', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
        $roleB = DB::table('roles')->insertGetId(['name' => 'commissaire', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
        $user = $this->insertUser('x');

        DB::table('model_has_roles')->insert(['role_id' => $roleA, 'model_type' => 'App\Models\User', 'model_id' => $user]);

        $this->expectException(QueryException::class);
        DB::table('model_has_roles')->insert(['role_id' => $roleB, 'model_type' => 'App\Models\User', 'model_id' => $user]);
    }

    public function test_permissions_are_custom_by_default(): void
    {
        $now = now();
        $id = DB::table('permissions')->insertGetId(['name' => 'demo.view', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);

        $this->assertSame('custom', DB::table('permissions')->where('id', $id)->value('source'));
    }

    public function test_activity_logs_are_append_only_by_design_and_reference_their_author(): void
    {
        $this->assertTrue(Schema::hasColumn('activity_logs', 'created_at'));
        $this->assertFalse(Schema::hasColumn('activity_logs', 'updated_at'));
        $this->assertFalse(Schema::hasColumn('activity_logs', 'deleted_at'));

        $user = $this->insertUser('auteur');
        $id = DB::table('activity_logs')->insertGetId([
            'user_id' => $user, 'action' => 'auth.login', 'module' => 'auth', 'description' => 'Connexion', 'channel' => 'web',
        ]);
        $this->assertNotNull(DB::table('activity_logs')->where('id', $id)->value('created_at'));

        // Un utilisateur qui a un historique d'audit ne peut pas être supprimé physiquement.
        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $user)->delete();
    }

    private function commissariat(string $name): int
    {
        return DB::table('commissariats')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function mairie(string $name): int
    {
        return DB::table('mairies')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
    }

    private int $phoneSequence = 100;

    private function insertUser(string $name, array $attributes = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name' => $name, 'phone' => '+223700'.str_pad((string) $this->phoneSequence++, 5, '0', STR_PAD_LEFT),
            'password' => 'not-a-real-hash', 'created_at' => now(), 'updated_at' => now(),
        ], $attributes));
    }
}
