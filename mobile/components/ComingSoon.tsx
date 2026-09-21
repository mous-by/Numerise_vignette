import { MaterialCommunityIcons } from '@expo/vector-icons';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Card, Chip, Text } from 'react-native-paper';
import MockBanner from '@/components/MockBanner';
import { colors } from '@/lib/theme';

type IconName = React.ComponentProps<typeof MaterialCommunityIcons>['name'];

/** Fonction prévue au cahier des charges dont le serveur n'existe pas encore : fiche « À venir », aucune donnée. */
export default function ComingSoon({ icon, title, text }: { icon: IconName; title: string; text: string }) {
  return (
    <ScrollView style={styles.screen} contentContainerStyle={styles.content}>
      <MockBanner />
      <Card style={styles.card}>
        <Card.Content style={styles.body}>
          <MaterialCommunityIcons name={icon} size={40} color={colors.primary} />
          <View style={{ flex: 1, gap: 4 }}>
            <Text style={styles.title}>{title}</Text>
            <Text style={styles.text}>{text}</Text>
          </View>
        </Card.Content>
        <Card.Content style={styles.chip}>
          <Chip compact textStyle={{ fontSize: 10 }}>
            À venir
          </Chip>
        </Card.Content>
      </Card>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: 16, gap: 12 },
  card: { borderRadius: 14 },
  body: { flexDirection: 'row', alignItems: 'center', gap: 14 },
  title: { fontWeight: '700', color: colors.text },
  text: { color: colors.muted, fontSize: 13 },
  chip: { alignItems: 'flex-start', paddingTop: 10 },
});
