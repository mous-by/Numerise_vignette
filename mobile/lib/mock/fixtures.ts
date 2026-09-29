import type { ControleResult, Information, PublicMairie, PublicMotoRetrouvee, User } from '@/types/api';
import { SAMPLE_IMAGE } from './sampleImage';

// Données entièrement fictives (aucun vrai nom, numéro ni identifiant) : elles n'existent qu'en mode maquette.

export const MOCK_USER: User = {
  id: 1,
  name: 'Agent de démonstration',
  phone: '+22300000000',
  role: { name: 'police', label: 'Police' },
  institution: { type: 'commissariat', id: 1, name: 'Commissariat de démonstration' },
  must_change_password: false,
  permissions: [],
};

const HOURS = 3_600_000;
const ago = (hours: number) => new Date(Date.now() - hours * HOURS).toISOString();

export const MOCK_INFORMATIONS: Information[] = [
  {
    id: 4,
    commissaire_name: 'Commissaire de démonstration',
    commissariat_name: 'Commissariat de démonstration',
    description: 'Exemple d\'information avec deux images : communication d\'un commissariat à la population (texte fictif).',
    image_urls: [SAMPLE_IMAGE, SAMPLE_IMAGE],
    document_urls: [],
    published_at: ago(2),
  },
  {
    id: 3,
    commissaire_name: 'Commissaire de démonstration',
    commissariat_name: 'Commissariat de démonstration',
    description: 'Exemple d\'information avec un document PDF. Rappel (exemple) : pensez à faire renouveler votre VGT avant sa date d\'échéance.',
    image_urls: [],
    document_urls: ['mock://document.pdf'],
    published_at: ago(30),
  },
  {
    id: 2,
    commissaire_name: 'Commissaire d\'un autre commissariat',
    commissariat_name: 'Autre commissariat de démonstration',
    description: null,
    image_urls: [SAMPLE_IMAGE],
    document_urls: ['mock://document.pdf'],
    published_at: ago(72),
  },
  {
    id: 1,
    commissaire_name: 'Commissaire d\'un autre commissariat',
    commissariat_name: 'Autre commissariat de démonstration',
    description: 'Exemple d\'information ne contenant qu\'un texte, plus ancien : c\'est la deuxième page de la liste.',
    image_urls: [],
    document_urls: [],
    published_at: ago(200),
  },
];

// M1 : trois matricules de démonstration pour essayer les trois résultats (voir CLAUDE.md, §9). N'importe quel
// autre matricule tapé renvoie « non trouvée », comme le ferait le vrai serveur pour une moto inconnue.
export const MOCK_CONTROLES: Record<string, ControleResult> = {
  'AB 1234 CD': { matricule: 'AB 1234 CD', volee: false, vgt_a_jour: true },
  'EF 5678 GH': { matricule: 'EF 5678 GH', volee: true, vgt_a_jour: false },
  'IJ 9012 KL': { matricule: 'IJ 9012 KL', volee: false, vgt_a_jour: false },
};

// M5 : motos retrouvées, non encore récupérées par leur propriétaire (voir CLAUDE.md, §8 « Contrat de la
// population »). Aucune donnée personnelle : les mêmes champs que l'API publique.
export const MOCK_MOTOS_RETROUVEES: PublicMotoRetrouvee[] = [
  { id: 3, matricule: 'QR 3344 ST', genre: 'Sanili', couleur: 'Noire', lieu: 'Pont des Martyrs', date_arret: ago(20).slice(0, 10), commissariat: 'Commissariat de démonstration' },
  { id: 2, matricule: 'UV 5566 WX', genre: 'Djakarta', couleur: 'Rouge', lieu: 'Marché de Médine', date_arret: ago(96).slice(0, 10), commissariat: 'Commissariat de démonstration' },
  { id: 1, matricule: 'YZ 7788 AB', genre: 'Sanili', couleur: 'Bleue', lieu: 'Rond-point de l\'Indépendance', date_arret: ago(240).slice(0, 10), commissariat: 'Autre commissariat de démonstration' },
];

// M4 : mairies de retrait proposées à la demande de VGT.
export const MOCK_MAIRIES: PublicMairie[] = [
  { id: 1, nom: 'Mairie de la Commune III (démonstration)' },
  { id: 2, nom: 'Mairie de la Commune IV (démonstration)' },
];

// M4 : identité fictive reconnue pour essayer une demande de VGT (matricule + téléphone enregistré, voir §8). Sa
// vignette est déjà à jour pour MOCK_OWNER_VGT_YEAR : demander cette année-là ou une année antérieure reproduit
// l'erreur « vignette déjà à jour » du vrai serveur, comme demander l'année suivante crée une demande.
export const MOCK_OWNER_MATRICULE = 'MN 4567 OP';
export const MOCK_OWNER_PHONE = '+22370000055';
export const MOCK_OWNER_VGT_YEAR = new Date().getFullYear();
