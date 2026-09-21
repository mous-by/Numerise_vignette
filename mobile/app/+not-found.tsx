import { Link, Stack } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';

export default function NotFoundScreen() {
  return (
    <>
      <Stack.Screen options={{ title: 'Introuvable', headerShown: true }} />
      <View style={styles.container}>
        <Text variant="titleMedium">Cet écran n'existe pas.</Text>
        <Link href="/" style={styles.link}>
          <Text style={{ color: '#1d4e89' }}>Retour à l'accueil</Text>
        </Link>
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 20 },
  link: { marginTop: 15, paddingVertical: 15 },
});
