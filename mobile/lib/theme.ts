// Palette VigiMoto : synchronisée avec public/assets/css/numerise.css et bootstrap.min.css (mêmes valeurs
// hexadécimales, vérifiées ligne à ligne le 2026-09-30 après la refonte visuelle du Web) — voir CLAUDE.md, §7.
export const colors = {
  primary: '#1d4e89', // --nv-accent
  dark: '#123a63', // --nv-dark
  accent: '#f97316', // --nv-accent-2
  lightBlue: '#7fb0e8',
  background: '#f2f5fa', // --nv-bg
  surface: '#ffffff',
  text: '#4c5258', // body { color }
  muted: '#64748b', // en-têtes de tableau, texte discret
  border: '#dfe5ee',
  // --bs-success/--bs-danger/--bs-warning/--bs-info du thème (bootstrap.min.css), pas les valeurs par défaut de
  // Bootstrap : le thème les a remplacées.
  danger: '#f41127',
  success: '#17a00e',
  warning: '#ffc107',
  info: '#0dcaf0',
} as const;

/**
 * Pastilles de statut douces (`.bg-*-subtle`, numerise.css) : Bootstrap 5.0 (le thème) n'a pas ces variantes,
 * VigiMoto les a ajoutées pour les badges de statut (ex. DemandeVgtStatus côté Web). Mêmes couleurs ici pour
 * qu'un statut ait le même rendu sur mobile — ex. `statusColors.success` pour une demande « Payée ».
 */
export const statusColors = {
  primary: { bg: '#e4edf9', text: '#1d4e89' },
  secondary: { bg: '#eceff3', text: '#4d5b6e' },
  success: { bg: '#e0f5e8', text: '#14803f' },
  danger: { bg: '#fde6e8', text: '#b4232e' },
  warning: { bg: '#fff1d6', text: '#a86400' },
  // .bg-light/.text-dark (Bootstrap standard, pas remplacé par le thème) : « En attente » côté Web.
  light: { bg: '#f8f9fa', text: '#212529' },
} as const;

/**
 * Couleurs du statut d'une demande de VGT (M4), mêmes couleurs que `App\Enums\DemandeVgtStatus::badgeClass()`
 * côté Web — un statut a le même rendu sur les deux plateformes.
 */
export function demandeVgtStatusColors(code: string): (typeof statusColors)[keyof typeof statusColors] {
  switch (code) {
    case 'validee':
      return statusColors.success;
    case 'rejetee':
      return statusColors.danger;
    case 'payee':
      return statusColors.primary;
    case 'retiree':
      return statusColors.secondary;
    default:
      return statusColors.light; // en_attente
  }
}
