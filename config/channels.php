<?php

/*
|--------------------------------------------------------------------------
| Canaux d'accès par rôle (ARCHITECTURE §14)
|--------------------------------------------------------------------------
|
| Web : session, navigateur PC. API : jetons Bearer, application mobile.
| Les deux portes ne se mélangent jamais. La population n'a pas de compte (D32) :
| elle n'a donc pas de canal et utilise les routes publiques de l'API.
|
*/

return [

    'web' => ['superadmin', 'admin_national', 'commissaire', 'mairie'],

    'api' => ['police'],

];
