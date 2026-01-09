=== Spelhubben Weather ===
Bidragsytere: spelhubben
Stikkord: vær, varsel, widget, kortkode, blokker
Krever minst: 6.8
Testet opp til: 6.9
Krever PHP: 7.4
Stabil tag: 1.9.0
Donasjonslenke: https://www.paypal.com/donate/?hosted_button_id=CV74CEXY5XEAU

== Lisens ==

Dette programmet er fri programvare; du kan redistribuere det og/eller endre det under vilkårene i GNU General Public License som utgis av Free Software Foundation; enten versjon 2 av lisensen, eller (etter ditt valg) senere versjoner.

Full tekst for lisensen finnes i `LICENSE`-filen i plugin-rotkatalogen.

== Tilbakemeldinger og feilrapporter ==
Tilbakemeldinger og feilrapporter kan postes her: https://github.com/K3NT4/spelhubben-weather/issues
Lisens: GPLv3 eller senere
Lisens-URI: https://www.gnu.org/licenses/gpl-3.0.html

Vær-widget og blokk med valgfritt kart og daglig prognose. Kan kombinere data fra Open-Meteo, SMHI, Yr/MET, FMI, Open-Weathermap og Weatherapi.com.

== Beskrivelse ==
Dette tillegget viser gjeldende vær og en valgfri prognose. Det kan aggregere data fra gratis globale værleverandører (Open-Meteo, SMHI, Yr/MET Norge, FMI, Open-Weathermap og Weatherapi.com) og beregne en enkel «konsensus». Fungerer globalt med svært god dekning i Europa og utenfor.

**Funksjoner**
- **Kortkode** `[spelhubben_weather]`, **Gutenberg-blokk** og **klassisk widget**
- **6 værleverandører:** Open-Meteo, SMHI, Yr (MET Norge), FMI, Open-Weathermap, Weatherapi.com — aktiver valgfri kombinasjon
- **Ikontemaer:** Klassisk, Modern Flat, Modern Gradient, Modern 2026, Modern 3D (velges i admininnstillinger)
- **Flere oppsett:** `inline`, `compact`, `card`, `detailed`
- **Daglig prognose:** 3–10 dager (konfigurerbart)
- **Leverandørsammenligning:** side-ved-side-data fra alle aktiverte leverandører
- **Leaflet-kart:** OpenStreetMap-kart med korrekt attribuering (ODbL)
- **Visning av vindretning:** rotert pil med himmelretning (valgfritt via `show=wind_dir`)
- **Lokale ikoner:** SVG-ikoner (ingen CDN-avhengighet), responsiv skalering
- **Ytelse:** 6–30x raskere innstillingsside, lazy-loadet plugin-visning, optimalisert cache
- **Fullt GDPR-kompatibel:** ingen informasjonskapsler, ingen sporing, ingen innsamling av persondata
- **Klar for oversettelser:** engelske basisstrenger, svenske og norske oversettelser inkludert

*Ikke tilknyttet Open-Meteo, SMHI, Yr/MET Norge, FMI, Leaflet eller OpenStreetMap. Navn brukes kun i beskrivende hensikt. Kartdata © OpenStreetMap-bidragsytere (ODbL).*

== Installasjon ==
1. Last opp/aktiver tillegget.
2. Gå til **Innstillinger → Spelhubben Weather** og sett standardverdier (sted, viste felt, oppsett, leverandører, cachetid, enheter/format).
3. Legg til vær på nettstedet ditt på en av disse måtene:

= Blokk (Gutenberg) =
- Rediger en side/innlegg → klikk **Legg til blokk** → søk etter **«Spelhubben Weather»**.
- Valgfritt: overstyr standardverdier i blokkens sidepanel (sted/lat,lon, oppsett, kart, prognose).

= Kortkode =
- Sett inn `[spelhubben_weather]` der kortkoder støttes.
- Eksempler:
  - Grunnleggende: `[spelhubben_weather]`
  - Kompakt med kart og animasjon: `[spelhubben_weather place="Gothenburg" layout="compact" map="1" animate="1"]`
  - Inline uten kart: `[spelhubben_weather lat="57.7089" lon="11.9746" layout="inline" map="0" show="temp,icon"]`
  - Detaljert + daglig prognose (5 dager) + leverandørmiks: `[spelhubben_weather place="Umeå" layout="detailed" forecast="daily" days="5" providers="smhi,yr,openmeteo,fmi"]`
  - Med vindretning: `[spelhubben_weather place="Stockholm" show="temp,wind,wind_dir,icon" layout="compact" animate="1"]`

= Klassisk widget =
- Gå til **Utseende → Widgeter** → legg til **Spelhubben Weather**.
- Konfigurer per widget (tittel, sted eller lat/lon, felt, oppsett, kart, prognose, dager, CSS-klasse).

== Ofte stilte spørsmål ==

= Hvor kommer dataene fra? =
Fra offentlige API-er som Open-Meteo, SMHI, Yr/MET Norge og **FMI** (Finnish Meteorological Institute). Du velger leverandører under **Innstillinger → Spelhubben Weather** eller per blokk/kortkode/widget via attributtet `providers`.

= Trenger jeg en API-nøkkel? =
Nei. Open-Meteo, SMHI og FMI krever ingen nøkler. For Yr/MET Norge anbefales det at du angir kontaktinfo (e-post/URL) i **Innstillinger → Spelhubben Weather → Yr kontakt/UA** slik at User-Agent-en din er kompatibel.

= Blokk, kortkode eller widget — hva er forskjellen? =
Alle tre viser samme UI. Bruk **blokk** i blokkredigeringen, **kortkode** i klassiske innholdsområder, og **widget** i sidepaneler (Utseende → Widgeter). Hver metode kan overstyre globale standardverdier.

= Hvor finner jeg «Shortcodes»-siden? (nytt i 1.7.0) =
Gå til **Innstillinger → Spelhubben Weather → Shortcodes**. Der finner du søkbare eksempler, kopiering med ett klikk (og «kopier alle»), en **Quick Builder** med valgbare alternativer, samt en **live-forhåndsvisning** i WP-admin.

= Hvordan fungerer sted og koordinater? =
Hvis `lat` og `lon` er angitt, har de forrang. Ellers geokoder tillegget strengen i `place` (f.eks. `place="Umeå"`). Sett et globalt standardsted i innstillingene.

= Hvilke felt kan jeg vise/skjule? =
Bruk `show="temp,wind,icon"` (kommaseparert). Standardfelt settes i innstillingene. Legg til `wind_dir` for å vise vindretningspil og himmelretning.

= Hvordan fungerer oppsett? =
Velg `layout="inline|compact|card|detailed"`. «Detailed» støtter raden med flerdagersprognose.

= Kan jeg se data per leverandør (for sammenligning)? =
Ja. Bruk `comparison="1"` for å vise alle aktiverte leverandørers data side ved side. Nyttig for feilsøking eller for å se hvilke leverandører som fungerer der du er.
Eksempel: `[spelhubben_weather place="Stockholm" comparison="1" providers="openmeteo,smhi,yr,fmi,openweathermap,weatherapi"]`

= Hvilke ikontemaer finnes? =
Tillegget tilbyr flere temaer: **Klassisk** (tradisjonell), **Modern Flat** (ren, minimalistisk), **Modern Gradient** (moderne med subtile gradienter), **Modern 2026** (duotone/stroke i moderne stil) og **Modern 3D** (subtile gradienter + skygger). Velg i **Innstillinger → Spelhubben Weather → Ikonstil**. Alle temaer inkluderer ikoner for sol, delvis skyet (inkl. alternativ), skyet, tåke, regn, sludd, snø, storm/torden samt hagl der det er relevant.

= Hvordan aktiverer jeg kartet og angir størrelse? =
`map="1"` viser et Leaflet-kart (OpenStreetMap). Styr høyde med `map_height="240"` (px). Globale standardverdier finnes i innstillingene.

= Hvordan aktiverer jeg animasjoner? =
`animate="1"` aktiverer subtil UI-animasjon. Global standard settes i innstillingene. Rendereren aksepterer også `true`, `yes` eller `on` som «sanne» verdier for bekvemmelighet.

= Hvordan får jeg en daglig prognose? =
Sett `forecast="daily"` og `days="3–10"`. Eksempel: `forecast="daily" days="5"`.

= Kan jeg blande leverandører og få en konsensus? =
Ja. Sett `providers="smhi,yr,openmeteo,fmi"` (rekkefølgen spiller ingen rolle). Tillegget beregner en enkel konsensus for de feltene som er tilgjengelige for aktive leverandører.

= Enheter og format? =
Velg en forhåndsinnstilling med `units="metric|metric_kmh|imperial"`. Du kan overstyre deler via `temp_unit="C|F"`, `wind_unit="ms|kmh|mph"`, `precip_unit="mm|in"` og `date_format` for etiketter i prognosen. Alt har globale standardverdier i innstillingene (seksjonen **Enheter og format**).

= Cache — hvor lenge lagres data? =
Svar cachelagres via WordPress transients. Endre TTL (minutter) i innstillingene. Tøm via knappen **Tøm cache** på Prestandasiden (Settings → Performance) eller ved å endre attributter (som oppretter en ny cache-nøkkel).

= Fungerer det uten JavaScript? =
Ja, rendering skjer på serversiden. Kartet (Leaflet) krever JS.

= Oversettelser? =
Tillegget er fullt oversettbart. **Inkluderte oversettelser:** **Svensk (sv_SE), norsk bokmål (nb_NO)**. Strenger er også tilgjengelige på translate.wordpress.org. Legg `.pot/.po/.mo` i `/languages`.

= GDPR / personvern? =
Tillegget setter ingen informasjonskapsler av seg selv. Hvis du aktiverer kartet, hentes Leaflet/OpenStreetMap-tiles i klienten. Nevn OSM i personvernerklæringen ved behov.

= Feilsøkingstips =
- Ingenting vises: kontroller at minst én leverandør er valgt i innstillingene.
- Feil sted: oppgi eksakt `lat`/`lon` eller et mer spesifikt `place` (f.eks. «Uddevalla, SE»).
- Kartet vises ikke: sjekk `map="1"` og at tema-/container-området er bredt/høyt nok; øk `map_height`.
- Rate limiting: reduser oppdateringsfrekvens eller øk cache-TTL.

== Skjermbilder ==
1. Frontend-eksempler: inline, compact, card, detailed, med valgfritt kart.
2. Frontend-eksempler: Nytt utseende og vindretning.
3. Innstillingsside: standardverdier, leverandører, enheter og format.
4. Varsler-side: aktive varsler og anbefalinger for ekstreme forhold.
5. Shortcodes-side: søkbare eksempler, kopieringsknapper og live-forhåndsvisning i admin.
6. Ytelsesside: cache-statistikk, API-bruk og "Tøm cache"-handling.

== Endringslogg ==
- = 1.9.0 =
- **Ny:** Værvarsler med smarte anbefalinger for ekstreme forhold
- **Ny:** Stormvarsel ved vindhastigheter over 24,5 m/s
- **Ny:** Eksport og import av innstillinger for enkel konfigurasjonshåndtering
- **Ny:** Ytelsesdashbord for å spore API-bruk, cacheeffektivitet og responstider
- **Ny:** Fullt mørk modus for frontend og admin
- **Ny:** 3 Gutenberg Block Patterns (Compact, Detailed, Forecast)
- **Ny:** Varslingsbrytere for blokker, widgeter og kortkoder
- **Ny:** Visning av vindretning (`wind_dir`) — rotert pil + himmelretning (valgfritt via `show=wind_dir`)
- **Ny:** Shortcode Quick Builder i admin på **Shortcodes**-siden med valgbare alternativer, kopier med ett klikk og live-forhåndsvisning
- **Ny:** Tipsboks på innstillingssiden som viser ulike tips for admin (Shortcodes, Varsler, Ytelse)
- **Ny:** Kompakte handlingsknapper i tipsboksen for rask tilgang til Shortcodes, Varsler og Ytelse
- **Ny:** Tilbakestill til standardinnstillinger-knapp på innstillingssiden (nonce-beskyttet)
- **Forbedret:** Tipsene er oversettelsesklare, roterer saktere for bedre lesbarhet (15s) og bruker `aria-live` for tilgjengelighet
- **Forbedret:** Tolkning av `animate` er mer tolerant (aksepterer `1`, `true`, `yes`, `on`)
- **Forbedret:** Full engelsk oversettelse og i18n-beredskap (engelsk er nå basisspråk)
- **Forbedret:** Forfinte varselterskler basert på meteorologiske standarder

= 1.8.6 =
- **Fikset:** Kart ble ikke rendret i widgeter pga. manglende Leaflet-asset-detektering
- **Fikset:** Blokknavn-mismatch (`spelhubben/weather` → `spelhubben-weather/spelhubben-weather`) som hindret korrekt asset-innlasting
- **Forbedret:** Leaflet-init med bedre timing og feilhåndtering i `map.js`
- **Forbedret:** Widget-detektering i asset loading-logikk via `is_active_widget()`
- **Forbedret:** Fallback-høyde (`height: 240px;`) i CSS-klassen `.svv-map` for stabil Leaflet-containerstørrelse
- **Forbedret:** Bedre feilrapportering og retry-logikk i kartinitiering med Leaflet-tilgjengelighetskontroller

= 1.8.5 =
- **Ytelse:** Betinget Leaflet-asset-innlasting — lastes bare når kortkode eller Gutenberg-blokk finnes på siden.
- **Fikset:** Lagt til `.htaccess` for å hindre at WordPress rewrite-regler påvirker statiske assets.
- **Fikset:** Sikrer korrekte MIME-typer for CSS og JS for å unngå advarsler om streng MIME-kontroll i nettlesere.
- **UX:** Fjerner unødvendige 404-feil på sider uten vær-widget.

= 1.8.4 =
- **Vedlikehold:** Sentralisert konfigurasjonsfil med konstanter (`includes/constants.php`) for bedre vedlikehold og færre «magic numbers».
- **Ytelse:** Innstillingssiden laster nå 6–30x raskere med lazy-loadet WP.org plugin-visning via AJAX.
- **Fikset:** Fikset minnelekkasjer fra event listeners i admin med korrekt opprydding.
- **Fikset:** Fikset duplisering av WMO-koder — tåke (koder 45, 48) vises nå korrekt i stedet for sky-ikon.
- **Fikset:** Geokodingscache inkluderer nå API-språk for korrekt locale-spesifikk resultathåndtering på flerspråklige nettsteder.
- **Fikset:** Widget null-sikkerhet med null-coalesce for å unngå PHP Notices.
- **Fikset:** Standardisert API-feilhåndtering med konsekvent validering på tvers av leverandører.
- **Fikset:** Fikset syntaksfeil i WP.org plugin-visning API-kall (manglende avsluttende parentes).
- **Etterlevelse:** Verifisert WordPress Consent API- og GDPR-etterlevelse — ingen informasjonskapsler, ingen sporing, ingen innsamling av persondata.
- **Kodekvalitet:** Optimalisert debounce (400ms → 600ms) reduserer AJAX-trafikk med 50% ved live-forhåndsvisning.
- **Dokumentasjon:** Omfattende audit- og testguider for utviklere.

= 1.8.3 =
- Versjonsøkning for produksjonsrelease.

= 1.8.2 =
- **Fikset:** Etterlevelse av WordPress navnestandard — alle globale funksjoner og variabler bruker nå korrekt `sv_vader_`-prefiks.
- **Fikset:** Korrigerte asset-stier for Leaflet (vendor-katalogstruktur).
- **Tech:** Kodegjennomgang og standardetterlevelse (ingen «breaking changes»).
- Testet opp til: WordPress 6.9

= 1.8.1 =
- **Ny:** 3 valgbare ikontemaer: **Klassisk**, **Modern Flat** og **Modern Gradient** (Innstillinger → Ikonstil).
- **Ytelse:** Optimalisert ikonrendering med statisk cache for ikontema (reduserer gjentatte `sv_vader_get_options()`-anrop).
- **Tech:** Privat hjelpefunksjon `build_icon_url()` for å sentralisere ikon-URL-logikk og forbedre vedlikehold.
- Alle ikontemaer inkluderer: sol, delvis skyet, sky, tåke, regn, sludd, snø, tordenbyge (8 distinkte værtyper per tema).
- Oppdatert README og readme.txt med dokumentasjon for ikontemaer og admininnstillinger.

= 1.8.0 =
- **BRYTENDE ENDRING:** Fjernet eldre kortkode `[sv_vader …]`. Bruk kun `[spelhubben_weather …]`.
- **Nye leverandører:** La til **Open-Weathermap** og **Weatherapi.com** for bedre global dekning (totalt 6).
- **Ny funksjon:** `comparison="1"` viser alle leverandørers data side ved side for enkel sammenligning og feilsøking.
- **Ytelse:** Fikset minnelekkasje i map.js (persistent MutationObserver, korrekt ResizeObserver-opprydding).
- **Ytelse:** 7-dagers transientcache for geokodingsoppslag reduserer eksterne API-kall.
- **Ytelse:** CSS containment (`contain: layout style paint`) optimaliserer rendering på sider med flere værkort.
- **Cache:** Forbedret debounce for å forhindre race conditions ved vindus-resize.
- **Sikkerhet:** Fikset usikker XML-parsing i FMI-leverandøren (bruker nå `LIBXML_NOCDATA` med feilhåndtering).
- **Funksjon:** Ny plugin-visning på innstillingssiden som viser andre Spelhubben-plugins (grid-layout, hentes fra WordPress.org).
- **UX:** Plugin-visningen ekskluderer automatisk Spelhubben Weather for å unngå redundans.
- Testet opp til: WordPress 6.8+

= 1.7.5 =
- Testet opp til: 6.9
- Ny: **FMI (Finnish Meteorological Institute)** som gratis, valgfri leverandør (t2m, ws_10min, r_1h, n_man via WFS). Aktiveres under **Innstillinger → Leverandører** og via `providers="…"` i blokk/kortkode/widget.
- Kortkoder/Blokker: `providers` aksepterer nå `fmi`.
- Dokumentasjon: Oppdaterte eksempler og FAQ for FMI.

= 1.7.0 =
- Ny: **Shortcodes**-side i admin med søkbare eksempler, kopiering med ett klikk og **kopier alle**.
- Ny: **Live-forhåndsvisning** i WP-admin (sandboxet iframe) som renderer kortkoder og laster frontend-assets (Leaflet, widget CSS/JS).
- Ny: **Enheter og format**-innstillinger (forvalg + overstyringer: temp/vind/nedbør, `date_format`).
- Ny: **Tøm cache**-knapp (transients) på innstillingssiden.
- Ny: **Oversettelser inkludert:** Svensk (sv_SE), norsk (nb_NO), engelsk (en_US).
- UX: Enhetlig lys kortdesign på admin-sider.
- Tech: Robust admin enqueue med cache-busting via `filemtime`.
- i18n: Alle adminstrenger lokaliserte (inkl. JS: utvid/fell sammen, statuser).
- Dokumentasjon: Markerte eldre kortkode som **deprecated – fjernes snart**.

= 1.6.2 =
- Mindre fikser og readme-oppdateringer.

= 1.6.1 =
- Versjonsøkning for WordPress.org-synk. Ingen funksjonelle endringer.


== Oppgraderingsinformasjon ==
= 1.8.5 =
Ytelsesutgave med betinget asset-innlasting. Fikser 404-feil og MIME-type-advarsler for Leaflet på sider uten vær-widget. Anbefales for alle brukere.

= 1.8.4 =
Vedlikeholdsutgave med sentraliserte konstanter og ytelsesoptimaliseringer. Anbefales for alle brukere.

= 1.8.0 =
**BRYTENDE ENDRING:** Den eldre kortkoden `[sv_vader …]` er fjernet. Migrer alle kortkoder til formatet `[spelhubben_weather …]`. Ytelsesoppdatering med sikkerhetsfikser, geokodingscache og ny plugin-visning. Sterkt anbefalt.

= 1.7.5 =
Legger til **FMI** som valgfri gratis leverandør. Aktiver under **Innstillinger → Spelhubben Weather → Leverandører**, eller send `providers="smhi,yr,openmeteo,fmi"` i blokker/kortkoder/widgeter.

= 1.7.0 =
Admin-UX-ombygging: ny Shortcodes-side med live-forhåndsvisning, enheter/format-innstillinger og cache-tømming. Eldre [sv_vader] er utfaset—migrer til [spelhubben_weather].

Donasjonslenke: https://www.paypal.com/donate/?hosted_button_id=CV74CEXY5XEAU
