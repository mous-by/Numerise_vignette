import { useCallback, useEffect, useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Card, Text } from 'react-native-paper';
import MockBanner from '@/components/MockBanner';
import { api, apiErrorMessage, isNotFound } from '@/lib/api';
import { formatDate } from '@/lib/format';
import { colors } from '@/lib/theme';
import type { Paginated, PublicMotoRetrouvee } from '@/types/api';

// M5 : motos retrouvées non encore récupérées (cahier §8, §9), publique et sans connexion (D32). Aucune donnée
// personnelle du propriétaire — seulement ce qui permet de reconnaître sa moto (matricule, genre, couleur, lieu).

const PER_PAGE = 3;

export default function MotosRetrouveesList() {
  const [items, setItems] = useState<PublicMotoRetrouvee[]>([]);
  const [page, setPage] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchPage = useCallback(async (target: number) => {
    const { data } = await api.get<Paginated<PublicMotoRetrouvee>>('/motos-retrouvees', { params: { page: target } });
    return data;
  }, []);

  const reload = useCallback(async () => {
    setError(null);
    try {
      const data = await fetchPage(1);
      setItems(data.data);
      setPage(data.meta.current_page);
      setLastPage(data.meta.last_page);
    } catch (e) {
      setItems([]);
      setError(isNotFound(e) ? 'Les motos retrouvées ne sont pas encore disponibles sur le serveur.' : apiErrorMessage(e));
    }
  }, [fetchPage]);

  useEffect(() => {
    reload().finally(() => setLoading(false));
  }, [reload]);

  const onRefresh = async () => {
    setRefreshing(true);
    await reload();
    setRefreshing(false);
  };

  const loadMore = async () => {
    if (loadingMore || loading || page >= lastPage) return;
    setLoadingMore(true);
    try {
      const data = await fetchPage(page + 1);
      setItems((current) => [...current, ...data.data]);
      setPage(data.meta.current_page);
      setLastPage(data.meta.last_page);
    } catch (e) {
      setError(apiErrorMessage(e));
    } finally {
      setLoadingMore(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator />
      </View>
    );
  }

  return (
    <FlatList
      style={styles.screen}
      contentContainerStyle={styles.content}
      data={items}
      keyExtractor={(item) => String(item.id)}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      onEndReached={loadMore}
      onEndReachedThreshold={0.4}
      ListHeaderComponent={
        <>
          <MockBanner />
          <Text style={styles.intro}>Reconnaissez-vous une de ces motos ? Présentez-vous au commissariat indiqué avec une pièce d'identité.</Text>
        </>
      }
      ListEmptyComponent={
        <View style={styles.empty}>
          <Text style={styles.emptyText}>{error ?? 'Aucune moto retrouvée pour le moment.'}</Text>
          {error ? <Button onPress={onRefresh}>Réessayer</Button> : null}
        </View>
      }
      ListFooterComponent={loadingMore ? <ActivityIndicator style={{ marginVertical: 12 }} /> : items.length > 0 && error ? <Text style={styles.footerError}>{error}</Text> : null}
      renderItem={({ item }) => (
        <Card style={styles.card}>
          <Card.Content style={styles.body}>
            <View style={styles.headerRow}>
              <Text style={styles.plate}>{item.matricule}</Text>
              <Text style={styles.small}>{formatDate(item.date_arret)}</Text>
            </View>
            <Text style={styles.detail}>
              {item.genre} · {item.couleur}
            </Text>
            <View style={styles.meta}>
              <Text style={styles.small}>Retrouvée : {item.lieu}</Text>
              <Text style={styles.small}>{item.commissariat}</Text>
            </View>
          </Card.Content>
        </Card>
      )}
    />
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: 16, gap: 12 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.background },
  intro: { color: colors.muted, fontSize: 13, marginBottom: 4 },
  card: { borderRadius: 14 },
  body: { gap: 6, paddingVertical: 10 },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  plate: { fontSize: 17, fontWeight: '800', letterSpacing: 0.5, color: colors.text },
  detail: { color: colors.text },
  meta: { gap: 2, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.border, paddingTop: 6, marginTop: 4 },
  small: { color: colors.muted, fontSize: 12 },
  empty: { alignItems: 'center', gap: 8, paddingVertical: 48 },
  emptyText: { color: colors.muted, textAlign: 'center' },
  footerError: { color: colors.danger, textAlign: 'center', marginVertical: 8 },
});
