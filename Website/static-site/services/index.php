<?php
require __DIR__ . '/../includes/gallery-public.php';
require __DIR__ . '/../includes/site-settings.php';

$categories = maatlas_public_gallery_categories();
$allMedia = maatlas_public_gallery_media();
$settings = maatlas_site_settings_load();

$projectMedia = $allMedia;
$serviceAlbums = maatlas_public_gallery_category_entries($projectMedia, $categories);
?>
<!DOCTYPE html>
<html lang="nl-BE">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Diensten | W&amp;S Maatlaswerk</title>
	<meta name="description" content="Ontdek de diensten en realisaties van W&amp;S Maatlaswerk per categorie, rechtstreeks opgebouwd uit de galerij.">
	<link rel="stylesheet" href="/assets/themes/bluehost-blueprint/style.css?ver=2.0.2">
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
				<p class="maatlas-eyebrow">Diensten</p>
				<h1 class="maatlas-title">Realisaties</h1>
			</div>
		</section>

		<section class="maatlas-page-section">
			<?php if ($serviceAlbums === []): ?>
			<div class="maatlas-card">
				<h2>Nog geen albums met foto&apos;s</h2>
				<p>Voeg in de admin eerst albums toe en koppel daar galerijfoto&apos;s aan. Daarna verschijnen hier automatisch de albums.</p>
			</div>
			<?php else: ?>
			<div class="maatlas-service-grid">
				<?php foreach ($serviceAlbums as $album): ?>
				<a class="maatlas-service-card maatlas-service-link-card" href="<?= htmlspecialchars((string) $album['detail_url'], ENT_QUOTES, 'UTF-8'); ?>">
					<img src="<?= htmlspecialchars((string) $album['cover_media']['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars((string) $album['name'], ENT_QUOTES, 'UTF-8'); ?>">
					<div class="maatlas-service-card-body">
						<h2><?= htmlspecialchars((string) $album['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
				<p><?= htmlspecialchars(trim((string) ($album['description'] ?? '')) !== '' ? (string) $album['description'] : "Bekijk alle foto's en realisaties binnen deze categorie.", ENT_QUOTES, 'UTF-8'); ?></p>
					</div>
				</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</section>

		<section class="maatlas-page-section">
			<div class="maatlas-cta-band">
				<div>
					<p class="maatlas-eyebrow">Volgende stap</p>
					<h2>Klaar om jouw project te bespreken?</h2>
					<p>Vertel ons wat je wil bouwen of vernieuwen. We bekijken graag de mogelijkheden en werken een oplossing uit die technisch en esthetisch klopt.</p>
				</div>
				<div class="maatlas-cta-actions">
					<a class="maatlas-button" href="/contact/">Contacteer ons</a>
				</div>
			</div>
		</section>
	</main>
</div>
<?php maatlas_site_render_public_runtime_settings($settings); ?>
<script src="/assets/themes/bluehost-blueprint/site-shell.js?v=20260330-10"></script>
</body>
</html>
