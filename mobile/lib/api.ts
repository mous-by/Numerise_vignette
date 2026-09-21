import axios from 'axios';
import type { ApiErrorBody } from '@/types/api';
import { clearToken, getToken } from './storage';

// EXPO_PUBLIC_API_URL doit être joignable DEPUIS LE TÉLÉPHONE : utilisez l'IP de votre machine sur le réseau local
// (voir .env.example), jamais « localhost ».
export const API_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1';

export const api = axios.create({
  baseURL: API_URL,
  timeout: 15000,
  headers: { Accept: 'application/json' },
});

api.interceptors.request.use(async (config) => {
  const token = await getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Enregistré par AuthContext : un 401 (jeton expiré ou révoqué) ou un compte désactivé force une déconnexion propre
// au lieu de laisser l'application bloquée sur un écran en erreur.
let onUnauthorized: ((message?: string) => void) | null = null;

export function registerUnauthorizedHandler(handler: (message?: string) => void) {
  onUnauthorized = handler;
}

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status;
    const body: ApiErrorBody | undefined = error.response?.data;

    if (status === 401) {
      await clearToken();
      onUnauthorized?.();
    } else if (status === 403 && body?.code === 'account_disabled') {
      await clearToken();
      onUnauthorized?.(body.message);
    }

    return Promise.reject(error);
  },
);

/** Aucune réponse n'est arrivée (hors ligne, serveur injoignable, délai dépassé). */
export function isNetworkError(error: unknown): boolean {
  return axios.isAxiosError(error) && !error.response;
}

/** Code d'erreur métier renvoyé par l'API (ex. `password_change_required`). */
export function apiErrorCode(error: unknown): ApiErrorBody['code'] | undefined {
  return axios.isAxiosError(error) ? (error.response?.data as ApiErrorBody | undefined)?.code : undefined;
}

/** Message lisible en français pour l'utilisateur. */
export function apiErrorMessage(error: unknown, fallback = 'Une erreur est survenue. Réessayez.'): string {
  if (!axios.isAxiosError(error)) {
    return fallback;
  }
  if (!error.response) {
    return 'Impossible de joindre le serveur. Vérifiez votre connexion (Wi-Fi) et l\'adresse de l\'API.';
  }

  const data = error.response.data as ApiErrorBody | undefined;

  // Erreurs de validation : le premier message du premier champ.
  const first = data?.errors ? Object.values(data.errors)[0] : undefined;
  if (Array.isArray(first) && first.length > 0) {
    return first[0];
  }

  return data?.message ?? fallback;
}
