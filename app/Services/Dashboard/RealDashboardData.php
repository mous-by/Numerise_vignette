<?php

namespace App\Services\Dashboard;

use App\Enums\RoleName;
use App\Models\ActivityLog;
use App\Models\Commissariat;
use App\Models\Mairie;
use App\Models\User;

/**
 * Tableau de bord RÉEL du socle : uniquement ce qui existe (utilisateurs, institutions, audit).
 * Les cartes des modules métier viendront des manifestes des modules.
 */
class RealDashboardData
{
    /**
     * @return array{cards: list<array<string, mixed>>, quick: list<array<string, mixed>>, tables: list<array<string, mixed>>, alerts: list<array<string, mixed>>}
     */
    public function for(User $user): array
    {
        $cards = [];
        $alerts = [];
        $tables = [];

        if ($user->isSuperadmin()) {
            $activeSuperadmins = User::role(RoleName::Superadmin->value)->active()->count();

            $cards[] = $this->card('Commissariats', (string) Commissariat::count(), Commissariat::active()->count().' actif(s)', 'bx bx-buildings', 'primary');
            $cards[] = $this->card('Mairies', (string) Mairie::count(), Mairie::active()->count().' active(s)', 'bx bx-building-house', 'warning', true);
            $cards[] = $this->card('Utilisateurs', (string) User::count(), User::active()->count().' actif(s)', 'bx bx-group', 'info');
            $cards[] = $this->card('Superadmins actifs', (string) $activeSuperadmins, null, 'bx bx-shield-quarter', 'success');
            $cards[] = $this->card('Comptes désactivés', (string) User::where('is_active', false)->count(), User::where('must_change_password', true)->count().' mot(s) de passe temporaire(s)', 'bx bx-lock-alt', 'danger');
            $cards[] = $this->card('Connexions aujourd\'hui', (string) ActivityLog::where('action', 'auth.login')->where('created_at', '>=', now()->startOfDay())->count(), null, 'bx bx-log-in-circle', 'dark');

            if ($activeSuperadmins < 2) {
                $alerts[] = ['class' => 'alert-warning', 'icon' => 'bx bx-error', 'title' => 'Un seul superadmin actif.', 'text' => 'Créez le second compte superadmin (un compte individuel par développeur, D10) : le dernier superadmin actif est protégé et ne peut pas être désactivé.'];
            }

            $tables[] = [
                'title' => 'DERNIÈRES ACTIONS', 'icon' => 'bx bx-list-check',
                'columns' => ['Date', 'Auteur', 'Action'],
                'rows' => ActivityLog::latest('id')->limit(8)->get()->map(fn (ActivityLog $log) => [
                    $log->created_at->format('d/m/Y H:i'), $log->user_name ?? '—', $log->description,
                ])->all(),
            ];
        } else {
            if ($user->can('users.view')) {
                $visible = User::visibleTo($user);
                $cards[] = $this->card('Utilisateurs actifs', (string) $visible->clone()->active()->count(), $visible->clone()->count().' au total', 'bx bx-group', 'primary');
            }
            if ($user->can('commissariats.view')) {
                $cards[] = $this->card('Commissariats', (string) Commissariat::count(), Commissariat::active()->count().' actif(s)', 'bx bx-buildings', 'warning', true);
            }
            if ($user->can('mairies.view')) {
                $cards[] = $this->card('Mairies', (string) Mairie::count(), Mairie::active()->count().' active(s)', 'bx bx-building-house', 'success');
            }
        }

        return ['cards' => $cards, 'quick' => $this->quick($user), 'tables' => $tables, 'alerts' => $alerts];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function quick(User $user): array
    {
        $candidates = [
            ['users.index', 'users.view', 'Utilisateurs', 'bx bx-user-circle'],
            ['commissariats.index', 'commissariats.view', 'Commissariats', 'bx bx-buildings'],
            ['mairies.index', 'mairies.view', 'Mairies', 'bx bx-building-house'],
            ['roles.index', 'roles.view', 'Rôles', 'bx bx-id-card'],
            ['permissions.index', 'permissions.view', 'Permissions', 'bx bx-shield-alt-2'],
            ['user-permissions.index', 'permissions.assign', 'Attribution', 'bx bx-user-check'],
            ['audit.index', 'audit.view', 'Audit', 'bx bx-list-check'],
        ];

        $quick = [];
        foreach ($candidates as [$route, $permission, $label, $icon]) {
            if ($user->can($permission)) {
                $quick[] = ['label' => $label, 'icon' => $icon, 'route' => $route, 'params' => []];
            }
        }

        return $quick;
    }

    /**
     * @return array<string, mixed>
     */
    private function card(string $label, string $value, ?string $sub, string $icon, string $color, bool $darkText = false): array
    {
        return ['label' => $label, 'value' => $value, 'sub' => $sub, 'icon' => $icon, 'color' => $color, 'module' => null, 'darkText' => $darkText];
    }
}
