<?php

/*
|--------------------------------------------------------------------------
| Hiérarchie des rôles (ARCHITECTURE §6)
|--------------------------------------------------------------------------
|
| Source unique des niveaux, du plafond de rôle et du type d'institution de
| chaque rôle. Les Policies passent par App\Enums\RoleName, jamais par des
| comparaisons de chaînes dispersées. Personne n'agit sur un compte de niveau
| supérieur ou égal au sien, sauf le superadmin.
|
*/

return [

    'levels' => [
        'superadmin' => 100,
        'admin_national' => 80,
        'commissaire' => 50,
        'mairie' => 50,
        'police' => 30,
        'population' => 10,
    ],

    // Plafond de rôle : rôles qu'un rôle peut créer et gérer.
    'manages' => [
        'superadmin' => ['superadmin', 'admin_national', 'commissaire', 'mairie', 'police', 'population'],
        'admin_national' => ['commissaire', 'mairie', 'police'],
        'commissaire' => ['police'],
        'mairie' => [],
        'police' => [],
        'population' => [],
    ],

    // Rôles liés à une institution (les autres n'en ont aucune).
    'institution' => [
        'commissaire' => 'commissariat',
        'police' => 'commissariat',
        'mairie' => 'mairie',
    ],

];
