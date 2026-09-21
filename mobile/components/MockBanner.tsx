import { MaterialCommunityIcons } from '@expo/vector-icons';
import { StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';
import { USE_MOCK } from '@/lib/mock/config';
import { colors } from '@/lib/theme';

/** Bandeau du mode maquette (développement) : rappelle que les données affichées sont fictives. Invisible sinon. */
export default function MockBanner() {
  if (!USE_MOCK) {
    return null;
  }

  return (
    <View style={styles.banner}>
      <MaterialCommunityIcons name="flask-outline" size={16} color={colors.dark} />
      <Text style={styles.text}>Mode maquette : données fictives</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  banner: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, backgroundColor: colors.warning, borderRadius: 10, paddingVertical: 6, paddingHorizontal: 10 },
  text: { color: colors.dark, fontSize: 12, fontWeight: '700' },
});
