<?php

/*
| Manifeste du module « informations » (W6, D6, D7). Publiées par les commissaires (description, image, PDF —
| au moins un des trois) ; consultées par la mairie sur le Web, par la police et la population via l'API publique
| (D32, §8). Public par nature : la liste n'est pas cloisonnée par commissariat (Information::acrossCommissariats()),
| seules la création et la modification restent liées à l'institution de l'auteur.
*/

return [
    'label' => 'Informations',

    'permissions' => [
        'view' => 'Voir les informations',
        'create' => 'Publier une information',
        'update' => 'Modifier une information',
        'delete' => 'Supprimer une information',
    ],

    'reserved' => [],

    'roles' => [
        'commissaire' => [
            'defaults' => ['view', 'create', 'update', 'delete'],
            'optional' => [],
        ],
        'mairie' => [
            'defaults' => ['view'],
            'optional' => [],
        ],
        // Supervision nationale, lecture seule : l'admin national ne publie rien (le cahier ne le prévoit pas).
        'admin_national' => [
            'defaults' => ['view'],
            'optional' => [],
        ],
    ],

    'navigation' => [
        ['label' => 'Informations', 'icon' => 'bx bx-info-circle', 'route' => 'informations.index', 'permission' => 'informations.view', 'active' => 'informations.*', 'order' => 280],
    ],
];
