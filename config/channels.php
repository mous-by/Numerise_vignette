<?php

/*
|--------------------------------------------------------------------------
| Canaux d'accès par rôle (ARCHITECTURE §14)
|--------------------------------------------------------------------------
|
| Web : session, navigateur PC. API : jetons Bearer, application mobile.
| Les deux portes ne se mélangent jamais. Le rôle `population` passera en API
| quand le module Population activera son authentification (D17).
|
*/

return [

    'web' => ['superadmin', 'admin_national', 'commissaire', 'mairie'],

    'api' => ['police'],

];
