<?php
require __DIR__ . '/includes/gallery-public.php';
require __DIR__ . '/includes/site-settings.php';

$days = ['zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag'];
$now = new DateTime('now', new DateTimeZone('Europe/Brussels'));
$dayName = $days[(int) $now->format('w')];
$liveDateTime = $dayName . ' ' . $now->format('d/m/y H:i');
$settings = maatlas_site_settings_load();

$categories = maatlas_public_gallery_categories();
$allMedia = maatlas_public_gallery_media();
$projectMedia = $allMedia;
$categoryShowcaseItems = maatlas_public_gallery_category_entries($projectMedia, $categories, 6);

$heroImageUrl = '/assets/uploads/static/MaatLasWerk-13.jpg';
$heroMedia = maatlas_public_pick_random_media($projectMedia);
if ($heroMedia !== null) {
	$heroImageUrl = (string) $heroMedia['url'];
}
?>
<!DOCTYPE html>
<html lang="nl-BE">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>W&amp;S Maatlaswerk | Staal en glas op maat</title>
	<meta name="description" content="W&amp;S Maatlaswerk uit Kluisbergen maakt ramen, deuren, balustrades en metaalwerk op maat in staal, inox, aluminium en glas.">
	<link rel="stylesheet" href="/assets/themes/bluehost-blueprint/style.css?ver=2.0.2">
	<?php maatlas_site_render_theme_style($settings); ?>
	<link rel="icon" href="/assets/uploads/static/MaatLasWerk-13-150x150.jpg" sizes="32x32">
	<link rel="icon" href="/assets/uploads/static/MaatLasWerk-13.jpg" sizes="192x192">
	<link rel="apple-touch-icon" href="/assets/uploads/static/MaatLasWerk-13.jpg">
	<meta name="msapplication-TileImage" content="/assets/uploads/static/MaatLasWerk-13.jpg">
</head>
<body>
<div class="top-datetime"><?= htmlspecialchars($liveDateTime, ENT_QUOTES, 'UTF-8'); ?></div>
<div class="site-site-blocks">
	<main class="maatlas-main">
		<section class="maatlas-page-section maatlas-grid-2 maatlas-home-hero">
			<div class="maatlas-hero-copy">
				<p class="maatlas-eyebrow">Metaal en glas op maat</p>
				<h1 class="maatlas-title">Strak maatwerk in staal, met oog voor detail.</h1>
				<p class="maatlas-lead">W&amp;S Maatlaswerk maakt ramen, deuren, balustrades, poorten en verfijnde staalconstructies op maat. Vanuit Kluisbergen bouwen we oplossingen die technisch sterk zijn en visueel rustig blijven.</p>
				<ul class="maatlas-highlight-list">
					<li>Stalen ramen en deuren op maat</li>
					<li>Balustrades, poorten en buitentoepassingen</li>
					<li>Interieurwerk in staal, inox en aluminium</li>
					<li>Van ontwerp tot plaatsing door een vast aanspreekpunt</li>
				</ul>
				<div class="maatlas-button-row">
					<a class="maatlas-button" href="/about/">Meer over ons</a>
					<a class="maatlas-button-secondary" href="/contact/">Contacteer ons</a>
				</div>
			</div>
			<div class="maatlas-hero-media">
				<a class="maatlas-hero-frame maatlas-lightbox-trigger" href="<?= htmlspecialchars($heroImageUrl, ENT_QUOTES, 'UTF-8'); ?>" data-lightbox="image" data-lightbox-caption="Project van W&amp;S Maatlaswerk">
					<img src="<?= htmlspecialchars($heroImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Project van W&amp;S Maatlaswerk">
				</a>
			</div>
		</section>

		<section class="maatlas-page-section">
			<div class="maatlas-section-heading">
				<p class="maatlas-eyebrow">Waarom W&amp;S</p>
				<h2>Vakmanschap dat in lijn blijft met jouw woning of project.</h2>
				<p>We werken met slanke profielen, doordachte details en een praktische aanpak. Elk stuk wordt op maat uitgewerkt, met aandacht voor afwerking, stabiliteit en dagelijkse bruikbaarheid.</p>
			</div>
			<div class="maatlas-showcase-grid">
				<article class="maatlas-showcase-card">
					<h3>Speciale afmetingen</h3>
					<p>Wij maken oplossingen die niet uit standaardmaten komen, maar exact aansluiten bij opening, architectuur en gebruik.</p>
				</article>
				<article class="maatlas-showcase-card">
					<h3>Slanke profielen</h3>
					<p>Onze realisaties combineren een lichte uitstraling met de stevigheid die nodig is voor dagelijks gebruik op lange termijn.</p>
				</article>
				<article class="maatlas-showcase-card">
					<h3>Een vast traject</h3>
					<p>Van eerste idee tot plaatsing werken we helder en rechtstreeks, zodat ontwerp, uitvoering en afwerking op elkaar blijven aansluiten.</p>
				</article>
			</div>
		</section>

		<section class="maatlas-page-section">
			<div class="maatlas-section-heading">
				<p class="maatlas-eyebrow">Realisaties</p>
				<h2>Een greep uit ons werk.</h2>
				<p>Klik op een album om binnen onze diensten de volledige fotoreeks en extra info per categorie te bekijken.</p>
			</div>
			<?php if ($categoryShowcaseItems === []): ?>
			<div class="maatlas-card">
				<h2>Nog geen albums beschikbaar</h2>
				<p>Voeg in de admin albums toe en koppel daar galerijfoto&apos;s aan. Daarna verschijnt deze selectie hier automatisch.</p>
			</div>
			<?php else: ?>
			<div class="maatlas-image-grid">
				<?php foreach ($categoryShowcaseItems as $categoryItem): ?>
				<a class="maatlas-photo-card" href="<?= htmlspecialchars((string) $categoryItem['detail_url'], ENT_QUOTES, 'UTF-8'); ?>">
					<img src="<?= htmlspecialchars((string) $categoryItem['cover_media']['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars((string) $categoryItem['name'], ENT_QUOTES, 'UTF-8'); ?>">
					<span><?= htmlspecialchars((string) $categoryItem['name'], ENT_QUOTES, 'UTF-8'); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</section>

		<section class="maatlas-page-section">
			<div class="maatlas-cta-band">
				<div>
					<p class="maatlas-eyebrow">Project bespreken</p>
					<h2>Klaar om jouw idee uit te werken?</h2>
					<p>Stuur ons jouw plannen, afmetingen of referentiebeelden door. We bekijken graag hoe we er een sterke en duurzame uitvoering van maken.</p>
				</div>
				<div class="maatlas-cta-actions">
					<a class="maatlas-button" href="/services/">Bekijk onze diensten</a>
				</div>
			</div>
		</section>
	</main>
</div>
<?php maatlas_site_render_public_runtime_settings($settings); ?>
<script src="/assets/themes/bluehost-blueprint/site-shell.js?v=20260330-10"></script>
</body>
</html>
