<?php
return array (
  0 => 
  array (
    'meta' => 
    array (
      'document_title' => 'Technisch lastenboek website W&S Maatlaswerk',
      'project_name' => '',
      'client_name' => '',
      'reference' => '',
      'version' => '1.0',
      'introduction' => 'Technische documentatie van de website, met focus op opbouw, beheer, publicatie, galerij, formulieren en adminfuncties.',
    ),
    'items' => 
    array (
      0 => 
      array (
        'id' => 'lb-template-1',
        'rubric' => 'Algemeen',
        'code' => '01.01',
        'title' => 'Doel en scope van de website',
        'content' => 'Deze website is opgezet als statische/publieke bedrijfswebsite met een licht PHP-beheerluik. De site ondersteunt voorstelling van diensten, dynamische galerijweergave per categorie, contactaanvragen, privacy- en cookie-informatie en intern beheer via de adminomgeving.',
        'status' => 'concept',
        'position' => 1,
        'created_at' => '2026-03-30T12:00:00+02:00',
        'updated_at' => '2026-03-30T12:00:00+02:00',
      ),
      1 => 
      array (
        'id' => 'lb-template-2',
        'rubric' => 'Structuur',
        'code' => '02.01',
        'title' => 'Bestandsstructuur en pagina-opbouw',
        'content' => 'De publieke site bestaat hoofdzakelijk uit `index.php`, `about/index.php`, `services/index.php`, `services/detail.php`, `contact/index.php`, `privacy/index.php` en `cookies/index.php`. Gedeelde logica zit in `includes/`, styling en interactie in `assets/themes/bluehost-blueprint/`.',
        'status' => 'concept',
        'position' => 2,
        'created_at' => '2026-03-30T12:00:00+02:00',
        'updated_at' => '2026-03-30T12:00:00+02:00',
      ),
      2 => 
      array (
        'id' => 'lb-template-3',
        'rubric' => 'Admin',
        'code' => '03.01',
        'title' => 'Beheeromgeving en toegangscontrole',
        'content' => 'De adminomgeving op `/admin/` is beveiligd met login en sessiebeheer. Binnen deze omgeving kunnen administrators, galerijcategorieen, afbeeldingen, instellingen, mobiele uploads en technische documentatie beheerd worden. Wachtwoorden worden gehasht opgeslagen.',
        'status' => 'concept',
        'position' => 3,
        'created_at' => '2026-03-30T12:00:00+02:00',
        'updated_at' => '2026-03-30T12:00:00+02:00',
      ),
      3 => 
      array (
        'id' => 'lb-admin-workflow',
        'rubric' => 'Admin',
        'code' => '03.02',
        'title' => 'Workflow administratorgedeelte',
        'content' => 'Doel: het administratorgedeelte opnieuw kunnen opbouwen als beveiligde PHP-beheeromgeving onder `/admin/`.

Belangrijkste bestanden:
- `admin/bootstrap.php`: gedeelde adminlogica, sessies, opslag, logincontrole, mailfuncties en rendering van header/footer.
- `admin/login.php`: aanmeldscherm en afhandeling van gebruikersnaam/wachtwoord.
- `admin/administrators.php`: beheer van beheerders, rollen, status, directe activatie en uitnodigingen.
- `admin/activate.php`: activatielink voor nieuwe beheerders die per e-mail moeten bevestigen.
- `admin/logout.php`: sessie afsluiten.
- `admin/storage/admins.php`: PHP-array met beheerders. Wachtwoorden worden alleen als hash opgeslagen.
- `admin/storage/sessions/`: fallbackmap voor PHP-sessies wanneer de server geen schrijfbare sessiemap heeft.

Eerste setup:
1. Als er nog geen echte beheerder bestaat, maakt het systeem een tijdelijke beheerder aan.
2. Tijdelijke login is `admin` met wachtwoord `admin`.
3. Na tijdelijke login mag de gebruiker alleen naar `admin/administrators.php?setup=1`.
4. Daar moet een eerste echte beheerder worden aangemaakt.
5. De eerste echte beheerder wordt automatisch superadmin, actief gezet en moet een veilig wachtwoord kiezen.
6. Na aanmaken logt het systeem automatisch in met de nieuwe beheerder.
7. Alle tijdelijke beheerders worden daarna automatisch verwijderd.

Normale login:
1. Gebruiker opent `/admin/login.php`.
2. Het formulier gebruikt een CSRF-token uit de sessie.
3. De ingevoerde gebruikersnaam wordt opgezocht in `admin/storage/admins.php`.
4. Alleen actieve beheerders mogen aanmelden.
5. Het wachtwoord wordt gecontroleerd met `password_verify`.
6. Bij correcte login wordt `maatlas_admin_id` in de sessie opgeslagen.
7. Beveiligde adminpagina’s gebruiken `maatlas_admin_require_login()`.

Beheerders aanmaken:
1. Een ingelogde beheerder opent `/admin/administrators.php`.
2. Verplichte velden zijn gebruikersnaam, volledige naam en geldig e-mailadres.
3. Gebruikersnamen moeten uniek zijn.
4. Rol is `admin` of `superadmin`.
5. De bestaande beheerder kiest de activatiemethode.

Direct activeren:
1. De beheerder kiest `Direct activeren`.
2. Er wordt meteen een startwachtwoord ingevuld.
3. Het wachtwoord moet minstens 12 tekens hebben en mag niet `admin` of de gebruikersnaam zijn.
4. De account wordt actief opgeslagen.
5. De nieuwe beheerder krijgt een e-mailmelding dat de account bestaat.
6. Het wachtwoord wordt nooit per e-mail verzonden.

Activeren na bevestiging:
1. De beheerder kiest `Activatie pas na bevestiging via e-mail`.
2. De account wordt inactief opgeslagen.
3. Er wordt een willekeurige activatietoken gegenereerd.
4. Alleen de SHA-256 hash van de token wordt opgeslagen.
5. De nieuwe beheerder krijgt een e-mail met link naar `/admin/activate.php?token=...`.
6. De link is 7 dagen geldig.
7. Op de activatiepagina kiest de nieuwe beheerder zelf een veilig wachtwoord.
8. Na activatie wordt de account actief, token en vervaldatum worden gewist en de gebruiker wordt ingelogd.

Overzicht en onderhoud:
1. Het beheerdersoverzicht toont gebruikersnaam, naam, rol, status, laatste login en acties.
2. Status is `actief`, `inactief` of `wacht op bevestiging`.
3. Bij verlopen uitnodigingen kan een nieuwe activatiemail worden verzonden.
4. Een beheerder kan zichzelf niet verwijderen.
5. De laatste actieve beheerder kan niet gedeactiveerd of verwijderd worden.
6. Bij directe activatie of wachtwoordwijziging wordt het wachtwoord opnieuw gehasht opgeslagen.

Mailafhandeling:
1. Adminmails gebruiken `mail()` via `maatlas_admin_send_mail()`.
2. Afzender komt uit site-instellingen: eerst `contact_sender_email`, daarna publieke of privacy e-mail, fallback `info@maatlaswerk.be`.
3. Directe activatie stuurt alleen een melding zonder wachtwoord.
4. Bevestigingsactivatie stuurt een unieke activatielink.

Beveiligingsregels:
1. Alle POST-acties controleren een CSRF-token.
2. Wachtwoorden worden nooit plain text opgeslagen.
3. Activatietokens worden niet plain text opgeslagen.
4. Tijdelijke `admin/admin` mag alleen bestaan zolang er geen echte beheerder is.
5. Beveiligde pagina’s redirecten naar login wanneer er geen actieve sessie is.
6. Tijdens eerste setup wordt toegang beperkt tot beheerder-aanmaak en logout.',
        'status' => 'concept',
        'position' => 4,
        'created_at' => '2026-04-21T20:00:00+02:00',
        'updated_at' => '2026-04-21T20:00:00+02:00',
      ),
      4 => 
      array (
        'id' => 'lb-admin-interface',
        'rubric' => 'Admin',
        'code' => '03.03',
        'title' => 'Admininterface, statusbalken en bedieningselementen',
        'content' => 'Doel: naast de functionele workflow moet ook de zichtbare admininterface opnieuw opgebouwd kunnen worden.

Globale adminlayout:
1. Alle adminpagina’s gebruiken `maatlas_admin_render_header()` en `maatlas_admin_render_footer()` uit `admin/bootstrap.php`.
2. Wanneer een gebruiker is aangemeld, krijgt de pagina de layoutklasse `maatlas-admin-shell-layout`.
3. De hoofdindeling bestaat uit een vaste zijbalk links en een inhoudsgebied rechts.
4. Niet-aangemelde pagina’s zoals login en activatie gebruiken dezelfde publieke header/footer, maar zonder adminzijbalk.

Floating status bovenaan:
1. Na login toont elke adminpagina een zwevende statusbalk met klasse `maatlas-admin-floating-status`.
2. De statusbalk bevat de tekst `U bent aangemeld`.
3. De balk toont gebruikersnaam en rol: `Gebruiker: ... | Rol: ...`.
4. Rechts in de balk staat een directe link `Uitloggen`.
5. De statusbalk gebruikt `role="status"` en `aria-live="polite"` zodat statusinformatie semantisch beschikbaar is.
6. Op mobiel verandert de floating status van rijlayout naar compacte kolomlayout.

Zijmenu:
1. Ingelogde gebruikers zien links `maatlas-admin-sidebar`.
2. De zijbalk toont de site-identiteit, korte instructietekst, huidige naam en rol.
3. Navigatie-items zijn Dashboard, Beheerders, Galerij, Mobiele upload, Lastenboek en Instellingen.
4. Het actieve menu-item krijgt klasse `is-current`.
5. Onderaan staat een aparte logoutlink `maatlas-admin-sidebar-logout`.

Galerij-submenu:
1. Op de galerijpagina kan de zijbalk een submenu tonen.
2. Het submenu gebruikt `data-submenu-toggle="gallery"` en `aria-expanded`.
3. De toggleknop gebruikt klasse `maatlas-admin-sidebar-toggle`.
4. De visuele pijl zit in `maatlas-admin-sidebar-toggle-icon`.
5. JavaScript in de footer schakelt de klasse `is-collapsed` op het submenu.

Wachtwoordknoppen:
1. Wachtwoordvelden gebruiken `maatlas-admin-password-row`.
2. De toon/verbergknop gebruikt `data-password-toggle`.
3. JavaScript zoekt het doelveld via het id in `data-password-toggle`.
4. De knop wisselt tussen `Toon wachtwoord` en `Verberg wachtwoord`.
5. `aria-pressed` wordt aangepast naar `true` of `false`.

Actieknoppen:
1. Primaire acties gebruiken `maatlas-admin-button`.
2. Secundaire acties gebruiken `maatlas-admin-button-secondary`.
3. Gevaarlijke acties zoals verwijderen gebruiken `maatlas-admin-button-danger` of tabelknoppen met bevestiging.
4. Tabelacties worden gegroepeerd in `maatlas-admin-table-actions`.
5. Bij verwijderen wordt een browserconfirmatie gebruikt waar dit destructief is.

Statusmeldingen:
1. Succesmeldingen gebruiken `maatlas-admin-alert maatlas-admin-alert-success`.
2. Foutmeldingen gebruiken `maatlas-admin-alert maatlas-admin-alert-error`.
3. Deze meldingen staan bovenaan de relevante beheerpagina.
4. Voorbeelden zijn ongeldige CSRF-token, verzonden activatiemail, ongeldige login of geslaagde opslag.

Badges en statuslabels:
1. Kleine statuslabels gebruiken `maatlas-admin-badge`.
2. In het lastenboek wordt hiermee de status van een item getoond, bijvoorbeeld `concept`, `te-bekijken` of `goedgekeurd`.
3. In beheerdersoverzichten wordt status als tekst weergegeven: `actief`, `inactief` of `wacht op bevestiging`.
4. Bij activatielinks wordt ook `vervallen` vermeld wanneer de link niet meer geldig is.

Beheerdersscherm:
1. Het formulier bevat velden voor gebruikersnaam, volledige naam, e-mail, rol en activatiemethode.
2. Bij directe activatie verschijnen startwachtwoordvelden.
3. Bij e-mailactivatie mogen wachtwoordvelden leeg blijven.
4. De beheerder kan vanuit het overzicht opnieuw een activatiemail verzenden.
5. De laatste actieve beheerder kan niet verwijderd of gedeactiveerd worden.

Dashboardkaarten:
1. Het dashboard gebruikt kaarten om aantallen en snelkoppelingen te tonen.
2. Kaarten bevatten beheerders, actieve accounts, galerij, mobiele upload, instellingen en lastenboek.
3. De mobiele uploadkaart toont een QR-code naar `/admin/mobile-upload.php`.

Mobiele uploadinterface:
1. Mobiele upload gebruikt een grote uploadknop met klasse `maatlas-mobile-upload-picker-button`.
2. Uploadstatus wordt getoond met `maatlas-mobile-upload-status`.
3. Succes en fouten krijgen aparte klassen `is-success` en `is-error`.
4. De pagina is bedoeld voor gebruik op smartphone en bevat een manifest en service worker.

Public shell binnen admin:
1. Adminpagina’s behouden dezelfde publieke header en footer als de website.
2. De footer bevat links naar Privacyverklaring, Cookiebeleid, sociale profielen en de adminlink.
3. De materialenregel in de footer bevat een vaste regelbreuk: `Materialen met profielen van<br>Forster Systems. Bekijk onze dealerfiche.`

Belangrijke CSS-klassen:
- `maatlas-admin-floating-status`
- `maatlas-admin-sidebar`
- `maatlas-admin-sidebar-toggle`
- `maatlas-admin-sidebar-submenu`
- `maatlas-admin-button`
- `maatlas-admin-button-secondary`
- `maatlas-admin-button-danger`
- `maatlas-admin-alert-success`
- `maatlas-admin-alert-error`
- `maatlas-admin-badge`
- `maatlas-admin-password-row`
- `maatlas-admin-toggle-password`

Heropbouwregel:
Bij het opnieuw opbouwen van het admingedeelte moet eerst de beveiligde workflow werken, daarna de visuele adminlayout. De floating status, logoutlinks, zijmenu, actieve menu-aanduiding, meldingen, wachtwoordtoggles en statuslabels horen bij de basisfunctionaliteit en mogen niet als decoratie worden beschouwd.',
        'status' => 'concept',
        'position' => 5,
        'created_at' => '2026-04-21T20:20:00+02:00',
        'updated_at' => '2026-04-21T20:20:00+02:00',
      ),
      5 => 
      array (
        'id' => 'lb-admin-security-export',
        'rubric' => 'Admin',
        'code' => '03.04',
        'title' => 'Adminbeveiliging, .htaccess en lastenboekexport',
        'content' => 'Doel: het admingedeelte moet zowel applicatief als via serverregels beschermd zijn, en het volledige lastenboek moet downloadbaar blijven als tekstbestand.

Applicatieve beveiliging:
1. Elke beheerpagina laadt `admin/bootstrap.php`.
2. Beveiligde pagina’s roepen `maatlas_admin_require_login()` aan.
3. Zonder actieve sessie volgt redirect naar `/admin/login.php`.
4. Alleen actieve beheerders kunnen aanmelden.
5. Wachtwoorden worden met `password_hash()` opgeslagen en met `password_verify()` gecontroleerd.
6. POST-acties gebruiken CSRF-controle via `maatlas_admin_csrf_token()` en `maatlas_admin_verify_csrf()`.
7. De eerste tijdelijke `admin/admin` login mag alleen bestaan zolang er geen echte beheerder is.

Serverbeveiliging via `.htaccess`:
1. `admin/.htaccess` zet `Options -Indexes`, zodat directory listing uit staat.
2. `admin/.htaccess` forceert HTTPS voor alle adminrequests.
3. `admin/.htaccess` blokkeert toegang tot `admin/storage/`.
4. `admin/.htaccess` blokkeert directe toegang tot `admin/bootstrap.php`.
5. `admin/.htaccess` blokkeert verborgen bestanden zoals `.htaccess`.
6. `admin/storage/.htaccess` blokkeert alle directe webtoegang tot opslagbestanden en sessies.
7. Deze `.htaccess`-laag is aanvullend op de PHP-login, geen vervanging ervan.

Waarom geen globale Basic Auth:
1. De adminomgeving heeft al eigen gebruikers, rollen, sessies, activatielinks en wachtwoordbeheer.
2. Een extra Basic Auth-laag zou activatielinks en normale loginflows dubbel maken.
3. De gekozen oplossing beschermt gevoelige bestanden server-side en laat de PHP-authenticatie het gebruikersbeheer afhandelen.

TXT-export lastenboek:
1. Het volledige lastenboek is downloadbaar via `/admin/lastenboek.php?download=txt`.
2. De export is alleen bereikbaar na login, omdat `maatlas_admin_require_login()` eerst uitgevoerd wordt.
3. De functie `maatlas_lastenboek_to_text()` bouwt de TXT-inhoud op.
4. De export bevat documenttitel, website/domein, beheerder/eigenaar, technische referentie, versie, exportdatum, scope en alle hoofdstukken.
5. De response gebruikt `Content-Type: text/plain; charset=UTF-8`.
6. De response gebruikt `Content-Disposition: attachment`, zodat de browser het bestand downloadt.
7. Bestandsnaamformaat: `maatlaswerk-lastenboek-YYYYMMDD-HHMMSS.txt`.

Controle na wijziging:
1. `/admin/` moet bereikbaar blijven via HTTPS.
2. `/admin/storage/lastenboek.php` moet direct `403 Forbidden` geven.
3. `/admin/bootstrap.php` moet direct `403 Forbidden` geven.
4. `/admin/lastenboek.php?download=txt` moet na login een `.txt` download geven.
5. `/admin/lastenboek.php` moet normaal blijven laden.',
        'status' => 'concept',
        'position' => 6,
        'created_at' => '2026-04-21T20:45:00+02:00',
        'updated_at' => '2026-04-21T20:45:00+02:00',
      ),
      6 => 
      array (
        'id' => 'lb-template-4',
        'rubric' => 'Galerij',
        'code' => '04.01',
        'title' => 'Galerij, categorieen en dynamische inhoud',
        'content' => 'Afbeeldingen worden beheerd onder `assets/uploads` en gekoppeld aan categorieen via de admin. De publieke site leest deze metadata uit de opslagbestanden in `admin/storage/` en bouwt daaruit dynamisch albumoverzichten, detailpagina’s en willekeurige projectbeelden op.',
        'status' => 'concept',
        'position' => 7,
        'created_at' => '2026-03-30T12:00:00+02:00',
        'updated_at' => '2026-03-30T12:00:00+02:00',
      ),
      7 => 
      array (
        'id' => 'lb-template-5',
        'rubric' => 'Formulieren',
        'code' => '05.01',
        'title' => 'Contactformulier en mailafhandeling',
        'content' => 'Het contactformulier op `/contact/` leest zijn ontvanger, afzender, testmodus en publieke contactgegevens uit de admininstellingen. In testmodus worden berichten doorgestuurd naar een testadres; in live modus naar het effectieve ontvangstadres.',
        'status' => 'concept',
        'position' => 8,
        'created_at' => '2026-03-30T12:00:00+02:00',
        'updated_at' => '2026-03-30T12:00:00+02:00',
      ),
      8 => 
      array (
        'id' => 'lb-social-media',
        'rubric' => 'Sociale media',
        'code' => '05.02',
        'title' => 'Sociale media opbouw en beheer',
        'content' => 'Doel: sociale media centraal beheren vanuit de admin en consequent tonen in footer en floating knoppen.

Belangrijkste bestanden:
- `admin/settings.php`: formulier waarin Facebook- en Instagram-links worden beheerd.
- `admin/storage/site_settings.php`: opslagbestand met `facebook_url` en `instagram_url`.
- `includes/site-settings.php`: standaardwaarden, laden/opslaan van instellingen en runtime export naar JavaScript.
- `assets/themes/bluehost-blueprint/site-shell.js`: bouwt publieke footerlinks en floating social buttons.
- `assets/themes/bluehost-blueprint/style.css`: styling voor footerlinks en floating social buttons.
- `admin/bootstrap.php`: adminpagina’s gebruiken dezelfde publieke footer en lezen sociale links uit site-instellingen.

Instellingen in de admin:
1. Beheerder opent `/admin/settings.php`.
2. Onder Administratieve instellingen staan de velden `Facebook-link` en `Instagram-link`.
3. Beide velden gebruiken inputtype `url`.
4. Lege waarden zijn toegestaan.
5. Niet-lege waarden worden gevalideerd met `FILTER_VALIDATE_URL`.
6. Bij opslaan worden de waarden bewaard als `facebook_url` en `instagram_url`.

Standaardwaarden:
1. `includes/site-settings.php` bevat standaardlinks voor Facebook en Instagram.
2. `maatlas_site_settings_load()` merge bestaande opslag met standaardwaarden.
3. Als een opgeslagen link leeg is, gebruikt de publieke runtime de standaardwaarde.
4. Hierdoor verdwijnen links niet onverwacht wanneer oude instellingen nog geen sociale velden bevatten.

Publieke runtime:
1. Publieke pagina’s laden `$settings = maatlas_site_settings_load()`.
2. Vlak voor de shell-JavaScript wordt `maatlas_site_render_public_runtime_settings($settings)` aangeroepen.
3. Die functie schrijft `window.maatlasSiteSettings`.
4. In die payload zitten `facebookUrl` en `instagramUrl`.
5. `site-shell.js` leest deze waarden via `runtimeSettings.facebookUrl` en `runtimeSettings.instagramUrl`.

Footerlinks:
1. `site-shell.js` bouwt `socialLinks` op basis van beschikbare URLs.
2. De footer toont alleen een blok `maatlas-shell-footer-socials` wanneer er minstens één sociale link bestaat.
3. Elke link krijgt klasse `maatlas-shell-social`.
4. Labels zijn zichtbaar als `Facebook` en `Instagram`.
5. Links gebruiken `rel="noopener noreferrer"`.

Floating social buttons:
1. `site-shell.js` bouwt naast de footer ook `maatlas-floating-socials`.
2. Elke knop krijgt klasse `maatlas-floating-social`.
3. Facebook krijgt extra klasse `maatlas-floating-facebook`.
4. Instagram krijgt extra klasse `maatlas-floating-instagram`.
5. Iconen worden inline in JavaScript opgebouwd via SVG-markup.
6. De knoppen worden met `document.body.insertAdjacentHTML("beforeend", floatingSocials)` aan de pagina toegevoegd.
7. Als er geen sociale links zijn, wordt het floating social blok niet gerenderd.

Styling:
1. Footerlinks worden gestyled met `maatlas-shell-footer-socials` en `maatlas-shell-social`.
2. Floating knoppen worden gestyled met `maatlas-floating-socials`, `maatlas-floating-social` en `maatlas-floating-social-icon`.
3. Platformspecifieke kleuren zitten in `maatlas-floating-facebook` en `maatlas-floating-instagram`.
4. Hover en focus states zijn voorzien.
5. Tijdens lightboxgebruik worden floating socials verborgen via `body.maatlas-lightbox-open .maatlas-floating-socials`.
6. Responsive regels verplaatsen of schalen de knoppen op kleinere schermen.

Adminomgeving:
1. `admin/bootstrap.php` gebruikt `maatlas_site_settings_load()` in de publieke footer binnen adminpagina’s.
2. Facebook en Instagram worden daar nu uit `facebook_url` en `instagram_url` gelezen.
3. Als een adminlink leeg is, valt de footer terug op de standaardwaarde uit `maatlas_site_settings_default()`.
4. De adminfooter gebruikt dezelfde klassen `maatlas-shell-footer-socials` en `maatlas-shell-social`.

Uitbreiden met extra kanalen:
1. Voeg een veld toe aan `maatlas_site_settings_default()`.
2. Voeg opslag en validatie toe in `admin/settings.php`.
3. Voeg het veld toe aan `admin/storage/site_settings.php` of laat defaults mergen.
4. Voeg de waarde toe aan `maatlas_site_render_public_runtime_settings()`.
5. Breid `socialLinks` in `site-shell.js` uit met label, className, ariaLabel en icon.
6. Voeg CSS toe voor platformkleur en responsive gedrag.
7. Werk de adminfooter in `admin/bootstrap.php` bij als de link ook daar zichtbaar moet zijn.

Controle na wijziging:
1. Open `/admin/settings.php` en sla Facebook/Instagram URLs op.
2. Controleer `/` of `/index.php` op footerlinks.
3. Controleer of floating knoppen zichtbaar zijn buiten de lightbox.
4. Controleer `/admin/` of de adminfooter dezelfde sociale links toont.
5. Controleer mobiel of de knoppen niet over hoofdcontent of formulieren vallen.

Heropbouwregel:
Sociale media horen niet hardcoded per pagina te staan. De bron is de admininstelling, de publieke pagina’s krijgen de links via runtime settings, en de shell rendert footerlinks en floating buttons centraal.',
        'status' => 'concept',
        'position' => 9,
        'created_at' => '2026-04-21T20:35:00+02:00',
        'updated_at' => '2026-04-21T20:35:00+02:00',
      ),
      9 => 
      array (
        'id' => 'lb-template-6',
        'rubric' => 'Compliance',
        'code' => '06.01',
        'title' => 'Privacy, cookies en externe diensten',
        'content' => 'De website bevat afzonderlijke pagina’s voor privacy en cookies. Externe diensten zoals Google Maps worden publiek vermeld, en de contactpagina bevat de nodige privacytoelichting en toestemmingsverwijzing conform de ingestelde sitegegevens.',
        'status' => 'concept',
        'position' => 10,
        'created_at' => '2026-03-30T12:00:00+02:00',
        'updated_at' => '2026-03-30T12:00:00+02:00',
      ),
      10 => 
      array (
        'id' => 'lb-template-7',
        'rubric' => 'Publicatie',
        'code' => '07.01',
        'title' => 'Upload, publicatie en onderhoud',
        'content' => 'Wijzigingen worden lokaal uitgevoerd in `Website/` en daarna gericht geüpload naar de server. Voor onderhoud is het belangrijk dat alleen gewijzigde bestanden worden gepubliceerd en dat oude, overbodige exports of testmappen niet opnieuw worden meegezet.',
        'status' => 'concept',
        'position' => 11,
        'created_at' => '2026-03-30T12:00:00+02:00',
        'updated_at' => '2026-03-30T12:00:00+02:00',
      ),
    ),
  ),
);
