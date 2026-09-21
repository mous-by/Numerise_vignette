/**
 * Mode maquette : l'application répond avec des données factices conformes au contrat d'API (CLAUDE.md, §8), sans
 * serveur Laravel. Il permet de construire un écran avant que son endpoint existe. Développement uniquement : `__DEV__`
 * vaut false dans un build de production, le mode est alors ignoré même si la variable est définie.
 */
export const USE_MOCK = __DEV__ && process.env.EXPO_PUBLIC_USE_MOCK === 'true';
