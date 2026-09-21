import { AxiosError, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios';
import type { AuthResponse, Paginated } from '@/types/api';
import { MOCK_INFORMATIONS, MOCK_USER } from './fixtures';

// Adaptateur axios du mode maquette (voir config.ts) : il répond à la place du serveur, dans le format du contrat d'API
// (CLAUDE.md, §8). Il ne vérifie aucun identifiant : n'importe quel numéro et mot de passe non vides ouvrent une session
// factice, aucun compte ni secret n'existe ici.

const PER_PAGE = 3;
const LATENCY_MS = 350; // fait apparaître les indicateurs de chargement comme sur un vrai réseau

function respond<T>(config: InternalAxiosRequestConfig, status: number, data: T): Promise<AxiosResponse<T>> {
  const response: AxiosResponse<T> = { data, status, statusText: String(status), headers: {}, config, request: {} };

  return new Promise((resolve, reject) => {
    setTimeout(() => {
      if (status >= 200 && status < 300) {
        resolve(response);
      } else {
        reject(new AxiosError(`Request failed with status code ${status}`, AxiosError.ERR_BAD_REQUEST, config, {}, response));
      }
    }, LATENCY_MS);
  });
}

function authResponse(): AuthResponse {
  const expires = new Date(Date.now() + 30 * 24 * 3_600_000).toISOString();

  return { token: 'mock-token', token_type: 'Bearer', expires_at: expires, abilities: ['*'], password_change_required: false, user: MOCK_USER };
}

function body(config: InternalAxiosRequestConfig): Record<string, string | undefined> {
  if (typeof config.data === 'string') {
    try {
      return JSON.parse(config.data);
    } catch {
      return {};
    }
  }

  return (config.data as Record<string, string | undefined>) ?? {};
}

function isAuthenticated(config: InternalAxiosRequestConfig): boolean {
  return Boolean(config.headers.get('Authorization'));
}

export function mockAdapter(config: InternalAxiosRequestConfig): Promise<AxiosResponse> {
  const method = (config.method ?? 'get').toLowerCase();
  const path = (config.url ?? '').split('?')[0];
  const route = `${method} ${path}`;

  // Publiques
  if (route === 'get /health') {
    return respond(config, 200, { status: 'ok', time: new Date().toISOString() });
  }
  if (route === 'post /auth/login') {
    const { phone, password } = body(config);
    if (!phone || !password) {
      return respond(config, 422, { message: 'Identifiants incorrects.', errors: { phone: ['Identifiants incorrects.'] } });
    }

    return respond(config, 200, authResponse());
  }

  // Le reste exige un jeton, comme le vrai serveur.
  if (!isAuthenticated(config)) {
    return respond(config, 401, { message: 'Non authentifié.' });
  }

  switch (route) {
    case 'post /auth/logout':
      return respond(config, 200, { message: 'Déconnecté.' });

    case 'get /auth/me':
      return respond(config, 200, { user: MOCK_USER });

    case 'put /auth/password': {
      const { password, password_confirmation: confirmation } = body(config);
      if (!password || password.length < 8) {
        return respond(config, 422, { message: 'Mot de passe trop court.', errors: { password: ['Le mot de passe doit contenir au moins 8 caractères.'] } });
      }
      if (password !== confirmation) {
        return respond(config, 422, { message: 'Confirmation différente.', errors: { password: ['La confirmation ne correspond pas.'] } });
      }

      return respond(config, 200, authResponse());
    }

    case 'get /informations': {
      const page = Math.max(1, Number((config.params as { page?: number } | undefined)?.page ?? 1));
      const result: Paginated<(typeof MOCK_INFORMATIONS)[number]> = {
        data: MOCK_INFORMATIONS.slice((page - 1) * PER_PAGE, page * PER_PAGE),
        meta: { current_page: page, last_page: Math.ceil(MOCK_INFORMATIONS.length / PER_PAGE) },
      };

      return respond(config, 200, result);
    }
  }

  return respond(config, 404, { message: `Route inconnue en mode maquette : ${route}` });
}
