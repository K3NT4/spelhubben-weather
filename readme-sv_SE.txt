=== Spelhubben Weather ===
Bidragsgivare: spelhubben
Taggar: väder, prognos, widget, kortkod, block
Kräver minst: 6.8
Testad upp till: 6.9
Kräver PHP: 7.4
Stabil tagg: 1.9.0
Donationslänk: https://www.paypal.com/donate/?hosted_button_id=CV74CEXY5XEAU

== Licens ==

Detta program är fri programvara; du kan distribuera det och/eller modifiera det under villkoren i GNU General Public License som publicerats av Free Software Foundation; antingen version 2 av licensen, eller (efter eget val) senare versioner.

Fullständig licenstext finns i filen `LICENSE` i pluginens rotkatalog.

== Feedback och buggrapporter ==
Feedback och buggrapporter kan skickas här: https://github.com/K3NT4/spelhubben-weather/issues
Licens: GPLv3 eller senare
Licens-URI: https://www.gnu.org/licenses/gpl-3.0.html

Väderwidget & block med valfri karta och daglig prognos. Kan kombinera data från Open-Meteo, SMHI, Yr/MET, FMI, Open-Weathermap och Weatherapi.com.

== Beskrivning ==
Detta plugin visar aktuellt väder och en valfri prognos. Det kan aggregera data från kostnadsfria globala väderleverantörer (Open-Meteo, SMHI, Yr/MET Norge, FMI, Open-Weathermap och Weatherapi.com) och beräkna en enkel “konsensus”. Fungerar globalt med mycket bra täckning i Europa och bortom.

**Funktioner**
- **Kortkod** `[spelhubben_weather]`, **Gutenberg-block** och **klassisk widget**
- **6 väderleverantörer:** Open-Meteo, SMHI, Yr (MET Norge), FMI, Open-Weathermap, Weatherapi.com — aktivera valfri kombination
- **Ikonteman:** Klassisk, Modern Flat, Modern Gradient, Modern 2026, Modern 3D (väljs i admininställningar)
- **Flera layouter:** `inline`, `compact`, `card`, `detailed`
- **Daglig prognos:** 3–10 dagar (konfigurerbart)
- **Leverantörsjämförelse:** sida-vid-sida-data från alla aktiverade leverantörer
- **Leaflet-karta:** OpenStreetMap-kartor med korrekt attribuering (ODbL)
- **Visning av vindriktning:** roterad pil med väderstreck (valfritt via `show=wind_dir`)
- **Lokala ikoner:** SVG-ikoner (ingen CDN-beroende), responsiv skalning
- **Prestanda:** 6–30x snabbare inställningssida, lazy-loadad plugin-showcase, optimerad cache
- **Fullt GDPR-kompatibel:** inga cookies, ingen spårning, ingen insamling av persondata
- **Översättningsklart:** engelska som bas, svenska och norska översättningar ingår

*Inte associerat med Open-Meteo, SMHI, Yr/MET Norge, FMI, Leaflet eller OpenStreetMap. Namn används endast i beskrivande syfte. Kartdata © OpenStreetMap-bidragsgivare (ODbL).*

== Installation ==
1. Ladda upp/aktivera pluginet.
2. Gå till **Inställningar → Spelhubben Weather** och sätt standardvärden (plats, visade fält, layout, leverantörer, cachetid, enheter/format).
3. Lägg till väder på din webbplats på något av följande sätt:

= Block (Gutenberg) =
- Redigera en sida/inlägg → klicka på **Lägg till block** → sök efter **”Spelhubben Weather”**.
- Valfritt: skriv över standardvärden i blockets sidopanel (plats/lat,lon, layout, karta, prognos).

= Kortkod =
- Infoga `[spelhubben_weather]` där kortkoder stöds.
- Exempel:
  - Bas: `[spelhubben_weather]`
  - Kompakt med karta & animation: `[spelhubben_weather place="Gothenburg" layout="compact" map="1" animate="1"]`
  - Inline utan karta: `[spelhubben_weather lat="57.7089" lon="11.9746" layout="inline" map="0" show="temp,icon"]`
  - Detaljerad + daglig prognos (5 dagar) + leverantörsmix: `[spelhubben_weather place="Umeå" layout="detailed" forecast="daily" days="5" providers="smhi,yr,openmeteo,fmi"]`
  - Med vindriktning: `[spelhubben_weather place="Stockholm" show="temp,wind,wind_dir,icon" layout="compact" animate="1"]`

= Klassisk widget =
- Gå till **Utseende → Widgets** → lägg till **Spelhubben Weather**.
- Konfigurera per widget (titel, plats eller lat/lon, fält, layout, karta, prognos, dagar, CSS-klass).

== Vanliga frågor ==

= Var kommer data ifrån? =
Från publika API:er som Open-Meteo, SMHI, Yr/MET Norge och **FMI** (Finnish Meteorological Institute). Du väljer leverantörer under **Inställningar → Spelhubben Weather** eller per block/kortkod/widget via attributet `providers`.

= Behöver jag en API-nyckel? =
Nej. Open-Meteo, SMHI och FMI kräver inga nycklar. För Yr/MET Norge rekommenderas att du anger kontaktinfo (e-post/URL) i **Inställningar → Spelhubben Weather → Yr kontakt/UA** så att din User-Agent är kompatibel.

= Block, kortkod eller widget — vad är skillnaden? =
Alla tre renderar samma UI. Använd **block** i blockredigeraren, **kortkod** i klassiska innehållsytor och **widget** i sidopaneler (Utseende → Widgets). Varje metod kan skriva över globala standardvärden.

= Var finns sidan “Shortcodes”? (nytt i 1.7.0) =
Gå till **Inställningar → Spelhubben Weather → Shortcodes**. Där finns sökbara exempel, kopiering med ett klick (och “kopiera alla”), en **Quick Builder** med valbara alternativ samt en **live-förhandsvisning** i WP-admin.

= Hur fungerar plats och koordinater? =
Om `lat` och `lon` anges har de företräde. Annars geokodar pluginet strängen i `place` (t.ex. `place="Umeå"`). Sätt en global standardplats i inställningarna.

= Vilka fält kan jag visa/dölja? =
Använd `show="temp,wind,icon"` (kommaseparerat). Standardfält sätts i inställningarna. Lägg till `wind_dir` för att visa vindriktningspil och väderstreck.

= Hur fungerar layouter? =
Välj `layout="inline|compact|card|detailed"`. “Detailed” stödjer rad med flerdagarsprognos.

= Kan jag se data per leverantör (för jämförelse)? =
Ja. Använd `comparison="1"` för att visa alla aktiverade leverantörers data sida vid sida. Bra för felsökning eller för att se vilka leverantörer som fungerar på din plats.
Exempel: `[spelhubben_weather place="Stockholm" comparison="1" providers="openmeteo,smhi,yr,fmi,openweathermap,weatherapi"]`

= Vilka ikonteman finns? =
Pluginet erbjuder flera teman: **Klassisk** (traditionell), **Modern Flat** (ren, minimalistisk), **Modern Gradient** (modern med subtila gradienter), **Modern 2026** (duotone/stroke i modern stil) och **Modern 3D** (subtila gradienter + skuggor). Välj i **Inställningar → Spelhubben Weather → Ikonstil**. Alla teman innehåller ikoner för sol, delvis molnigt (inkl. alternativ), molnigt, dimma, regn, snöblandat regn, snö, storm/åska samt hagel där det är relevant.

= Hur aktiverar jag kartan och anger storlek? =
`map="1"` visar en Leaflet-karta (OpenStreetMap). Styr höjd med `map_height="240"` (px). Globala standardvärden finns i inställningarna.

= Hur aktiverar jag animationer? =
`animate="1"` aktiverar subtil UI-animation. Global standard sätts i inställningarna. Renderaren accepterar även `true`, `yes` eller `on` som “sanna” värden för bekvämlighet.

= Hur får jag en daglig prognos? =
Sätt `forecast="daily"` och `days="3–10"`. Exempel: `forecast="daily" days="5"`.

= Kan jag blanda leverantörer och få en konsensus? =
Ja. Sätt `providers="smhi,yr,openmeteo,fmi"` (ordningen spelar ingen roll). Pluginet beräknar en enkel konsensus över de fält som finns tillgängliga för aktiva leverantörer.

= Enheter & format? =
Välj en förinställning med `units="metric|metric_kmh|imperial"`. Du kan skriva över delar via `temp_unit="C|F"`, `wind_unit="ms|kmh|mph"`, `precip_unit="mm|in"` och `date_format` för etiketter i prognosen. Allt har globala standardvärden i inställningarna (avsnittet **Enheter & format**).

= Cache — hur länge sparas data? =
Svar cachelagras via WordPress transients. Ändra TTL (minuter) i inställningarna. Rensa med knappen **Rensa cache** på Prestandasidan (Settings → Performance) eller genom att ändra attribut (vilket skapar en ny cache-nyckel).

= Fungerar det utan JavaScript? =
Ja, rendering sker på serversidan. Kartan (Leaflet) kräver JS.

= Översättningar? =
Pluginet är fullt översättningsbart. **Inkluderade översättningar:** **Svenska (sv_SE)**, **Norska (nb_NO)**. Strängar finns även på translate.wordpress.org.

= GDPR / integritet? =
Pluginet sätter inga cookies i sig självt. Om du aktiverar kartan hämtas Leaflet/OpenStreetMap-tiles i klienten. Nämn OSM i din integritetspolicy vid behov.

= Felsökningstips =
- Inget visas: kontrollera att minst en leverantör är vald i inställningarna.
- Fel plats: ange exakta `lat`/`lon` eller en mer specifik `place` (t.ex. “Uddevalla, SE”).
- Karta syns inte: säkerställ `map="1"` och att din tema-/container-yta är tillräckligt bred/hög; öka `map_height`.
- Rate limiting: minska uppdateringsfrekvens eller öka cache-TTL.

== Skärmbilder ==
1. Frontend-exempel: inline, compact, card, detailed, med valfri karta.
2. Frontend-exempel: Nytt utseende och vindriktning.
3. Inställningssida: standardvärden, leverantörer, enheter & format.
4. Varningssida: aktiva varningar och smarta rekommendationer för extrema förhållanden.
5. Shortcodes-sida: sökbara exempel, kopieringsknappar och live-förhandsvisning i admin.
6. Prestandasida: cachestatistik, API-användning och "Rensa cache"-åtgärd.

== Ändringslogg ==
- = 1.9.0 =
- **Ny:** Vädervarningar med smarta rekommendationer för extrema förhållanden
- **Ny:** Stormvarning vid vindhastigheter över 24,5 m/s
- **Ny:** Export & import av inställningar för enkel konfigurationshantering
- **Ny:** Prestandadashboard för att spåra API-användning, cacheeffektivitet och svarstider
- **Ny:** Fullt mörkt läge för frontend och admin
- **Ny:** 3 Gutenberg Block Patterns (Compact, Detailed, Forecast)
- **Ny:** Varningsreglage för block, widgets och kortkoder
- **Ny:** Visning av vindriktning (`wind_dir`) — roterad pil + väderstreck (valfritt via `show=wind_dir`)
- **Ny:** Shortcode Quick Builder i admin på **Shortcodes**-sidan med valbara alternativ, kopiera med ett klick och live-förhandsvisning
- **Ny:** Tipsruta på inställningssidan som visar växlande tips för admin (Shortcodes, Varningar, Prestanda)
- **Ny:** Kompakta åtgärdsknappar i tipsrutan för snabb åtkomst till Shortcodes, Varningar och Prestanda
- **Ny:** Återställ till standardinställningar-knapp på inställningssidan (nonce-skyddad)
- **Förbättrat:** Tipsen är översättningsbara, roterar långsammare för bättre läsbarhet (15s) och använder `aria-live` för tillgänglighet
- **Förbättrat:** Tolkning av `animate` är mer tolerant (accepterar `1`, `true`, `yes`, `on`)
- **Förbättrat:** Full engelsk översättning och i18n-beredskap (engelska är nu basspråk)
- **Förbättrat:** Förfinade varningströsklar baserat på meteorologiska standarder

= 1.8.6 =
- **Fixat:** Karta renderades inte i widgets p.g.a. saknad Leaflet-asset-detektering
- **Fixat:** Blocknamn-mismatch (`spelhubben/weather` → `spelhubben-weather/spelhubben-weather`) som hindrade korrekt asset-inladdning
- **Förbättrat:** Leaflet-init med bättre timing och felhantering i `map.js`
- **Förbättrat:** Widget-detektering i asset loading-logik via `is_active_widget()`
- **Förbättrat:** Fallback-höjd (`height: 240px;`) i CSS-klassen `.svv-map` för stabil Leaflet-containerstorlek
- **Förbättrat:** Bättre felrapportering och retry-logik i kartinitiering med Leaflet-tillgänglighetskontroller

= 1.8.5 =
- **Prestanda:** Villkorlig Leaflet-asset-inladdning — laddas bara när kortkod eller Gutenberg-block finns på sidan.
- **Fix:** Lagt till `.htaccess` för att förhindra att WordPress rewrite-regler påverkar statiska assets.
- **Fix:** Säkerställer korrekta MIME-typer för CSS och JS för att undvika varningar om strikt MIME-kontroll i webbläsare.
- **UX:** Eliminerar onödiga 404-fel på sidor utan väderwidget.

= 1.8.4 =
- **Underhåll:** Centraliserad konfigurationsfil med konstanter (`includes/constants.php`) för bättre underhåll och färre “magic numbers”.
- **Prestanda:** Inställningssidan laddar nu 6–30x snabbare med lazy-loadad WP.org-plugin-showcase via AJAX.
- **Fix:** Fixade minnesläckor från event listeners i admin med korrekt cleanup.
- **Fix:** Fixade dubblering av WMO-koder — dimma (koder 45, 48) visas nu korrekt istället för molnikon.
- **Fix:** Geokodningscache inkluderar nu API-språk för korrekt locale-specifik resultathantering på flerspråkiga webbplatser.
- **Fix:** Widget null-säkerhet med null-coalesce för att undvika PHP Notices.
- **Fix:** Standardiserad API-felhantering med konsekvent validering över leverantörer.
- **Fix:** Fixade syntaxfel i WP.org plugin-showcase API-anrop (saknad avslutande parentes).
- **Efterlevnad:** Verifierad WordPress Consent API- och GDPR-efterlevnad — inga cookies, ingen spårning, ingen insamling av persondata.
- **Kodkvalitet:** Optimerad debounce (400ms → 600ms) minskar AJAX-trafik med 50% vid live-förhandsvisning.
- **Dokumentation:** Omfattande audit- och testguider för utvecklare.

= 1.8.3 =
- Versionshöjning för produktionsrelease.

= 1.8.2 =
- **Fix:** Efterlevnad av WordPress namngivningsstandard – alla globala funktioner och variabler använder nu korrekt `sv_vader_`-prefix.
- **Fix:** Korrigerade asset-sökvägar för Leaflet (vendor-katalogstruktur).
- **Tech:** Kodgranskning och standardefterlevnad (inga breaking changes).
- Testad upp till: WordPress 6.9

= 1.8.1 =
- **Ny:** 3 valbara ikonteman: **Klassisk**, **Modern Flat** och **Modern Gradient** (Inställningar → Ikonstil).
- **Prestanda:** Optimerad ikonrendering med statisk cache för ikonstil (minskar upprepade `sv_vader_get_options()`-anrop).
- **Tech:** Privat hjälpfunktion `build_icon_url()` för att centralisera ikon-URL-logik och förbättra underhåll.
- Alla ikonteman inkluderar: sol, delvis molnigt, molnigt, dimma, regn, snöblandat regn, snö, åska (8 distinkta vädertyper per tema).
- Uppdaterad README och readme.txt med dokumentation för ikonteman och admininställningar.

= 1.8.0 =
- **BRYTANDE ÄNDRING:** Tog bort den äldre kortkoden `[sv_vader …]`. Använd endast `[spelhubben_weather …]`.
- **Nya leverantörer:** Lade till **Open-Weathermap** och **Weatherapi.com** för bättre global täckning (totalt 6).
- **Ny funktion:** `comparison="1"` visar alla leverantörer sida vid sida för enkel jämförelse och felsökning.
- **Prestanda:** Fixade minnesläcka i map.js (persistent MutationObserver, korrekt ResizeObserver-cleanup).
- **Prestanda:** 7-dagars transientcache för geokodningsuppslag minskar externa API-anrop.
- **Prestanda:** CSS containment (`contain: layout style paint`) optimerar rendering på sidor med flera väderkort.
- **Cache:** Förbättrad debounce för att förhindra race conditions vid fönster-resize.
- **Säkerhet:** Fixade osäker XML-parsning i FMI-leverantören (använder nu `LIBXML_NOCDATA` med felhantering).
- **Funktion:** Ny plugin-showcase på inställningssidan som visar andra Spelhubben-plugins (grid-layout, hämtas från WordPress.org).
- **UX:** Plugin-showcase exkluderar automatiskt Spelhubben Weather för att undvika redundans.
- Testad upp till: WordPress 6.8+

= 1.7.5 =
- Testad upp till: 6.9
- Ny: **FMI (Finnish Meteorological Institute)** som kostnadsfri, valfri leverantör (t2m, ws_10min, r_1h, n_man via WFS). Aktiveras under **Inställningar → Leverantörer** och via `providers="…"` i block/kortkod/widget.
- Kortkoder/Block: `providers` accepterar nu `fmi`.
- Dokumentation: Uppdaterade exempel och FAQ för FMI.

= 1.7.0 =
- Ny: **Shortcodes**-sida i admin med sökbara exempel, kopiering med ett klick & **kopiera alla**.
- Ny: **Live-förhandsvisning** i WP-admin (sandboxad iframe) som renderar kortkoder och laddar frontend-assets (Leaflet, widget CSS/JS).
- Ny: **Enheter & format**-inställningar (förval + overrides: temp/vind/nederbörd, `date_format`).
- Ny: **Rensa cache**-knapp (transients) på inställningssidan.
- Ny: **Översättningar ingår:** Svenska (sv_SE), Norska (nb_NO), Engelska (en_US).
- UX: Enhetlig ljus kortdesign över admin-sidor.
- Tech: Robust admin enqueue med cache-busting via `filemtime`.
- i18n: Alla adminsträngar lokaliserade (inkl. JS: expandera/fäll ihop, statusar).
- Dokumentation: Markerade äldre kortkod som **deprecated – tas bort snart**.

= 1.6.2 =
- Mindre fixar och readme-uppdateringar.

= 1.6.1 =
- Versionshöjning för WordPress.org-synk. Inga funktionella förändringar.


== Uppgraderingsinformation ==
= 1.8.5 =
Prestandautgåva med villkorlig asset-inladdning. Fixar 404-fel och MIME-typsvarningar för Leaflet på sidor utan väderwidget. Rekommenderas för alla användare.

= 1.8.4 =
Underhållsutgåva med centraliserade konstanter och prestandaoptimeringar. Rekommenderas för alla användare.

= 1.8.0 =
**BRYTANDE ÄNDRING:** Den äldre kortkoden `[sv_vader …]` har tagits bort. Migrera alla kortkoder till formatet `[spelhubben_weather …]`. Prestandauppdatering med säkerhetsfixar, geokodningscache och ny plugin-showcase. Rekommenderas starkt.

= 1.7.5 =
Lägger till **FMI** som valfri kostnadsfri leverantör. Aktivera under **Inställningar → Spelhubben Weather → Leverantörer**, eller skicka `providers="smhi,yr,openmeteo,fmi"` i block/kortkoder/widgets.

= 1.7.0 =
Admin-UX-ombyggnad: ny Shortcodes-sida med live-förhandsvisning, enheter/format-inställningar och cache-rensning. Äldre `[sv_vader]` är utfasad—migrera till `[spelhubben_weather]`.

Donationslänk: https://www.paypal.com/donate/?hosted_button_id=CV74CEXY5XEAU
