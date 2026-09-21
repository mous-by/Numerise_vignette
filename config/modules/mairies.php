<?php

return [
    'label' => 'Mairies',

    'permissions' => [
        'view' => 'Voir les mairies',
        'create' => 'Créer une mairie',
        'update' => 'Modifier une mairie',
        'delete' => 'Supprimer une mairie',
    ],

    'reserved' => [],

    'roles' => [
        'admin_national' => [
            'defaults' => ['view', 'create', 'update'],
            'optional' => ['delete'],
        ],
    ],

    'navigation' => [
        ['label' => 'Mairies', 'icon' => 'bx bx-building-house', 'route' => 'mairies.index', 'permission' => 'mairies.view', 'active' => 'mairies.*', 'order' => 20],
    ],
];
