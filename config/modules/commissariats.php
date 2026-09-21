<?php

return [
    'label' => 'Commissariats',

    'permissions' => [
        'view' => 'Voir les commissariats',
        'create' => 'Créer un commissariat',
        'update' => 'Modifier un commissariat',
        'delete' => 'Supprimer un commissariat',
    ],

    'reserved' => [],

    'roles' => [
        'admin_national' => [
            'defaults' => ['view', 'create', 'update'],
            'optional' => ['delete'],
        ],
    ],

    'navigation' => [
        ['label' => 'Commissariats', 'icon' => 'bx bx-buildings', 'route' => 'commissariats.index', 'permission' => 'commissariats.view', 'active' => 'commissariats.*', 'order' => 10],
    ],
];
