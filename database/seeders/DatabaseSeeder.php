<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Volontairement vide à l'étape 1. Le socle n'amorcera que les rôles
     * (RoleSeeder, étape 4) : aucun compte, aucun mot de passe dans Git.
     * Les superadmins se créent avec `php artisan app:create-superadmin`.
     */
    public function run(): void
    {
        //
    }
}
