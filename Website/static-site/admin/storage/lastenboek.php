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
        'id' => 'lb-template-4',
        'rubric' => 'Galerij',
        'code' => '04.01',
        'title' => 'Galerij, categorieen en dynamische inhoud',
        'content' => 'Afbeeldingen worden beheerd onder `assets/uploads` en gekoppeld aan categorieen via de admin. De publieke site leest deze metadata uit de opslagbestanden in `admin/storage/` en bouwt daaruit dynamisch albumoverzichten, detailpagina’s en willekeurige projectbeelden op.',
        'status' => 'concept',
        'position' => 4,
        'created_at' => '2026-03-30T12:00:00+02:00',
        'updated_at' => '2026-03-30T12:00:00+02:00',
      ),
      4 => 
      array (
        'id' => 'lb-template-5',
        'rubric' => 'Formulieren',
        'code' => '05.01',
        'title' => 'Contactformulier en mailafhandeling',
        'content' => 'Het contactformulier op `/contact/` leest zijn ontvanger, afzender, testmodus en publieke contactgegevens uit de admininstellingen. In testmodus worden berichten doorgestuurd naar een testadres; in live modus naar het effectieve ontvangstadres.',
        'status' => 'concept',
        'position' => 5,
        'created_at' => '2026-03-30T12:00:00+02:00',
        'updated_at' => '2026-03-30T12:00:00+02:00',
      ),
      5 => 
      array (
        'id' => 'lb-template-6',
        'rubric' => 'Compliance',
        'code' => '06.01',
        'title' => 'Privacy, cookies en externe diensten',
        'content' => 'De website bevat afzonderlijke pagina’s voor privacy en cookies. Externe diensten zoals Google Maps worden publiek vermeld, en de contactpagina bevat de nodige privacytoelichting en toestemmingsverwijzing conform de ingestelde sitegegevens.',
        'status' => 'concept',
        'position' => 6,
        'created_at' => '2026-03-30T12:00:00+02:00',
        'updated_at' => '2026-03-30T12:00:00+02:00',
      ),
      6 => 
      array (
        'id' => 'lb-template-7',
        'rubric' => 'Publicatie',
        'code' => '07.01',
        'title' => 'Upload, publicatie en onderhoud',
        'content' => 'Wijzigingen worden lokaal uitgevoerd in `Website/static-site/` en daarna gericht geüpload naar de server. Voor onderhoud is het belangrijk dat alleen gewijzigde bestanden worden gepubliceerd en dat oude, overbodige exports of testmappen niet opnieuw worden meegezet.',
        'status' => 'concept',
        'position' => 7,
        'created_at' => '2026-03-30T12:00:00+02:00',
        'updated_at' => '2026-03-30T12:00:00+02:00',
      ),
    ),
  ),
);
