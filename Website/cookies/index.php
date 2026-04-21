<?php

require __DIR__ . '/../includes/site-settings.php';

$settings = maatlas_site_settings_load();
?>
<!DOCTYPE html>
<html lang="nl-BE">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Cookiebeleid | W&amp;S Maatlaswerk</title>
	<meta name="description" content="Cookiebeleid van W&amp;S Maatlaswerk met uitleg over noodzakelijke cookies en externe inhoud zoals Google Maps.">
	<link rel="stylesheet" href="/assets/themes/bluehost-blueprint/style.css?ver=2.0.4">
	<?php maatlas_site_render_theme_style($settings); ?>
	<link rel="icon" href="/assets/uploads/static/MaatLasWerk-13-150x150.jpg" sizes="32x32">
	<link rel="icon" href="/assets/uploads/static/MaatLasWerk-13.jpg" sizes="192x192">
</head>
<body>
<div class="site-site-blocks">
	<main class="maatlas-main">
		<section class="maatlas-page-section">
			<div class="maatlas-contact-card">
				<p class="maatlas-eyebrow">Cookies</p>
				<h1>Cookiebeleid</h1>
				<p class="maatlas-lead">Deze website gebruikt geen marketing- of analysecookies, maar op de contactpagina is wel een Google Maps-kaart ingesloten die door Google geladen wordt.</p>
			</div>
		</section>

		<section class="maatlas-page-section">
			<div class="maatlas-legal-grid">
				<article class="maatlas-contact-panel">
					<h2>Welke cookies gebruiken we?</h2>
					<p>Op de publieke website gebruiken we geen marketing- of analysecookies. Alleen technisch noodzakelijke functies mogen actief zijn voor de goede werking van de site.</p>
				</article>

				<article class="maatlas-contact-panel">
					<h2>Google Maps</h2>
					<p>De kaart op de contactpagina wordt rechtstreeks van Google geladen. Daardoor kan Google bij het openen van die pagina eigen cookies of gelijkaardige technologie gebruiken.</p>
				</article>

				<article class="maatlas-contact-panel">
					<h2>Sociale media</h2>
					<p>Links naar Facebook en Instagram zijn gewone uitgaande links. Ze plaatsen via deze website geen cookies zolang je er niet zelf op klikt.</p>
				</article>

				<article class="maatlas-contact-panel">
					<h2>Wat als dit later verandert?</h2>
					<p>Als er later analysecookies, marketingcookies of andere niet-noodzakelijke tracking wordt toegevoegd, moet daarvoor voorafgaande toestemming gevraagd worden voordat die technologie geladen wordt.</p>
				</article>
			</div>
		</section>
	</main>
</div>
<?php maatlas_site_render_public_runtime_settings($settings); ?>
<script src="/assets/themes/bluehost-blueprint/site-shell.js?v=20260421-3"></script>
</body>
</html>
