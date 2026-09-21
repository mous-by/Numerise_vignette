import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { PaperProvider } from 'react-native-paper';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AuthProvider, useAuth } from '@/context/AuthContext';
import { paperTheme } from '@/lib/paperTheme';

export { ErrorBoundary } from 'expo-router';

// L'écran de démarrage reste affiché tant que la session n'est pas restaurée (jeton + /auth/me).
SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <AuthProvider>
          <PaperProvider theme={paperTheme}>
            <StatusBar style="light" />
            <Navigation />
          </PaperProvider>
        </AuthProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}

function Navigation() {
  const { signedIn, isLoading, passwordChangeRequired } = useAuth();

  useEffect(() => {
    if (!isLoading) {
      SplashScreen.hideAsync();
    }
  }, [isLoading]);

  if (isLoading) {
    return null;
  }

  return (
    <Stack screenOptions={{ headerShown: false }}>
      {/* Connecté : l'application. */}
      <Stack.Protected guard={signedIn && !passwordChangeRequired}>
        <Stack.Screen name="(tabs)" />
      </Stack.Protected>

      {/* Mot de passe temporaire : seul le changement de mot de passe est accessible (D14). */}
      <Stack.Protected guard={signedIn && passwordChangeRequired}>
        <Stack.Screen name="change-password" />
      </Stack.Protected>

      {/* Non connecté : l'espace public de la population (sans compte, D32) d'abord, la connexion (police) en plus. */}
      <Stack.Protected guard={!signedIn}>
        <Stack.Screen name="public" />
        <Stack.Screen name="login" />
      </Stack.Protected>
    </Stack>
  );
}
