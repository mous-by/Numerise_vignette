# Numerise_vignette — instructions pour l'assistant IA

Plateforme malienne de numérisation des vignettes de motos (VGT) et de contrôle des motos.
Source officielle des exigences : `docs/cahier_Plateforme.pdf`. Deux développeurs, Moustapha BARRY et Amadou KAREMBE, chacun avec son assistant IA. Langue de travail : français.

## Où lire quoi (avant toute action)

1. `docs/DECISIONS.md` — décisions validées / retenues + points encore ouverts. **Lire en premier.**
2. `docs/ARCHITECTURE.md` — architecture finale du SOCLE COMMUN (tables, rôles, permissions, audit, routes, Sanctum, arborescence, template).
3. `docs/cahier_Plateforme.pdf` — cahier des charges du client.

## Périmètre actuel : le SOCLE COMMUN uniquement

Authentification, rôles et permissions, institutions (commissariats, mairies), utilisateurs, journal d'audit `activity_logs`, API/Sanctum, template. **Ne pas commencer les modules métier** (propriétaires, motos, déclarations, VGT, paiements, contrôles, motos retrouvées) : ils viennent après le socle, un module = un manifeste `config/modules/<module>.php` sans modifier les fichiers du socle.

## Règles de travail

- Le cahier des charges est la source principale. Ce qui n'y est pas est « À VALIDER AVEC LE CLIENT ». Toute amélioration non prévue est « PROPOSITION TECHNIQUE — À VALIDER » et n'est jamais une exigence client.
- Ne créer ni table, ni fonctionnalité « parce qu'elle semble logique » : vérifier d'abord cahier, décisions, modèle de données, contrat API.
- Ordre du projet : architecture → MCD → dictionnaire de données → migrations → modèles/relations → auth et rôles → API REST → interface Web → contrat API React Native → tests/intégration.
- **ORGEST** (`/opt/lampp/htdocs/ORGEST`) est une référence en LECTURE SEULE : ne jamais le modifier, ne pas reprendre son métier ni ses données ni ses identifiants de test. À chaque emprunt, citer le fichier de référence (chemin relatif à ORGEST + ligne), le fichier cible ici, et le statut (copié / adapté / nouveau).
- **Licence du template** : ORGEST utilise « Dashkote Admin » (codervent, commercial). Ne copier AUCUN de ses assets tant que la licence n'est pas confirmée pour ce projet (voir DECISIONS.md).
- Aucun mot de passe, secret ni compte de test dans Git. Aucun CDN ni ressource externe : assets locaux.
- Toute action sensible est journalisée dans `activity_logs` (le superadmin aussi). Tout modèle métier utilise `LogsActivity`.
- Pas de permission créée depuis l'interface : les permissions vivent dans les manifestes et se synchronisent avec `php artisan permissions:sync`.

## État du dépôt

Étape 1 du socle faite (2026-09-21) : squelette Laravel 13 fusionné, Spatie Permission et Sanctum installés et configurés (Bearer uniquement), français / `Africa/Bamako`, dépôt Git indépendant sur `main`, pipeline frontend Vite/Tailwind/Bunny/npm supprimé (D19). Prochaine étape : migrations (étape 2 de « Ordre de construction du socle », `docs/ARCHITECTURE.md`), **à ne coder qu'après validation de la conception par l'utilisateur (D21)**. Interface : reproduire ORGEST (D20), sans copier d'asset sous licence non confirmée.

**Ne pas lancer `php artisan migrate` avant l'étape 2** : les migrations livrées avec le squelette (`users` avec e-mail, `password_reset_tokens`) contredisent D5 et seront remplacées.

Le squelette Laravel contient son propre `CLAUDE.md`/`AGENTS.md` (amorçage Laravel Boost) : ne pas les reprendre.

Environnement local : PHP 8.3, Composer 2.8, Node 20, MariaDB 10.4 (XAMPP, socket `/opt/lampp/var/mysql/mysql.sock`). Base de développement : `db_numerise_vignette`. Base des tests : `db_numerise_vignette_test` (`phpunit.xml` force `DB_DATABASE`, les tests ne touchent jamais la base de développement).
