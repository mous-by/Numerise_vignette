<?php

namespace App\Services\Dashboard;

use App\Enums\DemandeVgtStatus;
use App\Enums\RoleName;
use App\Models\ActivityLog;
use App\Models\Commissariat;
use App\Models\Declaration;
use App\Models\DemandeVgt;
use App\Models\Mairie;
use App\Models\Moto;
use App\Models\MotoRetrouvee;
use App\Models\Proprietaire;
use App\Models\User;

/**
 * Tableau de bord RÉEL du socle : uniquement ce qui existe (utilisateurs, institutions, audit).
 * Les cartes des modules métier viendront des manifestes des modules.
 */
class RealDashboardData
{
    /**
     * @return array{cards: list<array<string, mixed>>, quick: list<array<string, mixed>>, tables: list<array<string, mixed>>, alerts: list<array<string, mixed>>, hero: array<string, mixed>, tasks: list<array<string, mixed>>, pipeline: array<string, mixed>|null}
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

        [$tasks, $pipeline] = $this->business($user, $cards, $tables);

        return ['cards' => $cards, 'quick' => $this->quick($user), 'tables' => $tables, 'alerts' => $alerts, 'hero' => $this->hero($user), 'tasks' => $tasks, 'pipeline' => $pipeline];
    }

    /**
     * Nombre de demandes VGT qui attendent une action de cet utilisateur (pastille du menu, comme les tâches
     * du tableau de bord).
     */
    public function pendingDemandesCount(User $user): int
    {
        if (! $user->can('demandes-vgt.view')) {
            return 0;
        }

        $counts = DemandeVgt::visibleTo($user)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $pending = 0;

        foreach ([
            'demandes-vgt.validate' => DemandeVgtStatus::EnAttente,
            'demandes-vgt.confirm_payment' => DemandeVgtStatus::Validee,
            'demandes-vgt.confirm_retrait' => DemandeVgtStatus::Payee,
            'demandes-vgt.update' => DemandeVgtStatus::Rejetee,
        ] as $permission => $status) {
            if ($user->can($permission)) {
                $pending += (int) ($counts[$status->value] ?? 0);
            }
        }

        return $pending;
    }

    /**
     * Accueil personnalisé : salutation, rôle, institution et date.
     *
     * @return array<string, mixed>
     */
    private function hero(User $user): array
    {
        $institution = $user->commissariat?->name ?? $user->mairie?->name;
        $now = now()->locale('fr');

        return [
            'greeting' => ($now->hour >= 18 ? 'Bonsoir' : 'Bonjour').', '.$user->name,
            'subtitle' => trim(($user->roleName()?->label() ?? '').($institution ? ' · '.$institution : '')),
            'date' => ucfirst($now->translatedFormat('l j F Y')),
        ];
    }

    /**
     * Données réelles des modules métier déjà construits (propriétaires, motos, déclarations, motos retrouvées,
     * demandes VGT), limitées à ce que l'utilisateur peut voir (cloisonnement, §4.7) : chiffres, tâches à traiter
     * et entonnoir des demandes.
     *
     * @param  list<array<string, mixed>>  $cards
     * @param  list<array<string, mixed>>  $tables
     * @return array{0: list<array<string, mixed>>, 1: array<string, mixed>|null}
     */
    private function business(User $user, array &$cards, array &$tables): array
    {
        $tasks = [];
        $pipeline = null;

        if ($user->can('proprietaires.view')) {
            $cards[] = $this->card('Propriétaires', (string) Proprietaire::count(), Proprietaire::where('created_at', '>=', now()->startOfWeek())->count().' cette semaine', 'bx bx-user-pin', 'primary');
        }
        if ($user->can('motos.view')) {
            $cards[] = $this->card('Motos enregistrées', (string) Moto::count(), Moto::where('is_stolen', true)->count().' déclarée(s) volée(s)', 'bx bx-cycling', 'info');
        }
        if ($user->can('declarations.view')) {
            $cards[] = $this->card('Déclarations', (string) Declaration::count(), Declaration::where('created_at', '>=', now()->startOfWeek())->count().' cette semaine', 'bx bx-error-circle', 'danger');
        }
        if ($user->can('motos-retrouvees.view')) {
            $cards[] = $this->card('Motos retrouvées', (string) MotoRetrouvee::count(), null, 'bx bx-search-alt', 'success');
        }

        if (! $user->can('demandes-vgt.view')) {
            return [$tasks, $pipeline];
        }

        $counts = DemandeVgt::visibleTo($user)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $counts->sum();
        $count = fn (DemandeVgtStatus $status): int => (int) ($counts[$status->value] ?? 0);
        $link = fn (DemandeVgtStatus $status): array => ['route' => 'demandes-vgt.index', 'params' => ['statut' => $status->value]];

        if ($user->can('demandes-vgt.validate')) {
            $tasks[] = ['label' => 'Demandes à valider', 'hint' => 'Formulaires VGT en attente', 'count' => $count(DemandeVgtStatus::EnAttente), 'icon' => 'bx bx-check-shield', 'color' => 'warning'] + $link(DemandeVgtStatus::EnAttente);
        }
        if ($user->can('demandes-vgt.confirm_payment')) {
            $tasks[] = ['label' => 'Paiements à confirmer', 'hint' => 'Demandes validées', 'count' => $count(DemandeVgtStatus::Validee), 'icon' => 'bx bx-money', 'color' => 'primary'] + $link(DemandeVgtStatus::Validee);
        }
        if ($user->can('demandes-vgt.confirm_retrait')) {
            $tasks[] = ['label' => 'Cartes à remettre', 'hint' => 'Payées, prêtes au retrait', 'count' => $count(DemandeVgtStatus::Payee), 'icon' => 'bx bx-id-card', 'color' => 'success'] + $link(DemandeVgtStatus::Payee);
        }
        if ($user->can('demandes-vgt.update')) {
            $tasks[] = ['label' => 'Demandes rejetées', 'hint' => 'À corriger et resoumettre', 'count' => $count(DemandeVgtStatus::Rejetee), 'icon' => 'bx bx-revision', 'color' => 'danger'] + $link(DemandeVgtStatus::Rejetee);
        }

        $cards[] = $this->card('Demandes VGT', (string) $total, DemandeVgt::visibleTo($user)->where('created_at', '>=', now()->startOfWeek())->count().' cette semaine', 'bx bx-file', 'warning', true);

        if ($user->can('demandes-vgt.confirm_payment') || $user->can('demandes-vgt.manage_tarifs')) {
            $collected = DemandeVgt::visibleTo($user)->whereIn('status', [DemandeVgtStatus::Payee->value, DemandeVgtStatus::Retiree->value])
                ->where('payment_confirmed_at', '>=', now()->startOfMonth())->selectRaw('coalesce(sum(base_amount + surcharge_amount), 0) as amount')->value('amount');
            $cards[] = $this->card('Encaissé ce mois', number_format((int) $collected, 0, ',', ' ').' FCFA', 'Paiements confirmés', 'bx bx-wallet', 'dark');
        }

        $pipeline = [
            'total' => $total,
            'segments' => collect(DemandeVgtStatus::cases())->map(fn (DemandeVgtStatus $status) => [
                'label' => $status->label(), 'count' => $count($status), 'class' => $status->value,
                'percent' => $total > 0 ? round($count($status) / $total * 100, 1) : 0,
            ] + $link($status))->all(),
        ];

        $tables[] = [
            'title' => 'DERNIÈRES DEMANDES VGT', 'icon' => 'bx bx-file',
            'columns' => ['Date', 'Moto', 'Année', 'Statut'],
            'rows' => DemandeVgt::visibleTo($user)->with('moto')->latest('id')->limit(8)->get()->map(fn (DemandeVgt $demande) => [
                $demande->created_at->format('d/m/Y'), $demande->moto?->plate_number ?? '—', (string) $demande->vgt_year,
                ['badge' => $demande->status->label(), 'class' => $demande->status->badgeClass()],
            ])->all(),
        ];

        return [$tasks, $pipeline];
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
