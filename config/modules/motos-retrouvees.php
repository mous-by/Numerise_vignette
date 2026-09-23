<?php

/*
| Manifeste du module « motos-retrouvees » (W10, D6, D7). Clé du manifeste = clé déjà utilisée par
| config/planned_modules.php (jamais modifié, CLAUDE.md §0.5) : permissions et routes en tirets
| (`motos-retrouvees.view/create/update`, `/motos-retrouvees`). La table SQL, elle, reste en soulignés
| (`motos_retrouvees`, Laravel/SQL) : MotoRetrouvee::activityModule() est donc explicitement surchargée pour
| revenir à la clé du module (« motos-retrouvees ») plutôt que de suivre par défaut le nom de la table — sinon
| l'audit et les permissions désigneraient le même module par deux chaînes différentes.
|
| Cahier §5 Cas 2, §9 Moto retrouvée : enregistrement (lieu et date d'arrêt) par le commissariat qui l'a
| retrouvée — pas forcément celui où elle a été déclarée volée, d'où une lecture nationale des motos volées
| (BelongsToCommissariat sur MotoRetrouvee cloisonne « qui a retrouvé », pas « quelle moto » : voir
| MotoRetrouvee::moto(), acrossCommissariats()). Pas de permission « delete » : absente du cahier pour ce module
| (juste lister et modifier, y compris marquer « récupérée »).
*/

return [
    'label' => 'Motos retrouvées',

    'permissions' => [
        'view' => 'Voir les motos retrouvées',
        'create' => 'Enregistrer une moto retrouvée',
        'update' => 'Modifier une moto retrouvée',
    ],

    'reserved' => [],

    'roles' => [
        'commissaire' => [
            'defaults' => ['view', 'create', 'update'],
            'optional' => [],
        ],
    ],

    'navigation' => [
        ['label' => 'Motos retrouvées', 'icon' => 'bx bx-search-alt', 'route' => 'motos-retrouvees.index', 'permission' => 'motos-retrouvees.view', 'active' => 'motos-retrouvees.*', 'order' => 70],
    ],
];
