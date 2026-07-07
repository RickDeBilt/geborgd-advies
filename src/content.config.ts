import { defineCollection, z } from "astro:content";
import { glob } from "astro/loaders";

/**
 * Toegestane categorieën voor Actueel-artikelen.
 * Wilt u een nieuwe categorie? Voeg hem hier toe én in de filterlijst
 * op /actueel/ (src/pages/actueel/index.astro). Zie docs/BLOG-TOEVOEGEN.md.
 */
export const CATEGORIES = [
  "Financieel",
  "Personeel",
  "Wet- en regelgeving",
  "Ondernemen",
  "WIA & IVA",
] as const;

/** Eén bron onder een artikel (naam, titel en URL). */
const sourceSchema = z.object({
  name: z.string(),
  title: z.string(),
  url: z.string().url(),
});

const actueel = defineCollection({
  // Elk artikel is een los Markdown-bestand in src/content/actueel/.
  // Bestanden die met een underscore beginnen (bijv. _template.md) worden
  // door Astro genegeerd en nooit als artikel verwerkt.
  loader: glob({ pattern: "**/[^_]*.md", base: "./src/content/actueel" }),
  schema: z.object({
    title: z.string(),
    /** Alternatieve titel voor de <title>/SEO. Valt terug op `title`. */
    seoTitle: z.string().optional(),
    description: z.string(),
    /** Korte intro bovenaan het artikel. Valt terug op `description`. */
    intro: z.string().optional(),
    publishedDate: z.coerce.date(),
    updatedDate: z.coerce.date().optional(),
    /** Datum waarop de inhoud voor het laatst is gecontroleerd. */
    reviewedDate: z.coerce.date().optional(),
    category: z.enum(CATEGORIES),
    author: z.string().default("Edwin Borghuis"),
    /** Organisatie/rol van de auteur. */
    authorRole: z.string().default("Geborgd Advies"),
    featured: z.boolean().default(false),
    draft: z.boolean().default(false),
    /** Pad naar een afbeelding in /public, bijv. "/images/actueel/naam.jpg". */
    image: z.string().optional(),
    imageAlt: z.string().optional(),
    /** Opvallend "In het kort"-blok bovenaan het artikel. */
    keyTakeaways: z.array(z.string()).optional(),
    /** Bronnen die onderaan het artikel worden getoond. */
    sources: z.array(sourceSchema).default([]),
  }),
});

export const collections = { actueel };
