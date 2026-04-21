# Statische versie (zonder WordPress)

## Resultaat
- Bron WordPress: `C:\laragon\www\maatlaswerk`
- Statische export: `C:\Users\Admin\OneDrive\Digisteps\AI  - Ontwerp websites\Maatlaswerk\Website\static-site`
- Live testpad in Laragon: `C:\laragon\www\maatlaswerk-static`
- Test-URL: `http://localhost/maatlaswerk-static/`

## Export opnieuw uitvoeren
```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\export-static.ps1
```

## Let op
- Contactformulieren, login, zoeken en andere dynamische WP-functies werken niet meer in de statische variant.
- Na contentwijzigingen in WordPress moet je de export opnieuw draaien.
