export interface FaqItem {
  question: string;
  answer: string;
}

export const SITE = {
  name: 'simplysichtbar',
  legalName: 'Magnolia Media KG',
  url: 'https://simplysichtbar.at',
  email: 'hallo@simplysichtbar.at',
  legalEmail: 'info@magnolia-media.at',
  phoneDisplay: '+43 677/639 81 658',
  phoneHref: 'tel:+4367763981658',
  whatsappHref: 'https://wa.me/4367763981658',
  address: {
    street: 'Kendl 12',
    postalCode: '3254',
    city: 'Bergland',
    region: 'Niederösterreich',
    country: 'AT',
  },
} as const;

export const NAV_LINKS = [
  { label: 'Warum neu?', href: '/#probleme' },
  { label: 'Preise', href: '/#preise' },
  { label: 'Ablauf', href: '/#ablauf' },
  { label: 'FAQ', href: '/#faq' },
] as const;

export const FAQ_ITEMS: FaqItem[] = [
  {
    question: 'Warum brauche ich bei euch keinen Wartungsvertrag?',
    answer:
      'Klassische Websites (z. B. mit WordPress) bestehen aus einer Datenbank und Dutzenden Plugins, die laufend Sicherheitsupdates brauchen, damit nichts kaputtgeht oder gehackt wird. Wir entwickeln deine Website als modernen, statischen Code – da gibt es keine Sicherheitslücken durch veraltete Erweiterungen und somit auch keine laufende Wartungspflicht.',
  },
  {
    question: 'Was bedeutet „GEO“ und warum ist das für meinen Betrieb wichtig?',
    answer:
      'GEO steht für Generative Engine Optimization – also die Optimierung für KI-Suchmaschinen wie ChatGPT, Google Gemini oder Perplexity. Wenn heute jemand fragt „Welcher Betrieb in meiner Nähe ist empfehlenswert?“, greifen KIs auf Websites zurück, die technisch sauber, schnell und mit strukturierten Daten programmiert sind. Genau darauf sind unsere Websites ausgelegt.',
  },
  {
    question: 'Reicht eine einmalige SEO- & GEO-Optimierung oder brauche ich ein laufendes Abo?',
    answer:
      'Für lokale Betriebe in der Region reicht ein technisch perfektes Fundament mit klaren lokalen Inhalten in 90 % der Fälle völlig aus, um vor der Konkurrenz zu stehen – ganz ohne monatliches SEO-Abo.',
  },
  {
    question: 'Was ist, wenn ich später Öffnungszeiten, Preise oder Fotos ändern möchte?',
    answer:
      'Schick uns einfach eine kurze E-Mail oder WhatsApp-Nachricht. Kleine Änderungen setzen wir meist innerhalb von 24 Stunden um und rechnen fair nach tatsächlichem Aufwand ab (ab 25 €) – ganz ohne laufenden Vertrag.',
  },
  {
    question: 'Gehört die Website wirklich mir?',
    answer:
      'Ja, zu 100 %. Du hast volle Rechte an Design, Texten und Quellcode. Wenn du irgendwann wechseln möchtest, kannst du deine komplette Website jederzeit mitnehmen.',
  },
  {
    question: 'Ich habe noch keine Texte oder professionelle Fotos – geht das trotzdem?',
    answer:
      'Ja! Wir helfen dir bei der Texterstellung, damit deine Leistungen klar und verkaufsstark auf den Punkt gebracht werden. Für den Start reichen oft schon gute Smartphone-Fotos deines Betriebs.',
  },
];
