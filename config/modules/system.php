<?php

return [
    'label' => 'Système',

    'permissions' => [
        'view' => 'Consulter la page système',
        'maintain' => 'Lancer les actions de maintenance',
    ],

    'reserved' => ['view', 'maintain'],

    'roles' => [],

    'navigation' => [
        ['label' => 'Système', 'icon' => 'bx bx-server', 'route' => 'system.index', 'permission' => 'system.view', 'active' => 'system.*', 'order' => 100],
    ],
];
