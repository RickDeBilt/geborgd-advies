import { getCollection, type CollectionEntry } from "astro:content";

export type Article = CollectionEntry<"actueel">;

/**
 * Concepten (draft: true) worden in de productiebuild nooit getoond.
 * Tijdens `npm run dev` zijn ze wel zichtbaar, zodat u ze kunt controleren.
 */
function isVisible(entry: Article): boolean {
  return import.meta.env.PROD ? entry.data.draft !== true : true;
}

/** Nieuwste eerst, op publicatiedatum. */
function byNewest(a: Article, b: Article): number {
  return b.data.publishedDate.getTime() - a.data.publishedDate.getTime();
}

/** Alle publiceerbare artikelen, nieuwste eerst. */
export async function getPublishedArticles(): Promise<Article[]> {
  const entries = await getCollection("actueel", isVisible);
  return entries.sort(byNewest);
}

/**
 * Maximaal drie gerelateerde artikelen: eerst dezelfde categorie,
 * daarna aangevuld met de nieuwste overige artikelen.
 */
export function getRelatedArticles(
  current: Article,
  all: Article[],
  limit = 3,
): Article[] {
  const others = all.filter((a) => a.id !== current.id);
  const sameCategory = others.filter(
    (a) => a.data.category === current.data.category,
  );
  const rest = others.filter((a) => a.data.category !== current.data.category);
  return [...sameCategory, ...rest].slice(0, limit);
}

/** Geschatte leestijd in minuten (ca. 200 woorden per minuut). */
export function readingTime(body: string | undefined): number {
  if (!body) return 1;
  const words = body.trim().split(/\s+/).filter(Boolean).length;
  return Math.max(1, Math.round(words / 200));
}

const dateFormatter = new Intl.DateTimeFormat("nl-NL", {
  day: "numeric",
  month: "long",
  year: "numeric",
});

/** Datum als "7 juli 2026". */
export function formatDate(date: Date): string {
  return dateFormatter.format(date);
}

/** Datum als machineleesbare ISO-datum (YYYY-MM-DD) voor <time datetime>. */
export function isoDate(date: Date): string {
  return date.toISOString().split("T")[0];
}
