import { AxiosError, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios';
import type { AuthResponse, DemandeVgt, Paginated } from '@/types/api';
import {
  MOCK_CONTROLES,
  MOCK_INFORMATIONS,
  MOCK_MAIRIES,
  MOCK_MOTOS_RETROUVEES,
  MOCK_OWNER_MATRICULE,
  MOCK_OWNER_PHONE,
  MOCK_OWNER_VGT_YEAR,
  MOCK_USER,
} from './fixtures';

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
  return bodyAny(config) as Record<string, string | undefined>;
}

/** Comme `body()`, sans forcer les valeurs en chaînes : utile pour un corps qui mélange nombres et texte. */
function bodyAny(config: InternalAxiosRequestConfig): Record<string, unknown> {
  if (typeof config.data === 'string') {
    try {
      return JSON.parse(config.data);
    } catch {
      return {};
    }
  }

  return (config.data as Record<string, unknown>) ?? {};
}

function isAuthenticated(config: InternalAxiosRequestConfig): boolean {
  return Boolean(config.headers.get('Authorization'));
}

// M4, état de session du mode maquette : les demandes créées pendant l'essai, pour que le suivi les retrouve.
const mockDemandes: DemandeVgt[] = [];

const UNKNOWN_OWNER = 'Aucune moto ne correspond à ce matricule et à ce numéro de téléphone.';

function normalizePlate(value: unknown): string {
  return String(value ?? '').trim().toUpperCase();
}

function normalizePhone(value: unknown): string {
  const digits = String(value ?? '').replace(/\D/g, '');
  if (digits.length === 8) return `+223${digits}`;
  if (digits.startsWith('223')) return `+${digits}`;

  return String(value ?? '').trim();
}

/** Identité reconnue en mode maquette : un seul propriétaire fictif (voir fixtures.ts). */
function isKnownOwner(matricule: string, phone: string): boolean {
  return matricule === MOCK_OWNER_MATRICULE && phone === normalizePhone(MOCK_OWNER_PHONE);
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

  // Publique aussi (D32 : la population n'a pas de compte) : aucun jeton exigé.
  if (route === 'get /informations') {
    const page = Math.max(1, Number((config.params as { page?: number } | undefined)?.page ?? 1));
    const result: Paginated<(typeof MOCK_INFORMATIONS)[number]> = {
      data: MOCK_INFORMATIONS.slice((page - 1) * PER_PAGE, page * PER_PAGE),
      meta: { current_page: page, last_page: Math.ceil(MOCK_INFORMATIONS.length / PER_PAGE) },
    };

    return respond(config, 200, result);
  }

  // M5 : motos retrouvées, publique (D32).
  if (route === 'get /motos-retrouvees') {
    const page = Math.max(1, Number((config.params as { page?: number } | undefined)?.page ?? 1));
    const result: Paginated<(typeof MOCK_MOTOS_RETROUVEES)[number]> = {
      data: MOCK_MOTOS_RETROUVEES.slice((page - 1) * PER_PAGE, page * PER_PAGE),
      meta: { current_page: page, last_page: Math.ceil(MOCK_MOTOS_RETROUVEES.length / PER_PAGE) },
    };

    return respond(config, 200, result);
  }

  // M4 : mairies de retrait, publique (D32).
  if (route === 'get /mairies') {
    return respond(config, 200, { data: MOCK_MAIRIES });
  }

  // M4 : demande de VGT sans compte, publique (D32) — identité par matricule + téléphone enregistré.
  if (route === 'post /demandes-vgt') {
    const data = bodyAny(config);
    const matricule = normalizePlate(data.matricule);
    const phone = normalizePhone(data.phone);
    const year = Number(data.vgt_year);

    if (!isKnownOwner(matricule, phone)) {
      return respond(config, 422, { message: UNKNOWN_OWNER, errors: { matricule: [UNKNOWN_OWNER] } });
    }
    if (!data.mairie_id) {
      return respond(config, 422, { message: 'La mairie de retrait est obligatoire.', errors: { mairie_id: ['La mairie de retrait est obligatoire.'] } });
    }
    if (!year || year <= MOCK_OWNER_VGT_YEAR) {
      const text = `La vignette de cette moto est déjà à jour jusqu'en ${MOCK_OWNER_VGT_YEAR}.`;
      return respond(config, 422, { message: text, errors: { vgt_year: ['Vignette déjà à jour pour cette année.'] } });
    }
    if (mockDemandes.some((d) => d.annee === year)) {
      return respond(config, 409, { message: `Une demande est déjà en cours pour l'année ${year}.` });
    }

    const mairie = MOCK_MAIRIES.find((m) => m.id === Number(data.mairie_id)) ?? MOCK_MAIRIES[0];
    const demande: DemandeVgt = {
      reference: `VGT-${year}-${String(mockDemandes.length + 1).padStart(6, '0')}`,
      annee: year,
      statut: { code: 'en_attente', libelle: 'En attente' },
      motif_rejet: null,
      montant: { base: 6000, majoration: 0, total: 6000 },
      mairie: { id: mairie.id, nom: mairie.nom },
      paiement_confirme_le: null,
      retrait_le: null,
      cree_le: new Date().toISOString(),
    };
    mockDemandes.unshift(demande);

    return respond(config, 201, { data: demande });
  }

  // M4 : suivi des demandes d'une moto, publique (D32) — même identité que la demande.
  if (route === 'post /demandes-vgt/suivi') {
    const data = bodyAny(config);
    const matricule = normalizePlate(data.matricule);
    const phone = normalizePhone(data.phone);

    if (!isKnownOwner(matricule, phone)) {
      return respond(config, 422, { message: UNKNOWN_OWNER, errors: { matricule: [UNKNOWN_OWNER] } });
    }

    return respond(config, 200, { matricule, vgt_a_jour: true, data: mockDemandes });
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

    case 'get /controles': {
      const matricule = String((config.params as { matricule?: string } | undefined)?.matricule ?? '').trim().toUpperCase();
      if (matricule === '') {
        return respond(config, 422, { message: 'Le matricule est obligatoire.', errors: { matricule: ['Le matricule est obligatoire.'] } });
      }

      const result = MOCK_CONTROLES[matricule];
      if (!result) {
        return respond(config, 404, { message: 'Aucune moto trouvée avec ce matricule.' });
      }

      return respond(config, 200, result);
    }
  }

  return respond(config, 404, { message: `Route inconnue en mode maquette : ${route}` });
}
