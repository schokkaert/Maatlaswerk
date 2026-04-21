<?php
require __DIR__ . '/../includes/gallery-public.php';
require __DIR__ . '/../includes/site-settings.php';

$settings = maatlas_site_settings_load();
$categories = maatlas_public_gallery_categories();
$allMedia = maatlas_public_gallery_media();
$requestedSlug = (string) ($_GET['category'] ?? '');
$category = maatlas_public_find_category_by_slug($categories, $requestedSlug);

if ($category === null) {
	http_response_code(404);
}

$categoryName = $category !== null ? trim((string) ($category['name'] ?? '')) : 'Categorie niet gevonden';
$categoryDescription = $category !== null ? trim((string) ($category['description'] ?? '')) : '';
$categoryMedia = $category !== null ? maatlas_public_media_by_category_id($allMedia, (string) ($category['id'] ?? '')) : [];
$primaryMedia = $categoryMedia !== [] ? $categoryMedia[0] : null;
$primaryCaption = $primaryMedia !== null ? trim((string) ($primaryMedia['title'] ?? '')) : '';
?>
<!DOCTYPE html>
<html lang="nl-BE">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?> | Diensten | W&amp;S Maatlaswerk</title>
	<meta name="description" content="Bekijk de realisaties en foto&apos;s binnen de categorie <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?> van W&amp;S Maatlaswerk.">
	<link rel="stylesheet" href="<?= htmlspecialchars(maatlas_site_url('/assets/themes/bluehost-blueprint/style.css?ver=2.0.4'), ENT_QUOTES, 'UTF-8'); ?>">
	<?php maatlas_site_render_theme_style($settings); ?>
	<link rel="icon" href="<?= htmlspecialchars(maatlas_site_url('/assets/uploads/static/MaatLasWerk-13-150x150.jpg'), ENT_QUOTES, 'UTF-8'); ?>" sizes="32x32">
	<link rel="icon" href="<?= htmlspecialchars(maatlas_site_url('/assets/uploads/static/MaatLasWerk-13.jpg'), ENT_QUOTES, 'UTF-8'); ?>" sizes="192x192">
	<link rel="apple-touch-icon" href="<?= htmlspecialchars(maatlas_site_url('/assets/uploads/static/MaatLasWerk-13.jpg'), ENT_QUOTES, 'UTF-8'); ?>">
	<meta name="msapplication-TileImage" content="<?= htmlspecialchars(maatlas_site_url('/assets/uploads/static/MaatLasWerk-13.jpg'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<div class="site-site-blocks">
	<main class="maatlas-main">
		<section class="maatlas-page-section">
			<div class="maatlas-section-heading maatlas-section-heading-wide">
				<p class="maatlas-eyebrow">Diensten</p>
				<nav class="maatlas-breadcrumb" aria-label="Breadcrumb">
					<a href="<?= htmlspecialchars(maatlas_site_url('/'), ENT_QUOTES, 'UTF-8'); ?>">Home</a>
					<span>/</span>
					<a href="<?= htmlspecialchars(maatlas_site_url('/services/'), ENT_QUOTES, 'UTF-8'); ?>">Diensten</a>
					<span>/</span>
					<strong><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></strong>
				</nav>
				<h1 class="maatlas-title"><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></h1>
				<p class="maatlas-lead">
					<?= htmlspecialchars($categoryDescription !== '' ? $categoryDescription : "Deze pagina wordt automatisch opgebouwd uit de galerijfoto's van deze categorie.", ENT_QUOTES, 'UTF-8'); ?>
				</p>
			</div>
		</section>

		<section class="maatlas-page-section">
			<?php if ($category === null): ?>
			<div class="maatlas-card">
				<h2>Deze categorie bestaat niet</h2>
				<p>De gevraagde categorie werd niet gevonden. Keer terug naar het dienstenoverzicht om een geldig album te kiezen.</p>
			</div>
			<?php elseif ($categoryMedia === []): ?>
			<div class="maatlas-card">
				<h2>Nog geen foto&apos;s in deze categorie</h2>
				<p>De categorie bestaat al, maar er zijn nog geen beelden aan gekoppeld in de galerij.</p>
			</div>
			<?php else: ?>
			<div class="maatlas-service-detail-gallery">
				<a
					class="maatlas-service-detail-stage maatlas-lightbox-trigger"
					id="maatlas-service-stage-link"
					href="<?= htmlspecialchars((string) ($primaryMedia['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
					data-lightbox="image"
					data-lightbox-caption="<?= htmlspecialchars($primaryCaption !== '' ? $primaryCaption : $categoryName, ENT_QUOTES, 'UTF-8'); ?>"
				>
					<img
						id="maatlas-service-stage-image"
						src="<?= htmlspecialchars((string) ($primaryMedia['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
						alt="<?= htmlspecialchars(maatlas_public_label_for_media((array) $primaryMedia, $categories), ENT_QUOTES, 'UTF-8'); ?>"
					>
				</a>
				<?php if ($primaryCaption !== ''): ?>
				<p class="maatlas-service-detail-caption" id="maatlas-service-stage-caption"><?= htmlspecialchars($primaryCaption, ENT_QUOTES, 'UTF-8'); ?></p>
				<?php else: ?>
				<p class="maatlas-service-detail-caption" id="maatlas-service-stage-caption" hidden></p>
				<?php endif; ?>

				<div class="maatlas-service-detail-strip-wrap">
					<button class="maatlas-service-detail-strip-nav maatlas-service-detail-strip-prev" type="button" aria-label="Vorige foto's">&lsaquo;</button>
					<div class="maatlas-service-detail-strip" id="maatlas-service-thumb-strip">
						<?php foreach ($categoryMedia as $index => $media): ?>
						<button
							class="maatlas-service-detail-thumb<?= $index === 0 ? ' is-active' : ''; ?>"
							type="button"
							data-image-src="<?= htmlspecialchars((string) $media['url'], ENT_QUOTES, 'UTF-8'); ?>"
							data-image-alt="<?= htmlspecialchars(maatlas_public_label_for_media($media, $categories), ENT_QUOTES, 'UTF-8'); ?>"
							data-image-caption="<?= htmlspecialchars(trim((string) ($media['title'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
							aria-label="Toon foto <?= $index + 1; ?>"
						>
							<img src="<?= htmlspecialchars((string) $media['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars(maatlas_public_label_for_media($media, $categories), ENT_QUOTES, 'UTF-8'); ?>">
						</button>
						<?php endforeach; ?>
					</div>
					<button class="maatlas-service-detail-strip-nav maatlas-service-detail-strip-next" type="button" aria-label="Volgende foto's">&rsaquo;</button>
				</div>
			</div>
			<?php endif; ?>
		</section>
	</main>
</div>
<?php maatlas_site_render_public_runtime_settings($settings); ?>
<script src="<?= htmlspecialchars(maatlas_site_url('/assets/themes/bluehost-blueprint/site-shell.js?v=20260421-4'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script>
(function () {
  var gallery = document.querySelector('.maatlas-service-detail-gallery');
  var stageLink = document.getElementById('maatlas-service-stage-link');
  var stageImage = document.getElementById('maatlas-service-stage-image');
  var stageCaption = document.getElementById('maatlas-service-stage-caption');
  var thumbStrip = document.getElementById('maatlas-service-thumb-strip');

  if (!gallery || !stageLink || !stageImage || !thumbStrip) {
    return;
  }

  var thumbs = Array.prototype.slice.call(thumbStrip.querySelectorAll('.maatlas-service-detail-thumb'));
  var prevButton = document.querySelector('.maatlas-service-detail-strip-prev');
  var nextButton = document.querySelector('.maatlas-service-detail-strip-next');
  var stripWrap = document.querySelector('.maatlas-service-detail-strip-wrap');

  var syncGalleryViewport = function () {
    if (window.innerWidth <= 1024) {
      gallery.style.removeProperty('--maatlas-gallery-available-height');
      gallery.style.removeProperty('--maatlas-gallery-stage-height');
      return;
    }

    var rect = gallery.getBoundingClientRect();
    var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    var availableHeight = Math.max(320, viewportHeight - rect.top - 18);
    var captionHeight = stageCaption && !stageCaption.hidden ? stageCaption.offsetHeight + 8 : 0;
    var stripHeight = stripWrap ? stripWrap.offsetHeight : 110;
    var stageHeight = Math.max(240, availableHeight - captionHeight - stripHeight - 16);

    gallery.style.setProperty('--maatlas-gallery-available-height', availableHeight + 'px');
    gallery.style.setProperty('--maatlas-gallery-stage-height', stageHeight + 'px');
  };

  var activateThumb = function (thumb) {
    if (!thumb) {
      return;
    }

    var src = thumb.getAttribute('data-image-src') || '';
    var alt = thumb.getAttribute('data-image-alt') || 'Projectfoto';
    var caption = thumb.getAttribute('data-image-caption') || '';

    stageLink.setAttribute('href', src);
    stageLink.setAttribute('data-lightbox-caption', caption || '<?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?>');
    stageImage.setAttribute('src', src);
    stageImage.setAttribute('alt', alt);

    if (stageCaption) {
      stageCaption.textContent = caption;
      stageCaption.hidden = caption === '';
    }

    syncGalleryViewport();

    thumbs.forEach(function (item) {
      item.classList.toggle('is-active', item === thumb);
    });

    thumb.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
  };

  thumbs.forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      activateThumb(thumb);
    });
  });

  if (prevButton) {
    prevButton.addEventListener('click', function () {
      thumbStrip.scrollBy({ left: -240, behavior: 'smooth' });
    });
  }

  if (nextButton) {
    nextButton.addEventListener('click', function () {
      thumbStrip.scrollBy({ left: 240, behavior: 'smooth' });
    });
  }

  syncGalleryViewport();
  window.addEventListener('resize', syncGalleryViewport);
})();
</script>
</body>
</html>
