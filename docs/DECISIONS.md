# Registre des décisions — Numerise_vignette (socle commun)

Dernière mise à jour : 2026-09-21.

## Statut de la validation

Le dossier d'architecture finale (`ARCHITECTURE.md`) a été présenté avec 6 points à confirmer. L'utilisateur a répondu « oui » à l'ouverture de la session dans le nouveau dossier ; ce « oui » est interprété comme la validation de l'architecture **avec les recommandations proposées** pour ces 6 points. À reconfirmer explicitement en cas de doute.

## Décisions validées définitivement

| Décision | Contenu |
|---|---|
| Séparation des rôles | SUPERADMIN (technique) et ADMINISTRATEUR_NATIONAL (fonctionnel) sont deux rôles distincts |
| Superadmin | Accès global en lecture et écriture, toutes institutions, données métier, gestion technique, utilisateurs/rôles/permissions, maintenance, audit. Deux comptes individuels : Moustapha BARRY et Amadou KAREMBE. Aucun compte partagé. Toutes ses actions sont journalisées et visibles dans l'audit |
| Admin national | Administration fonctionnelle nationale ; gère commissariats, mairies et utilisateurs autorisés ; aucun privilège technique (pas de `system.*`) ; ne gère pas les Superadmins ; accès aux modules métier selon les permissions définies par les modules |
| Hiérarchie | Superadmin > Admin national > Commissariats (Commissaire > Police) et Mairies (Agents mairie). Population = profil externe |

## Décisions D1 à D18 (retenues, validées avec l'architecture)

| # | Décision |
|---|---|
| D1 | MariaDB en développement, compatibilité MySQL/MariaDB en production (migrations sur le sous-ensemble commun ; le CHECK est un filet, la validation applicative est le garde principal) |
| D2 | Laravel 13, PHP >= 8.3, Spatie Permission, Sanctum |
| D3 | Noms métier en français, colonnes et actions techniques en anglais |
| D4 | Connexion Web par `username` |
| D5 | Pas d'e-mail dans le socle |
| D6 | Permissions par rôle + exceptions additives ; définies dans le code, jamais créées depuis l'interface |
| D7 | Un manifeste par module + un fichier de routes par module |
| D8 | Cloisonnement fail-closed |
| D9 | Le commissaire crée et gère les comptes POLICE de son commissariat ; admin national et superadmin aussi, selon permissions |
| D10 | Superadmin créé par commande Artisan, aucun mot de passe dans Git, dernier superadmin actif protégé |
| D11 | Audit complet, synchrone, non modifiable, actions superadmin visibles |
| D12 | Sanctum avec Bearer Tokens ; pas de mode SPA/cookie |
| D13 | Session Web de 120 minutes |
| D14 | Mot de passe >= 8 caractères, lettres et chiffres ; changement obligatoire du mot de passe temporaire |
| D15 | Assets locaux, pas de CDN ; vérifier la licence du template ORGEST avant toute réutilisation |
| D16 | Le superadmin lit ET écrit les données métier (maintenance) ; toute modification est auditée |
| D17 | Authentification de la population reportée au module Population |
| D18 | `docs/HOW_TO_ADD_MODULE.md` (à écrire avec le socle) |

## Décisions D19 à D21 (2026-09-21, fin de l'étape 1)

| # | Décision |
|---|---|
| D19 | Pipeline frontend du squelette Laravel supprimé (Vite, Tailwind, Bunny Fonts, npm). Vérifié avant suppression : aucun fichier du dépôt ne consommait le build (aucun `@vite`). Blade + Bootstrap 5 + assets locaux dans `public/assets/`, aucun CDN. Une dépendance Node ne sera réintroduite que si la stratégie d'assets l'exige (décision explicite) |
| D20 | L'interface doit reproduire visuellement et structurellement ORGEST (layout, sidebar, topbar, thème « Semi Bleu », composants), avec une architecture et un code indépendants. ORGEST reste en lecture seule. Supprimer Tailwind/Bunny ne signifie pas abandonner le design ORGEST. Aucun asset soumis à licence n'est copié avant confirmation du droit de réutilisation |
| D21 | Migrations : la conception détaillée est présentée et validée par l'utilisateur AVANT toute exécution de `php artisan migrate`. Les migrations du squelette (`users` avec e-mail, `password_reset_tokens`) ne sont jamais exécutées |

## Les 6 points de l'architecture finale (recommandations retenues)

1. **Licence Dashkote** : à vérifier auprès de la personne qui l'a acquise pour ORGEST. Ne bloque pas le socle : les vues de contenu sont en Bootstrap 5 standard, seules `layouts/admin.blade.php` et `partials/` dépendent du thème. Aucun fichier Dashkote n'est copié avant confirmation. *Mise à jour 2026-09-21 : l'utilisateur indique que le template d'ORGEST est gratuit. Non confirmé à ce jour : l'en-tête de `app.css` et `icons.css` porte « Dashkote Admin / codervent » et Dashkote est vendu sur CodeCanyon (licence Envato : un produit final par licence). La porte de licence reste fermée jusqu'à une preuve d'origine (reçu / code d'achat Envato, ou source d'une version gratuite).*
2. `users.username` **nullable**, unique (obligatoire pour le personnel, validé côté application) pour ne pas modifier `users` au module Population (D4 + D17).
3. **Défauts des rôles** : voir le tableau de `ARCHITECTURE.md` §7 ; la suppression est réservée au superadmin, attribuable en exception.
4. **« Audit complet »** = toutes les écritures + authentification + opérations techniques. Les lectures ne sont pas journalisées dans le socle (`ActivityLogger::read()` disponible, chaque module décide).
5. **Jetons mobile** : durée de vie 30 jours, paramétrable.
6. **Pas de table `settings`** dans le socle.

## Points ouverts — client (À VALIDER AVEC LE CLIENT)

Non bloquants pour le socle :
- Durée de conservation des journaux d'audit.
- Nombre de commissaires par commissariat.
- Canal de remise du mot de passe temporaire (main propre, SMS plus tard).
- Champs additionnels des institutions (adresse, téléphone, région, commune).
- Création des agents de mairie : admin national et superadmin uniquement (pas de « responsable de mairie » dans le cahier).

Bloquants pour les modules métier (à traiter avant le MCD métier) :
- Comptes/authentification de la population (D17).
- Règles de la vignette : durée de validité, tarifs, arriérés.
- Paiement : montant, qui confirme, statut, rôle du « code marchand de l'État ».
- Qui enregistre « moto retrouvée » : commissaire, police ou les deux.
- Workflow mairie : statuts, rejet, confirmation de remise de la carte.
- Nature et effet de la « taxe à payer » du contrôle de police.
- Identification de la moto : matricule seul ou châssis en plus ; unicité.
- Un propriétaire peut-il avoir plusieurs motos ; historique des changements de propriétaire.
- Opérateur SMS, langue et contenu des messages.
- QR Code et Mobile Money : phase 1 ou plus tard.
