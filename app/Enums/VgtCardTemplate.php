<?php

namespace App\Enums;

/**
 * Modèle visuel de la carte VGT imprimée au retrait (W13). Chaque modèle reproduit une vraie vignette moto du
 * District de Bamako (photos fournies par le développeur, 2017 à 2026) sur une carte CR80 ; le verso ne porte que
 * le nom et le numéro du détenteur. PROPOSITION TECHNIQUE — À VALIDER AVEC LE CLIENT (le cahier ne décrit aucun
 * visuel) : voir CLAUDE.md §5. Les armoiries et le monument sont des dessins originaux, pas les emblèmes officiels.
 * Réglage : `mairies.card_template` (présélection de la mairie) ; le choix se refait à chaque impression.
 */
enum VgtCardTemplate: string
{
    case Officiel = 'officiel';
    case Jaune = 'jaune';
    case Rose = 'rose';
    case Classique = 'classique';
    case Ancien = 'ancien';

    public function label(): string
    {
        return match ($this) {
            self::Officiel => 'Officiel (2026)',
            self::Jaune => 'Jaune (2025)',
            self::Rose => 'Rose (2023)',
            self::Classique => 'Classique (2018)',
            self::Ancien => 'Ancien (2017)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Officiel => 'Blanc et gris, pastille jaune, QR Code et monument.',
            self::Jaune => 'Jaune, QR Code, monument rose et sceaux.',
            self::Rose => 'Rose aux guillochis bleus, titre doré, sceau bleu.',
            self::Classique => 'Blanc, VIGNETTE en rouge, hologramme et signature.',
            self::Ancien => 'Lignes ondulées bleues, VIGNETTE espacé bordeaux.',
        };
    }

    /** @return string vue Blade du fragment imprimable, resources/views/demandes-vgt/_card_templates/<value>.blade.php */
    public function view(): string
    {
        return 'demandes-vgt._card_templates.'.$this->value;
    }
}
