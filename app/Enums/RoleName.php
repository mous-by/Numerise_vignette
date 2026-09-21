<?php

namespace App\Enums;

/**
 * Les six rôles du socle (ARCHITECTURE §6). Un utilisateur a exactement un rôle.
 * Niveaux, plafond de rôle et canaux vivent dans config/role_hierarchy.php et config/channels.php.
 */
enum RoleName: string
{
    case Superadmin = 'superadmin';
    case AdminNational = 'admin_national';
    case Commissaire = 'commissaire';
    case Mairie = 'mairie';
    case Police = 'police';
    case Population = 'population';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::AdminNational => 'Administrateur national',
            self::Commissaire => 'Commissaire',
            self::Mairie => 'Agent de mairie',
            self::Police => 'Police',
            self::Population => 'Population',
        };
    }

    public function level(): int
    {
        return (int) config("role_hierarchy.levels.{$this->value}", 0);
    }

    /**
     * Rôles que ce rôle peut créer et gérer (plafond de rôle).
     *
     * @return list<self>
     */
    public function manages(): array
    {
        return array_map(self::from(...), config("role_hierarchy.manages.{$this->value}", []));
    }

    public function canManage(self $target): bool
    {
        return in_array($target, $this->manages(), true);
    }

    /**
     * Type d'institution imposé à ce rôle : 'commissariat', 'mairie' ou null (aucune).
     */
    public function institution(): ?string
    {
        return config("role_hierarchy.institution.{$this->value}");
    }

    /**
     * Canal d'accès autorisé : 'web', 'api' ou null (aucun).
     */
    public function channel(): ?string
    {
        foreach (config('channels', []) as $channel => $roles) {
            if (in_array($this->value, $roles, true)) {
                return $channel;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
