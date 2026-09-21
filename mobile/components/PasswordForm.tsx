import { useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { Button, HelperText, TextInput } from 'react-native-paper';
import { apiErrorMessage } from '@/lib/api';

interface Props {
  submitLabel: string;
  onSubmit: (current: string, next: string, confirmation: string) => Promise<void>;
  onSuccess?: () => void;
}

/** Règle D14 : 8 caractères au moins, avec des lettres et des chiffres (le serveur reste le garde principal). */
function localError(current: string, next: string, confirmation: string): string | null {
  if (!current) return 'Saisissez votre mot de passe actuel.';
  if (next.length < 8 || !/[A-Za-z]/.test(next) || !/\d/.test(next)) {
    return 'Le nouveau mot de passe doit avoir 8 caractères au moins, avec des lettres et des chiffres.';
  }
  if (next === current) return 'Le nouveau mot de passe doit être différent de l\'actuel.';
  if (next !== confirmation) return 'La confirmation ne correspond pas au nouveau mot de passe.';
  return null;
}

export default function PasswordForm({ submitLabel, onSubmit, onSuccess }: Props) {
  const [current, setCurrent] = useState('');
  const [next, setNext] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [visible, setVisible] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    const problem = localError(current, next, confirmation);
    if (problem) {
      setError(problem);
      return;
    }

    setError(null);
    setLoading(true);
    try {
      await onSubmit(current, next, confirmation);
      setCurrent('');
      setNext('');
      setConfirmation('');
      onSuccess?.();
    } catch (e) {
      setError(apiErrorMessage(e));
    } finally {
      setLoading(false);
    }
  };

  const eye = <TextInput.Icon icon={visible ? 'eye-off' : 'eye'} onPress={() => setVisible((v) => !v)} />;

  return (
    <View style={styles.form}>
      <TextInput mode="outlined" label="Mot de passe actuel" value={current} onChangeText={setCurrent} secureTextEntry={!visible} autoCapitalize="none" right={eye} />
      <TextInput mode="outlined" label="Nouveau mot de passe" value={next} onChangeText={setNext} secureTextEntry={!visible} autoCapitalize="none" />
      <TextInput mode="outlined" label="Confirmer le nouveau mot de passe" value={confirmation} onChangeText={setConfirmation} secureTextEntry={!visible} autoCapitalize="none" />
      <HelperText type="error" visible={!!error}>
        {error}
      </HelperText>
      <Button mode="contained" onPress={submit} loading={loading} disabled={loading}>
        {submitLabel}
      </Button>
    </View>
  );
}

const styles = StyleSheet.create({
  form: { gap: 10 },
});
