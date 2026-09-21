<?php

namespace App\Services\Dashboard;

use App\Enums\RoleName;

/**
 * Données FICTIVES du tableau de bord (D25) : maquette pour visualiser l'écran final avant les modules métier.
 * Rien ici ne vient de la base. Les noms, matricules et montants sont inventés.
 */
class MockDashboardData
{
    /**
     * @return array{cards: list<array<string, mixed>>, quick: list<array<string, mixed>>, tables: list<array<string, mixed>>}
     */
    public function for(RoleName $role): array
    {
        return match ($role) {
            RoleName::Superadmin, RoleName::AdminNational => $this->national($role),
            RoleName::Commissaire => $this->commissaire(),
            RoleName::Mairie => $this->mairie(),
            default => ['cards' => [], 'quick' => [], 'tables' => []],
        };
    }

    private function national(RoleName $role): array
    {
        $tables = [
            [
                'title' => 'COMMISSARIATS', 'icon' => 'bx bx-buildings',
                'columns' => ['Commissariat', 'Utilisateurs', 'Propriétaires', 'Motos', 'Demandes en cours', 'Statut'],
                'rows' => [
                    ['Commissariat du 1er Arrondissement', '14', '312', '458', '9', $this->status(true)],
                    ['Commissariat du 3e Arrondissement', '11', '268', '401', '7', $this->status(true)],
                    ['Commissariat de Kalaban Coura', '9', '187', '279', '12', $this->status(true)],
                    ['Commissariat de Badalabougou', '10', '203', '310', '5', $this->status(true)],
                    ['Commissariat de Sébénikoro', '7', '141', '203', '14', $this->status(true)],
                    ['Commissariat de Sotuba', '6', '0', '0', '0', $this->status(false)],
                ],
            ],
            [
                'title' => 'MAIRIES', 'icon' => 'bx bx-building-house',
                'columns' => ['Mairie', 'Agents', 'Cartes à remettre', 'Remises (mois)', 'Statut'],
                'rows' => [
                    ['Mairie de la Commune I', '4', '11', '64', $this->status(true)],
                    ['Mairie de la Commune III', '3', '7', '52', $this->status(true)],
                    ['Mairie de la Commune IV', '4', '15', '71', $this->status(true)],
                    ['Mairie de la Commune V', '3', '6', '38', $this->status(true)],
                ],
            ],
        ];

        if ($role === RoleName::Superadmin) {
            $tables[] = [
                'title' => 'DERNIÈRES ACTIONS', 'icon' => 'bx bx-list-check',
                'columns' => ['Date', 'Auteur', 'Action'],
                'rows' => [
                    ['21/09/2026 09:14', 'Moustapha BARRY', 'Connexion'],
                    ['21/09/2026 09:02', 'Amadou KAREMBE', 'Permissions modifiées — Commissaire Diarra'],
                    ['20/09/2026 17:40', 'Adama Coulibaly', 'Création — Utilisateur « Agent Traoré »'],
                    ['20/09/2026 16:05', 'Fanta Sidibé', 'Mot de passe réinitialisé — Police 07'],
                    ['20/09/2026 11:27', 'Moustapha BARRY', 'Création — Commissariat « Sotuba »'],
                ],
            ];
        }

        return [
            'cards' => [
                $this->card('Commissariats', '12', '11 actifs', 'bx bx-buildings', 'primary'),
                $this->card('Mairies', '8', '8 actives', 'bx bx-building-house', 'warning', module: null, darkText: true),
                $this->card('Utilisateurs', '146', '139 actifs', 'bx bx-group', 'info'),
                $this->card('Propriétaires', '1 248', '+32 ce mois', 'bx bx-user-pin', 'success', 'proprietaires'),
                $this->card('Motos enregistrées', '1 902', '+58 ce mois', 'bx bx-cycling', 'danger', 'motos'),
                $this->card('Motos volées', '37', '5 retrouvées ce mois', 'bx bx-error-alt', 'dark', 'declarations'),
                $this->card('Demandes VGT en attente', '64', 'à traiter par les mairies', 'bx bx-file', 'primary', 'demandes-vgt'),
                $this->card('VGT délivrées (année)', '1 461', '+118 ce mois', 'bx bx-id-card', 'success', 'retraits-vgt'),
                $this->card('Paiements du jour', '2 350 000 FCFA', '47 paiements confirmés', 'bx bx-money', 'info', 'paiements'),
            ],
            'quick' => $this->quick(['users.index', 'commissariats.index', 'mairies.index', 'roles.index', 'permissions.index', 'audit.index']),
            'tables' => $tables,
        ];
    }

    private function commissaire(): array
    {
        return [
            'cards' => [
                $this->card('Propriétaires', '214', '+6 ce mois', 'bx bx-user-pin', 'primary', 'proprietaires'),
                $this->card('Motos enregistrées', '331', '+9 ce mois', 'bx bx-cycling', 'warning', 'motos', darkText: true),
                $this->card('Déclarations de vol ouvertes', '6', '2 cette semaine', 'bx bx-error-alt', 'danger', 'declarations'),
                $this->card('Motos retrouvées (mois)', '3', '1 en attente de récupération', 'bx bx-search-alt', 'success', 'motos-retrouvees'),
                $this->card('Demandes VGT en cours', '18', '4 en attente de paiement', 'bx bx-file', 'info', 'demandes-vgt'),
                $this->card('Retraits à venir', '9', 'cette semaine', 'bx bx-id-card', 'dark', 'retraits-vgt'),
            ],
            'quick' => $this->quick(['users.index'], planned: ['proprietaires', 'motos', 'declarations', 'demandes-vgt']),
            'tables' => [
                [
                    'title' => 'DERNIÈRES DÉCLARATIONS', 'icon' => 'bx bx-error-alt',
                    'columns' => ['Date', 'Matricule', 'Propriétaire', 'Lieu', 'Statut'],
                    'rows' => [
                        ['20/09/2026', 'A 4521 BK', 'Oumar Keïta', 'Marché de Médina Coura', $this->badge('Volée', 'bg-danger')],
                        ['18/09/2026', 'B 1180 DM', 'Mariam Traoré', 'Pont des Martyrs', $this->badge('Retrouvée', 'bg-success')],
                        ['17/09/2026', 'A 7734 KY', 'Seydou Diallo', 'Sogoniko', $this->badge('Volée', 'bg-danger')],
                        ['15/09/2026', 'C 2209 BM', 'Aïssata Coulibaly', 'Lafiabougou', $this->badge('Volée', 'bg-danger')],
                        ['12/09/2026', 'A 3097 BK', 'Bakary Sangaré', 'Kalaban Coura', $this->badge('Retrouvée', 'bg-success')],
                    ],
                ],
                [
                    'title' => 'DERNIÈRES DEMANDES VGT', 'icon' => 'bx bx-file',
                    'columns' => ['Date', 'Matricule', 'Année', 'Mairie de retrait', 'Statut'],
                    'rows' => [
                        ['21/09/2026', 'A 8812 BK', '2026', 'Commune III', $this->badge('Payée', 'bg-info')],
                        ['21/09/2026', 'B 6640 DM', '2026', 'Commune I', $this->badge('En attente', 'bg-secondary')],
                        ['19/09/2026', 'A 2275 KY', '2026', 'Commune IV', $this->badge('Prête au retrait', 'bg-success')],
                        ['18/09/2026', 'C 9013 BM', '2026', 'Commune V', $this->badge('Payée', 'bg-info')],
                        ['16/09/2026', 'A 1456 BK', '2026', 'Commune III', $this->badge('Retirée', 'bg-dark')],
                    ],
                ],
            ],
        ];
    }

    private function mairie(): array
    {
        return [
            'cards' => [
                $this->card('Demandes VGT reçues', '41', '+7 aujourd\'hui', 'bx bx-file', 'primary', 'demandes-vgt'),
                $this->card('Formulaires à valider', '12', 'à traiter', 'bx bx-edit', 'warning', 'demandes-vgt', darkText: true),
                $this->card('Cartes à remettre', '9', 'prêtes au retrait', 'bx bx-id-card', 'success', 'retraits-vgt'),
                $this->card('Cartes remises (mois)', '76', '+12 cette semaine', 'bx bx-check-circle', 'info', 'retraits-vgt'),
                $this->card('Taxes en attente', '5', 'VGT non à jour', 'bx bx-money', 'danger', 'paiements'),
                $this->card('Paiements confirmés', '38', 'ce mois', 'bx bx-wallet', 'dark', 'paiements'),
            ],
            'quick' => $this->quick([], planned: ['demandes-vgt', 'retraits-vgt', 'informations']),
            'tables' => [
                [
                    'title' => 'DEMANDES À TRAITER', 'icon' => 'bx bx-file',
                    'columns' => ['Date', 'Matricule', 'Propriétaire', 'Commissariat', 'Statut'],
                    'rows' => [
                        ['21/09/2026', 'A 8812 BK', 'Ibrahima Konaté', '1er Arrondissement', $this->badge('À valider', 'bg-warning text-dark')],
                        ['21/09/2026', 'B 6640 DM', 'Kadiatou Dembélé', 'Kalaban Coura', $this->badge('À valider', 'bg-warning text-dark')],
                        ['20/09/2026', 'C 4471 KY', 'Moussa Camara', 'Badalabougou', $this->badge('Rejetée', 'bg-danger')],
                        ['19/09/2026', 'A 5903 BK', 'Nana Diarra', '3e Arrondissement', $this->badge('Validée', 'bg-success')],
                    ],
                ],
                [
                    'title' => 'RETRAITS DU JOUR', 'icon' => 'bx bx-id-card',
                    'columns' => ['Heure', 'Matricule', 'Propriétaire', 'Statut'],
                    'rows' => [
                        ['09:30', 'A 2275 KY', 'Sekou Doumbia', $this->badge('Remise', 'bg-success')],
                        ['10:15', 'A 1456 BK', 'Fatoumata Bagayoko', $this->badge('Remise', 'bg-success')],
                        ['11:40', 'B 3388 DM', 'Yacouba Sissoko', $this->badge('Attendue', 'bg-secondary')],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function card(string $label, string $value, ?string $sub, string $icon, string $color, ?string $module = null, bool $darkText = false): array
    {
        return compact('label', 'value', 'sub', 'icon', 'color', 'module', 'darkText');
    }

    /**
     * @return array{badge: string, class: string}
     */
    private function badge(string $text, string $class): array
    {
        return ['badge' => $text, 'class' => $class];
    }

    private function status(bool $active): array
    {
        return $active ? $this->badge('Actif', 'bg-success') : $this->badge('Désactivé', 'bg-secondary');
    }

    /**
     * @param  list<string>  $routes
     * @param  list<string>  $planned
     * @return list<array<string, mixed>>
     */
    private function quick(array $routes, array $planned = []): array
    {
        $labels = [
            'users.index' => ['Utilisateurs', 'bx bx-user-circle'],
            'commissariats.index' => ['Commissariats', 'bx bx-buildings'],
            'mairies.index' => ['Mairies', 'bx bx-building-house'],
            'roles.index' => ['Rôles', 'bx bx-id-card'],
            'permissions.index' => ['Permissions', 'bx bx-shield-alt-2'],
            'audit.index' => ['Audit', 'bx bx-list-check'],
        ];

        $quick = [];
        foreach ($routes as $route) {
            $quick[] = ['label' => $labels[$route][0], 'icon' => $labels[$route][1], 'route' => $route, 'params' => []];
        }
        foreach ($planned as $key) {
            $quick[] = ['label' => config("planned_modules.{$key}.label"), 'icon' => config("planned_modules.{$key}.icon"), 'route' => 'modules.show', 'params' => [$key], 'planned' => true];
        }

        return $quick;
    }
}
