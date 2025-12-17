# Testing Guide - Performance Optimizations

## Steg-för-steg Testning på Live Site

### 1. Testa Inställningssida Laddtid

**Före (Reference):**
1. Öppna DevTools → Network tab
2. Gå till WordPress Admin → Spelhubben Weather → Settings
3. Notera hur länge sidan tar att ladda (bör nu vara mycket snabbare)
4. Ser du att plugin showcase hämtas i bakgrunden? (bör se ett AJAX-anrop till `admin-ajax.php` med `action=svv_load_wporg_showcase`)

**Förväntad Resultatet:**
- ✅ Inställningssida laddar omedelbar (<500ms)
- ✅ "Loading other Spelhubben plugins..." placeholder syns först
- ✅ Plugin showcase fyller in efter 1-3 sekunder utan att blockera sidan

---

### 2. Testa Live Preview av Shortcodes

**Steg:**
1. Gå till Spelhubben Weather → Shortcodes tab
2. Skriv in en testshortcode i preview textarea: `[sv_vader lat=59.33 lon=18.07]`
3. Öppna DevTools → Network tab
4. Notera hur många AJAX-anrop som görs

**Förväntad Resultat:**
- ✅ Live preview uppdateras smooth
- ✅ Färre AJAX-anrop än tidigare (ca 50% mindre)
- ✅ Ingen fördröjning märkbar för användaren (debounce på 600ms istället för 400ms)

---

### 3. Testa Event Listener Cleanup

**Steg:**
1. Öppna DevTools → Memory/Profiler tab
2. Ta en heap snapshot
3. Navigera bort från Spelhubben Weather sida → navigera tillbaka
4. Upprepa 2-3 gånger
5. Ta en ny heap snapshot

**Förväntad Resultat:**
- ✅ Ingen exponentiell minnesökning (heap snapshot bör ha samma storlek)
- ✅ Event listeners tas bort korrekt mellan sidbyten
- ⚠️ Om du ser minneslacka = rapportera problem

---

### 4. Testa Felhantering (API Down)

**Steg:**
1. Simulera att WP.org API är nere (använd DevTools Network throttling eller offline mode)
2. Ladda inställningssida igen
3. Notera vad som händer med plugin showcase

**Förväntad Resultat:**
- ✅ Inställningssidan laddar fortfarande utan lag
- ✅ Plugin showcase visar error message: "Could not load plugin showcase."
- ✅ Resten av sidan fungerar normalt (graceful degradation)

---

### 5. Testa Plugin Showcase Ajax

**Steg:**
1. Öppna DevTools → Console tab
2. Inspektera Network-fliken medan inställningssida laddar
3. Leta efter ett request som:
   - URL: `/wp-admin/admin-ajax.php`
   - Method: POST
   - POST data innehåller: `action=svv_load_wporg_showcase`

**Förväntad Resultat:**
- ✅ AJAX-anrop görs i bakgrunden
- ✅ Response innehåller HTML med plugin cards
- ✅ Statuscode 200 OK

---

## Console Debugging

### Kontrollera att AJAX-handlens är registrerad:
```javascript
// I DevTools Console, på inställningssida:
// Borde returnera funktionen (inte undefined)
wp.ajax.send('svv_load_wporg_showcase')
```

### Kontrollera Event Listeners (Chrome DevTools):
```javascript
// I DevTools, höger-klicka på element → Inspect
// I Elements-panelen → Event Listeners tab
// Borde visa registrerade listeners med funktion-namn
```

---

## Performance Metrics (Browser DevTools)

### Lighthouse Score
1. Öppna DevTools → Lighthouse tab
2. Kör audit för "Performance"
3. Notera score före/efter optimeringar

**Målvärden:**
- Performance: >85 (var: ~70 före optimeringar)
- First Contentful Paint: <1.5s
- Largest Contentful Paint: <2.5s

---

## Regression Testing

### Funktioner som MÅSTE fortfarande fungera:

- [ ] Copy-knapp på shortcode-exempel fungerar
- [ ] Copy all visible-knapp fungerar
- [ ] Search/filter av shortcodes fungerar
- [ ] Live preview av shortcodes fungerar
- [ ] Expand/Collapse preview fungerar
- [ ] Plugin showcase links är klickbara
- [ ] WP.org plugin modal öppnas vid click
- [ ] Alla formulär i inställningar sparas korrekt
- [ ] Cache clearing funktion fungerar

---

## Problembeskrivning vid Issue

Om du hittar problem, notera:

```
Environment:
- Browser: [Chrome/Firefox/Safari]
- WordPress version: [X.X.X]
- Plugin version: [1.8.2+]
- Server OS: [Linux/Windows]

Issue Description:
[Beskrivning av problemet]

Steps to Reproduce:
1. [Steg 1]
2. [Steg 2]

Expected Result:
[Vad borde hända]

Actual Result:
[Vad som faktiskt hände]

Console Errors:
[Eventuella JavaScript fel]

Network Errors:
[Eventuella network errors]
```

---

## Performance Baseline (för jämförelse)

Spara dessa mätvärden för framtida jämförelse:

| Metrik | Värde |
|--------|-------|
| Settings Page Load | ___ ms |
| Plugin Showcase Load | ___ ms |
| Live Preview AJAX Calls (typing 10 chars) | ___ calls |
| Memory After 5 Page Reloads | ___ MB |
| Lighthouse Performance Score | ___ |

---

**Testning Completed:** ______  
**Tester Name:** ______  
**Date:** ______  
