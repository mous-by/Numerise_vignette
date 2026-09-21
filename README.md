# Numerise_vignette

Plateforme malienne de numérisation des vignettes de motos (VGT) et de contrôle des motos.
Web (commissariats, mairies) + API mobile (police, population).

Source des exigences : `docs/cahier_Plateforme.pdf`.
Décisions et architecture : `docs/DECISIONS.md` (à lire en premier), `docs/ARCHITECTURE.md`.

## État

Périmètre actuel : le **socle commun** (authentification, rôles et permissions, institutions, utilisateurs, audit, API/Sanctum, template). Aucun module métier n'est commencé.

## Prérequis

PHP >= 8.3, Composer 2, MariaDB 10.4+ (ou MySQL). Base de développement : `db_numerise_vignette`. Base des tests : `db_numerise_vignette_test` (jamais la base de développement).

## Installation locale

```sh
composer install
cp .env.example .env        # puis renseigner DB_USERNAME / DB_PASSWORD localement
php artisan key:generate
```

Créer les deux bases vides (utf8mb4, `utf8mb4_unicode_ci`) : `db_numerise_vignette` et `db_numerise_vignette_test`.

Ne pas lancer `php artisan migrate` avant l'étape 2 du socle (migrations) : les migrations livrées avec le squelette Laravel seront remplacées.

## Tests

```sh
composer test
```

## Règles

Aucun mot de passe, secret ni compte de test dans Git. Aucun CDN ni ressource externe : assets locaux.
