# Headless WordPress voor "Actueel"

Dit document beschrijft hoe de klant zelf artikelen kan schrijven onder
**Actueel** via WordPress, terwijl de website zelf de snelle, op maat gebouwde
Astro-site blijft.

## Hoe het werkt (in het kort)

```
  Edwin schrijft in WordPress            De website (Astro)
  ┌──────────────────────────┐           ┌─────────────────────────┐
  │  cms.geborgdadvies.nl     │  REST     │  www.geborgdadvies.nl   │
  │  (wp-admin)               │──API────▶ │  bouwt de artikelen in  │
  │  Bericht + velden         │           │  het bestaande ontwerp  │
  └──────────────────────────┘           └─────────────────────────┘
```

- **WordPress** is alleen de schrijfomgeving. Bezoekers komen er nooit.
- De **Astro-site** haalt de artikelen bij het *bouwen* op via de WordPress
  REST API en rendert ze met het bestaande ontwerp (dezelfde opmaak, "In het
  kort"-blokken, bronnen, SEO enz.).
- Na een publicatie moet de site **opnieuw gebouwd** worden. Dat kan handmatig
  of automatisch (zie [Publiceren & herbouwen](#5-publiceren--herbouwen)).

Zonder de omgevingsvariabele `WP_API_URL` werkt de site precies zoals voorheen
op basis van de lokale Markdown-bestanden. Zodra `WP_API_URL` is ingesteld,
komen de artikelen uit WordPress. Zo kun je rustig overstappen.

---

## 1. WordPress installeren op de hosting

1. Maak op de bestaande webhosting een **subdomein** aan, bijvoorbeeld
   `cms.geborgdadvies.nl`, met een eigen map.
2. Maak in het hostingpaneel een **MySQL-database** aan (naam, gebruiker,
   wachtwoord noteren).
3. Installeer WordPress in dat subdomein. Bij veel hosters kan dat met één klik
   (Softaculous/Installatron); anders handmatig via
   <https://nl.wordpress.org/download/>.
4. Rond de WordPress-installatie af (sitenaam, beheeraccount).

> Tip: kies bij de installatie meteen een sterk beheerderswachtwoord en gebruik
> **HTTPS** (SSL-certificaat) voor het subdomein. De contactform-PHP en de rest
> van de site staan hier los van.

## 2. De plugin activeren

De plugin **Geborgd Advies — Actueel CMS** zorgt dat wp-admin exact de velden
toont die de website gebruikt, en stelt ze beschikbaar via de REST API.

1. Comprimeer de map [`wordpress/geborgd-advies-cms/`](../wordpress/geborgd-advies-cms/)
   tot een `.zip`.
2. Ga in wp-admin naar **Plugins → Nieuwe plugin → Plugin uploaden**, kies het
   zip-bestand en klik op **Nu installeren** → **Activeren**.

Bij het activeren worden meteen de vijf categorieën aangemaakt:
*Financieel, Personeel, Wet- en regelgeving, Ondernemen, WIA & IVA*.

## 3. WordPress instellen

1. **Permalinks**: ga naar **Instellingen → Permalinks** en kies
   **Berichtnaam** (`/%postname%/`). De slug van een bericht wordt de URL op de
   website: `www.geborgdadvies.nl/actueel/<slug>/`.
2. **Samenvatting tonen**: open een bericht, klik rechtsboven op
   **Schermopties** (of in de blok-editor op de drie puntjes → **Voorkeuren →
   Panelen**) en zet **Samenvatting** aan. Die samenvatting wordt de korte tekst
   in de artikellijst en de meta-description.
3. **Gebruiker voor Edwin**: maak onder **Gebruikers** een account aan met de
   rol **Auteur** of **Redacteur**. Vul bij het profiel de weergavenaam
   (bijv. "Edwin Borghuis") in — die verschijnt als auteur op de site.
4. Optioneel: verberg de standaardcategorie "Geen categorie" door hem niet te
   gebruiken; kies altijd één van de vijf vaste categorieën.

## 4. De website aan WordPress koppelen

De website leest de artikelen via de omgevingsvariabele `WP_API_URL`.

1. Kopieer [`.env.example`](../.env.example) naar `.env`.
2. Zet de basis-URL van de WordPress-site (zonder `/wp-json` en zonder slash):

   ```
   WP_API_URL=https://cms.geborgdadvies.nl
   ```

3. Bouw de site:

   ```
   npm run build
   ```

   In de bouwlog zie je: `Artikelen ophalen uit WordPress …` en het aantal
   geladen artikelen. De statische site komt in de map `dist/`.

> Controleer of `https://cms.geborgdadvies.nl/wp-json/wp/v2/posts` in de browser
> een JSON-lijst met berichten teruggeeft. Zo niet, controleer permalinks en of
> de REST API niet door een beveiligingsplugin geblokkeerd wordt.

## 5. Publiceren & herbouwen

Omdat de site statisch is, verschijnt een nieuw artikel pas online nadat de site
opnieuw is **gebouwd en geüpload**. Er zijn twee manieren.

### Optie A — Handmatig (eenvoudig, geen extra techniek)

Wie de site beheert, draait na een publicatie:

```
npm run build
```

en uploadt de inhoud van `dist/` via FTP naar de webhosting (dezelfde plek waar
de site nu staat). Geschikt als artikelen niet direct live hoeven.

### Optie B — Automatisch via GitHub Actions (aanbevolen)

Hiermee gaat een nieuw artikel vanzelf live nadat Edwin op **Publiceren** klikt.
De site staat al op GitHub: `RickDeBilt/geborgd-advies`.

**Stap 1 — Workflow toevoegen.**
Kopieer [`docs/examples/deploy.yml`](examples/deploy.yml) naar
`.github/workflows/deploy.yml` en push naar `main`.

**Stap 2 — Secrets zetten** (GitHub → repo → **Settings → Secrets and variables
→ Actions → New repository secret**):

| Secret           | Waarde                                             |
| ---------------- | -------------------------------------------------- |
| `WP_API_URL`     | `https://cms.geborgdadvies.nl`                     |
| `FTP_SERVER`     | FTP-host van de webhosting (bijv. `ftp.mijnhost.nl`) |
| `FTP_USERNAME`   | FTP-gebruikersnaam                                 |
| `FTP_PASSWORD`   | FTP-wachtwoord                                     |
| `FTP_REMOTE_DIR` | Doelmap van de site, bijv. `/public_html/`         |

**Stap 3 — GitHub-token maken.**
Ga naar GitHub → **Settings (account) → Developer settings → Personal access
tokens → Tokens (classic) → Generate new token**. Geef scope **`repo`** en
kopieer de token (`ghp_…`).

**Stap 4 — WordPress koppelen.**
In wp-admin → **Instellingen → Schrijven**:

- **Deploy-webhook**: `https://api.github.com/repos/RickDeBilt/geborgd-advies/dispatches`
- **Deploy-token**: de token uit stap 3

> Liever geen token in de database? Zet in plaats daarvan in `wp-config.php`:
> ```php
> define('GA_DEPLOY_HOOK_URL', 'https://api.github.com/repos/RickDeBilt/geborgd-advies/dispatches');
> define('GA_DEPLOY_HOOK_TOKEN', 'ghp_uw_token');
> ```

Zodra een bericht wordt gepubliceerd of bijgewerkt, stuurt de plugin een
*repository_dispatch* (type `publish`) naar GitHub. De workflow bouwt de site
met de artikelen uit WordPress en zet `dist/` via FTP op de hosting — binnen
enkele minuten staat het artikel online.

> Kun of wil je (nog) geen token instellen? Begin dan met Optie A. De code is
> hetzelfde; alleen het herbouwen gaat dan handmatig.

## 6. Bestaande artikelen overzetten

> **Snelste manier — importbestand.** In de repo staat een kant-en-klaar
> WordPress-importbestand met de drie bestaande artikelen, inclusief alle velden
> (In het kort, bronnen, categorie, uitlichten): `wordpress/import/actueel-artikelen.xml`.
>
> 1. Verwijder in wp-admin eerst het voorbeeldbericht **"Hello world!"**.
> 2. Ga naar **Gereedschappen → Importeren → WordPress** en installeer zo nodig
>    de importer.
> 3. Upload `actueel-artikelen.xml`.
> 4. Bij **Auteurs toewijzen**: kies je eigen account (zet de weergavenaam op
>    "Edwin Borghuis" onder Gebruikers → Profiel). "Bijlagen importeren" is niet
>    nodig.
> 5. Controleer een bericht: het blok **"Actueel — artikelgegevens"** moet
>    gevuld zijn.
>
> Het importbestand is gegenereerd uit de Markdown-bronnen; je hoeft dus niets
> handmatig over te tikken.

Liever handmatig? Maak dan per artikel een nieuw **Bericht** aan en neem de
tekst en velden over:

| Website-veld        | In WordPress                                     |
| ------------------- | ------------------------------------------------ |
| Titel               | Berichttitel                                     |
| Slug (URL)          | Permalink/slug (houd exact gelijk voor SEO)      |
| Samenvatting        | Paneel **Samenvatting**                          |
| Categorie           | Categorie (kies uit de vaste lijst)              |
| Hoofdtekst          | De editor (koppen, lijsten, tabellen)            |
| Afbeelding          | **Uitgelichte afbeelding**                       |
| Intro, In het kort, Bronnen, SEO-titel, Controledatum, Uitlichten, CTA | Blok **"Actueel — artikelgegevens"** onder de editor |

Houd de **slug gelijk** aan de oude bestandsnaam, dan blijven bestaande links en
de SEO-waarde behouden.

## 7. Onderhoud & beveiliging

- Houd WordPress, thema en plugins **up-to-date** (of zet automatische updates
  aan). Dit is de belangrijkste taak bij een eigen WordPress.
- Overweeg een eenvoudige beveiligingsplugin en beperk inlogpogingen.
- De REST API moet **openbaar leesbaar** blijven voor gepubliceerde berichten
  (dat is standaard). Blokkeer hem niet volledig met een security-plugin.
- Maak periodiek een back-up van de database en de mediabibliotheek.

---

## Bijlage: artikel schrijven (voor de redacteur)

1. Ga naar **Berichten → Nieuw bericht**.
2. Typ de **titel** en schrijf de **hoofdtekst** in de editor (gebruik koppen
   "H2" voor tussenkopjes, net als in een tekstverwerker).
3. Rechts in het zijpaneel:
   - Kies één **Categorie**.
   - Stel eventueel een **Uitgelichte afbeelding** in.
   - Vul het paneel **Samenvatting** met één of twee zinnen (verschijnt in de
     lijst en bij Google).
4. Onder de editor, in het blok **"Actueel — artikelgegevens"**:
   - **Intro** — optionele openingszin bovenaan het artikel.
   - **In het kort** — de kernpunten, één per regel.
   - **Bronnen** — één per regel als `Naam | Titel | https://url`.
   - **Uitlichten** — aanvinken om het artikel groot bovenaan te tonen.
   - **Oproep onderaan (CTA)** — laat de CTA-velden leeg voor de standaardoproep,
     of vul een eigen titel, tekst, knoptekst en knoplink in. Vink **CTA
     verbergen** aan om er onder dit artikel géén te tonen.
   - Overige velden zijn optioneel.

> **Over de CTA (oproep onderaan).** Onder elk artikel staat standaard een
> oproep om contact op te nemen. Vult u niets in, dan verschijnt automatisch de
> standaardtekst. Wilt u voor een specifiek artikel een andere oproep of link
> (bijv. naar `/diensten/`)? Vul dan de CTA-velden in het blok *"Actueel —
> artikelgegevens"*. Alleen de ingevulde velden overschrijven de standaard; lege
> velden blijven de standaard gebruiken.
5. Klik op **Publiceren**. Bij automatische koppeling (Optie B) staat het
   artikel binnen enkele minuten online.
