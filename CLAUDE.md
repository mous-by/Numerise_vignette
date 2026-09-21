# VigiMoto — guide du projet (dépôt `Numerise_vignette`)

Ce fichier est la **seule documentation** du dépôt. Il se lit en entier avant de commencer, par le développeur comme par son assistant IA (Claude Code le charge automatiquement). Tout autre assistant (Codex, Cursor, Copilot…) doit l'ouvrir et le lire en premier : il ne le charge pas tout seul. Langue de travail : français. Source officielle des exigences : `docs/cahier_Plateforme.pdf` (cahier des charges du client).

## 0. Règles pour l'assistant IA

1. **Ne travaille que sur les tâches assignées à ton développeur** (§10, colonne « Qui »). Ne commence ni une autre tâche, ni un module métier qui ne t'est pas assigné.
2. Le **socle** (authentification, rôles et permissions, audit, API, thème) ne se modifie qu'après revue par les deux développeurs. Un module s'ajoute **sans** modifier les fichiers du socle (§6).
3. Le cahier des charges est la source principale. Ce qui n'y est pas est « À VALIDER AVEC LE CLIENT » ; toute idée non prévue est « PROPOSITION TECHNIQUE — À VALIDER » et n'est jamais une exigence.
4. Ne crée ni table ni fonctionnalité « parce qu'elle semble logique » : vérifie d'abord le cahier, les décisions (§5) et le modèle de données (§4).
5. Aucun mot de passe, secret, numéro de téléphone réel ni compte de test dans Git. Aucune ressource externe (CDN) : assets locaux.
6. Toute action sensible est journalisée dans `activity_logs`. Tout modèle métier utilise `LogsActivity`. Les permissions ne se créent pas « à la main » dans la base (§4.4).
7. Avant de conclure une tâche : `composer test` vert, `vendor/bin/pint` propre, et la tâche vérifiée dans le navigateur ou sur le mobile.
8. Un fichier `.md` de plus n'est pas souhaité : mets à jour **ce fichier**.

### 0.1 Début de session (assistant IA)

1. **Sache pour qui tu travailles.** Lis `git config user.name` (ou demande au développeur) : **Moustapha BARRY = mobile** (`mobile/`, tâches M1, M2, M4, M5) **et les écrans Web Audit et Système** (W3, W4) ; **Amadou KAREMBE = Web** (code Laravel, tâches W1, W2 et W5 à W14, API des modules comprise). Tu ne modifies que les fichiers de tes tâches : `mobile/` est à Moustapha, le code Laravel est à Amadou, sauf les écrans Audit et Système (W3, W4) qui sont à Moustapha. Les points de jonction sont le contrat d'API (§8, discuté avant d'être changé) et les routes publiques de la population (D32), qui touchent au socle et se relisent à deux. Le socle et ce fichier ne changent qu'avec l'accord des deux.
2. **Lis ce fichier en entier** (§4 architecture, §5 décisions, §6 ajout d'un module, §10 tâches), puis `docs/cahier_Plateforme.pdf` pour le module que tu construis. Le cahier prime sur toute idée ; ce qui n'y est pas est « À VALIDER AVEC LE CLIENT ».
3. **Vérifie l'environnement avant de coder** (§3) : `composer install`, `.env`, les deux bases, `php artisan migrate --seed`, puis `composer test` **vert avant ta première modification** (côté mobile : `npm run typecheck` dans `mobile/`).
4. **Imite l'existant, ne réinvente pas.** Fichiers de référence :

| Besoin | Fichier de référence |
|---|---|
| Manifeste de module (permissions, défauts par rôle, menu) | `config/modules/commissariats.php`, `config/modules/users.php` |
| Écran Web (DataTables, modales, alertes) | `app/Http/Controllers/Web/Admin/PermissionController.php`, `resources/views/permissions/index.blade.php`, `routes/web/permissions.php` |
| Logique partagée Web et API | `app/Services/Users/UserProvisioningService.php`, `app/Services/Permissions/PermissionManager.php` |
| FormRequest (messages en français) | `app/Http/Requests/Web/UpdateProfileRequest.php` |
| Modèle audité et cloisonné | `app/Models/Commissariat.php`, `app/Models/Concerns/{LogsActivity,BelongsToCommissariat,BelongsToMairie}.php` |
| API (contrôleur, Resource, route) | `app/Http/Controllers/Api/V1/Auth/AuthController.php`, `app/Http/Resources/UserResource.php`, `routes/api/v1/auth.php` |
| Tests (jeu d'essai, rôles, institutions) | `tests/Concerns/BuildsFoundation.php`, `tests/Feature/Permissions/PermissionScreensTest.php`, `tests/Feature/Api/AuthApiTest.php`, `database/factories/` |
| Policy | aucune pour l'instant : `CommissariatPolicy` (W1) devient le modèle |
| Écran mobile | `mobile/app/(tabs)/index.tsx`, `mobile/lib/api.ts`, `mobile/context/AuthContext.tsx` |

5. **Pièges connus** :
   - `composer test` tourne sur `db_numerise_vignette_test`, jamais sur la base de développement ; pas de `migrate:fresh` sur celle-ci sans l'annoncer.
   - MariaDB 10.4 (XAMPP) : pas de requête JSON, pas d'`enum` SQL, `created_at` en DATETIME (§4.1).
   - `User::roleName()` et non `role()` ; un seul rôle par utilisateur. Les numéros passent par `App\Support\PhoneNumber::normalize()` (E.164), jamais comparés tels que saisis.
   - Pas d'`update()` de masse sans audit ; pas de permission créée à la main en base (manifeste + `php artisan permissions:sync`).
   - Rien de secret dans Git : `.env`, mots de passe, numéros réels, jetons. Aucun CDN, aucun Vite ni Tailwind (D19).
   - Ne pas modifier `config/planned_modules.php` : l'entrée d'un module disparaît quand son manifeste existe. Un module ne modifie pas les fichiers du socle (§6).
   - Vues : `@extends('layouts.admin')`, composants existants (`card-header-brand`, survol unifié §7), pas de CSS ad hoc. Textes visibles en français ; tables et colonnes en anglais (D3).
   - Un serveur déjà lancé sur le port 8000 est peut-être celui du développeur : ne le tue pas, lance `php artisan serve --port=8001`.
6. **Git** : dépôt `git@github.com:mous-by/Numerise_vignette.git` (`origin`). Depuis l'import initial, on ne pousse plus directement sur `main` : une branche `feature/<id>-<nom>` par tâche, une Pull Request (interface web de GitHub), la revue de l'autre développeur. Messages de commit en français avec préfixe (`feat(W1) : …`, `fix : …`, `test : …`, `docs : …`). Avant de pousser : `composer test`, `vendor/bin/pint --test` (et `npm run typecheck` côté mobile). **L'assistant ne commite ni ne pousse que si son développeur le demande.**

## 1. Le projet

**VigiMoto** est la plateforme nationale malienne de numérisation des vignettes de motos (**VGT**) et de contrôle des motos : sécurité des biens (moto volée ou en règle), centralisation des données accessibles 24 h sur 24, réduction des délais d'obtention de la carte VGT.

Architecture hybride (cahier §3) : **plateforme Web PC** (commissariats, mairies), **application Android** (police de patrouille, population), **base centralisée**.

| Profil | Canal | Fonctions clés (cahier §4, §6, §9) |
|---|---|---|
| Commissaire | Web | Saisie des profils (propriétaire + moto), déclarations de vol, demandes et retraits de VGT, motos retrouvées, informations aux populations. Il crée et gère la police de son commissariat |
| Police (patrouille) | Mobile | Contrôle rapide : moto volée ou non, vignette à jour ; consultation des informations |
| Agent de mairie | Web | Validation des formulaires VGT, réception des preuves de paiement, remise de la carte physique |
| Population | Mobile, **sans connexion** (D32) | Consultation des motos retrouvées et des informations, demande de renouvellement VGT après un premier enregistrement (confirmation par SMS) |

Cas d'usage : (1) enregistrement initial et acquisition de la VGT au commissariat (identité, moto, attestation de vente) ; (2) déclaration de vol : la moto est marquée « Volée » dans une base consultable par tous les agents, et un SMS part au propriétaire quand elle est retrouvée. Équipe : **Moustapha BARRY** et **Amadou KAREMBE**, chacun avec son assistant IA.

## 2. Où en est-on

**Fait** (socle Web, API et mobile de base) :
- Authentification Web par **numéro de téléphone**, mot de passe temporaire à changer, 5 tentatives, comptes et institutions désactivables à chaud.
- 6 rôles, hiérarchie, plafond de rôle, **permissions à deux voies** (manifestes + création depuis l'interface), écrans Rôles, Permissions, Attribution (menu **Paramètres**).
- Audit synchrone immuable, cloisonnement institutionnel fail-closed, protection du dernier superadmin, superadmins créés automatiquement.
- Tableau de bord (deux vues) en données fictives activables, sidebar listant tous les modules à venir, **Paramètres** en bas de la sidebar.
- Profil à deux onglets (informations, mot de passe), page de connexion avec formulaire à droite et diaporama d'images, survol unifié dans toute l'interface.
- API mobile d'authentification (`/api/v1`) et application Expo (connexion, changement de mot de passe, profil, **informations** de la police en mode maquette, M2).
- 16 tables migrées, tests automatiques (`composer test`), thème et assets locaux.

**Reste** : voir §10 (backlog).

## 3. Démarrer

Prérequis : PHP ≥ 8.3, Composer 2, MariaDB 10.4+ (ou MySQL 8), Node ≥ 20 (mobile seulement).

```sh
composer install
cp .env.example .env
php artisan key:generate
# créer deux bases vides (utf8mb4, utf8mb4_unicode_ci) : db_numerise_vignette et db_numerise_vignette_test
php artisan migrate --seed      # rôles, permissions, superadmins déclarés dans .env
php artisan serve               # crée aussi les superadmins manquants au lancement
composer test                   # tests (base db_numerise_vignette_test, jamais la base de développement)
```

Dans `.env` (jamais dans Git) : `SUPERADMIN_PASSWORD`, `SUPERADMIN_1_NAME` / `_PHONE`, `SUPERADMIN_2_NAME` / `_PHONE` ; `DASHBOARD_MOCK=true` pour voir le tableau de bord rempli de données fictives (sans effet en production). **Connexion : numéro de téléphone + mot de passe.**

Application mobile : voir §9.

## 4. Architecture du socle

```
Navigateur PC (Blade + Bootstrap) → routes/web.php → session (guard web) ─┐
                                                                          ├→ Middleware → Contrôleurs → Services / Policies → Modèles → base
App mobile (Expo) → routes/api.php (/api/v1) → Sanctum (Bearer) ──────────┘   (actif, canal,   (minces)    (logique partagée Web + API)     ↑
                                                                                mot de passe)                                                ActivityLogger → activity_logs
```

1. Un dépôt, deux portes : Web par session, mobile par jeton ; elles ne se mélangent jamais.
2. La logique vit dans des **Services** appelés à l'identique par le Web et l'API.
3. Autorisation en quatre couches : canal (web/api) → permission → Policy (plafond de rôle) → cloisonnement institutionnel.
4. Le **superadmin** contourne l'autorisation (`Gate::before`), pas la logique métier ni l'audit : mêmes Services, toute écriture journalisée.
5. L'audit est écrit dans la même transaction que l'action.
6. Modules par manifeste : un module s'ajoute sans modifier le socle.

### 4.1 Tables (16) et dictionnaire de données

Moteur InnoDB, `utf8mb4_unicode_ci`. Migrations dans `database/migrations/` ; ordre : institutions → users → sessions → Spatie → Sanctum → audit.

| Table | Contenu |
|---|---|
| `commissariats`, `mairies` | `id`, `name` (150, unique), `code` (30, unique, nullable), `is_active`, timestamps, `deleted_at`. Adresse, téléphone, région, commune : À VALIDER AVEC LE CLIENT |
| `users` | `id`, `name` (150), **`phone` (20, unique, obligatoire = identifiant de connexion, E.164 `+223XXXXXXXX`)**, `password`, `is_active`, `must_change_password`, `password_changed_at`, `commissariat_id`, `mairie_id` (FK nullables, RESTRICT), `last_login_at`, `remember_token`, timestamps, `deleted_at`. Ni e-mail ni photo. **CHECK** : jamais les deux institutions |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Spatie Permission (`teams` désactivé). `permissions.source` = `manifest` ou `custom` (défaut `custom`). `model_has_roles` : index **UNIQUE** (`model_id`, `model_type`) = un seul rôle par utilisateur |
| `personal_access_tokens` | Sanctum (jetons Bearer, `expires_at`) |
| `activity_logs` | Audit : `user_id` (FK), `user_name`, `user_role` (copies figées), `commissariat_id`, `mairie_id` (FK), `action` (`auth.login`, `users.updated`…), `module`, `description`, `subject_type` (alias morph), `subject_id`, `subject_label`, `old_values`, `new_values` (JSON, champs modifiés seulement, jamais de mot de passe ni de jeton), `channel` (`web`/`api`/`console`), `ip_address`, `user_agent`, `created_at` (DATETIME). Index : (`user_id`,`created_at`), (`module`,`created_at`), (`subject_type`,`subject_id`), `action`, `created_at` |
| `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | Laravel (sessions, cache et file d'attente en base) |

Clés étrangères vers les institutions et l'audit : `RESTRICT`. Institutions et utilisateurs se suppriment en **soft delete** uniquement ; un téléphone supprimé reste réservé.

**Règles rôle ↔ institution** (le rôle est dans une autre table, un CHECK ne peut pas les porter) : gérées par `UserProvisioningService`, les FormRequests et les Policies, avec tests. `commissaire` et `police` : `commissariat_id` obligatoire ; `mairie` : `mairie_id` obligatoire ; `superadmin`, `admin_national`, `population` : aucune institution.

**Stratégie MariaDB 10.4 / MySQL** : un seul pilote `mysql`, sous-ensemble commun ; pas d'`enum` SQL (chaîne + enum PHP) ; CHECK en SQL brut (Laravel n'a pas de constructeur CHECK ; ignoré avant MySQL 8.0.16, la validation applicative reste le garde principal) ; `activity_logs.created_at` en DATETIME (MariaDB ajoute sinon un `ON UPDATE` implicite aux TIMESTAMP) ; JSON = alias LONGTEXT sur MariaDB, aucune requête JSON en base ; en production, un compte de migration distinct du compte applicatif (celui-ci sans UPDATE/DELETE sur `activity_logs`). Les tests tournent sur MariaDB, pas SQLite.

### 4.2 Rôles

| Rôle | Niveau | Institution | Canal | Périmètre | Créé par |
|---|---|---|---|---|---|
| `superadmin` | 100 | aucune | Web | Global, lecture + écriture, audité | Amorçage automatique / commande |
| `admin_national` | 80 | aucune | Web | National, selon permissions ; aucun privilège technique | Superadmin |
| `commissaire` | 50 | commissariat | Web | Son commissariat ; crée sa police | Admin national, superadmin |
| `mairie` (agent) | 50 | mairie | Web | Sa mairie | Admin national, superadmin |
| `police` | 30 | commissariat | **Mobile** | Son commissariat ; lecture nationale des motos volées au contrôle | Commissaire, admin national, superadmin |
| `population` | 10 | aucune | **aucun** (pas de compte, D32) | — | jamais : rôle inutilisé, retrait du socle proposé (PROPOSITION TECHNIQUE — À VALIDER : `RoleName`, `RoleSeeder`, `role_hierarchy`, factory et tests) |

Plafond de rôle (`config/role_hierarchy.php`, via `App\Enums\RoleName`) : superadmin → tous ; admin national → commissaire, mairie, police ; commissaire → police de son commissariat ; les autres → aucun. Personne n'agit sur un compte de niveau supérieur ou égal, sauf le superadmin. L'admin national ne voit ni ne gère les superadmins.

### 4.3 Canaux

`config/channels.php` : Web = superadmin, admin national, commissaire, mairie ; API = police. La population n'a pas de canal : elle n'a pas de compte et utilise les routes publiques de l'API (D32, §4.9). Un rôle connecté sur le mauvais canal est refusé, et l'échec est audité.

### 4.4 Permissions (deux voies, D6 + D23)

Convention `module.action` (modules en français, actions en anglais).

| Module | Permissions |
|---|---|
| `users` | `view`, `create`, `update`, `delete`, `activate`, `reset_password`, `revoke_access` |
| `commissariats`, `mairies` | `view`, `create`, `update`, `delete` |
| `roles`, `permissions`, `audit`, `system` | `roles.view`, `permissions.view/create/assign`, `audit.view`, `system.view/maintain` — **réservées** au superadmin |

Défauts : `admin_national` = `users.*` (sauf `delete`), `commissariats.view/create/update`, `mairies.view/create/update` ; `commissaire` = `users.view/create/update/activate/reset_password/revoke_access` (sa police) ; `users.delete`, `commissariats.delete`, `mairies.delete` s'attribuent en exception ; `mairie`, `police`, `population` : aucun défaut.

- **Voie A — code** : un manifeste `config/modules/<module>.php` (`label`, `permissions`, `reserved`, par rôle `defaults` / `optional`, `navigation`) + `php artisan permissions:sync` (idempotent : crée rôles et permissions, resynchronise les défauts, signale les orphelines sans les supprimer). À lancer à chaque déploiement, après `migrate`.
- **Voie B — interface** : le superadmin crée des permissions `module.action` depuis **Paramètres → Permissions** (`source = custom`), les attache à un rôle ou les accorde en exception à un utilisateur. Garde-fous : format `module.action` en minuscules, unique ; les modules `system`, `roles`, `permissions`, `audit` sont refusés ; jamais de permission réservée pour un autre que le superadmin ; une exception ne dépasse pas le « pool » du rôle ciblé (`optional` du manifeste + permissions `custom`) ; tout est audité. `permissions:sync` ne touche jamais aux permissions `custom` ni à leurs attributions ni aux exceptions des utilisateurs.
- Toutes les routes sont protégées par `permission:module.action` (jamais `role:`). Limite connue : une permission héritée du rôle ne peut pas être retirée à un seul utilisateur.

### 4.5 Audit

Synchrone, dans la même transaction, **non modifiable** (le modèle refuse `update` et `delete`). `ActivityLogger::log()` pour les actions métier, trait `LogsActivity` pour création, modification, suppression, restauration. Journalisé dès le socle : connexions (réussies, échouées avec le numéro tenté, blocages, verrouillages), déconnexions, changements et réinitialisations de mot de passe, cycle de vie des utilisateurs, rôles, permissions, institutions, synchronisation des permissions. Les lectures ne sont pas journalisées (`ActivityLogger::read()` disponible). Actions du superadmin visibles (rôle figé dans chaque ligne). Les mises à jour de masse (`update()` sur le builder) ne déclenchent pas les événements de modèle : interdites sans journalisation explicite (test d'architecture). Durée de conservation : À VALIDER AVEC LE CLIENT.

### 4.6 Superadmins (D10, D27)

Deux comptes individuels : Moustapha BARRY et Amadou KAREMBE, aucun compte partagé. Ils sont **créés automatiquement s'ils n'existent pas** (`SuperadminBootstrapper`, idempotent, verrouillé, silencieux si la base n'est pas prête) : au lancement de `php artisan serve`, après chaque `migrate`, et à la première visite de la page de connexion. Valeurs lues dans `.env` (`SUPERADMIN_*`). **Hors `local`, le mot de passe de `.env` est temporaire** : changement forcé à la première connexion. Risque connu : un mot de passe par défaut simple, choisi pour le développement, est exposé sur un serveur hébergé tant que ce premier changement n'a pas eu lieu → en hébergement, mettre un mot de passe fort dans le `.env` du serveur. `php artisan app:create-superadmin` reste disponible (mot de passe saisi de façon interactive). Le dernier superadmin actif est protégé (ni désactivation, ni suppression).

### 4.7 Institutions et cloisonnement (D8)

Désactiver une institution coupe l'accès de tous ses utilisateurs à la requête suivante (sessions et jetons supprimés par `UserProvisioningService::revokeAccess`). Traits `BelongsToCommissariat` et `BelongsToMairie` (avec `InstitutionScope`) à appliquer aux modèles métier — **fail-closed** : superadmin et admin national voient tout ; un utilisateur d'une institution voit la sienne ; population, compte sans institution ou requête sans utilisateur : **zéro résultat**. Console et files d'attente ne sont pas filtrées. Une lecture inter-institutions légitime (contrôle de police sur les motos volées) passe par `acrossCommissariats()` : contournement explicite, protégé par permission et audité par le module. Le modèle `User` n'utilise pas ces traits : la visibilité des comptes passe par `User::visibleTo($acteur)` et `UserPolicy`.

### 4.8 Routes Web

Session, CSRF actif, un fichier par module dans `routes/web/*.php` (chargés automatiquement).

| Route | Protection |
|---|---|
| `/login` (GET, POST) | invité, 5 tentatives, amorçage des superadmins |
| `/logout`, `/`, `/profile` (GET, PUT), `/profile/password`, `/modules/{module}` | connecté (`auth`, `active`, `channel:web`, `password.changed`) |
| `/password/change` | connecté, accessible même avec un mot de passe temporaire |
| `/roles`, `/permissions`, `/user-permissions`, `/users/{user}/permissions`, `/roles/{role}/permissions` | `roles.view`, `permissions.view/create/assign` |
| `/up` | santé Laravel |

Middleware : `active` (compte ET institution actifs), `channel:web|api`, `password.changed`, `permission:…`. **À construire** (§10) : `/users`, `/commissariats`, `/mairies`, `/audit`, `/system`.

### 4.9 API et Sanctum

Préfixe `/api/v1`, JSON uniquement, sans session ni CSRF, un fichier par module dans `routes/api/v1/*.php`. Pile d'une route : `auth:sanctum` → `active` → `channel:api` → `throttle:api`. Sanctum : Bearer seulement (`guard` vidé, `stateful` vide, `/sanctum/csrf-cookie` désactivé), jeton par appareil, 30 jours (`SANCTUM_TOKEN_EXPIRATION`, minutes). Compatibilité Sanctum + Spatie testée. **Routes publiques (D32)** : la population n'ayant pas de compte, certains endpoints de lecture (informations, motos retrouvées) et la demande de VGT sont **sans jeton** ; ils sont limités par IP (limiteur dédié, à ajouter au socle avec W6), ne renvoient aucune donnée personnelle, et figurent dans la liste blanche du test d'architecture « toute route est protégée » (aujourd'hui : santé et connexion). Contrat complet : §8.

### 4.10 Arborescence

```
app/Console/Commands   CreateSuperadmin, SyncPermissions
app/Enums              RoleName, ActivityChannel
app/Http               Controllers/{Web,Api/V1}, Middleware, Requests/{Web,Api/V1}, Resources
app/Models             User, Commissariat, Mairie, Permission, ActivityLog ; Concerns/ (LogsActivity, BelongsTo*) ; Scopes/
app/Policies           (à venir avec les écrans utilisateurs et institutions)
app/Providers          AppServiceProvider (Gate::before, alias morph, écouteurs), ModuleServiceProvider (menu)
app/Services           Audit/, Auth/CredentialChecker, Permissions/{PermissionSynchronizer,PermissionManager}, Users/{UserProvisioningService,SuperadminBootstrapper}, Dashboard/
app/Support            ModuleRegistry, PlannedModules, PhoneNumber
config/                modules/*.php (manifestes), role_hierarchy, channels, authorization, brand, superadmins, dashboard, planned_modules
database/              migrations, seeders (RoleSeeder, SuperadminSeeder), factories
mobile/                application Expo (§9)
public/assets/         thème Web local
resources/views/       layouts, partials, auth, dashboard, roles, permissions, user-permissions, modules, errors
routes/                web.php + web/*.php, api.php + api/v1/*.php
tests/                 Feature, Unit, Architecture
```

## 5. Décisions

| # | Décision |
|---|---|
| D1 | MariaDB en développement, compatibilité MySQL/MariaDB en production (le CHECK est un filet, la validation applicative est le garde principal) |
| D2 | Laravel 13, PHP ≥ 8.3, Spatie Permission, Sanctum |
| D3 | Noms métier en français, colonnes et actions techniques en anglais |
| D4 | ~~Connexion par `username`~~ → **D26** |
| D5 | Pas d'e-mail dans le socle |
| D6 | Permissions par rôle + exceptions additives ; définies dans le code (**amendé par D23**) |
| D7 | Un manifeste par module + un fichier de routes par module |
| D8 | Cloisonnement fail-closed |
| D9 | Le commissaire crée et gère les comptes `police` de son commissariat ; admin national et superadmin aussi |
| D10 | Superadmin sans mot de passe dans Git, dernier superadmin actif protégé (**aménagé par D27**) |
| D11 | Audit complet, synchrone, non modifiable, actions superadmin visibles |
| D12 | Sanctum avec jetons Bearer ; pas de mode SPA/cookie |
| D13 | Session Web de 120 minutes |
| D14 | Mot de passe ≥ 8 caractères, lettres et chiffres ; changement obligatoire d'un mot de passe temporaire |
| D15 | Assets locaux, aucun CDN |
| D16 | Le superadmin lit ET écrit les données métier (maintenance) ; toute modification est auditée |
| D17 | ~~Authentification de la population reportée au module Population~~ → **D32** |
| D18 | Documentation en un seul fichier (ce guide, voir aussi D30) |
| D19 | Aucun pipeline frontend Node dans l'application Web (pas de Vite, Tailwind ni police distante) : Blade + Bootstrap 5 + assets locaux dans `public/assets/` |
| D20 | Interface Web : thème « Semi Bleu » (sidebar sombre, cartes à bandeau bleu, modales bleues, DataTables), architecture et code propres au projet |
| D21 | Les migrations ont été présentées puis validées avant exécution ; celles du squelette Laravel (avec e-mail) ne sont jamais exécutées |
| D22 | Deux tableaux de bord : un accueil piloté par les rôles et permissions, une supervision pour le superadmin ; cartes KPI en dégradé, accès rapides, aperçus en tableau |
| D23 | **Permissions à deux voies** : manifestes + `permissions:sync` (`source = manifest`) et création par le superadmin depuis l'interface (`source = custom`), avec garde-fous (§4.4) |
| D24 | Le thème d'interface (« Dashkote Admin », codervent) est réutilisé sous la responsabilité de l'utilisateur, qui déclare l'avoir adapté et pouvoir l'utiliser ; risque juridique porté par lui |
| D25 | **Maquette des modules à venir** : la sidebar liste les modules métier prévus et le tableau de bord affiche des **données fictives** (interrupteur `DASHBOARD_MOCK`, jamais actif en production). Présentation seulement : aucune table, permission, route ni modèle métier. Une entrée disparaît dès que le manifeste du module existe |
| D26 | **Connexion par numéro de téléphone** (Web et API) : `users.phone` obligatoire, unique, E.164 ; « 70 00 00 01 » accepté ; `username` supprimé. Le numéro tenté est audité en cas d'échec, jamais le mot de passe |
| D27 | **Superadmins créés automatiquement** partout, depuis `.env` (§4.6), mot de passe temporaire hors `local` |
| D28 | Nom de la plateforme : **VigiMoto** (proposé, modifiable dans `config/brand.php` + `APP_NAME`). Le dépôt et le dossier restent `Numerise_vignette` |
| D29 | Application mobile : **Expo (React Native) dans le dossier `mobile/`** du même dépôt, testée avec Expo Go ; elle ne consomme que l'API `/api/v1` |
| D30 | Un seul fichier `.md` dans le dépôt : ce guide |
| D31 | **Répartition par plateforme** : Moustapha = **mobile** (M1, M2, M4, M5) et les écrans Web **Audit** et **Système** (W3, W4), Amadou = **Web** (W1, W2, W5 à W14, avec l'API de chaque module). Les deux travaillent en parallèle : le mobile avance sur le contrat d'API (§8) sans attendre le Web. Hébergement **reporté** jusqu'au moment opportun (aucune tâche pour l'instant). Charge : 15 points contre 39 (§10) |
| D32 | **La population n'a pas d'authentification** (décision de Moustapha, conforme au cahier §7 « Sécurité » : seuls la police, les commissaires et les agents de mairie utilisent la connexion). Elle consulte les informations et les motos retrouvées, et demande une VGT **sans compte**, par des routes **publiques** de l'API (sans jeton, limitées par IP, lecture seule sauf la demande de VGT). Comment elle s'identifie pour la demande (matricule, téléphone enregistré, code reçu par SMS : le cahier §8 parle d'un « sms pour la confirmation ») : À VALIDER AVEC LE CLIENT. Supprime la tâche M3 |

### Points ouverts — client (À VALIDER AVEC LE CLIENT)

Non bloquants pour le socle : durée de conservation de l'audit ; nombre de commissaires par commissariat ; canal de remise du mot de passe temporaire (main propre, SMS plus tard) ; champs additionnels des institutions ; création des agents de mairie par l'admin national et le superadmin seulement ; logo définitif de la plateforme ; **validation des visuels du diaporama de connexion** (images générées par IA, sans texte, drapeau, blason ni marque ; à faire valider par le client avant toute mise en ligne — sur la diapositive 5, les deux agents flous à l'arrière-plan semblent tenir des armes longues : à régénérer avec les prompts de la version 2 si cela gêne).

**Bloquants pour les modules métier** (à traiter avant leur conception) : identification de la population **sans compte** pour la demande de VGT (D32) ; règles de la vignette (durée de validité, tarifs, arriérés) ; paiement (montant, qui confirme, statut, rôle du « code marchand de l'État ») ; qui enregistre « moto retrouvée » (commissaire, police ou les deux) ; workflow mairie (statuts, rejet, confirmation de remise de la carte) ; nature et effet de la « taxe à payer » du contrôle de police ; identification de la moto (matricule seul ou châssis, unicité) ; un propriétaire peut-il avoir plusieurs motos, historique des changements de propriétaire ; opérateur SMS, langue et contenu des messages ; QR Code et Mobile Money : phase 1 ou plus tard.

## 6. Comment ajouter un module

Principe : **un module ne modifie jamais les fichiers du socle**. Modules prévus (sidebar « Modules à venir », `config/planned_modules.php`) : propriétaires, motos, déclarations, motos retrouvées, demandes VGT, retrait VGT, paiements, contrôle de police, informations, SMS, QR Code.

Checklist :
1. Manifeste `config/modules/<module>.php` (permissions, défauts par rôle, `navigation`) puis `php artisan permissions:sync`. L'entrée « à venir » du module disparaît toute seule.
2. Migrations : clé d'institution, `softDeletes`, index. Aucune table sans que le cahier ou une décision la justifie.
3. Modèle avec `LogsActivity` et le trait de cloisonnement (`BelongsToCommissariat` / `BelongsToMairie`).
4. Policy, FormRequests, **Service partagé Web/API**, contrôleur Web + `routes/web/<module>.php`.
5. Vues : `@extends('layouts.admin')`, mêmes composants que les écrans existants (bandeau `card-header-brand`, DataTables, modales).
6. API : `app/Http/Controllers/Api/V1`, Resource, `routes/api/v1/<module>.php`, et mise à jour du contrat (§8).
7. Audit explicite des actions métier (`ActivityLogger::log('vgt.validated', …)`).
8. Tests obligatoires : permission, cloisonnement, audit ; les tests d'architecture doivent rester verts.

Conventions : permissions `module.action` ; verbes d'audit `module.verbe_au_passé` ; réponses API en JSON et en français. **Interdits** : contourner le scope sans le signaler, créer des permissions à la main dans la base, `update()` de masse sans audit, secrets dans le dépôt, ressources externes.

Cas particuliers : lecture inter-institutions (`acrossCommissariats()`, avec permission et audit) ; écritures du superadmin (l'institution est indiquée explicitement) ; les cartes de tableau de bord d'un module viendront de son manifeste (PROPOSITION TECHNIQUE — À VALIDER).

## 7. Interface Web

- **Thème** « Dashkote Admin » (codervent) + styles propres `public/assets/css/numerise.css` (palette : bleu `#1d4e89`, bleu foncé `#123a63`, orange `#f97316` ; thème « Semi Bleu » par défaut ; police Inter ; icônes Boxicons). Toute la structure dépend de `resources/views/layouts/admin.blade.php` et `partials/` ; les vues de contenu sont en Bootstrap 5.
- **Sidebar** : Tableau de bord, entrées des manifestes (filtrées par permission, si la route existe), section « Modules à venir » (fiches sans donnée), puis **Paramètres** tout en bas (Rôles, Permissions, Attribution s'ouvrent depuis son sous-menu).
- **Gabarit d'une page** : fil d'ariane + bouton d'action, `card` à bandeau `card-header-brand`, tableaux DataTables (libellés français), modales à en-tête bleu, alertes SweetAlert2 (`session('status')`, `session('error')`).
- **Identité** : `config/brand.php` (nom, logo 3D bicolore, sigle, slogan).
- **Assets** : tout est local (`public/assets/`) ; un test d'architecture échoue si une vue ou un CSS référence une ressource externe.

- **Survol unifié** : un seul effet de survol partout (fond bleu clair, texte bleu de marque, coins arrondis), défini par les variables `--nv-hover-bg` / `--nv-hover-fg` de `numerise.css` (avec variante pour le thème sombre). Il s'applique aux menus Paramètres, aux menus déroulants (profil, notifications), aux onglets, à la pagination, aux listes Select2 et aux tableaux `.table-hover`. La sidebar, sur fond sombre, garde son équivalent clair. Ne pas écrire de survol ad hoc : réutiliser ces variables.
- **Profil** (`/profile`) : carte pleine largeur à **deux onglets**. « Mes informations » : nom et numéro de téléphone modifiables (le numéro est l'identifiant de connexion : le changer exige le mot de passe actuel ; rôle, institution et statut ne se modifient jamais ici ; audit `users.updated` avec anciennes et nouvelles valeurs) ; « Mot de passe » : changement (les autres sessions et jetons sont fermés). L'onglet actif suit l'action ou l'erreur.
- **Page de connexion** : formulaire de connexion **à droite**, diaporama plein écran **à gauche** (fondu, léger zoom, légende et pastille par image, points cliquables, une image toutes les 6 s ; sur mobile, seule la carte reste). Diapositives déclarées dans `config/brand.php` (`login_slides`) ; images déposées dans `public/assets/images/login/` sous le nom `slide-1` à `slide-5` (`.jpg`, `.jpeg`, `.webp` ou `.png`, dans cet ordre de priorité), **JPEG ≤ 400 Ko, 1280 px de large au moins** (un test l'impose : un PNG brut de 2 Mo est refusé). Chaque diapositive a un `position` (cadrage CSS) pour recentrer le sujet quand l'image est rognée. **Images livrées** (générées par IA, version 2 des prompts) : 5 JPEG en 1660 × 948 (16:9), de 230 à 335 Ko chacun, convertis des PNG d'origine (≈ 2 Mo) qui restent hors dépôt ; un test impose le format paysage (ratio ≥ 1,5). Une image absente n'est pas une erreur : la diapositive s'affiche avec un dégradé bleu. Le formulaire occupe le tiers droit et les légendes le bas gauche : le sujet doit être au centre gauche, sans élément important dans le tiers droit ni le bas de l'image (un dégradé bleu sombre est appliqué par-dessus).

**Prompts des images du diaporama — version 2** (à donner à l'IA de génération d'images, à coller après le bloc de style commun). La première version a produit de belles images mais **pleines de texte, de drapeaux, de blasons et de logos** : la version 2 les interdit explicitement.

*Style commun (début de chaque prompt)* : « Photorealistic documentary photograph, cinematic natural light, Bamako (Mali), West African urban setting, warm golden-hour tones with deep blue shadows, 35mm lens, shallow depth of field, high detail, **wide 16:9 landscape frame (1792x1024)**, main subject in the left-center of the frame, calm uncluttered space on the right third and along the bottom edge. **Absolutely no writing anywhere in the image: plain blank walls, no signs, no posters, no banners, no billboards, no lettering on clothing, vehicles or documents, no badges or patches with text, no flags, no coat of arms, no emblems, no brand logos on any laptop, phone, motorbike or car, no QR code, license plates not visible.** »

1. `slide-1` (Contrôle) : « A Malian police officer in a plain dark patrol uniform with no visible badges calmly checks a laminated card held in his hand at a roadside checkpoint in Bamako at sunset, while holding a smartphone; the motorcyclist in a helmet stands beside a simple motorbike seen from the front; reddish laterite road, blurred traffic and acacia trees in the background, orange traffic cone. »
2. `slide-2` (Commissariat) : « Inside a modern, tidy police station reception with plain light-blue walls: a police officer in a plain uniform types on a laptop while an owner hands over identity papers across a granite counter; binders on shelves, a plant, warm interior light, welcoming and orderly atmosphere, colleagues blurred in the background. »
3. `slide-3` (Mairie) : « At the wooden counter of a town hall office, a municipal agent in a patterned shirt hands a small blank laminated card to a smiling young motorbike-taxi rider wearing a plain reflective vest; ceiling fan, soft daylight through large windows, stamp pad and neatly stacked forms on the desk, plain cream walls. »
4. `slide-4` (Sécurité routière) : « Wide cinematic view of a busy Bamako avenue at dusk with many motorcycles flowing safely, riders wearing helmets, streetlights turning on, deep blue and orange sky, a distant monument silhouette, sense of order and movement, slight motion blur, no close-up faces, no billboards. »
5. `slide-5` (Motos retrouvées) : « A relieved young man smiles at his smartphone, whose screen only gives off a soft green glow, standing next to his recovered motorcycle parked in front of a plain police station building at sunset; two blurred officers in the background; warm rim light, shallow depth of field, reassuring mood. »

*Prompt négatif* : « text, letters, numbers, words, signs, posters, banners, billboards, watermark, logo, brand name, signature, license plate, QR code, national flag, flag colors, coat of arms, emblem, badge, weapon, gun, violence, deformed hands, extra fingers, blurry, low resolution, cartoon, illustration, oversaturated, distorted faces. »

Conseils : générer 2 ou 3 variantes par image et garder la plus réaliste ; faire relire les uniformes et les décors par une personne du pays ; vérifier les droits d'usage commercial de l'outil ; convertir en JPEG (qualité ≈ 80) sous 400 Ko.

Licences des assets tiers (fichiers de licence dans `public/assets/licenses/`) : Bootstrap 5.0.0-beta1, SimpleBar, MetisMenu, perfect-scrollbar, Pace, DataTables 1.10, Select2, jQuery 3.6.0, SweetAlert2 : **MIT** ; Boxicons 2.1.4 : CC-BY-4.0 ou OFL-1.1 ou MIT ; Inter et Rye : **SIL OFL 1.1**. Le thème lui-même est couvert par D24. Copiés tels quels, sauf : `app.css` (import de police distante retiré, séparateur du fil d'ariane en Boxicons, fonds de démonstration retirés) et `app.js` (blocs de démonstration retirés).

## 8. Contrat de l'API mobile — v1 (authentification)

Statut : authentification implémentée et testée (`tests/Feature/Api`). Les endpoints métier viendront avec leurs modules, chacun dans `routes/api/v1/<module>.php`, et ce contrat sera complété alors.

Généralités : base `/api/v1` ; JSON uniquement (même sans `Accept: application/json`) ; **jetons Bearer**, sans session, cookie ni CSRF ; identifiant = **numéro de téléphone** (`70 00 00 01`, `+223 70 00 00 01` ou `0022370000001` acceptés, renvoyé en E.164) ; un jeton par appareil (`device_name`), se reconnecter sur le même appareil remplace le jeton ; 30 jours ; seuls les rôles du canal API se connectent (police aujourd'hui) ; limitation : 5 tentatives par numéro et par IP, 20 connexions par minute et par IP, 120 requêtes par minute et par utilisateur ; tout est audité (canal `api`).

| Méthode | Route | Auth | Rôle |
|---|---|---|---|
| GET | `/health` | publique | `{"status":"ok","time":"…"}` |
| POST | `/auth/login` | publique | Corps : `phone`, `password`, `device_name` → jeton |
| POST | `/auth/logout` | jeton | Révoque le jeton courant |
| GET | `/auth/me` | jeton | `{"user": {…}}` |
| PUT | `/auth/password` | jeton, **même restreint** | `current_password`, `password`, `password_confirmation` → nouveau jeton, tous les anciens révoqués |

Réponse de connexion (200) :
```json
{ "token": "12|Ab3…", "token_type": "Bearer", "expires_at": "2026-10-21T12:00:00+00:00", "abilities": ["*"],
  "password_change_required": false,
  "user": { "id": 7, "name": "Agent Traoré", "phone": "+22370000001",
            "role": {"name": "police", "label": "Police"},
            "institution": {"type": "commissariat", "id": 3, "name": "Commissariat du 1er Arrondissement"},
            "must_change_password": false, "permissions": [] } }
```
Tant que le mot de passe est **temporaire**, `password_change_required` vaut `true` et le jeton n'a que la capacité `["password:change"]` : seuls `/auth/password` et `/auth/logout` répondent, le reste renvoie `403 {"code":"password_change_required"}`.

| Statut | Quand |
|---|---|
| 401 `{"message":"Non authentifié."}` | Jeton absent, invalide, expiré ou révoqué → revenir à la connexion |
| 403 `code = account_disabled` | Compte ou institution désactivé (le jeton est supprimé) |
| 403 `code = channel_forbidden` | Rôle non autorisé sur l'API |
| 403 `code = password_change_required` | Mot de passe temporaire |
| 403 `code = forbidden` | Permission manquante |
| 422 `{"message","errors":{"phone":[…]}}` | Validation, ou identifiants refusés (un numéro inconnu et un mauvais mot de passe reçoivent le même message) |
| 429 | Trop de tentatives, ou limitation |

### Contrat des informations — BROUILLON (W6, consommé par M2 et M5)

Rédigé côté mobile d'après le cahier (§6 « Informations » et « Affichage »), **à valider par Amadou** : tant que W6 n'est pas fusionnée, aucun endpoint n'existe. L'application est déjà écrite contre ce contrat (mode maquette, §9).

| Méthode | Route | Auth | Rôle |
|---|---|---|---|
| GET | `/informations?page=1` | **publique** (aucun jeton, D32) | Liste paginée, la plus récente d'abord ; lecture seule ; consommée par la police (M2) et la population (M5) |

```json
{ "data": [ { "id": 4, "commissaire_name": "…", "commissariat_name": "…", "description": "…",
              "image_url": "https://…/image.jpg", "document_url": "https://…/document.pdf",
              "published_at": "2026-09-21T10:00:00+00:00" } ],
  "meta": { "current_page": 1, "last_page": 2 } }
```

- Champs du cahier : nom du commissaire, nom du commissariat, description, fichier PDF, image ; le cahier n'a **pas de titre**. `description`, `image_url` et `document_url` sont chacun optionnels (`null`) : « description **ou** fichier PDF **ou** image ». URL absolues, joignables depuis le téléphone.
- `published_at` (tri, date affichée) et la pagination sont des PROPOSITIONS TECHNIQUES — À VALIDER.
- Endpoint **public** : pas de jeton ni de permission, limitation par IP (429 au-delà), aucune donnée personnelle ; il renvoie les informations de **tous** les commissariats (le cahier dit « informations venant des différents commissariats »). Tant qu'il n'existe pas, l'application affiche « Les informations ne sont pas encore disponibles sur le serveur ».

## 9. Application mobile (`mobile/`)

Application **Expo (React Native, TypeScript, Expo Router)**, à essayer avec **Expo Go**, dans le même dépôt (D29). Elle consomme uniquement l'API `/api/v1` (§8). Mêmes versions d'Expo que le projet KalanNet (SDK 57) : compatible avec le même Expo Go. Pile : `expo-router`, `react-native-paper`, `axios`, `expo-secure-store` (le jeton est stocké dans le Keystore du téléphone, jamais en clair).

**Écrans** : **espace public sans connexion** (la population n'a pas de compte, D32 : informations ; motos retrouvées et demande de VGT en fiches « À venir » ; bouton « Connexion » en haut à droite, retour possible) ; connexion (numéro de téléphone + mot de passe, réservée à la police) ; **changement de mot de passe obligatoire** tant que le mot de passe est temporaire (le jeton est alors restreint, §8) ; accueil (bonjour, rôle, institution, état du serveur, liste « À venir » du contrôle de police, tirer pour rafraîchir) ; **informations** (M2 : liste en lecture seule, description, image, PDF, commissaire et commissariat, pages suivantes au défilement) ; profil (informations, changer le mot de passe, déconnexion). Un 401 ou un compte désactivé déconnecte proprement l'application et affiche un message.

```
mobile/app/            _layout.tsx (gardes de navigation Stack.Protected), login, change-password, public/ (espace de la population), (tabs)/{index,informations,profile} (police)
mobile/components/     BrandTitle, PasswordForm, InformationsList, ComingSoon, MockBanner
mobile/context/        AuthContext (session, jeton, changement de mot de passe)
mobile/lib/            api.ts (axios + intercepteurs + messages en français), storage.ts (SecureStore), theme.ts, mock/ (mode maquette)
mobile/types/api.ts    types du contrat d'API
```

**Lancer** (téléphone et ordinateur sur le même Wi-Fi) :

```sh
# 1. Serveur Laravel joignable depuis le téléphone (dans le dépôt)
php artisan serve --host=0.0.0.0

# 2. Application (dans mobile/)
npm install
cp .env.example .env      # EXPO_PUBLIC_API_URL=http://<IP de l'ordinateur>:8000/api/v1  (jamais « localhost »)
npx expo start            # scanner le QR code avec Expo Go
```

Avec XAMPP/Apache, l'URL est `http://<IP>/Numerise_vignette/public/api/v1`. Si le téléphone ne joint pas le serveur : pare-feu (ports 8000 et 8081), même réseau, IP de la machine à jour dans `mobile/.env` (l'accueil affiche l'état du serveur et l'URL utilisée).

**Mode maquette** (PROPOSITION TECHNIQUE — À VALIDER, même esprit que D25) : construire un écran **avant** que son endpoint existe. Dans `mobile/.env`, `EXPO_PUBLIC_USE_MOCK=true` puis `npx expo start -c` : un adaptateur axios (`lib/mock/adapter.ts`) répond à la place du serveur, dans le format du contrat (§8), et un bandeau jaune « Mode maquette : données fictives » s'affiche. N'importe quel numéro et mot de passe non vides ouvrent une session factice (agent de police fictif, aucun compte ni secret) ; les informations sont quatre exemples sur deux pages, le PDF ne s'ouvre pas. **Développement seulement** : `__DEV__` vaut false dans un build de production, la variable y est ignorée. Pour ajouter un écran : une entrée dans `adapter.ts`, ses données dans `fixtures.ts` (données entièrement fictives). Quand l'endpoint est fusionné, mettre la variable à `false` : les écrans n'ont pas à changer.

**Compte de test** : seul le rôle `police` se connecte à l'API. Tant que l'écran Utilisateurs (W5) n'existe pas, en créer un depuis `php artisan tinker` (le mot de passe temporaire est généré et affiché ; il devra être changé à la première connexion, ce qui montre l'écran obligatoire) :

```php
$c = App\Models\Commissariat::create(['name' => 'Commissariat de test']);
$r = app(App\Services\Users\UserProvisioningService::class)->create(null, ['name' => 'Agent Test', 'phone' => '70 00 00 10', 'commissariat_id' => $c->id], App\Enums\RoleName::Police);
$r['temporary_password'];
```

**Vérifications** : `npm run typecheck` (TypeScript strict) et `npm run bundle` (compile le paquet Android sans appareil). Icône et écran de démarrage : **provisoires** (sigle VM) en attendant le logo du client ; identifiant Android `com.vigimoto.mobile` ; aucune configuration de build EAS (Expo Go seulement pour l'instant).

**Ajouter un écran** : un fichier dans `mobile/app/`, appels via `lib/api.ts`, types dans `types/api.ts`, et mise à jour du contrat (§8) si l'API change. Ne jamais appeler la base ni contourner l'API.

## 10. Répartition du travail

**Principe** (décidé par Moustapha, D31) : **Moustapha = mobile** (application Expo `mobile/`) **plus les écrans Web Audit et Système** (W3, W4, sans dépendance ni blocage client), **Amadou = Web** (Laravel : écrans Web **et API** de chaque module, selon la checklist du §6). Chacun travaille sur sa plateforme, **en parallèle** : le mobile n'attend pas la fin du Web, il avance sur le **contrat d'API** (§8). L'hébergement est **reporté** jusqu'au moment opportun : aucune tâche pour l'instant. Charge en points (1 = petite tâche, 5 = grosse) : Amadou 39 (W1, W2, W5 à W14), Moustapha 15 (W3, W4, M1, M2, M4, M5), total 54. La tâche M3 (authentification de la population) est supprimée par D32.

**Mobile sans attendre le Web** (PROPOSITION TECHNIQUE — À VALIDER, mis en place avec M2, voir §9 « Mode maquette ») : pour chaque module, le contrat d'API est écrit d'abord au §8 ; l'écran mobile est développé contre des réponses factices conformes à ce contrat (`EXPO_PUBLIC_USE_MOCK=true`, jamais dans un build de production) ; le passage à l'API réelle se fait quand l'endpoint est fusionné, sans changer les écrans.

### Déjà fait (socle)

Authentification Web et API, rôles, permissions à deux voies, audit, cloisonnement, superadmins automatiques, thème, tableau de bord et sidebar (maquette), profil, page de connexion, migrations, tests, application mobile de base (§2).

### Tâches

| # | Tâche | Charge | Dépend de | Bloquée par le client | Qui |
|---|---|---|---|---|---|
| W1 | Écran **Commissariats** | 3 | — | non | Amadou |
| W2 | Écran **Mairies** | 2 | W1 (même patron) | non | Amadou |
| W3 | Écran **Audit** | 3 | — | non | Moustapha |
| W4 | Page **Système** | 2 | — | non | Moustapha |
| W5 | Écran **Utilisateurs** | 5 | W1, W2 | non | Amadou |
| W6 | Module **Informations** (Web + API) | 3 | — | non | Amadou |
| W7 | Module **Propriétaires** | 3 | — | plusieurs motos par propriétaire, historique des changements | Amadou |
| W8 | Module **Motos** | 4 | W7 | identification (matricule / châssis, unicité) | Amadou |
| W9 | Module **Déclarations** (vol, braquage, autre) | 3 | W8 | — | Amadou |
| W10 | Module **Motos retrouvées** | 3 | W9 | qui l'enregistre ; contenu du SMS | Amadou |
| W11 | Module **Demandes VGT** (commissaire et mairie) + endpoint de contrôle d'une moto | 4 | W8, W9, W2 | règles de la vignette, workflow mairie | Amadou |
| W12 | Module **Paiements** | 3 | W11 | montant, qui confirme, statuts, code marchand de l'État | Amadou |
| W13 | Module **Retrait VGT** | 3 | W11, W12 | remise de la carte, « taxe à payer » | Amadou |
| W14 | Module **SMS** | 3 | W10, W12 | opérateur, langue, contenu | Amadou |
| M1 | Mobile police : **contrôle d'une moto** | 4 | endpoint livré avec W11 | « taxe à payer » du contrôle | Moustapha |
| M2 | Mobile police : **informations** | 1 | API de W6 | non | Moustapha |
| M4 | Mobile population : **demande de VGT** et suivi (sans compte) | 3 | API publique de W11, W12 | règles de la vignette, identification sans compte | Moustapha |
| M5 | Mobile population : **motos retrouvées** et informations (sans compte) | 2 | API publique de W10, W6 | non | Moustapha |

Hors périmètre pour l'instant : QR Code et Mobile Money (phase 1 ou plus tard, À VALIDER AVEC LE CLIENT), hébergement.

### Détail des tâches

Chaque tâche suit la checklist du §6 et s'accompagne de tests ; aucune ne modifie le socle sans revue. **Chaque tâche Web fournit l'API que consomment les tâches mobiles qui en dépendent** (contrôleur `Api/V1`, Resource, route, contrat §8), Les endpoints de la population sont **publics** (D32).

- **W1 Commissariats** (`commissariats.view/create/update/delete`) : liste DataTables, création et édition en modale (nom, code), activation/désactivation (désactiver coupe l'accès de tous les utilisateurs de l'institution : sessions et jetons supprimés), suppression en soft delete, `CommissariatPolicy`, FormRequests, `routes/web/commissariats.php`, audit. Le manifeste existe (`config/modules/commissariats.php`) : l'entrée de menu apparaît dès que la route `commissariats.index` existe (le menu Paramètres la liste déjà).
- **W2 Mairies** : identique à W1 (`config/modules/mairies.php`, route `mairies.index`).
- **W3 Audit** (`audit.view`) : liste filtrable (auteur, module, action, dates, acteur superadmin) et détail avec anciennes et nouvelles valeurs ; actions du superadmin signalées ; lecture seule.
- **W4 Système** (`system.view`, `system.maintain`) : environnement, base, file d'attente, jobs échoués en lecture seule ; actions de maintenance sur liste blanche (vider le cache), journalisées ; rien de destructeur.
- **W5 Utilisateurs** (`users.*`) : liste (`User::visibleTo`), création par `UserProvisioningService::create` (mot de passe temporaire affiché **une seule fois**), modification, activer/désactiver, réinitialiser le mot de passe, révoquer sessions et jetons, suppression (soft), changement d'institution (admin national et superadmin), `UserPolicy` (plafond de rôle, D9 : le commissaire ne gère que sa police). Touche au service de comptes du socle. Une fois livrée, elle permet de créer les comptes police pour tester le mobile (en attendant : `tinker`, §9).
- **W6 Informations** : le commissaire publie (description, fichier PDF, image), la mairie, la police et la population consultent (cahier §8, §9). Fournit l'endpoint de lecture consommé par M2 et M5, **public** (D32) : W6 introduit donc le limiteur par IP et l'entrée de la liste blanche du test d'architecture, à relire par les deux. Permissions à définir dans le manifeste (PROPOSITION TECHNIQUE — À VALIDER).
- **W7 Propriétaires** : fiche (nom, prénom, genre, adresse, téléphone identifié à son nom, contact en cas d'urgence), liste, recherche, modification, suppression ; cloisonné par commissariat.
- **W8 Motos** : matricule, couleur, genre ou marque, année de la VGT ; attestation de vente (vendeur, témoins si besoin) ; lien avec le propriétaire ; liste, recherche, modification, suppression.
- **W9 Déclarations** : vol, braquage ou autre (lieu, date, circonstances) ; la moto est marquée « Volée » dans la base consultable par tous les agents.
- **W10 Motos retrouvées** : enregistrement (lieu et date d'arrêt), récupération ; SMS automatique au propriétaire (via W14). Fournit l'endpoint de lecture consommé par M5.
- **W11 Demandes VGT** : côté commissaire (matricule, année, commissariat, mairie de retrait, contact SMS, code marchand) et côté mairie (formulaire VGT, validation, statuts, rejet). Publie la méthode « VGT à jour » sur `Moto` et livre l'**endpoint de contrôle** (matricule → moto volée ou non, VGT à jour) : lecture nationale via `acrossCommissariats()`, protégée par permission et auditée. Fournit aussi l'API de demande et de suivi consommée par M4.
- **W12 Paiements** : paiement par le code marchand de l'État, confirmation, statuts ; API consommée par M4.
- **W13 Retrait VGT** : date de retrait, aperçu et impression de la carte ; cas VGT en jour, non en jour, non enregistrée (taxe).
- **W14 SMS** : service d'envoi (opérateur à choisir avec le client), déclenché par les événements « moto retrouvée » et « paiement confirmé », en file d'attente, audité.
- **M1 Contrôle d'une moto** : écran mobile de saisie du matricule et résultat (volée ou non, vignette à jour), à partir de l'endpoint de contrôle livré avec W11.
- **M2 Informations (police)** : consultation des informations de W6 dans l'application. **Écran fait**, contre le contrat brouillon du §8 en mode maquette ; il passera à l'API réelle quand W6 sera fusionnée (Amadou valide le contrat d'abord).
- **M4 Demande de VGT (population)** : demande de renouvellement après un premier enregistrement, choix de la mairie, paiement, suivi, **sans connexion** (D32).
- **M5 Motos retrouvées et informations (population)** : consultation, **sans connexion** (D32).

### Ordre de travail de chacun

- **Amadou** : W1 → W2 → W5 → W6 → W7 → W8 → W9 → W11 → W12 → W10 → W13 → W14. W5 (comptes de test) et W6 (informations) passent en premier ; W11 et W12 avant W10 parce que M1 et M4 en dépendent.
- **Moustapha** : **W3 → W4 dès maintenant** (aucune dépendance, aucun blocage client), M2 (écran et mode maquette faits, en attente de l'endpoint de W6), puis M1 → M4 → M5, qui attendent des réponses du client (§5) et les endpoints d'Amadou.

Chemin critique : W7 → W8 → W9 → W11 (endpoint de contrôle) → M1, puis W12 → M4 et W10 → M5.

### Points de contact à figer entre vous

1. **Contrat d'API (§8), module par module** : Amadou rédige le contrat avant de coder l'endpoint, Moustapha le valide et code l'écran contre des réponses factices. Un contrat modifié après validation se discute avant fusion.
2. **Comptes de test** : Moustapha crée ses comptes `police` par `tinker` (§9) tant que W5 n'est pas livrée.
3. **Routes publiques (D32)** : le limiteur par IP et la liste blanche du test d'architecture sont du socle, relus par les deux ; la suppression éventuelle du rôle `population` aussi.

Les modèles (`Proprietaire`, `Moto`), la validité de la VGT et les événements du SMS restent chez Amadou : ils ne sont plus un point de contact entre vous.

### Collaboration

- Une branche par tâche `feature/<id>-<nom>` depuis `main` ; fusion après **revue de l'autre développeur** ; `main` reste vert (`composer test`, Pint, et `npm run typecheck` dans `mobile/`).
- Un manifeste `config/modules/<module>.php` par module, un seul propriétaire. Ne pas modifier `config/planned_modules.php` : l'entrée d'un module disparaît toute seule quand son manifeste existe.
- Migrations : jamais de modification d'une migration déjà fusionnée (en ajouter une) ; prévenir l'autre avant de fusionner une migration.
- Ce fichier : chacun ne met à jour que ses lignes du §10 et la section qu'il a fait évoluer ; le contrat d'API (§8) est mis à jour par l'auteur de l'endpoint et validé par l'autre.
- **Client** : Moustapha recueille les réponses du client aux points ouverts (§5) pour que les modules W7 à W14 ne s'arrêtent pas ; la colonne « Bloquée par le client » dit quelle question débloque quelle tâche.
