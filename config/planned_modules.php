<?php

/*
|--------------------------------------------------------------------------
| Modules métier prévus, NON implémentés (D25)
|--------------------------------------------------------------------------
|
| Sert uniquement à afficher, dans la sidebar et sur une fiche, ce qui reste à
| construire. Aucune permission, table ni route métier n'existe pour ces
| modules : leurs règles sont à valider avec le client (CLAUDE.md, §5).
| Une entrée disparaît dès que le manifeste config/modules/<clé>.php existe.
|
| `roles` : rôles Web qui verront ce module dans le cahier des charges
| (superadmin et admin_national voient tout). `cahier` : où le cahier en parle.
| `open` : points ouverts client qui bloquent ce module.
|
*/

return [

    'proprietaires' => [
        'label' => 'Propriétaires',
        'icon' => 'bx bx-user-pin',
        'order' => 200,
        'roles' => ['commissaire'],
        'summary' => 'Saisie des profils de propriétaires : nom, prénom, genre, adresse, téléphone identifié à leur nom, contact en cas d\'urgence.',
        'cahier' => '§4 Commissaires · §5 Cas 1 · §9 Cas 1 (Accueil)',
        'open' => ['Un propriétaire peut-il avoir plusieurs motos ; historique des changements de propriétaire.'],
    ],

    'motos' => [
        'label' => 'Motos',
        'icon' => 'bx bx-cycling',
        'order' => 210,
        'roles' => ['commissaire'],
        'summary' => 'Enregistrement des motos : matricule, couleur, genre ou marque, année de la VGT ; attestation de vente (vendeur et témoins si besoin) ; liste, recherche, modification, suppression.',
        'cahier' => '§5 Cas 1 · §9 Accueil',
        'open' => ['Identification de la moto : matricule seul ou châssis en plus ; unicité.'],
    ],

    'declarations' => [
        'label' => 'Déclarations',
        'icon' => 'bx bx-error-alt',
        'order' => 220,
        'roles' => ['commissaire'],
        'summary' => 'Déclaration de vol, braquage ou autre : lieu, date et circonstances. Une fois signalée, la moto est marquée « Volée » dans la base consultable par les agents en patrouille.',
        'cahier' => '§5 Cas 2 · §9 Déclaration',
        'open' => ['Qui enregistre « moto retrouvée » : commissaire, police ou les deux.'],
    ],

    'motos-retrouvees' => [
        'label' => 'Motos retrouvées',
        'icon' => 'bx bx-search-alt',
        'order' => 230,
        'roles' => ['commissaire'],
        'summary' => 'Enregistrement d\'une moto retrouvée (lieu et date d\'arrêt) avec SMS automatique au propriétaire, puis suivi de la récupération. Consultation par la population.',
        'cahier' => '§4 · §5 Cas 2 · §9 Moto retrouvé',
        'open' => ['Qui enregistre « moto retrouvée » : commissaire, police ou les deux.', 'Opérateur SMS, langue et contenu des messages.'],
    ],

    'demandes-vgt' => [
        'label' => 'Demandes VGT',
        'icon' => 'bx bx-file',
        'order' => 240,
        'roles' => ['commissaire', 'mairie'],
        'summary' => 'Demande de vignette : matricule, année de la VGT, commissariat, mairie de retrait, contact pour SMS, code marchand de l\'État. Côté mairie : validation du formulaire VGT.',
        'cahier' => '§4 · §6 · §9 Demande VGT (commissaire et mairie)',
        'open' => ['Règles de la vignette : durée de validité, tarifs, arriérés.', 'Workflow mairie : statuts, rejet, confirmation de remise de la carte.'],
    ],

    'retraits-vgt' => [
        'label' => 'Retrait VGT',
        'icon' => 'bx bx-id-card',
        'order' => 250,
        'roles' => ['commissaire', 'mairie'],
        'summary' => 'Retrait de la carte VGT à la mairie : date de retrait, aperçu et impression de la carte ; cas VGT en jour, non en jour, ou non enregistrée dans la base (taxe à payer).',
        'cahier' => '§9 Retrait VGT (commissaire et mairie)',
        'open' => ['Nature et effet de la « taxe à payer » du contrôle de police.', 'Workflow mairie : statuts, rejet, confirmation de remise de la carte.'],
    ],

    'paiements' => [
        'label' => 'Paiements',
        'icon' => 'bx bx-money',
        'order' => 260,
        'roles' => ['commissaire', 'mairie'],
        'summary' => 'Paiement de la vignette par le code marchand de l\'État, confirmation et SMS indiquant que le paiement est fait et le jour de retrait de la carte.',
        'cahier' => '§9 Accueil · Demande VGT',
        'open' => ['Paiement : montant, qui confirme, statut, rôle du « code marchand de l\'État ».', 'QR Code et Mobile Money : phase 1 ou plus tard.'],
    ],

    'controles' => [
        'label' => 'Contrôle de police',
        'icon' => 'bx bx-shield-quarter',
        'order' => 270,
        'roles' => [],
        'summary' => 'Contrôle rapide en patrouille sur l\'application mobile : moto volée ou non, validité de la vignette. Lecture nationale des motos volées (règle du module).',
        'cahier' => '§4 Police (patrouille) · §8',
        'open' => ['Nature et effet de la « taxe à payer » du contrôle de police.'],
    ],

    'informations' => [
        'label' => 'Informations',
        'icon' => 'bx bx-info-circle',
        'order' => 280,
        'roles' => ['commissaire', 'mairie'],
        'summary' => 'Publication d\'informations aux populations par les commissaires (description, fichier PDF, image) ; consultation par la mairie, la police et la population.',
        'cahier' => '§8 · §9 Informations',
        'open' => [],
    ],

    'sms' => [
        'label' => 'Notifications SMS',
        'icon' => 'bx bx-message-rounded-dots',
        'order' => 290,
        'roles' => [],
        'summary' => 'SMS automatiques : confirmation de paiement et date de retrait, moto retrouvée, confirmation de demande de la population.',
        'cahier' => '§5 Cas 2 · §8 Population · §9',
        'open' => ['Opérateur SMS, langue et contenu des messages.'],
    ],

    'qr-code' => [
        'label' => 'QR Code',
        'icon' => 'bx bx-qr',
        'order' => 300,
        'roles' => [],
        'summary' => 'Vérification d\'une vignette par QR Code. Non prévu dans le cahier des charges : À VALIDER AVEC LE CLIENT.',
        'cahier' => 'Absent du cahier',
        'open' => ['QR Code et Mobile Money : phase 1 ou plus tard.'],
    ],

];
