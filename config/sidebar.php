<?php

/*
|--------------------------------------------------------------------------
| Sidebar : entrées regroupées ou absentes (2026-09-30)
|--------------------------------------------------------------------------
|
| `registre` : les écrans voisins du registre (propriétaires, motos, déclarations, motos retrouvées) n'ont pas
| chacun un lien dans le menu : une seule entrée y mène, et une barre d'onglets (partials/registre-tabs) permet
| de passer de l'un à l'autre. Chaque onglet n'apparaît que si l'utilisateur a la permission de la route.
|
| `embedded_planned` : modules de `config/planned_modules.php` déjà livrés à l'intérieur d'un autre écran
| (retrait et paiement vivent dans Demandes VGT, W12/W13) : inutile de les lister « à venir » dans le menu.
|
*/

return [

    'registre' => [
        'label' => 'Registre motos',
        'icon' => 'bx bx-cycling',
        'landing' => 'motos.index',
        'tabs' => [
            ['label' => 'Propriétaires', 'icon' => 'bx bx-user-pin', 'route' => 'proprietaires.index', 'permission' => 'proprietaires.view', 'active' => 'proprietaires.*'],
            ['label' => 'Motos', 'icon' => 'bx bx-cycling', 'route' => 'motos.index', 'permission' => 'motos.view', 'active' => 'motos.*'],
            ['label' => 'Déclarations', 'icon' => 'bx bx-error-alt', 'route' => 'declarations.index', 'permission' => 'declarations.view', 'active' => 'declarations.*'],
            ['label' => 'Motos retrouvées', 'icon' => 'bx bx-search-alt', 'route' => 'motos-retrouvees.index', 'permission' => 'motos-retrouvees.view', 'active' => 'motos-retrouvees.*'],
        ],
    ],

    'embedded_planned' => ['retraits-vgt', 'paiements'],

];
