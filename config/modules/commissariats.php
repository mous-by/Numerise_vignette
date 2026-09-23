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

    // Pas d'entrée de sidebar : l'écran s'ouvre depuis le sous-menu Paramètres (configuration/_menu.blade.php),
    // qui la propose déjà, comme Utilisateurs, Audit et Système.
    'navigation' => [],
];
