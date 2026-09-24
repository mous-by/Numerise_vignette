<?php

/*
| Manifeste du module « sms » (W14, D6, D7). Journal en lecture seule des SMS automatiques envoyés par la
| plateforme (moto retrouvée, paiement confirmé, demande reçue). L'envoi lui-même n'a pas de permission : il est
| déclenché par les actions métier (SmsNotifier). Numéros et textes sont des données personnelles : l'écran est
| réservé à la supervision nationale. Opérateur, langue et contenu : À VALIDER AVEC LE CLIENT (config/sms.php).
*/

return [
    'label' => 'Notifications SMS',

    'permissions' => [
        'view' => 'Consulter le journal des SMS',
    ],

    'reserved' => [],

    'roles' => [
        'admin_national' => [
            'defaults' => ['view'],
            'optional' => [],
        ],
    ],

    'navigation' => [
        ['label' => 'Notifications SMS', 'icon' => 'bx bx-message-rounded-dots', 'route' => 'sms.index', 'permission' => 'sms.view', 'active' => 'sms.*', 'order' => 290],
    ],
];
