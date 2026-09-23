import { useCallback, useEffect, useState } from 'react';
import { Alert, FlatList, Image, Linking, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Card, Text } from 'react-native-paper';
import MockBanner from '@/components/MockBanner';
import { api, apiErrorMessage, isNotFound } from '@/lib/api';
import { USE_MOCK } from '@/lib/mock/config';
import { colors } from '@/lib/theme';
import type { Information, Paginated } from '@/types/api';

// M2 et M5 : les informations publiées par les commissaires (cahier §6 et §8), en lecture seule, pour la police (onglet)
// et pour la population (espace public, sans connexion, D32). Chaque carte affiche, comme au cahier, la description,
// les images et les fichiers PDF (plusieurs de chaque possible, W6), puis le nom du commissaire et du commissariat.

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
}

async function openDocument(url: string) {
  if (USE_MOCK) {
    Alert.alert('Mode maquette', 'Aucun document réel n\'existe en mode maquette.');
    return;
  }
  try {
    await Linking.openURL(url);
  } catch {
    Alert.alert('Document', 'Impossible d\'ouvrir ce document.');
  }
}

export default function InformationsList() {
  const [items, setItems] = useState<Information[]>([]);
  const [page, setPage] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchPage = useCallback(async (target: number) => {
    const { data } = await api.get<Paginated<Information>>('/informations', { params: { page: target } });
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
      setError(isNotFound(e) ? 'Les informations ne sont pas encore disponibles sur le serveur.' : apiErrorMessage(e));
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
    if (loadingMore || loading || page >= lastPage) {
      return;
    }
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
      ListHeaderComponent={<MockBanner />}
      ListEmptyComponent={
        <View style={styles.empty}>
          <Text style={styles.emptyText}>{error ?? 'Aucune information publiée pour le moment.'}</Text>
          {error ? <Button onPress={onRefresh}>Réessayer</Button> : null}
        </View>
      }
      ListFooterComponent={loadingMore ? <ActivityIndicator style={{ marginVertical: 12 }} /> : items.length > 0 && error ? <Text style={styles.footerError}>{error}</Text> : null}
      renderItem={({ item }) => (
        <Card style={styles.card}>
          {item.image_urls.length === 1 ? (
            <Card.Cover source={{ uri: item.image_urls[0] }} />
          ) : item.image_urls.length > 1 ? (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.gallery} contentContainerStyle={styles.galleryContent}>
              {item.image_urls.map((url) => (
                <Image key={url} source={{ uri: url }} style={styles.galleryImage} />
              ))}
            </ScrollView>
          ) : null}
          <Card.Content style={styles.body}>
            {item.description ? <Text>{item.description}</Text> : null}
            {item.document_urls.length > 0 ? (
              <View style={styles.documents}>
                {item.document_urls.map((url, index) => (
                  <Button key={url} mode="outlined" icon="file-pdf-box" onPress={() => openDocument(url)} style={styles.document}>
                    {item.document_urls.length > 1 ? `Document ${index + 1} (PDF)` : 'Ouvrir le document (PDF)'}
                  </Button>
                ))}
              </View>
            ) : null}
            <View style={styles.meta}>
              <Text style={styles.author}>{item.commissaire_name}</Text>
              <Text style={styles.small}>{item.commissariat_name}</Text>
              <Text style={styles.small}>{formatDate(item.published_at)}</Text>
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
  card: { borderRadius: 14, overflow: 'hidden' },
  body: { gap: 10, paddingVertical: 12 },
  gallery: { maxHeight: 180 },
  galleryContent: { gap: 8, padding: 8 },
  galleryImage: { width: 240, height: 164, borderRadius: 10 },
  documents: { gap: 8, alignItems: 'flex-start' },
  document: { alignSelf: 'flex-start' },
  meta: { gap: 2, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.border, paddingTop: 8 },
  author: { fontWeight: '700', color: colors.text },
  small: { color: colors.muted, fontSize: 12 },
  empty: { alignItems: 'center', gap: 8, paddingVertical: 48 },
  emptyText: { color: colors.muted, textAlign: 'center' },
  footerError: { color: colors.danger, textAlign: 'center', marginVertical: 8 },
});
