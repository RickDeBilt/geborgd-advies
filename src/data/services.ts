export type IconName =
  | "ledger"
  | "bank"
  | "chart"
  | "backoffice"
  | "document"
  | "support";

export interface Service {
  /** Ankerslug, gebruikt op /diensten */
  id: string;
  title: string;
  /** Korte omschrijving voor kaarten */
  summary: string;
  /** Uitgebreide omschrijving voor de dienstenpagina */
  description: string;
  /** Concrete onderdelen binnen de dienst */
  points: string[];
  icon: IconName;
}

export const services: Service[] = [
  {
    id: "administratie",
    title: "Financiële administratie",
    summary:
      "Uw dagelijkse administratie accuraat bijgehouden, zodat uw cijfers altijd kloppen.",
    description:
      "Wij verzorgen en verwerken de volledige financiële administratie van uw onderneming. Van inkoop- en verkoopfacturen tot een verzorgde grootboekadministratie — netjes, actueel en controleerbaar.",
    points: [
      "Verwerken van inkoop- en verkoopfacturen",
      "Grootboek- en debiteuren-/crediteurenbeheer",
      "Voorbereiding richting uw accountant",
    ],
    icon: "ledger",
  },
  {
    id: "bankgiro",
    title: "Bank- & giroverwerking",
    summary:
      "Bank- en giromutaties tijdig en correct verwerkt en afgestemd.",
    description:
      "Bankafschriften en giromutaties worden nauwkeurig verwerkt en afgestemd met uw administratie. Zo houdt u zicht op inkomende en uitgaande geldstromen en blijft uw liquiditeit inzichtelijk.",
    points: [
      "Dagelijkse of periodieke verwerking van mutaties",
      "Afletteren en aansluiten van betalingen",
      "Signaleren van openstaande posten",
    ],
    icon: "bank",
  },
  {
    id: "inzicht",
    title: "Financieel inzicht & rapportage",
    summary:
      "Heldere overzichten en rapportages die grip geven op uw cijfers.",
    description:
      "Cijfers zijn pas waardevol als u ze begrijpt. Wij vertalen uw administratie naar overzichtelijke rapportages, zodat u weet waar u staat en onderbouwde beslissingen kunt nemen.",
    points: [
      "Periodieke tussentijdse overzichten",
      "Inzicht in omzet, kosten en marge",
      "Toelichting in begrijpelijke taal",
    ],
    icon: "chart",
  },
  {
    id: "backoffice",
    title: "Backoffice ondersteuning",
    summary:
      "Een betrouwbare backoffice die uw administratieve processen ontzorgt.",
    description:
      "Als verlengstuk van uw organisatie nemen wij administratieve en organisatorische taken uit handen. Flexibel inzetbaar en afgestemd op uw processen, zodat u zich kunt richten op ondernemen.",
    points: [
      "Administratieve en organisatorische taken",
      "Ondersteuning bij accountancywerkzaamheden",
      "Structuur en overzicht in uw processen",
    ],
    icon: "backoffice",
  },
  {
    id: "hr",
    title: "Beleidsstukken & HR-documentatie",
    summary:
      "Zorgvuldig opgestelde beleidsstukken, arbeidscontracten en personeelshandboeken.",
    description:
      "Goede documentatie voorkomt misverstanden en geeft duidelijkheid aan u en uw medewerkers. Wij stellen beleidsstukken, arbeidscontracten en personeelshandboeken op die passen bij uw organisatie.",
    points: [
      "Arbeidscontracten en aanvullende afspraken",
      "Personeelshandboeken en huisregels",
      "Beleidsstukken op maat",
    ],
    icon: "document",
  },
  {
    id: "wia-iva",
    title: "Begeleiding bij WIA/IVA-trajecten",
    summary:
      "Persoonlijke begeleiding en ondersteuning bij WIA- en IVA-trajecten.",
    description:
      "WIA- en IVA-trajecten zijn ingrijpend en complex. Wij bieden praktische ondersteuning en begeleiding, zodat u en uw medewerkers weten waar u aan toe bent en de juiste stappen zet.",
    points: [
      "Uitleg van rechten, plichten en het proces",
      "Ondersteuning bij dossiervorming",
      "Begeleiding voor werkgever en medewerker",
    ],
    icon: "support",
  },
];

/** SVG-padnotatie per icoon (24×24, stroke-based) */
export const iconPaths: Record<IconName, string> = {
  ledger: "M5 4h11l3 3v13H5V4Zm3 5h7M8 12h7M8 15h4",
  bank: "M4 9 12 4l8 5M5 9v9h14V9M9 12v3m6-3v3M4 20h16",
  chart: "M4 20V4m0 16h16M8 16v-4m4 4V8m4 8v-6",
  backoffice: "M4 7h16v11H4V7Zm0 4h16M9 3h6v4H9V3Z",
  document: "M6 3h8l4 4v14H6V3Zm8 0v4h4M9 12h6m-6 3h6m-6-6h2",
  support: "M12 3a7 7 0 0 0-7 7v4a3 3 0 0 0 3 3h1v-6H7v-1a5 5 0 0 1 10 0v1h-2v6h1a3 3 0 0 0 3-3v-4a7 7 0 0 0-7-7Z",
};
