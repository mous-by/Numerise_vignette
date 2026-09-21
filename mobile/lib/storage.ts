import * as SecureStore from 'expo-secure-store';

// Le jeton d'accès vit dans le stockage sécurisé du téléphone (Keystore Android), jamais en clair.
const TOKEN_KEY = 'vigimoto_token';

export async function getToken(): Promise<string | null> {
  return SecureStore.getItemAsync(TOKEN_KEY);
}

export async function setToken(token: string): Promise<void> {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
}

export async function clearToken(): Promise<void> {
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}
