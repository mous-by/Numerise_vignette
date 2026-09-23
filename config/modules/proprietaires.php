<?php

/*
| Manifeste du module « proprietaires » (W7, D6, D7). Saisis par le commissaire lors de l'enregistrement initial
| (cahier §5 Cas 1) : nom, prénom, genre, adresse, téléphone identifié à leur nom, contact en cas d'urgence.
| Cloisonné par commissariat (Proprietaire::class utilise BelongsToCommissariat) : réservé au commissaire, pas
| un module de consultation nationale comme les informations (W6).
*/

return [
    'label' => 'Propriétaires',

    'permissions' => [
        'view' => 'Voir les propriétaires',
        'create' => 'Créer un propriétaire',
        'update' => 'Modifier un propriétaire',
        'delete' => 'Supprimer un propriétaire',
    ],

    'reserved' => [],

    'roles' => [
        'commissaire' => [
            'defaults' => ['view', 'create', 'update', 'delete'],
            'optional' => [],
        ],
    ],

    'navigation' => [
        ['label' => 'Propriétaires', 'icon' => 'bx bx-user-pin', 'route' => 'proprietaires.index', 'permission' => 'proprietaires.view', 'active' => 'proprietaires.*', 'order' => 40],
    ],
];
