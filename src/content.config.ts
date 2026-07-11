import { defineCollection, z } from "astro:content";
import { glob } from "astro/loaders";
import { CATEGORIES } from "./data/categories";
import { wordpressLoader } from "./loaders/wordpress";

// Her-export zodat bestaande imports vanuit content.config blijven werken.
export { CATEGORIES } from "./data/categories";

/**
 * Bron van de artikelen.
 *
 * - Zonder WP_API_URL: lokale Markdown-bestanden in src/content/actueel/
 *   (het gedrag zoals de site altijd heeft gewerkt).
 * - Met WP_API_URL gezet: artikelen worden bij het bouwen uit de headless
 *   WordPress-installatie gehaald. Zie docs/WORDPRESS.md.
 */
const WP_API_URL = process.env.WP_API_URL?.trim();

/** Eén bron onder een artikel (naam, titel en URL). */
const sourceSchema = z.object({
  name: z.string(),
  title: z.string(),
  url: z.string().url(),
});

const actueel = defineCollection({
  // Bron: WordPress (headless) wanneer WP_API_URL is gezet, anders lokale
  // Markdown-bestanden in src/content/actueel/. Bestanden die met een
  // underscore beginnen (bijv. _template.md) worden door Astro genegeerd.
  loader: WP_API_URL
    ? wordpressLoader({ endpoint: WP_API_URL })
    : glob({ pattern: "**/[^_]*.md", base: "./src/content/actueel" }),
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
