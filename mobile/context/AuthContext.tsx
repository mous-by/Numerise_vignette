import Constants from 'expo-constants';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { Platform } from 'react-native';
import { api, apiErrorCode, registerUnauthorizedHandler } from '@/lib/api';
import { clearToken, getToken, setToken } from '@/lib/storage';
import type { AuthResponse, User } from '@/types/api';

interface AuthContextValue {
  /** Un jeton est présent (la session peut n'être que « restreinte » : mot de passe temporaire). */
  signedIn: boolean;
  isLoading: boolean;
  user: User | null;
  /** Mot de passe temporaire : seul le changement de mot de passe est possible (D14). */
  passwordChangeRequired: boolean;
  /** Message à afficher sur l'écran de connexion après une déconnexion forcée (compte désactivé…). */
  notice: string | null;
  login: (phone: string, password: string) => Promise<void>;
  changePassword: (current: string, next: string, confirmation: string) => Promise<void>;
  refreshUser: () => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

/** Nom de l'appareil envoyé à l'API : un jeton par appareil (se reconnecter sur le même appareil remplace le jeton). */
function deviceName(): string {
  return (Constants.deviceName ?? `${Platform.OS}-${Platform.Version}`).slice(0, 100);
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [signedIn, setSignedIn] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [user, setUser] = useState<User | null>(null);
  const [passwordChangeRequired, setPasswordChangeRequired] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);

  const reset = useCallback((message?: string) => {
    setSignedIn(false);
    setUser(null);
    setPasswordChangeRequired(false);
    setNotice(message ?? null);
  }, []);

  const apply = useCallback(async (response: AuthResponse) => {
    await setToken(response.token);
    setSignedIn(true);
    setUser(response.user);
    setPasswordChangeRequired(response.password_change_required);
    setNotice(null);
  }, []);

  useEffect(() => {
    registerUnauthorizedHandler(reset);
  }, [reset]);

  // Au démarrage : restaurer la session à partir du jeton stocké.
  useEffect(() => {
    (async () => {
      const token = await getToken();
      if (!token) {
        setIsLoading(false);
        return;
      }

      setSignedIn(true);
      try {
        const { data } = await api.get<{ user: User }>('/auth/me');
        setUser(data.user);
      } catch (error) {
        if (apiErrorCode(error) === 'password_change_required') {
          setPasswordChangeRequired(true);
        }
        // 401 / compte désactivé : l'intercepteur a déjà déconnecté. Hors ligne : on garde le jeton et on réessaiera.
      } finally {
        setIsLoading(false);
      }
    })();
  }, []);

  const login = useCallback(
    async (phone: string, password: string) => {
      const { data } = await api.post<AuthResponse>('/auth/login', { phone, password, device_name: deviceName() });
      await apply(data);
    },
    [apply],
  );

  const changePassword = useCallback(
    async (current: string, next: string, confirmation: string) => {
      const { data } = await api.put<AuthResponse>('/auth/password', {
        current_password: current,
        password: next,
        password_confirmation: confirmation,
      });
      // Tous les anciens jetons sont révoqués : on remplace celui du téléphone par le nouveau.
      await apply(data);
    },
    [apply],
  );

  const refreshUser = useCallback(async () => {
    const { data } = await api.get<{ user: User }>('/auth/me');
    setUser(data.user);
  }, []);

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      // Au mieux : même si l'appel échoue (hors ligne), la session locale est effacée.
    }
    await clearToken();
    reset();
  }, [reset]);

  const value = useMemo(
    () => ({ signedIn, isLoading, user, passwordChangeRequired, notice, login, changePassword, refreshUser, logout }),
    [signedIn, isLoading, user, passwordChangeRequired, notice, login, changePassword, refreshUser, logout],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth doit être utilisé dans un AuthProvider');
  }
  return context;
}
