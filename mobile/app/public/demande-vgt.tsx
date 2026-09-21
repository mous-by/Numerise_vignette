import ComingSoon from '@/components/ComingSoon';

// M4 : demande de VGT sans compte, en attendant W11 et W12 et les réponses du client (règles de la vignette, identification).
export default function VgtRequestScreen() {
  return <ComingSoon icon="file-document-outline" title="Demande de VGT" text="Une fois votre moto enregistrée au commissariat, demandez ici le renouvellement de votre VGT." />;
}
