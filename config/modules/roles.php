<?php

return [
    'label' => 'Rôles',

    'permissions' => [
        'view' => 'Consulter le catalogue des rôles',
    ],

    // Réservée au superadmin : jamais attribuable à un autre rôle.
    'reserved' => ['view'],

    'roles' => [],

    'navigation' => [
        // Point d'entrée de la zone Paramètres (Rôles, Permissions, Attribution, ...), toujours en bas de la sidebar.
        ['label' => 'Paramètres', 'icon' => 'bx bx-cog', 'route' => 'roles.index', 'permission' => 'roles.view', 'active' => ['roles.*', 'permissions.*', 'user-permissions.*', 'users.permissions.*'], 'position' => 'bottom', 'order' => 900],
    ],
];
