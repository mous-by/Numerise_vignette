import type { Information, User } from '@/types/api';
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
