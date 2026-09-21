# Architecture du socle commun — Numerise_vignette (v2, décisions intégrées)

Ce document décrit le SOCLE COMMUN. Aucun module métier (propriétaires, motos, déclarations, VGT, paiements, contrôles, motos retrouvées) n'en fait partie. Les décisions sont dans `DECISIONS.md`. ORGEST (`/opt/lampp/htdocs/ORGEST`) est une référence en lecture seule.

## 1. Architecture générale

```
Navigateur PC (Blade + Bootstrap) ─► routes/web.php ─► session (guard web) ──┐
                                                                             ├─► Middleware ─► Controllers ─► Services / Policies ─► Models ─► base
App React Native ─► routes/api.php (/api/v1) ─► Sanctum (Bearer token) ──────┘   (actif, canal,     (minces)     (logique partagée         ▲
                                                                                  mot de passe)                    Web + API)                │
                                                                                                                    ActivityLogger ─► activity_logs
```

1. Un dépôt, deux portes : Web par session, mobile par jeton. Elles ne se mélangent jamais.
2. La logique métier vit dans des Services appelés à l'identique par le Web et l'API.
3. Autorisation en quatre couches : canal (web/api) → permission → Policy (dont plafond de rôle) → cloisonnement institutionnel.
4. Le SUPERADMIN contourne l'autorisation (`Gate::before`), pas la logique métier ni l'audit : mêmes Services, toute écriture journalisée (D16).
5. L'audit est écrit dans la même transaction que l'action.
6. Modules par manifeste : un module s'ajoute sans modifier les fichiers du socle.

## 2. Tables du socle

| Table | Origine |
|---|---|
| `users` | Laravel, adaptée |
| `commissariats`, `mairies` | nouvelles |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Spatie (`teams` désactivé) |
| `personal_access_tokens` | Sanctum |
| `activity_logs` | nouvelle |
| `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | Laravel (sessions, cache et file d'attente en base) |

Exclus : `password_reset_tokens` (D5) et toute table métier.

## 3. Relations

```
commissariats 1 ──< users >── 1 mairies     users.commissariat_id | users.mairie_id (nullables, jamais les deux)
users >──< roles                             exactement 1 rôle par utilisateur (règle applicative)
roles >──< permissions                       défauts par rôle (D6)
users >──< permissions                       exceptions additives (D6)
users 1 ──< personal_access_tokens           (morph tokenable)
users 1 ──< activity_logs                    user_id nullable (console, connexion échouée)
activity_logs >── objet quelconque           (morph subject_type / subject_id)
```

Clés étrangères vers les institutions : `RESTRICT`. Institutions et utilisateurs se suppriment en soft delete uniquement, pour ne jamais orpheliner l'historique d'audit.

## 4. Structure User

| Colonne | Type | Rôle |
|---|---|---|
| `id` | bigint | |
| `name` | string(150) | Nom complet affiché |
| `username` | string(60), unique, nullable | Identifiant de connexion (D4). Obligatoire pour le personnel (validé côté application). Nullable pour ne pas modifier `users` au module Population (D17) |
| `phone` | string(20), unique, nullable | Contact, normalisé E.164 (+223…) |
| `password` | string, cast `hashed` | Règle D14 : >= 8 caractères, lettres et chiffres |
| `is_active` | boolean, défaut `true` | |
| `must_change_password` | boolean | Vrai après création ou réinitialisation par un supérieur |
| `password_changed_at` | timestamp nullable | |
| `commissariat_id`, `mairie_id` | FK nullables | |
| `last_login_at` | timestamp nullable | Repérer les comptes dormants |
| `remember_token`, `created_at`, `updated_at`, `deleted_at` | standard | « Se souvenir de moi » non exposé |

Absents : `email` (D5) et `photo`.

| Rôle | `commissariat_id` | `mairie_id` |
|---|---|---|
| `superadmin`, `admin_national`, `population` | NULL | NULL |
| `commissaire`, `police` | obligatoire | NULL |
| `mairie` | NULL | obligatoire |

Contraintes : unicité de `username` et `phone`, CHECK « pas les deux institutions », un seul rôle par utilisateur.

Le modèle `User` n'utilise PAS le trait de cloisonnement automatique (un scope global dépendant de l'utilisateur connecté créerait une boucle au chargement de cet utilisateur). La visibilité des comptes passe par `User::visibleTo($acteur)` et `UserPolicy`.

## 5. Institutions

`commissariats` et `mairies` sont identiques :

| Colonne | Notes |
|---|---|
| `id` | |
| `name` (unique) | Le cahier parle du « nom du commissariat / de la mairie » (p.3, 5) |
| `code` (unique, nullable) | Référence courte, pas un identifiant de connexion |
| `is_active` | Désactiver une institution bloque tous ses utilisateurs |
| `timestamps`, `deleted_at` | |

Adresse, téléphone, région et commune : À VALIDER AVEC LE CLIENT, ajoutés avec les modules qui en ont besoin. Pas d'institution « nationale » (l'admin national est un rôle), pas de hiérarchie entre institutions. Évolutivité : deux clés étrangères nullables ; une troisième institution demandera une colonne, un trait de cloisonnement et un manifeste.

## 6. Hiérarchie des rôles

```
SUPERADMIN (100) ── technique, global, lecture + écriture, audité
   └─ ADMINISTRATEUR_NATIONAL (80) ── fonctionnel national, permissions explicites
         ├─ COMMISSARIATS ─ COMMISSAIRE (50) ─ POLICE (30)
         └─ MAIRIES ─ AGENT MAIRIE (50)
POPULATION (10) ── profil externe, hors hiérarchie
```

| Rôle | Institution | Périmètre des données | Canal | Créé par |
|---|---|---|---|---|
| `superadmin` | aucune | Global | Web | Commande Artisan, ou autre superadmin |
| `admin_national` | aucune | National, selon permissions | Web | Superadmin |
| `commissaire` | commissariat | Son commissariat | Web | Admin national, superadmin |
| `mairie` (agent) | mairie | Sa mairie | Web | Admin national, superadmin |
| `police` | commissariat | Son commissariat pour la gestion ; lecture nationale des motos volées au contrôle (règle du module métier) | API mobile | Commissaire (son commissariat), admin national, superadmin |
| `population` | aucune | Ses propres données | API mobile | Module Population (D17) |

Plafond de rôle (`config/role_hierarchy.php`, centralisé dans les Policies) : `superadmin` → tous ; `admin_national` → commissaire, mairie, police ; `commissaire` → police de son commissariat ; `mairie`, `police`, `population` → aucun. Personne n'agit sur un compte de niveau supérieur ou égal au sien, sauf le superadmin.

## 7. Permissions

Convention `module.action` (modules en français, actions en anglais).

| Module | Permissions |
|---|---|
| `users` | `view`, `create`, `update`, `delete`, `activate`, `reset_password`, `revoke_access` |
| `commissariats` | `view`, `create`, `update`, `delete` |
| `mairies` | `view`, `create`, `update`, `delete` |
| `roles` | `view` (réservée) |
| `permissions` | `assign` (réservée) |
| `audit` | `view` (réservée) |
| `system` | `view`, `maintain` (réservées) |

Permissions réservées : jamais attribuables à un autre que le superadmin (les outils d'attribution les refusent). C'est ce qui garantit l'absence d'accès `system.*` pour l'admin national.

Défauts par rôle au socle :

| Rôle | Défauts | Attribuables en exception |
|---|---|---|
| `superadmin` | tout (`Gate::before`) | — |
| `admin_national` | `users.view/create/update/activate/reset_password/revoke_access` ; `commissariats.view/create/update` ; `mairies.view/create/update` | `users.delete`, `commissariats.delete`, `mairies.delete` |
| `commissaire` | `users.view/create/update/activate/reset_password/revoke_access` (police de son commissariat) | `users.delete` |
| `mairie`, `police`, `population` | aucun | — |

Fonctionnement (D6, D7) :
- Un manifeste par module, `config/modules/<module>.php` : `label`, `permissions`, par rôle `defaults` et `optional`, et `navigation` (entrées de menu). Les modules du socle en ont aussi.
- `php artisan permissions:sync`, idempotente : crée les permissions manquantes, resynchronise exactement les permissions de chaque rôle avec les manifestes, laisse intactes les exceptions directes des utilisateurs, signale les orphelines sans les supprimer, purge le cache Spatie. À lancer à chaque déploiement, après `migrate`.
- Aucune permission ne se crée depuis l'interface. L'écran des rôles est en lecture seule ; l'ajustement des exceptions par utilisateur est réservé au superadmin (`permissions.assign`).
- Toutes les routes sont protégées par `permission:module.action` (pas de `role:` dans les routes).
- Limite connue de D6 : une permission héritée d'un rôle ne peut pas être retirée à un seul utilisateur.

## 8. Audit : `activity_logs`

| Colonne | Contenu |
|---|---|
| `id` | |
| `user_id` (FK nullable) | Auteur ; NULL pour console ou connexion échouée |
| `user_name`, `user_role` | Copie figée de l'auteur au moment de l'action |
| `commissariat_id`, `mairie_id` | Institution de l'auteur au moment de l'action |
| `action` | Verbe normé : `auth.login`, `users.updated`, `vgt.validated` |
| `module` | `auth`, `users`, `commissariats`… |
| `description` | Phrase lisible en français |
| `subject_type`, `subject_id` | Objet concerné (alias morph stables) |
| `subject_label` | Libellé figé, ex. « VGT #4587 » |
| `old_values`, `new_values` (JSON) | Seulement les champs modifiés ; jamais mot de passe, `remember_token` ou jeton |
| `channel` | `web`, `api`, `console` |
| `ip_address`, `user_agent` | |
| `created_at` | Indexé. Pas de `updated_at` |

Index : `(user_id, created_at)`, `(module, created_at)`, `(subject_type, subject_id)`, `action`.

Règles (D11, D16) :
- Synchrone, dans la même transaction que l'action.
- Non modifiable : le modèle refuse `update` et `delete`, aucune route ne supprime un log. En production, le compte applicatif n'a pas les droits UPDATE/DELETE sur cette table.
- Service `ActivityLogger` + trait `LogsActivity` (création, modification, suppression avec anciennes et nouvelles valeurs). Les actions métier s'appellent explicitement.
- Garde-fou : un test d'architecture échoue si un modèle métier n'utilise pas `LogsActivity`. Les mises à jour de masse (`update()` sur le builder) ne déclenchent pas les événements de modèle : interdites sans journalisation explicite.
- Superadmin visible : rôle figé dans chaque ligne, filtre « acteur superadmin », signalement visuel.
- Journalisé dès le socle : connexions (réussies, échouées — identifiant tenté, jamais le mot de passe —, déconnexions), changement et réinitialisation de mot de passe, cycle de vie des utilisateurs, changement de rôle, permissions, révocation d'accès, CRUD des institutions, opérations de maintenance.
- Lectures de données non journalisées dans le socle (`ActivityLogger::read()` disponible).
- Durée de conservation : À VALIDER AVEC LE CLIENT.

## 9. SUPERADMIN

- Accès global lecture + écriture via `Gate::before`, toutes institutions, données métier comprises.
- Mêmes Services que les autres : il contourne l'autorisation, pas la validation métier ni l'audit.
- Création : `php artisan app:create-superadmin` (nom, identifiant, mot de passe saisis de façon interactive, règle D14). Aucun mot de passe dans Git ni dans un seeder. Moustapha BARRY et Amadou KAREMBE ont chacun leur compte, identifiant choisi à l'exécution (par exemple `superadmin_1`, `superadmin_2`). Chaque développeur a sa base locale ; les deux comptes sont créés dans les environnements partagés (test, production).
- Protections : impossible de désactiver ou supprimer le dernier superadmin actif ; comptes superadmin invisibles et intouchables pour les autres rôles.
- Interface : utilisateurs (activer/désactiver, réinitialiser, révoquer sessions et jetons), catalogue des rôles et permissions, exceptions par utilisateur, audit, page `system` en lecture seule (environnement, base, file d'attente, jobs échoués), actions de maintenance sur liste blanche (vider le cache…), toutes journalisées. Rien de destructeur depuis l'interface.
- Hors socle : impersonation, double authentification (recommandée plus tard), table `settings`.
- Création de données comme superadmin : l'institution est indiquée explicitement.

## 10. ADMINISTRATEUR_NATIONAL

- Gère commissariats, mairies et les comptes `commissaire`, `mairie`, `police`.
- Aucun privilège technique : ni `system.*`, ni `roles.*`, ni `permissions.*`. Ne voit pas et ne gère pas les Superadmins, ne crée pas d'autre admin national.
- Vue nationale : pas d'institution, données non cloisonnées ; ce qu'il consulte dans les modules métier dépend des permissions que les modules définissent dans leurs manifestes.
- `audit.view` reste réservée au superadmin dans le socle.
- Plusieurs admins nationaux possibles, tous créés par un superadmin.

## 11. Comptes institutionnels

- Création d'une institution (admin national ou superadmin), puis de son premier compte responsable avec un mot de passe temporaire généré, affiché une seule fois à l'auteur, `must_change_password = true`.
- Le commissaire gère sa police (D9) : créer, modifier, activer/désactiver, réinitialiser, révoquer. La Policy vérifie que la cible est un `police` du même commissariat et force `commissariat_id`.
- Agents de mairie : créés et gérés par l'admin national et le superadmin uniquement.
- Un utilisateur = une institution. Changement d'institution réservé à l'admin national et au superadmin, journalisé.
- Désactiver une institution : refus de connexion pour tous ses utilisateurs, suppression de leurs sessions et de leurs jetons Sanctum.
- Cloisonnement fail-closed : traits `BelongsToCommissariat` et `BelongsToMairie` (avec une classe de scope dédiée) à appliquer aux modèles métier.

| Utilisateur | Effet |
|---|---|
| `superadmin`, `admin_national` | pas de filtre |
| avec `commissariat_id` (ou `mairie_id`) | filtré sur son institution |
| `population` ou tout compte sans institution | zéro résultat (à l'inverse d'ORGEST, où l'absence de `bureau_id` signifie « tout voir ») |

Console et files d'attente ne sont pas filtrées. Les lectures inter-institutions légitimes (contrôle de police sur les motos volées) passent par un contournement explicite et repérable, protégé par permission et audité.

## 12. Routes Web

Session (guard `web`), CSRF actif. Chaque route est protégée par `permission:module.action` et par Policy. Un fichier de routes par module (`routes/web/*.php`, chargés automatiquement).

| Route | Méthode | Protection |
|---|---|---|
| `/login` | GET, POST | invité, 5 tentatives max |
| `/logout` | POST | connecté |
| `/` | GET | connecté |
| `/password/change` | GET, PUT | connecté (même si `must_change_password`) |
| `/profile`, `/profile/password` | GET, PUT | connecté |
| `/users` (resource) | index, create, store, show, edit, update | `users.view/create/update` |
| `/users/{user}/activate`, `/deactivate` | PATCH | `users.activate` |
| `/users/{user}/reset-password` | POST | `users.reset_password` |
| `/users/{user}/revoke-access` | POST | `users.revoke_access` |
| `/users/{user}` | DELETE | `users.delete` (soft delete) |
| `/users/{user}/permissions` | GET, PUT | `permissions.assign` |
| `/commissariats`, `/mairies` (resources) | CRUD | `commissariats.*` / `mairies.*` |
| `/roles` | GET | `roles.view` (lecture seule) |
| `/audit`, `/audit/{log}` | GET | `audit.view` |
| `/system`, `/system/cache/clear` | GET, POST | `system.view` / `system.maintain` |
| `/up` | GET | santé Laravel |

Le middleware `password.changed` redirige vers `/password/change` tant que `must_change_password` est vrai.

## 13. Routes API

Préfixe `/api/v1`, JSON uniquement, jetons Bearer, sans session ni CSRF.

| Route | Méthode | Auth | Rôle |
|---|---|---|---|
| `/auth/login` | POST | public, limitation de tentatives | Identifiant, mot de passe, `device_name` → jeton + utilisateur + permissions |
| `/auth/logout` | POST | jeton | Révoque le jeton courant |
| `/auth/me` | GET | jeton | Utilisateur, rôle, institution, permissions |
| `/auth/password` | PUT | jeton (même restreint) | Change le mot de passe, révoque les autres jetons |
| `/health` | GET | public | Disponibilité |

Erreurs : 401 non authentifié, 403 (compte ou institution désactivés, canal interdit, changement de mot de passe requis), 422 validation, 429 limitation. Contrat complet versionné dans `docs/API_CONTRACT.md`. La connexion de la population n'est pas construite (D17).

## 14. Sanctum

- Bearer tokens uniquement (D12). Pas de mode SPA/cookie.
- Dans `config/sanctum.php`, le paramètre `guard` est vidé : une requête API n'est jamais authentifiée par un cookie de session.
- Pile d'une route API : `auth:sanctum` → `active` (utilisateur et institution actifs) → `channel:api` → `throttle`.
- Canaux par rôle (`config/channels.php`) : `police` → API ; `population` → API (activé par le module Population) ; `superadmin`, `admin_national`, `commissaire`, `mairie` → Web.
- Un jeton par appareil (`device_name`), `last_used_at` natif. Durée de vie 30 jours, paramétrable.
- Mot de passe temporaire : le jeton émis n'a que la capacité `password:change`. Sinon capacité `*`, et les vérifications réelles restent les permissions Spatie.
- Désactivation d'un utilisateur ou d'une institution : jetons révoqués, sessions supprimées.
- Risque à tester dès le socle : le couple Sanctum + Spatie (rôles sur le guard `web`, requêtes sous `auth:sanctum`).
- CORS non ouvert (application native).

## 15. Arborescence Laravel

```
Numerise_vignette/
├─ app/
│  ├─ Console/Commands/          CreateSuperadmin, SyncPermissions
│  ├─ Enums/                     RoleName, ActivityChannel
│  ├─ Http/
│  │  ├─ Controllers/
│  │  │  ├─ Web/                 Auth/, HomeController, ProfileController,
│  │  │  │                       Admin/{User,Commissariat,Mairie,Role,UserPermission,ActivityLog,System}Controller
│  │  │  └─ Api/V1/              Auth/AuthController, HealthController
│  │  ├─ Middleware/             EnsureUserIsActive, EnsureChannelAllowed, EnsurePasswordChanged, ForceJson
│  │  ├─ Requests/               Web/, Api/V1/
│  │  └─ Resources/              UserResource…
│  ├─ Models/                    User, Commissariat, Mairie, ActivityLog
│  │  └─ Concerns/               BelongsToCommissariat, BelongsToMairie, LogsActivity
│  ├─ Policies/                  UserPolicy, CommissariatPolicy, MairiePolicy
│  ├─ Providers/                 AppServiceProvider (Gate::before), ModuleServiceProvider (lit config/modules/*.php)
│  └─ Services/                  Audit/ActivityLogger, Users/UserProvisioningService, Auth/…
├─ config/
│  ├─ modules/                   users.php, commissariats.php, mairies.php, audit.php, …  (un manifeste par module)
│  ├─ role_hierarchy.php         niveaux et plafond de rôle
│  ├─ channels.php               canaux web/api par rôle
│  └─ sanctum.php, permission.php
├─ database/{migrations, seeders (rôles seulement, aucun mot de passe), factories}
├─ docs/                         ARCHITECTURE.md, DECISIONS.md, HOW_TO_ADD_MODULE.md, API_CONTRACT.md, MCD…
├─ public/assets/                template (fichiers locaux)
├─ resources/views/{layouts, partials, auth, admin/…, profile, errors}
├─ routes/{web.php, api.php, console.php} + web/*.php + api/v1/*.php    (un fichier par module)
└─ tests/{Feature, Unit, Architecture}
```

Tests livrés avec le socle : authentification (connexion, déconnexion, limitation, compte inactif, mot de passe temporaire) ; règles rôle ↔ institution ; plafond de rôle ; protection du dernier superadmin ; cloisonnement fail-closed (population comprise) ; une ligne d'audit par action sensible ; cycle de vie des jetons ; compatibilité Sanctum + Spatie ; idempotence de `permissions:sync` ; tests d'architecture (tout modèle métier utilise `LogsActivity`, aucune URL externe dans les vues).

## 16. Intégration du template ORGEST

Faits établis :
- Le template d'ORGEST est « Dashkote Admin » de codervent (en-tête de `public/assets/css/app.css`), template commercial vendu sur CodeCanyon/Envato. Aucun fichier de licence dans `public/assets`. Les conditions exactes n'ont pas été lues : à vérifier sur la page du produit et auprès de la personne qui a acquis la licence pour ORGEST.
- `public/assets/docs` d'ORGEST contient des captures d'une autre application (chauffeurs, véhicules…) : ne pas copier.
- `app.css` importe une police Google Fonts : à héberger localement.

Porte de licence : aucun fichier Dashkote n'est copié tant que la licence n'est pas confirmée. Issues possibles : (A) licence confirmée ou acquise → réutilisation de l'habillage ORGEST ; (B) thème sous licence libre, rendu différent ; (C) en attendant, Bootstrap 5 standard.

Découplage : seuls `layouts/admin.blade.php` et `partials/` dépendent du thème. Les vues de contenu sont écrites en Bootstrap 5 standard.

| Fichier de référence (ORGEST, relatif à sa racine) | Action | Notes |
|---|---|---|
| `resources/views/layouts/admin.blade.php` | Adapté | Squelette conservé ; retrait de `formaterMontantEnDirect`, `imprimerFacture`, `facture_url` et de `partials.pwa` ; alertes Swal `status`/`error` conservées |
| `resources/views/partials/head.blade.php` | Adapté | Titre/nom d'application ; retrait des balises PWA, de la police « Rye » et de tous les CDN (unpkg, jsdelivr, code.jquery.com, Google Fonts) |
| `resources/views/partials/foot.blade.php` | Presque copié | Réglages DataTables en français, select2 |
| `resources/views/partials/sidebar.blade.php` | Réécrit | Logo Numerise_vignette ; menu généré depuis les manifestes |
| `resources/views/partials/navbar.blade.php` | Adapté | Suppression du bloc `@php` `JourneeFinanciere` ; cloche = emplacement générique ; menu utilisateur avec rôle et institution |
| `resources/views/partials/footer.blade.php`, `theme-customizer.blade.php` | Copié / adapté | |
| `partials/pwa.blade.php`, `public/manifest.webmanifest`, `public/sw.js` | Non repris | PWA absente du cahier |
| `resources/views/auth/login.blade.php` | Réécrit (même style) | Contenu propre à ORGEST |
| `app/Http/Controllers/Auth/AuthController.php`, `app/Http/Requests/Auth/LoginRequest.php` | Adapté | 5 tentatives (`LoginRequest.php:60`), refus si compte inactif (`:47`), session régénérée ; identifiant = `username` |
| `ProfileController`, `resources/views/profile/edit.blade.php` | Adapté | |
| `UserController`, `users/`, `permissions/`, `user-permissions/` | Réécrits sur le nouveau modèle | Plafond de rôle dans une Policy, pas de création de permission |
| `public/assets/*` (plugins, css, js, fonts, icons) | Copié sélectivement, après la porte de licence | Seulement ce que le layout charge ; sans `docs/`, images ni branding ORGEST ; `monCss/` et `mon_js/` inspectés avant reprise |
| Vues et contrôleurs métier ORGEST (home, pdf, achats, stock, ventes, trésorerie, crédits, barèmes, clients, bureaux) | Non repris | |

Points ORGEST à ne pas reproduire : `AppServiceProvider.php:26` (`Password::min(3)`), `BelongsToBureau` (l'absence d'institution ne doit jamais signifier « tout voir »), `RoleSeeder.php:47-52` (superadmin avec mot de passe codé en dur).

## 17. HOW_TO_ADD_MODULE.md (à écrire avec le socle)

1. Principe : un module ne modifie jamais les fichiers du socle.
2. Checklist : manifeste `config/modules/<module>.php` → `php artisan permissions:sync` → migrations (clé d'institution, `softDeletes`, index) → modèle avec trait de cloisonnement et `LogsActivity` → Policy → FormRequests → Service partagé Web/API → contrôleur Web + `routes/web/<module>.php` → vues (`@extends('layouts.admin')`, Bootstrap 5 standard) → API (`Api/V1`, Resource, `routes/api/v1/<module>.php`, contrat mis à jour) → audit explicite des actions métier → tests obligatoires (permission, cloisonnement, audit).
3. Conventions : nommage, format des permissions, verbes d'audit, réponses API.
4. Interdits : contourner le scope sans le signaler, créer des permissions depuis l'interface, `update()` de masse sans audit, secrets dans le dépôt, ressources externes (CDN).
5. Cas particuliers : lecture inter-institutions (contrôle de police), écritures du superadmin.
6. Collaboration : propriété des fichiers, changements du socle revus par les deux développeurs, branches `feature/<module>`.
7. Exemple complet d'un mini-module.

## Ordre de construction du socle

1. Squelette Laravel (dossier temporaire puis fusion), dépendances, configuration, dépôt Git indépendant.
2. Migrations.
3. Modèles.
4. Commandes `app:create-superadmin` et `permissions:sync`.
5. Authentification Web.
6. Audit.
7. Institutions et utilisateurs.
8. API et Sanctum.
9. Layouts et partials.
10. Tests.
11. Documentation (`HOW_TO_ADD_MODULE.md`, `API_CONTRACT.md`).
