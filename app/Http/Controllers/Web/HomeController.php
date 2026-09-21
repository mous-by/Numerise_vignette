<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tableau de bord : deux vues (D22), un accueil piloté par les rôles et permissions, et une
 * supervision pour le superadmin. Données réelles du socle, ou fictives si DASHBOARD_MOCK (D25).
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): View
    {
        $user = $request->user();

        return view($user->isSuperadmin() ? 'home-superadmin' : 'home', ['dashboard' => $dashboard->for($user)]);
    }
}
