<?php

/*
|--------------------------------------------------------------------------
| Identité de l'application
|--------------------------------------------------------------------------
|
| Le nom est affiché en logo 3D bicolore dans la sidebar et sur la page de connexion :
| `logo.first` en bleu, `logo.second` en orange. Changer de nom = modifier ce fichier et APP_NAME dans .env.
| (Le dépôt et le dossier restent « Numerise_vignette » ; VigiMoto est le nom de la plateforme.)
|
*/

return [

    'name' => env('APP_NAME', 'VigiMoto'),

    'logo' => ['first' => 'VIGI', 'second' => 'MOTO'],

    // Mini logo affiché quand la sidebar est repliée.
    'mini' => 'VM',

    'tagline' => 'VIGNETTES & CONTRÔLE DES MOTOS',

    /*
    | Diaporama de la page de connexion (formulaire à droite, images qui défilent à gauche).
    | `file` : nom de l'image SANS extension, dans public/assets/images/login/ (jpg, jpeg, webp ou png, dans cet ordre).
    | `position` : cadrage de l'image (background-position CSS : « 30% 40% », « left center »…), car l'écran est en 16:9
    | et l'image ne l'est pas toujours. Une image absente n'est pas une erreur : dégradé bleu.
    | Les images se génèrent avec les prompts de CLAUDE.md (§7) : 1920 × 1080, ≤ 400 Ko.
    */
    // Dossier des images du diaporama, relatif à public/ (surchargé par les tests).
    'login_images_directory' => 'assets/images/login',

    'login_slides' => [
        ['file' => 'slide-1', 'position' => '35% center', 'icon' => 'bx bx-shield-quarter', 'badge' => 'Contrôle', 'title' => 'Un contrôle en quelques secondes', 'text' => 'En patrouille, la police vérifie sur son téléphone si une moto est volée ou en règle.'],
        ['file' => 'slide-2', 'position' => '25% center', 'icon' => 'bx bx-buildings', 'badge' => 'Commissariat', 'title' => 'Un seul enregistrement', 'text' => 'Le propriétaire et sa moto sont enregistrés au commissariat, puis la VGT se renouvelle en ligne.'],
        ['file' => 'slide-3', 'position' => '30% center', 'icon' => 'bx bx-building-house', 'badge' => 'Mairie', 'title' => 'La carte VGT sans attente', 'text' => 'La mairie valide la demande, reçoit la preuve de paiement et remet la carte physique.'],
        ['file' => 'slide-4', 'position' => 'center center', 'icon' => 'bx bx-cycling', 'badge' => 'Sécurité routière', 'title' => 'Des routes plus sûres', 'text' => 'Une base nationale, accessible 24 h sur 24 par les autorités compétentes.'],
        ['file' => 'slide-5', 'position' => '40% center', 'icon' => 'bx bx-search-alt', 'badge' => 'Motos retrouvées', 'title' => 'Retrouver sa moto', 'text' => 'Dès qu\'une moto volée est retrouvée, son propriétaire est prévenu par SMS.'],
    ],

];
