# Een nieuw artikel toevoegen aan "Actueel"

Deze handleiding legt in eenvoudige stappen uit hoe u een nieuw artikel
publiceert in de sectie **Actueel & inzicht** op de website van Geborgd Advies.
U hoeft hiervoor geen programmeur te zijn: een artikel is gewoon één
tekstbestand.

---

## In het kort

1. Kopieer het sjabloon `docs/blog-template.md`.
2. Plak het in de map `src/content/actueel/` en geef het een eigen naam.
3. Vul de gegevens bovenaan (de "frontmatter") en schrijf uw tekst.
4. Zet `draft: false` als het klaar is om te publiceren.
5. Controleer het resultaat en bouw de site.

---

## Stap 1 — Welk bestand kopiëren?

Kopieer het bestand:

```
docs/blog-template.md
```

Dit is het sjabloon. **Bewerk het sjabloon zelf niet**, maar maak er telkens
een kopie van voor een nieuw artikel.

## Stap 2 — De bestandsnaam en de slug kiezen

Plaats de kopie in de map:

```
src/content/actueel/
```

De **bestandsnaam** bepaalt automatisch de webadres-naam (de "slug") van het
artikel. Het bestand `minimumloon-2027.md` wordt bijvoorbeeld bereikbaar op:

```
/actueel/minimumloon-2027/
```

Richtlijnen voor een goede bestandsnaam:

- gebruik alleen **kleine letters**;
- gebruik **koppeltekens** (`-`) in plaats van spaties;
- gebruik **geen** hoofdletters, spaties, punten of speciale tekens;
- houd het kort en beschrijvend;
- het bestand eindigt altijd op `.md`.

> Let op: begin een bestandsnaam **niet** met een liggend streepje (`_`).
> Bestanden die met `_` beginnen worden expres genegeerd en niet als artikel
> gepubliceerd.

## Stap 3 — Welke velden zijn verplicht?

Bovenaan het bestand staat een blok tussen twee regels met `---`. Dit heet de
frontmatter. Deze velden zijn **verplicht**:

| Veld            | Uitleg                                                        |
| --------------- | ------------------------------------------------------------- |
| `title`         | De titel van het artikel.                                     |
| `description`   | Korte samenvatting (1 à 2 zinnen).                            |
| `publishedDate` | Publicatiedatum in de vorm `JAAR-MAAND-DAG`, bijv. `2026-07-07`. |
| `category`      | Precies één categorie (zie hieronder).                        |
| `draft`         | `true` of `false` (zie stap 4).                               |

Kies bij `category` één van deze waarden:

```
Financieel | Personeel | Wet- en regelgeving | Ondernemen | WIA & IVA
```

Optionele velden (mag u weglaten): `seoTitle`, `intro`, `updatedDate`,
`reviewedDate`, `author`, `authorRole`, `featured`, `image`, `imageAlt`,
`keyTakeaways` en `sources`. In het sjabloon staat bij elk veld uitleg.

- Laat u `author` en `authorRole` weg, dan wordt automatisch
  **Edwin Borghuis** / **Geborgd Advies** gebruikt.
- Zet `featured: true` om een artikel uit te lichten bovenaan de
  overzichtspagina.
- Met `keyTakeaways` vult u het opvallende blok **"In het kort"** bovenaan het
  artikel.

## Stap 4 — Hoe werken `draft: true` en `draft: false`?

Het veld `draft` bepaalt of een artikel al zichtbaar is op de live website:

- **`draft: true`** → het artikel is een **concept**. Het verschijnt **niet** op
  de gepubliceerde website, niet op de homepage, niet in het overzicht, niet in
  de sitemap en niet in de RSS-feed. Tijdens het lokaal bekijken
  (`npm run dev`) ziet u het concept wél, zodat u het kunt controleren.
- **`draft: false`** → het artikel wordt **gepubliceerd** zodra de site opnieuw
  wordt gebouwd.

Kortom: schrijf rustig met `draft: true`, en zet het pas op `false` wanneer het
artikel echt af en gecontroleerd is.

## Stap 5 — Waar plaatst u een afbeelding?

Een afbeelding is **niet verplicht**. Zonder afbeelding toont de site
automatisch een rustige placeholder in de huisstijl — de pagina breekt dus
nooit.

Wilt u wél een afbeelding gebruiken:

1. Plaats het afbeeldingsbestand in de map `public/images/actueel/`.
2. Verwijs er in de frontmatter naar met een pad dat begint met `/images/`:

   ```yaml
   image: "/images/actueel/mijn-afbeelding.jpg"
   imageAlt: "Korte beschrijving van de afbeelding."
   ```

Gebruik **alleen afbeeldingen waarvan u de rechten heeft**. Neem nooit foto's of
illustraties van nieuwswebsites of andere bronnen over.

## Stap 6 — Bronnen toevoegen

Onderaan elk artikel staat automatisch een bronnenblok. U vult de bronnen in de
frontmatter in. Herhaal het blokje per bron:

```yaml
sources:
  - name: "Rijksoverheid"
    title: "Titel van de pagina waar u het vandaan heeft"
    url: "https://www.rijksoverheid.nl/..."
  - name: "Belastingdienst"
    title: "Titel van de tweede bron"
    url: "https://www.belastingdienst.nl/..."
```

De bronnen openen automatisch in een nieuw tabblad met veilige instellingen.

## Stap 7 — De benodigde commando's

Open een terminal in de projectmap en gebruik:

```bash
# De site lokaal bekijken tijdens het schrijven (met live vernieuwen):
npm run dev

# De definitieve website bouwen (voor publicatie):
npm run build
```

Met `npm run dev` opent u de site in uw browser (meestal op
`http://localhost:4321`) en ziet u meteen hoe uw artikel eruitziet.

## Stap 8 — Inhoud controleren vóór publicatie

Voor het publiceren geldt een belangrijke regel: **fiscale, juridische en
arbeidsrechtelijke informatie moet vóór publicatie worden gecontroleerd** bij
primaire bronnen, zoals Rijksoverheid, Belastingdienst, UWV, KVK of de officiële
wetgeving. Bedragen, data en regels veranderen regelmatig.

Noteer waar mogelijk in `reviewedDate` wanneer u de inhoud voor het laatst heeft
gecontroleerd.

## Stap 9 — Nooit letterlijk overnemen

Gebruik andere websites (zoals De Zaak) **uitsluitend als inspiratie en bron**.
Neem **nooit** teksten, titels, formuleringen, afbeeldingen of de
artikelstructuur letterlijk over. Schrijf altijd een volledig originele tekst
voor Geborgd Advies.

---

## Een artikel later aanpassen of verwijderen

- **Aanpassen:** open het bestand in `src/content/actueel/`, wijzig de tekst, en
  vul eventueel `updatedDate` in. Bouw daarna de site opnieuw.
- **Tijdelijk offline halen:** zet `draft: true`.
- **Definitief verwijderen:** verwijder het `.md`-bestand uit
  `src/content/actueel/` en bouw de site opnieuw.

## Redactionele richtlijnen (kort)

- Gebruik de beleefde aanspreekvorm "u".
- Schrijf in helder Nederlands en leg vakjargon uit.
- Gebruik korte alinea's; maak de tekst scanbaar.
- Maak geen garanties over belastingaftrek, uitkeringen, subsidies of
  juridische uitkomsten.
- Positioneer Geborgd Advies als financieel specialist en praktische
  ondersteuner — niet als accountant, advocaat of juridisch adviesbureau.
- Verzin geen onderzoeken, percentages, wetten, bedragen of uitspraken.
