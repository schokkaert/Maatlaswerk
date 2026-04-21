# Statische versie (zonder WordPress)

## Resultaat
- Bron WordPress: `C:\laragon\www\maatlaswerk`
- Actieve lokale website: `C:\Users\Admin\OneDrive\Digisteps\AI  - Ontwerp websites\Maatlaswerk\Website`
- Nieuwe export-output: `C:\Users\Admin\OneDrive\Digisteps\AI  - Ontwerp websites\Maatlaswerk\Website\_generated-export`
- Live testpad in Laragon: koppel indien nodig rechtstreeks naar de map `Website`
- Test-URL: afhankelijk van je lokale Laragon-koppeling

## Export opnieuw uitvoeren
```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\export-static.ps1
```

## Let op
- Contactformulieren, login, zoeken en andere dynamische WP-functies werken niet meer in de statische variant.
- Na contentwijzigingen in WordPress kan je de export opnieuw draaien en daarna gericht overzetten naar `Website`.
