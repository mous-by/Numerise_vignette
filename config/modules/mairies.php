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

    // Pas d'entrée de sidebar : l'écran s'ouvre depuis le sous-menu Paramètres (configuration/_menu.blade.php),
    // qui la propose déjà, comme Utilisateurs, Commissariats, Audit et Système.
    'navigation' => [],
];
