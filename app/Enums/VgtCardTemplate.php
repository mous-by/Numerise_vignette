<?php

namespace App\Enums;

/**
 * Modèle visuel de la carte VGT imprimée au retrait (W13). PROPOSITION TECHNIQUE — À VALIDER AVEC LE CLIENT (le
 * cahier ne décrit aucun visuel, seulement les champs) : voir CLAUDE.md §5. `Officiel` reprend les couleurs et
 * une évocation des armoiries du Mali (demande explicite du développeur, 2026-09-29) — à valider avant mise en
 * production (risque de contrefaçon d'un symbole d'État, cahier DGI). Les autres sont des habillages VigiMoto
 * sans symbole national. Réglage : `mairies.card_template` (une mairie choisit son modèle, W13).
 */
enum VgtCardTemplate: string
{
    case Officiel = 'officiel';
    case Classique = 'classique';
    case Moderne = 'moderne';
    case Premium = 'premium';
    case Minimaliste = 'minimaliste';

    public function label(): string
    {
        return match ($this) {
            self::Officiel => 'Officiel (Mali)',
            self::Classique => 'Classique',
            self::Moderne => 'Moderne',
            self::Premium => 'Premium',
            self::Minimaliste => 'Minimaliste',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Officiel => 'Couleurs et sceau évoquant les armoiries du Mali.',
            self::Classique => 'Identité VigiMoto sobre, bleu et blanc.',
            self::Moderne => 'Dégradé bleu-orange, typographie large.',
            self::Premium => 'Fond sombre, liseré doré, style soigné.',
            self::Minimaliste => 'Blanc, fines lignes, sans couleur.',
        };
    }

    /** @return string vue Blade du fragment imprimable, resources/views/demandes-vgt/_card_templates/<value>.blade.php */
    public function view(): string
    {
        return 'demandes-vgt._card_templates.'.$this->value;
    }
}
