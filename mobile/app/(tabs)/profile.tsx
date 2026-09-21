import { useState } from 'react';
import { Alert, ScrollView, StyleSheet } from 'react-native';
import { Button, Card, List, Text } from 'react-native-paper';
import PasswordForm from '@/components/PasswordForm';
import { useAuth } from '@/context/AuthContext';
import { colors } from '@/lib/theme';

export default function ProfileScreen() {
  const { user, changePassword, logout } = useAuth();
  const [changing, setChanging] = useState(false);

  const confirmLogout = () =>
    Alert.alert('Se déconnecter ?', 'Vous devrez saisir à nouveau votre numéro et votre mot de passe.', [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Se déconnecter', style: 'destructive', onPress: logout },
    ]);

  return (
    <ScrollView style={styles.screen} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Card style={styles.card}>
        <Card.Title title="Mes informations" titleStyle={styles.cardTitle} />
        <Card.Content>
          <List.Item title="Nom" description={user?.name ?? '—'} left={(p) => <List.Icon {...p} icon="account" />} />
          <List.Item title="Téléphone (identifiant)" description={user?.phone ?? '—'} left={(p) => <List.Icon {...p} icon="phone" />} />
          <List.Item title="Rôle" description={user?.role?.label ?? '—'} left={(p) => <List.Icon {...p} icon="badge-account" />} />
          <List.Item title="Institution" description={user?.institution?.name ?? '—'} left={(p) => <List.Icon {...p} icon="office-building" />} />
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Title title="Mot de passe" titleStyle={styles.cardTitle} />
        <Card.Content style={{ gap: 10 }}>
          {changing ? (
            <>
              <PasswordForm submitLabel="Enregistrer" onSubmit={changePassword} onSuccess={() => setChanging(false)} />
              <Text style={styles.small}>Vos autres appareils seront déconnectés.</Text>
              <Button onPress={() => setChanging(false)}>Annuler</Button>
            </>
          ) : (
            <Button mode="outlined" icon="lock-reset" onPress={() => setChanging(true)}>
              Changer mon mot de passe
            </Button>
          )}
        </Card.Content>
      </Card>

      <Button mode="contained" icon="logout" buttonColor={colors.danger} onPress={confirmLogout}>
        Se déconnecter
      </Button>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: 16, gap: 12 },
  card: { borderRadius: 14 },
  cardTitle: { fontWeight: '800' },
  small: { color: colors.muted, fontSize: 12 },
});
