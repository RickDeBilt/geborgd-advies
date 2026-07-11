/**
 * Toegestane categorieën voor Actueel-artikelen.
 *
 * Wilt u een nieuwe categorie? Voeg hem hier toe. Zorg dat dezelfde naam
 * bestaat als WordPress-categorie (zie docs/WORDPRESS.md) én, indien u nog
 * met Markdown werkt, in de filterlijst op /actueel/.
 */
export const CATEGORIES = [
  "Financieel",
  "Personeel",
  "Wet- en regelgeving",
  "Ondernemen",
  "WIA & IVA",
] as const;

export type Category = (typeof CATEGORIES)[number];
