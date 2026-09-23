<?php

/*
| Manifeste du module « demandes-vgt » (W11, D6, D7). Cahier §9 Demande VGT : déposée par le commissaire,
| validée ou rejetée par la mairie de retrait choisie. Ni BelongsToCommissariat ni BelongsToMairie seuls :
| visible du commissariat qui a déposé ET de la mairie choisie (DemandeVgt::scopeVisibleTo(), comme User §4.7).
|
| Workflow (statuts, rejet) et majoration pour arriéré : PROPOSITION TECHNIQUE — À VALIDER, le cahier ne les
| détaille pas (voir App\Models\DemandeVgt, App\Enums\DemandeVgtStatus). Tarif par genre : réglage configurable
| (table tarifs_vgt), permission `manage_tarifs` réservée au superadmin et à l'admin national.
*/

return [
    'label' => 'Demandes VGT',

    'permissions' => [
        'view' => 'Voir les demandes VGT',
        'create' => 'Déposer une demande VGT',
        'update' => 'Modifier une demande VGT rejetée',
        'validate' => 'Valider ou rejeter une demande VGT',
        'manage_tarifs' => 'Gérer les tarifs VGT',
    ],

    'reserved' => [],

    'roles' => [
        'commissaire' => [
            'defaults' => ['view', 'create', 'update'],
            'optional' => [],
        ],
        'mairie' => [
            'defaults' => ['view', 'validate'],
            'optional' => [],
        ],
        'admin_national' => [
            // `view` en plus de `manage_tarifs` : sans lui, l'admin national ne pourrait jamais atteindre le
            // bouton « Tarifs », imbriqué dans cet écran (pas de page séparée, §7). Il voit alors toutes les
            // demandes nationalement (DemandeVgt::scopeVisibleTo(), comme les institutions et les utilisateurs).
            'defaults' => ['view', 'manage_tarifs'],
            'optional' => [],
        ],
    ],

    'navigation' => [
        ['label' => 'Demandes VGT', 'icon' => 'bx bx-file', 'route' => 'demandes-vgt.index', 'permission' => 'demandes-vgt.view', 'active' => 'demandes-vgt.*', 'order' => 80],
    ],
];
