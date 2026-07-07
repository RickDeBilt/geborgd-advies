---
# ─────────────────────────────────────────────────────────────
#  Sjabloon voor een nieuw Actueel-artikel.
#  Kopieer dit bestand naar src/content/actueel/ en geef het een
#  eigen bestandsnaam, bijvoorbeeld: mijn-nieuwe-artikel.md
#  Verwijder daarna deze uitleg-regels (alles met een #).
# ─────────────────────────────────────────────────────────────

# VERPLICHT — de titel van het artikel (dit wordt de <h1>).
title: "Titel van uw artikel"

# OPTIONEEL — alternatieve titel voor het browsertabblad/Google.
# Laat weg om gewoon 'title' te gebruiken.
# seoTitle: "Kortere SEO-titel"

# VERPLICHT — korte samenvatting (1 à 2 zinnen). Wordt gebruikt als
# meta-description en op de overzichtskaart.
description: "Korte, wervende samenvatting van waar dit artikel over gaat."

# OPTIONEEL — intro-alinea bovenaan het artikel. Zonder deze regel
# wordt 'description' gebruikt.
# intro: "Iets uitgebreidere introductie bovenaan de pagina."

# VERPLICHT — publicatiedatum in de vorm JAAR-MAAND-DAG.
publishedDate: 2026-01-01

# OPTIONEEL — datum waarop u het artikel later heeft aangepast.
# updatedDate: 2026-02-01

# OPTIONEEL — datum waarop u de inhoud voor het laatst heeft
# gecontroleerd. Zonder deze regel wordt de publicatiedatum gebruikt.
# reviewedDate: 2026-01-01

# VERPLICHT — kies precies één categorie uit deze lijst:
#   Financieel | Personeel | Wet- en regelgeving | Ondernemen | WIA & IVA
category: "Financieel"

# OPTIONEEL — auteur en organisatie. Zonder deze regels wordt
# automatisch "Edwin Borghuis" / "Geborgd Advies" gebruikt.
# author: "Edwin Borghuis"
# authorRole: "Geborgd Advies"

# OPTIONEEL — zet op true om dit artikel uit te lichten op /actueel/.
featured: false

# VERPLICHT om te publiceren — zet op false.
# Zolang dit true is, verschijnt het artikel NIET op de live website.
draft: true

# OPTIONEEL — headerafbeelding. Plaats het bestand in
# public/images/actueel/ en verwijs ernaar met een pad dat begint
# met /images/. Zonder afbeelding toont de site automatisch een
# nette placeholder in de huisstijl.
# image: "/images/actueel/mijn-afbeelding.jpg"
# imageAlt: "Beschrijving van de afbeelding voor toegankelijkheid en SEO."

# OPTIONEEL — het opvallende "In het kort"-blok bovenaan.
# keyTakeaways:
#   - "Belangrijkste punt 1."
#   - "Belangrijkste punt 2."
#   - "Belangrijkste punt 3."

# VERPLICHT (mag leeg blijven, maar liever niet) — de bronnen die
# onderaan het artikel worden getoond. Herhaal het blok per bron.
sources:
  - name: "Naam van de organisatie/website"
    title: "Titel van de bronpagina"
    url: "https://www.voorbeeld.nl/bronpagina"
---

Schrijf hier de eerste alinea van uw artikel. Gebruik korte alinea's en de
beleefde aanspreekvorm "u".

## Een tussenkop (H2)

Tekst onder de tussenkop. U kunt **belangrijke woorden vetgedrukt** maken.

### Een subkop (H3)

Nog meer tekst. U kunt ook lijsten gebruiken:

- eerste punt;
- tweede punt;
- derde punt.

Of een genummerd stappenplan:

1. eerste stap;
2. tweede stap;
3. derde stap.

## Afsluiting

Sluit af met een korte, praktische conclusie. Het bronnenblok, de disclaimer
en de contact-oproep worden automatisch onder het artikel toegevoegd — die
hoeft u hier niet zelf te typen.
