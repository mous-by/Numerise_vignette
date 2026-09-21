import { MD3LightTheme } from 'react-native-paper';
import { colors } from './theme';

export const paperTheme = {
  ...MD3LightTheme,
  roundness: 3,
  colors: {
    ...MD3LightTheme.colors,
    primary: colors.primary,
    onPrimary: '#ffffff',
    secondary: colors.accent,
    background: colors.background,
    surface: colors.surface,
    outline: colors.border,
    error: colors.danger,
  },
};
