import type { Loader, LoaderContext } from "astro/loaders";
import { CATEGORIES } from "../data/categories";

/**
 * Astro content-loader die "Actueel"-artikelen bij het bouwen ophaalt uit een
 * headless WordPress-installatie (REST API).
 *
 * De loader mapt de WordPress-velden 1-op-1 op hetzelfde schema dat de site al
 * gebruikt, inclusief de kant-en-klare HTML. Daardoor blijven alle bestaande
 * componenten (ArticleLayout, ArticleCard, RSS, sitemap) ongewijzigd werken.
 *
 * Activeren: zet de omgevingsvariabele WP_API_URL naar de basis-URL van de
 * WordPress-site, bijv. https://cms.geborgdadvies.nl  (zie docs/WORDPRESS.md).
 */

interface WordPressLoaderOptions {
  /** Basis-URL van de WordPress-site, zonder /wp-json. */
  endpoint: string;
  /** Aantal artikelen per pagina (max. 100). */
  perPage?: number;
}

/** Ruwe vorm zoals de WordPress REST API die teruggeeft (alleen wat wij gebruiken). */
interface WpPost {
  id: number;
  slug: string;
  date: string;
  modified: string;
  status: string;
  title: { rendered: string };
  excerpt: { rendered: string };
  content: { rendered: string };
  categories: number[];
  meta?: Record<string, unknown>;
  _embedded?: {
    author?: Array<{ name?: string }>;
    "wp:term"?: Array<Array<{ taxonomy: string; name: string }>>;
    "wp:featuredmedia"?: Array<{
      source_url?: string;
      alt_text?: string;
    }>;
  };
}

/** Verwijdert HTML-tags en normaliseert witruimte — voor leestijd en beschrijving. */
function stripHtml(html: string): string {
  return decodeEntities(html.replace(/<[^>]+>/g, " "))
    .replace(/\s+/g, " ")
    .trim();
}

/** Decodeert de meestvoorkomende HTML-entiteiten (titels, apostrofs, etc.). */
function decodeEntities(text: string): string {
  return text
    .replace(/&amp;/g, "&")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/&quot;/g, '"')
    .replace(/&#0?39;/g, "'")
    .replace(/&#8217;/g, "’")
    .replace(/&#8216;/g, "‘")
    .replace(/&#8220;/g, "“")
    .replace(/&#8221;/g, "”")
    .replace(/&#8211;/g, "–")
    .replace(/&#8212;/g, "—")
    .replace(/&nbsp;/g, " ")
    .replace(/&hellip;/g, "…")
    .replace(/&euro;/g, "€");
}

/** Eerste toegewezen categorie die geldig is voor de site; anders een veilige fallback. */
function resolveCategory(post: WpPost, logger: LoaderContext["logger"]): string {
  const terms =
    post._embedded?.["wp:term"]?.flat().filter((t) => t.taxonomy === "category") ??
    [];
  const match = terms.find((t) =>
    (CATEGORIES as readonly string[]).includes(decodeEntities(t.name)),
  );
  if (match) return decodeEntities(match.name);

  const fallback: string = CATEGORIES[3]; // "Ondernemen"
  if (terms.length > 0) {
    logger.warn(
      `Artikel "${post.slug}" heeft categorie "${terms[0].name}", die niet in de ` +
        `toegestane lijst staat. Gebruikt "${fallback}". Pas de categorie in ` +
        `WordPress aan naar één van: ${CATEGORIES.join(", ")}.`,
    );
  }
  return fallback;
}

/** Leest een meta-veld en geeft undefined terug bij lege waarden. */
function metaString(meta: WpPost["meta"], key: string): string | undefined {
  const value = meta?.[key];
  if (typeof value !== "string") return undefined;
  const trimmed = value.trim();
  return trimmed.length > 0 ? trimmed : undefined;
}

function metaBool(meta: WpPost["meta"], key: string): boolean {
  const value = meta?.[key];
  return value === true || value === "1" || value === 1;
}

function metaArray<T>(meta: WpPost["meta"], key: string): T[] {
  const value = meta?.[key];
  return Array.isArray(value) ? (value as T[]) : [];
}

async function fetchAllPosts(options: WordPressLoaderOptions): Promise<WpPost[]> {
  const base = options.endpoint.replace(/\/+$/, "");
  const perPage = options.perPage ?? 100;
  const posts: WpPost[] = [];
  let page = 1;
  let totalPages = 1;

  do {
    const url =
      `${base}/wp-json/wp/v2/posts` +
      `?per_page=${perPage}&page=${page}&_embed=author,wp:term,wp:featuredmedia&status=publish`;
    const response = await fetch(url, {
      headers: { Accept: "application/json" },
    });

    if (!response.ok) {
      throw new Error(
        `WordPress REST API gaf ${response.status} (${response.statusText}) ` +
          `terug voor ${url}. Controleer WP_API_URL en of de site bereikbaar is.`,
      );
    }

    const header = response.headers.get("x-wp-totalpages");
    totalPages = header ? Number(header) : 1;
    posts.push(...((await response.json()) as WpPost[]));
    page += 1;
  } while (page <= totalPages);

  return posts;
}

export function wordpressLoader(options: WordPressLoaderOptions): Loader {
  return {
    name: "geborgd-advies-wordpress",
    async load({ store, parseData, generateDigest, logger }: LoaderContext) {
      logger.info(`Artikelen ophalen uit WordPress: ${options.endpoint}`);
      const posts = await fetchAllPosts(options);
      store.clear();

      for (const post of posts) {
        const meta = post.meta ?? {};
        const media = post._embedded?.["wp:featuredmedia"]?.[0];
        const authorName = post._embedded?.author?.[0]?.name;

        const raw = {
          title: decodeEntities(post.title.rendered),
          seoTitle: metaString(meta, "ga_seo_title"),
          description: stripHtml(post.excerpt.rendered),
          intro: metaString(meta, "ga_intro"),
          publishedDate: post.date,
          reviewedDate: metaString(meta, "ga_reviewed_date"),
          category: resolveCategory(post, logger),
          author: authorName || undefined,
          authorRole: metaString(meta, "ga_author_role"),
          featured: metaBool(meta, "ga_featured"),
          draft: false,
          image: media?.source_url,
          imageAlt: media?.alt_text || metaString(meta, "ga_image_alt"),
          keyTakeaways: metaArray<string>(meta, "ga_key_takeaways"),
          sources: metaArray<{ name: string; title: string; url: string }>(
            meta,
            "ga_sources",
          ),
        };

        // Verwijder undefined-waarden zodat schema-standaardwaarden pakken.
        const data = Object.fromEntries(
          Object.entries(raw).filter(([, v]) => v !== undefined),
        );

        const parsed = await parseData({ id: post.slug, data });
        const html = post.content.rendered;

        store.set({
          id: post.slug,
          data: parsed,
          body: stripHtml(html),
          rendered: { html },
          digest: generateDigest({ html, data: parsed }),
        });
      }

      logger.info(`${posts.length} artikel(en) geladen uit WordPress.`);
    },
  };
}
