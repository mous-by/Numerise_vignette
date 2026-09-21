import { MaterialCommunityIcons } from '@expo/vector-icons';
import { Tabs } from 'expo-router';
import { colors } from '@/lib/theme';

export default function TabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerStyle: { backgroundColor: colors.dark },
        headerTintColor: '#ffffff',
        tabBarActiveTintColor: colors.primary,
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'Accueil',
          tabBarIcon: ({ color, size }) => <MaterialCommunityIcons name="home-outline" color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="informations"
        options={{
          title: 'Informations',
          tabBarIcon: ({ color, size }) => <MaterialCommunityIcons name="information-outline" color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="profile"
        options={{
          title: 'Profil',
          tabBarIcon: ({ color, size }) => <MaterialCommunityIcons name="account-outline" color={color} size={size} />,
        }}
      />
    </Tabs>
  );
}
