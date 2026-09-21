import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Tabs, useRouter } from 'expo-router';
import { useEffect } from 'react';
import { Button } from 'react-native-paper';
import { useAuth } from '@/context/AuthContext';
import { colors } from '@/lib/theme';

// Espace public : la population n'a pas de compte (D32), elle utilise l'application sans se connecter. Menus du cahier
// (§6) : demande VGT, motos retrouvées, informations. La connexion est réservée à la police (bouton en haut à droite).
export default function PublicLayout() {
  const router = useRouter();
  const { notice } = useAuth();

  // Déconnexion forcée (session expirée, compte désactivé) : le message s'affiche sur la connexion, on y retourne.
  useEffect(() => {
    if (notice) {
      router.push('/login');
    }
  }, [notice, router]);

  return (
    <Tabs
      initialRouteName="index"
      screenOptions={{
        headerStyle: { backgroundColor: colors.dark },
        headerTintColor: '#ffffff',
        tabBarActiveTintColor: colors.primary,
        headerRight: () => (
          <Button compact textColor="#ffffff" icon="login" onPress={() => router.push('/login')}>
            Connexion
          </Button>
        ),
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'Informations',
          tabBarIcon: ({ color, size }) => <MaterialCommunityIcons name="information-outline" color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="motos-retrouvees"
        options={{
          title: 'Motos retrouvées',
          tabBarIcon: ({ color, size }) => <MaterialCommunityIcons name="motorbike" color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="demande-vgt"
        options={{
          title: 'Demande VGT',
          tabBarIcon: ({ color, size }) => <MaterialCommunityIcons name="file-document-outline" color={color} size={size} />,
        }}
      />
    </Tabs>
  );
}
