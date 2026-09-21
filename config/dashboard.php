<?php

/*
|--------------------------------------------------------------------------
| Tableau de bord (D22, D25)
|--------------------------------------------------------------------------
|
| `mock` : affiche des données FICTIVES pour visualiser le tableau de bord avant
| l'existence des modules métier. Désactivé par défaut et jamais actif en
| production (DashboardService::usesMock() le vérifie), pour que des chiffres
| inventés ne passent jamais pour des chiffres réels.
|
*/

return [

    'mock' => (bool) env('DASHBOARD_MOCK', false),

];
