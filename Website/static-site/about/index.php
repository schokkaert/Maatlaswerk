<?php

require __DIR__ . '/../includes/site-settings.php';

$settings = maatlas_site_settings_load();
?>
<!DOCTYPE html>
<html lang="nl-BE">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Over ons | W&amp;S Maatlaswerk</title>
	<meta name="description" content="Ontdek W&amp;S Maatlaswerk uit Kluisbergen en onze aanpak in staal, inox, aluminium en glas op maat.">
	<link rel="stylesheet" href="/assets/themes/bluehost-blueprint/style.css?ver=2.0.4">
	<?php maatlas_site_render_theme_style($settings); ?>
	<link rel="icon" href="/assets/uploads/static/MaatLasWerk-13-150x150.jpg" sizes="32x32">
	<link rel="icon" href="/assets/uploads/static/MaatLasWerk-13.jpg" sizes="192x192">
	<link rel="apple-touch-icon" href="/assets/uploads/static/MaatLasWerk-13.jpg">
	<meta name="msapplication-TileImage" content="/assets/uploads/static/MaatLasWerk-13.jpg">
</head>
<body>
<div class="site-site-blocks">
	<main class="maatlas-main">
		<section class="maatlas-page-section">
			<div class="maatlas-section-heading maatlas-section-heading-wide">
				<p class="maatlas-eyebrow">Over ons</p>
				<h1 class="maatlas-title">Maatwerk dat technisch klopt en rustig oogt.</h1>
				<p class="maatlas-lead">Bij W&amp;S Maatlaswerk in Kluisbergen staan vakmanschap, precisie en duurzame afwerking centraal. We bouwen oplossingen in staal, inox, aluminium en glas die exact aansluiten op de ruimte, de stijl en het gebruik.</p>
			</div>
			<div class="maatlas-about-grid">
				<article class="maatlas-card">
					<h2>Over W&amp;S</h2>
					<p>Sinds onze opstart werken we aan realisaties die structureel sterk zijn en tegelijk verfijnd ogen. We combineren praktijkkennis met gevoel voor afwerking, zodat elk project goed werkt en mooi blijft.</p>
				</article>
				<article class="maatlas-card">
					<h2>Wie zijn we</h2>
					<p>W&amp;S Maatlaswerk is een gespecialiseerd maatwerkbedrijf uit Kluisbergen. Wij realiseren toepassingen in staal, inox, aluminium en glas voor particulieren, bedrijven en bouwprofessionals.</p>
				</article>
				<article class="maatlas-card">
					<h2>Onze aanpak</h2>
					<p>We denken mee vanaf het ontwerp en werken elk detail praktisch uit. Daardoor blijft het maatwerk niet alleen esthetisch sterk, maar ook logisch in productie, plaatsing en dagelijks gebruik.</p>
				</article>
			</div>
		</section>
		<section class="maatlas-page-section">
			<div class="maatlas-cta-band">
				<div>
					<p class="maatlas-eyebrow">Samenwerken</p>
					<h2>Vertel ons wat je wil bouwen.</h2>
					<p>Van een stalen deur tot een volledige buitentoepassing: we bekijken graag welke oplossing technisch en esthetisch het best werkt voor jouw project.</p>
				</div>
				<div class="maatlas-cta-actions">
					<a class="maatlas-button" href="/contact/">Contacteer ons</a>
				</div>
			</div>
		</section>
	</main>
</div>
<?php maatlas_site_render_public_runtime_settings($settings); ?>
<script src="/assets/themes/bluehost-blueprint/site-shell.js?v=20260421-2"></script>
</body>
</html>
