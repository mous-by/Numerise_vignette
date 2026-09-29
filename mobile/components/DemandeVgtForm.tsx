import { useEffect, useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Button, Card, Chip, HelperText, Menu, SegmentedButtons, Text, TextInput } from 'react-native-paper';
import MockBanner from '@/components/MockBanner';
import { api, apiErrorMessage } from '@/lib/api';
import { formatDate, formatFcfa } from '@/lib/format';
import { colors, demandeVgtStatusColors } from '@/lib/theme';
import type { DemandeVgt, PublicMairie, StoreDemandeVgtResponse, SuiviDemandeVgtResponse } from '@/types/api';

// M4 : demande de VGT et suivi, sans compte (cahier §8, §9 ; D32). Le propriétaire s'identifie par le matricule de
// sa moto et le téléphone enregistré à son nom au commissariat — validé par le client (CLAUDE.md, §5, 2026-09-30).

const CURRENT_YEAR = new Date().getFullYear();

function DemandeCard({ demande }: { demande: DemandeVgt }) {
  const status = demandeVgtStatusColors(demande.statut.code);

  return (
    <Card style={styles.demandeCard}>
      <Card.Content style={{ gap: 8 }}>
        <View style={styles.demandeHeader}>
          <Text style={styles.reference}>{demande.reference}</Text>
          <Chip compact style={{ backgroundColor: status.bg }} textStyle={{ color: status.text, fontWeight: '700' }}>
            {demande.statut.libelle}
          </Chip>
        </View>
        <Text style={styles.small}>Vignette {demande.annee} · Mairie {demande.mairie.nom ?? '—'}</Text>
        <Text style={styles.montant}>
          {formatFcfa(demande.montant.total)}
          {demande.montant.majoration > 0 ? <Text style={styles.small}> (dont {formatFcfa(demande.montant.majoration)} de majoration)</Text> : null}
        </Text>
        {demande.motif_rejet ? <Text style={[styles.small, { color: colors.danger }]}>Motif du rejet : {demande.motif_rejet}</Text> : null}
        <View style={styles.demandeDates}>
          <Text style={styles.small}>Déposée le {formatDate(demande.cree_le)}</Text>
          {demande.paiement_confirme_le ? <Text style={styles.small}>Paiement confirmé le {formatDate(demande.paiement_confirme_le)}</Text> : null}
          {demande.retrait_le ? <Text style={styles.small}>Carte retirée le {formatDate(demande.retrait_le)}</Text> : null}
        </View>
      </Card.Content>
    </Card>
  );
}

export default function DemandeVgtForm() {
  const [mode, setMode] = useState<'nouvelle' | 'suivi'>('nouvelle');
  const [matricule, setMatricule] = useState('');
  const [phone, setPhone] = useState('');
  const [year, setYear] = useState(String(CURRENT_YEAR));
  const [mairies, setMairies] = useState<PublicMairie[]>([]);
  const [mairieId, setMairieId] = useState<number | null>(null);
  const [menuOpen, setMenuOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [created, setCreated] = useState<DemandeVgt | null>(null);
  const [suivi, setSuivi] = useState<DemandeVgt[] | null>(null);

  useEffect(() => {
    api
      .get<{ data: PublicMairie[] }>('/mairies')
      .then(({ data }) => {
        setMairies(data.data);
        setMairieId((current) => current ?? data.data[0]?.id ?? null);
      })
      .catch(() => {
        // La liste des mairies n'est pas bloquante à l'ouverture de l'écran : l'erreur reviendra à l'envoi.
      });
  }, []);

  const selectedMairie = mairies.find((m) => m.id === mairieId);

  const submitNouvelle = async () => {
    if (!matricule.trim() || !phone.trim()) {
      setError('Saisissez le matricule de la moto et le numéro de téléphone enregistré.');
      return;
    }
    if (!mairieId) {
      setError('Choisissez la mairie de retrait.');
      return;
    }

    setError(null);
    setCreated(null);
    setLoading(true);
    try {
      const { data } = await api.post<StoreDemandeVgtResponse>('/demandes-vgt', {
        matricule: matricule.trim(),
        phone: phone.trim(),
        mairie_id: mairieId,
        vgt_year: Number(year),
      });
      setCreated(data.data);
    } catch (e) {
      setError(apiErrorMessage(e, 'La demande n\'a pas pu être envoyée. Réessayez.'));
    } finally {
      setLoading(false);
    }
  };

  const submitSuivi = async () => {
    if (!matricule.trim() || !phone.trim()) {
      setError('Saisissez le matricule de la moto et le numéro de téléphone enregistré.');
      return;
    }

    setError(null);
    setSuivi(null);
    setLoading(true);
    try {
      const { data } = await api.post<SuiviDemandeVgtResponse>('/demandes-vgt/suivi', { matricule: matricule.trim(), phone: phone.trim() });
      setSuivi(data.data);
    } catch (e) {
      setError(apiErrorMessage(e, 'Le suivi n\'a pas pu être récupéré. Réessayez.'));
    } finally {
      setLoading(false);
    }
  };

  const changeMode = (value: string) => {
    setMode(value as 'nouvelle' | 'suivi');
    setError(null);
    setCreated(null);
    setSuivi(null);
  };

  return (
    <ScrollView style={styles.screen} contentContainerStyle={styles.content}>
      <MockBanner />

      <SegmentedButtons
        value={mode}
        onValueChange={changeMode}
        buttons={[
          { value: 'nouvelle', label: 'Nouvelle demande', icon: 'file-plus-outline' },
          { value: 'suivi', label: 'Suivi', icon: 'text-box-search-outline' },
        ]}
      />

      <Card style={styles.card}>
        <Card.Content style={{ gap: 10 }}>
          <Text style={styles.helperTitle}>Votre moto doit déjà être enregistrée au commissariat.</Text>
          <TextInput
            mode="outlined"
            label="Matricule de la moto"
            placeholder="AB 1234 CD"
            value={matricule}
            onChangeText={(value) => setMatricule(value.toUpperCase())}
            autoCapitalize="characters"
            autoCorrect={false}
            left={<TextInput.Icon icon="card-text-outline" />}
          />
          <TextInput
            mode="outlined"
            label="Numéro de téléphone"
            placeholder="70 00 00 00"
            value={phone}
            onChangeText={setPhone}
            keyboardType="phone-pad"
            autoComplete="tel"
            left={<TextInput.Icon icon="phone" />}
          />
          <HelperText type="info" visible style={styles.tightHelper}>
            Le numéro enregistré à votre nom au commissariat. L'indicatif +223 est ajouté automatiquement.
          </HelperText>

          {mode === 'nouvelle' ? (
            <>
              <Menu
                visible={menuOpen}
                onDismiss={() => setMenuOpen(false)}
                anchor={
                  <TextInput
                    mode="outlined"
                    label="Mairie de retrait"
                    value={selectedMairie?.nom ?? ''}
                    editable={false}
                    onPressIn={() => setMenuOpen(true)}
                    left={<TextInput.Icon icon="office-building-outline" />}
                    right={<TextInput.Icon icon="menu-down" onPress={() => setMenuOpen(true)} />}
                  />
                }>
                {mairies.map((mairie) => (
                  <Menu.Item
                    key={mairie.id}
                    title={mairie.nom}
                    onPress={() => {
                      setMairieId(mairie.id);
                      setMenuOpen(false);
                    }}
                  />
                ))}
              </Menu>
              <TextInput mode="outlined" label="Année de la vignette" value={year} onChangeText={setYear} keyboardType="number-pad" left={<TextInput.Icon icon="calendar" />} />
              <HelperText type="info" visible style={styles.tightHelper}>
                Année déjà passée = majoration pour arriéré.
              </HelperText>
            </>
          ) : null}

          <HelperText type="error" visible={!!error}>
            {error}
          </HelperText>

          <Button mode="contained" icon={mode === 'nouvelle' ? 'send' : 'magnify'} onPress={mode === 'nouvelle' ? submitNouvelle : submitSuivi} loading={loading} disabled={loading}>
            {mode === 'nouvelle' ? 'Envoyer la demande' : 'Suivre mes demandes'}
          </Button>
        </Card.Content>
      </Card>

      {created ? (
        <View>
          <Text variant="titleSmall" style={styles.sectionTitle}>
            DEMANDE ENVOYÉE
          </Text>
          <DemandeCard demande={created} />
        </View>
      ) : null}

      {suivi ? (
        <View>
          <Text variant="titleSmall" style={styles.sectionTitle}>
            {suivi.length > 0 ? 'VOS DEMANDES' : ''}
          </Text>
          {suivi.length === 0 ? <Text style={styles.small}>Aucune demande trouvée pour cette moto.</Text> : suivi.map((demande) => <DemandeCard key={demande.reference} demande={demande} />)}
        </View>
      ) : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: 16, gap: 12 },
  card: { borderRadius: 14 },
  helperTitle: { color: colors.muted, fontSize: 12 },
  tightHelper: { marginTop: -8 },
  sectionTitle: { color: colors.muted, letterSpacing: 1, marginBottom: 8 },
  demandeCard: { borderRadius: 14, borderWidth: 1, borderColor: colors.border, marginBottom: 10 },
  demandeHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  reference: { fontWeight: '800', color: colors.text },
  montant: { fontSize: 16, fontWeight: '700', color: colors.text },
  demandeDates: { gap: 2, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.border, paddingTop: 6, marginTop: 2 },
  small: { color: colors.muted, fontSize: 12 },
});
