// Contrat de l'API VigiMoto v1 (voir CLAUDE.md, §8).

export interface Role {
  name: string;
  label: string;
}

export interface Institution {
  type: 'commissariat' | 'mairie';
  id: number;
  name: string;
}

export interface User {
  id: number;
  name: string;
  phone: string;
  role: Role | null;
  institution: Institution | null;
  must_change_password: boolean;
  permissions: string[];
}

/** Réponse de POST /auth/login et de PUT /auth/password. */
export interface AuthResponse {
  token: string;
  token_type: 'Bearer';
  expires_at: string;
  abilities: string[];
  password_change_required: boolean;
  user: User;
}

/** Corps d'erreur JSON de l'API. */
export interface ApiErrorBody {
  message?: string;
  code?: 'account_disabled' | 'channel_forbidden' | 'password_change_required' | 'forbidden';
  errors?: Record<string, string[]>;
}

/** Réponse paginée standard des ressources Laravel (seule `meta` est utilisée par l'application). */
export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number };
}

/**
 * Une information publiée par un commissaire (cahier §6 « Informations »). Contrat W6 validé (voir CLAUDE.md, §8) :
 * plusieurs images et plusieurs PDF par publication (glisser-déposer côté Web, au-delà du cahier qui n'en prévoit
 * qu'un de chaque) — `image_urls`/`document_urls` sont toujours des tableaux, vides si aucune pièce de ce type.
 */
export interface Information {
  id: number;
  commissaire_name: string;
  commissariat_name: string;
  description: string | null;
  /** URLs des images importées ; tableau vide si aucune. */
  image_urls: string[];
  /** URLs des fichiers PDF importés ; tableau vide si aucun. */
  document_urls: string[];
  published_at: string;
}

/**
 * Résultat de GET /controles (M1, cahier §4 Police, §8) : contrôle d'une moto par son matricule en patrouille.
 * Aucune donnée personnelle du propriétaire — uniquement les deux statuts demandés par le cahier.
 */
export interface ControleResult {
  matricule: string;
  volee: boolean;
  vgt_a_jour: boolean;
}

/**
 * Contrat de la population, routes publiques sans compte (D32, §8, consommé par M4 et M5). Aucune donnée
 * personnelle du propriétaire n'est jamais renvoyée.
 */
export interface PublicMotoRetrouvee {
  id: number;
  matricule: string;
  genre: string;
  couleur: string;
  lieu: string;
  date_arret: string;
  commissariat: string;
}

export interface PublicMairie {
  id: number;
  nom: string;
}

export type DemandeVgtStatusCode = 'en_attente' | 'validee' | 'rejetee' | 'payee' | 'retiree';

export interface DemandeVgt {
  reference: string;
  annee: number;
  statut: { code: DemandeVgtStatusCode; libelle: string };
  motif_rejet: string | null;
  montant: { base: number; majoration: number; total: number };
  mairie: { id: number; nom: string | null };
  paiement_confirme_le: string | null;
  retrait_le: string | null;
  cree_le: string;
}

/** Réponse de POST /demandes-vgt. */
export interface StoreDemandeVgtResponse {
  data: DemandeVgt;
}

/** Réponse de POST /demandes-vgt/suivi. */
export interface SuiviDemandeVgtResponse {
  matricule: string;
  vgt_a_jour: boolean;
  data: DemandeVgt[];
}
