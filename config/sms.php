<?php

/*
|--------------------------------------------------------------------------
| SMS (W14, cahier §5 Cas 2, §8, §9)
|--------------------------------------------------------------------------
|
| Opérateur, langue et contenu des messages : À VALIDER AVEC LE CLIENT (points ouverts §5). En attendant, le
| pilote `log` n'envoie rien : il écrit le message dans le journal Laravel et marque l'envoi « simulé ». Un vrai
| opérateur s'ajoute en implémentant App\Services\Sms\SmsDriver puis en l'enregistrant dans SmsManager.
|
| Gabarits en français (PROPOSITION TECHNIQUE — À VALIDER) : {matricule}, {lieu}, {commissariat}, {mairie},
| {annee}, {reference} sont remplacés à l'envoi.
|
*/

return [

    'enabled' => (bool) env('SMS_ENABLED', true),

    'driver' => env('SMS_DRIVER', 'log'),

    'templates' => [
        'moto_retrouvee' => 'VigiMoto : votre moto {matricule} a été retrouvée ({lieu}). Présentez-vous au {commissariat} pour la récupérer.',
        'paiement_confirme' => 'VigiMoto : le paiement de votre vignette {annee} est confirmé. Retirez votre carte VGT à la {mairie}.',
        'demande_recue' => 'VigiMoto : votre demande de vignette {annee} ({reference}) est bien reçue. La {mairie} vous contactera.',
    ],

];
