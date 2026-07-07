import rss from "@astrojs/rss";
import type { APIContext } from "astro";
import { getPublishedArticles } from "../utils/articles";

export async function GET(context: APIContext) {
  const articles = await getPublishedArticles();
  const site = context.site ?? new URL("https://www.geborgdadvies.nl");

  return rss({
    title: "Geborgd Advies — Actueel & inzicht",
    description:
      "Praktische uitleg over financiële, administratieve en personele ontwikkelingen die relevant zijn voor ondernemers.",
    site,
    items: articles.map((article) => ({
      title: article.data.title,
      description: article.data.description,
      pubDate: article.data.publishedDate,
      link: `/actueel/${article.id}/`,
      categories: [article.data.category],
    })),
    customData: "<language>nl-nl</language>",
  });
}
