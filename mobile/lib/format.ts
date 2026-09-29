// Mise en forme partagée entre les écrans (dates, montants) — mêmes conventions que la plateforme Web (§7 : «
// number_format($montant, 0, ',', ' ') . ' FCFA' »).

export function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
}

export function formatFcfa(amount: number): string {
  return `${amount.toLocaleString('fr-FR').replace(/ /g, ' ')} FCFA`;
}
