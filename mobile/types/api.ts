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
