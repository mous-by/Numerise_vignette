<?php

namespace App\Services\Dashboard;

use App\Enums\RoleName;
use App\Models\User;

/**
 * Construit les données du tableau de bord d'un utilisateur : réelles (socle) ou fictives (D25). Les deux voies
 * produisent la même structure, rendue par les mêmes vues.
 */
class DashboardService
{
    public function __construct(private readonly RealDashboardData $real, private readonly MockDashboardData $mock) {}

    /** Jamais de données fictives en production. */
    public function usesMock(): bool
    {
        return config('dashboard.mock') && ! app()->isProduction();
    }

    /**
     * @return array{mock: bool, cards: list<array<string, mixed>>, quick: list<array<string, mixed>>, tables: list<array<string, mixed>>, alerts: list<array<string, mixed>>, hero: array<string, mixed>, tasks: list<array<string, mixed>>, pipeline: array<string, mixed>|null}
     */
    public function for(User $user): array
    {
        $real = $this->real->for($user);

        if (! $this->usesMock()) {
            return ['mock' => false] + $real;
        }

        $mock = $this->mock->for($user->roleName() ?? RoleName::Population);

        // Les alertes du socle restent réelles (ex. un seul superadmin actif) ; tout le reste est fictif.
        return ['mock' => true, 'cards' => $mock['cards'], 'quick' => $mock['quick'], 'tables' => $mock['tables'], 'alerts' => $real['alerts'], 'hero' => $real['hero'], 'tasks' => [], 'pipeline' => null];
    }
}
