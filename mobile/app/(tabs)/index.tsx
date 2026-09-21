import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useCallback, useEffect, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Card, Chip, Text } from 'react-native-paper';
import MockBanner from '@/components/MockBanner';
import { useAuth } from '@/context/AuthContext';
import { API_URL, api, apiErrorMessage } from '@/lib/api';
import { colors } from '@/lib/theme';

// Modules du contrôle de police prévus par le cahier des charges : simple liste « à venir » (maquette, aucune donnée).
// Les règles sont à valider avec le client avant leur construction (voir CLAUDE.md, §5 et §10).
const UPCOMING = [
  { icon: 'shield-search', title: 'Contrôle d\'une moto', text: 'Moto volée ou non, vignette à jour.' },
] as const;

export default function HomeScreen() {
  const { user, refreshUser } = useAuth();
  const [server, setServer] = useState<'checking' | 'up' | 'down'>('checking');
  const [error, setError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      await api.get('/health');
      setServer('up');
      await refreshUser(); // rôle et permissions à jour (changement, désactivation…)
    } catch (e) {
      setServer('down');
      setError(apiErrorMessage(e));
    }
  }, [refreshUser]);

  useEffect(() => {
    load();
  }, [load]);

  const onRefresh = async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  };

  return (
    <ScrollView style={styles.screen} contentContainerStyle={styles.content} refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}>
      <MockBanner />
      {user ? (
        <Card style={styles.card}>
          <Card.Content style={{ gap: 4 }}>
            <Text style={styles.hello}>Bonjour,</Text>
            <Text variant="headlineSmall" style={styles.name}>
              {user.name}
            </Text>
            <View style={styles.chips}>
              {user.role ? <Chip icon="badge-account">{user.role.label}</Chip> : null}
              {user.institution ? <Chip icon="office-building">{user.institution.name}</Chip> : null}
            </View>
          </Card.Content>
        </Card>
      ) : (
        <Card style={styles.card}>
          <Card.Content style={{ gap: 10 }}>
            {server === 'checking' ? <ActivityIndicator /> : <Text>{error ?? 'Profil indisponible.'}</Text>}
            {server === 'down' ? <Button onPress={load}>Réessayer</Button> : null}
          </Card.Content>
        </Card>
      )}

      <Card style={styles.card}>
        <Card.Content style={styles.row}>
          <MaterialCommunityIcons name={server === 'up' ? 'check-circle' : server === 'down' ? 'alert-circle' : 'progress-clock'} size={22} color={server === 'up' ? colors.success : server === 'down' ? colors.danger : colors.muted} />
          <View style={{ flex: 1 }}>
            <Text style={{ fontWeight: '700' }}>{server === 'up' ? 'Serveur joignable' : server === 'down' ? 'Serveur injoignable' : 'Vérification du serveur…'}</Text>
            <Text style={styles.small}>{API_URL}</Text>
          </View>
        </Card.Content>
      </Card>

      <Text variant="titleSmall" style={styles.section}>
        À VENIR
      </Text>
      {UPCOMING.map((item) => (
        <Card key={item.title} style={[styles.card, styles.upcoming]}>
          <Card.Content style={styles.row}>
            <MaterialCommunityIcons name={item.icon} size={28} color={colors.primary} />
            <View style={{ flex: 1 }}>
              <Text style={{ fontWeight: '700' }}>{item.title}</Text>
              <Text style={styles.small}>{item.text}</Text>
            </View>
            <Chip compact textStyle={{ fontSize: 10 }}>
              À venir
            </Chip>
          </Card.Content>
        </Card>
      ))}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: 16, gap: 12 },
  card: { borderRadius: 14 },
  upcoming: { opacity: 0.85 },
  hello: { color: colors.muted },
  name: { fontWeight: '800', color: colors.text },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 8 },
  row: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  small: { color: colors.muted, fontSize: 12 },
  section: { color: colors.muted, letterSpacing: 1, marginTop: 8 },
});
