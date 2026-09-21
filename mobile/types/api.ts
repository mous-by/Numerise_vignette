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
 * Une information publiée par un commissaire (cahier §6 « Informations »). BROUILLON du contrat de W6 :
 * à valider par Amadou avant que l'endpoint existe (voir CLAUDE.md, §8).
 */
export interface Information {
  id: number;
  commissaire_name: string;
  commissariat_name: string;
  description: string | null;
  /** URL de l'image importée, ou null. */
  image_url: string | null;
  /** URL du fichier PDF importé, ou null. */
  document_url: string | null;
  published_at: string;
}
