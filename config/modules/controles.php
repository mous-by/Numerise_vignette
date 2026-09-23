<?php

/*
| Manifeste du module « controles » (W11, D6, D7). Contrôle rapide en patrouille (cahier §4 Police, §8) : le
| matricule d'une moto → volée ou non, vignette à jour ou non. Lecture nationale (Moto::acrossCommissariats()) :
| la police doit pouvoir contrôler n'importe quelle moto, pas seulement celles de son commissariat. Réservé au
| canal API (police, D31/§4.3) : pas d'écran Web, `navigation => []`.
*/

return [
    'label' => 'Contrôle de police',

    'permissions' => [
        'check' => 'Contrôler une moto par son matricule',
    ],

    'reserved' => [],

    'roles' => [
        'police' => [
            'defaults' => ['check'],
            'optional' => [],
        ],
    ],

    'navigation' => [],
];
