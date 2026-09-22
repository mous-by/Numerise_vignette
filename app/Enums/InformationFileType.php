<?php

namespace App\Enums;

/**
 * Type d'une pièce jointe d'information (W6). Pas d'enum SQL (CLAUDE.md §4.1) : colonne string, ce type PHP.
 */
enum InformationFileType: string
{
    case Image = 'image';
    case Document = 'document';
}
