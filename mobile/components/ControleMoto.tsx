import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { Button, Card, Chip, HelperText, Text, TextInput } from 'react-native-paper';
import { api, apiErrorMessage } from '@/lib/api';
import { colors } from '@/lib/theme';
import type { ControleResult } from '@/types/api';

// M1 : contrôle rapide d'une moto en patrouille (cahier §4 Police, §8) — le seul champ est le matricule, le
// résultat les deux statuts demandés par le cahier (volée ou non, vignette à jour ou non). Aucune donnée
// personnelle du propriétaire n'est ni demandée ni renvoyée.

const HISTORY_LIMIT = 5;

function StatusRow({ icon, label, ok, okText, badText }: { icon: React.ComponentProps<typeof MaterialCommunityIcons>['name']; label: string; ok: boolean; okText: string; badText: string }) {
  return (
    <View style={styles.statusRow}>
      <MaterialCommunityIcons name={icon} size={22} color={ok ? colors.success : colors.danger} />
      <View style={{ flex: 1 }}>
        <Text style={styles.statusLabel}>{label}</Text>
        <Text style={[styles.statusValue, { color: ok ? colors.success : colors.danger }]}>{ok ? okText : badText}</Text>
      </View>
    </View>
  );
}

export default function ControleMoto() {
  const [matricule, setMatricule] = useState('');
  const [result, setResult] = useState<ControleResult | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [history, setHistory] = useState<ControleResult[]>([]);

  const submit = async (value?: string) => {
    const query = (value ?? matricule).trim();
    if (query === '') {
      setError('Saisissez le matricule de la moto.');
      return;
    }

    setError(null);
    setResult(null);
    setLoading(true);
    try {
      const { data } = await api.get<ControleResult>('/controles', { params: { matricule: query } });
      setResult(data);
      setMatricule(data.matricule);
      setHistory((current) => [data, ...current.filter((item) => item.matricule !== data.matricule)].slice(0, HISTORY_LIMIT));
    } catch (e) {
      setError(apiErrorMessage(e, 'Contrôle impossible. Réessayez.'));
    } finally {
      setLoading(false);
    }
  };

  const reset = () => {
    setMatricule('');
    setResult(null);
    setError(null);
  };

  return (
    <View style={{ gap: 12 }}>
      <Card style={styles.card}>
        <Card.Content style={{ gap: 10 }}>
          <Text variant="titleMedium" style={styles.title}>
            <MaterialCommunityIcons name="shield-search" size={20} /> Contrôle d'une moto
          </Text>
          <TextInput
            mode="outlined"
            label="Matricule de la moto"
            placeholder="AB 1234 CD"
            value={matricule}
            onChangeText={(value) => setMatricule(value.toUpperCase())}
            autoCapitalize="characters"
            autoCorrect={false}
            left={<TextInput.Icon icon="card-text-outline" />}
            right={matricule ? <TextInput.Icon icon="close" onPress={reset} /> : undefined}
            onSubmitEditing={() => submit()}
          />
          <HelperText type="error" visible={!!error}>
            {error}
          </HelperText>
          <Button mode="contained" icon="magnify" onPress={() => submit()} loading={loading} disabled={loading}>
            Vérifier
          </Button>
        </Card.Content>
      </Card>

      {result ? (
        <Card style={[styles.card, styles.resultCard]}>
          <Card.Content style={{ gap: 12 }}>
            <Text style={styles.resultPlate}>{result.matricule}</Text>
            <StatusRow icon={result.volee ? 'alert-octagon' : 'check-decagram'} label="Statut de la moto" ok={!result.volee} okText="Non volée" badText="Déclarée volée" />
            <StatusRow icon={result.vgt_a_jour ? 'check-decagram' : 'alert-circle'} label="Vignette (VGT)" ok={result.vgt_a_jour} okText="À jour" badText="Non à jour" />
          </Card.Content>
        </Card>
      ) : null}

      {history.length > 0 ? (
        <View>
          <Text variant="titleSmall" style={styles.historyTitle}>
            DERNIERS CONTRÔLES DE CETTE SESSION
          </Text>
          <View style={styles.historyRow}>
            {history.map((item) => (
              <Chip
                key={item.matricule}
                compact
                icon={item.volee || !item.vgt_a_jour ? 'alert' : 'check'}
                style={{ backgroundColor: item.volee ? '#fbe4e6' : !item.vgt_a_jour ? '#fff3d6' : '#e3f3e8' }}
                onPress={() => submit(item.matricule)}>
                {item.matricule}
              </Chip>
            ))}
          </View>
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: { borderRadius: 14 },
  title: { fontWeight: '700', color: colors.text },
  resultCard: { borderWidth: 1, borderColor: colors.border },
  resultPlate: { fontSize: 22, fontWeight: '800', letterSpacing: 1, color: colors.text },
  statusRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  statusLabel: { color: colors.muted, fontSize: 12 },
  statusValue: { fontWeight: '700', fontSize: 15 },
  historyTitle: { color: colors.muted, letterSpacing: 1, marginBottom: 8 },
  historyRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
});
