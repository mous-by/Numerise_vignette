<?php

return [
    'label' => 'Audit',

    'permissions' => [
        'view' => 'Consulter le journal d\'audit',
    ],

    'reserved' => ['view'],

    'roles' => [],

    'navigation' => [
        ['label' => 'Audit', 'icon' => 'bx bx-list-check', 'route' => 'audit.index', 'permission' => 'audit.view', 'active' => 'audit.*', 'order' => 90],
    ],
];
