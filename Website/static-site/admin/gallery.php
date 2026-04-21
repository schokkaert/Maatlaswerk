<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/gallery-public.php';

$currentAdmin = maatlas_admin_require_login();
$message = null;
$error = null;
$filterCategory = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$allowedPanels = ['gallery-overview', 'gallery-upload', 'gallery-categories', 'gallery-library'];
$activePanel = (string) ($_GET['panel'] ?? $_POST['return_panel'] ?? 'gallery-overview');
if (!in_array($activePanel, $allowedPanels, true)) {
	$activePanel = 'gallery-overview';
}

$GLOBALS['maatlas_admin_sidebar_submenu'] = [
	['label' => 'Overzicht', 'href' => '/admin/gallery.php?panel=gallery-overview', 'is_current' => $activePanel === 'gallery-overview'],
	['label' => 'Uploaden', 'href' => '/admin/gallery.php?panel=gallery-upload', 'is_current' => $activePanel === 'gallery-upload'],
	['label' => 'Albums', 'href' => '/admin/gallery.php?panel=gallery-categories', 'is_current' => $activePanel === 'gallery-categories'],
	['label' => 'Afbeeldingen', 'href' => '/admin/gallery.php?panel=gallery-library', 'is_current' => $activePanel === 'gallery-library'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = (string) ($_POST['csrf_token'] ?? '');
	$action = (string) ($_POST['action'] ?? '');

	if (!maatlas_admin_verify_csrf($token)) {
		$error = 'Ongeldige beveiligingstoken. Herlaad de pagina en probeer opnieuw.';
	} else {
		try {
			if ($action === 'create_category') {
				$category = maatlas_gallery_create_category(
					(string) ($_POST['category_name'] ?? ''),
					(string) ($_POST['category_description'] ?? '')
				);
				$message = 'Album aangemaakt: ' . $category['name'];
			}

			if ($action === 'rename_category') {
				maatlas_gallery_update_category(
					(string) ($_POST['category_id'] ?? ''),
					(string) ($_POST['category_name'] ?? ''),
					(string) ($_POST['category_description'] ?? '')
				);
				$message = 'Album bijgewerkt.';
			}

			if ($action === 'delete_category') {
				maatlas_gallery_delete_category((string) ($_POST['category_id'] ?? ''));
				$message = 'Album verwijderd.';
				if ($filterCategory === (string) ($_POST['category_id'] ?? '')) {
					$filterCategory = '';
				}
			}

			if ($action === 'upload_media') {
				$rotationData = json_decode((string) ($_POST['rotations_json'] ?? '[]'), true);
				$rotations = is_array($rotationData) ? array_map('intval', $rotationData) : [];
				$uploadedPaths = maatlas_gallery_upload_files(
					'',
					$_FILES['media_files'] ?? [],
					(array) ($_POST['category_ids'] ?? []),
					$rotations
				);
				$message = count($uploadedPaths) . ' afbeelding(en) geupload in assets/uploads.';
			}

			if ($action === 'update_media_categories') {
				maatlas_gallery_update_media_categories(
					(string) ($_POST['relative_path'] ?? ''),
					(array) ($_POST['category_ids'] ?? [])
				);
				$message = 'Albums voor de afbeelding bijgewerkt.';
			}

			if ($action === 'delete_media') {
				maatlas_gallery_delete_file((string) ($_POST['relative_path'] ?? ''));
				$message = 'Afbeelding verwijderd.';
			}

			if ($action === 'replace_media') {
				$replacedMedia = maatlas_gallery_replace_media(
					(string) ($_POST['relative_path'] ?? ''),
					$_FILES['replacement_file'] ?? []
				);
				$message = 'Afbeelding vervangen: ' . (string) ($replacedMedia['filename'] ?? '');
			}
		} catch (RuntimeException $exception) {
			$error = $exception->getMessage();
		}
	}
}

$categories = maatlas_gallery_load_categories();
$mediaItems = maatlas_gallery_sync_media();

$categoryMap = [];
foreach ($categories as $category) {
	$categoryMap[(string) $category['id']] = $category;
}

$categoryCounts = maatlas_gallery_count_media_by_category($mediaItems);
$publicUsageMap = maatlas_public_gallery_usage_map();

$filteredMedia = array_values(array_filter($mediaItems, static function (array $media) use ($filterCategory): bool {
	$categoryIds = array_map('strval', (array) ($media['category_ids'] ?? []));
	$categoryMatches = $filterCategory === '' || in_array($filterCategory, $categoryIds, true);

	return $categoryMatches;
}));

usort($filteredMedia, static function (array $left, array $right): int {
	$leftUploadedAt = strtotime((string) ($left['uploaded_at'] ?? '')) ?: 0;
	$rightUploadedAt = strtotime((string) ($right['uploaded_at'] ?? '')) ?: 0;
	$uploadedCompare = $rightUploadedAt <=> $leftUploadedAt;
	if ($uploadedCompare !== 0) {
		return $uploadedCompare;
	}

	$leftModifiedAt = strtotime((string) ($left['modified_at'] ?? '')) ?: 0;
	$rightModifiedAt = strtotime((string) ($right['modified_at'] ?? '')) ?: 0;
	$modifiedCompare = $rightModifiedAt <=> $leftModifiedAt;
	if ($modifiedCompare !== 0) {
		return $modifiedCompare;
	}

	return strnatcasecmp((string) ($left['filename'] ?? ''), (string) ($right['filename'] ?? ''));
});

maatlas_admin_render_header('Galerijbeheer', $currentAdmin);
?>
<?php if ($message !== null): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-success"><?= maatlas_admin_h($message); ?></p>
<?php endif; ?>
<?php if ($error !== null): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-error"><?= maatlas_admin_h($error); ?></p>
<?php endif; ?>

<section class="maatlas-admin-panel-layout">
	<aside class="maatlas-admin-panel-menu maatlas-admin-panel-menu-hidden" aria-hidden="true">
		<div class="maatlas-admin-panel-menu-card">
			<p class="maatlas-admin-eyebrow">Galerijmenu</p>
			<nav class="maatlas-admin-panel-nav" aria-label="Galerij onderdelen">
				<button type="button" class="<?= $activePanel === 'gallery-overview' ? 'is-current' : ''; ?>" data-admin-panel-target="gallery-overview">Overzicht</button>
				<button type="button" class="<?= $activePanel === 'gallery-upload' ? 'is-current' : ''; ?>" data-admin-panel-target="gallery-upload">Uploaden</button>
				<button type="button" class="<?= $activePanel === 'gallery-categories' ? 'is-current' : ''; ?>" data-admin-panel-target="gallery-categories">Albums</button>
				<button type="button" class="<?= $activePanel === 'gallery-library' ? 'is-current' : ''; ?>" data-admin-panel-target="gallery-library">Afbeeldingen</button>
			</nav>
		</div>
	</aside>
	<div class="maatlas-admin-panel-content">
		<section id="gallery-overview" class="maatlas-admin-section-panel<?= $activePanel !== 'gallery-overview' ? ' maatlas-admin-section-hidden' : ''; ?>">
			<div class="maatlas-admin-grid">
				<article class="maatlas-admin-card">
					<p class="maatlas-admin-eyebrow">Foto's</p>
					<h2><?= count($mediaItems); ?> afbeeldingen</h2>
					<p>Alle foto&apos;s onder <strong>assets/uploads</strong> worden hier beheerd, behalve de beschermde systeemmap <strong>static</strong>.</p>
				</article>
				<article class="maatlas-admin-card">
					<p class="maatlas-admin-eyebrow">Albums</p>
					<h2><?= count($categories); ?> albums</h2>
					<p>Albums sturen nu de volledige opbouw van de galerij en de publieke fotopagina&apos;s aan.</p>
				</article>
				<article class="maatlas-admin-card">
					<p class="maatlas-admin-eyebrow">Opslag</p>
					<h2>1 centrale uploadmap</h2>
					<p>Nieuwe foto&apos;s worden rechtstreeks in <strong>assets/uploads</strong> geplaatst. Submappen worden niet meer gebruikt in de bediening.</p>
				</article>
			</div>
		</section>

		<section id="gallery-upload" class="maatlas-admin-section-panel<?= $activePanel !== 'gallery-upload' ? ' maatlas-admin-section-hidden' : ''; ?>">
			<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Uploaden</p>
		<h2>Nieuwe foto&apos;s toevoegen</h2>
		<form method="post" enctype="multipart/form-data" class="maatlas-admin-form" id="maatlas-gallery-upload-form">
			<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
			<input type="hidden" name="action" value="upload_media">
			<input type="hidden" name="return_panel" value="gallery-upload">
			<input type="hidden" name="rotations_json" id="maatlas-gallery-rotations" value="[]">
			<p class="maatlas-admin-help">Nieuwe foto&apos;s worden automatisch opgeslagen in <strong>assets/uploads</strong>.</p>
			<fieldset class="maatlas-admin-fieldset">
				<legend>Albums</legend>
				<?php if ($categories === []): ?>
				<p class="maatlas-admin-help">Nog geen albums aangemaakt.</p>
				<?php else: ?>
				<div class="maatlas-admin-check-grid">
					<?php foreach ($categories as $category): ?>
					<label class="maatlas-admin-check-option">
						<input type="checkbox" name="category_ids[]" value="<?= maatlas_admin_h((string) $category['id']); ?>">
						<span><?= maatlas_admin_h((string) $category['name']); ?></span>
					</label>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</fieldset>
			<label>
				<span>Selecteer afbeeldingen</span>
				<input type="file" name="media_files[]" id="maatlas-gallery-file-input" accept="image/*" multiple required>
			</label>
			<div id="maatlas-gallery-preview" class="maatlas-gallery-upload-preview">
				<p class="maatlas-admin-help">Na selectie krijg je hier een preview en rotatieknoppen per foto.</p>
			</div>
			<button type="submit" class="maatlas-admin-button">Foto&apos;s uploaden</button>
		</form>
			</article>
		</section>

		<section id="gallery-categories" class="maatlas-admin-section-panel<?= $activePanel !== 'gallery-categories' ? ' maatlas-admin-section-hidden' : ''; ?>">
			<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Albumbeheer</p>
		<h2>Albums beheren</h2>
		<form method="post" class="maatlas-admin-form maatlas-admin-inline-form">
			<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
			<input type="hidden" name="action" value="create_category">
			<input type="hidden" name="return_panel" value="gallery-categories">
			<label>
				<span>Nieuw album</span>
				<input type="text" name="category_name" required>
			</label>
			<label>
				<span>Extra tekst</span>
				<textarea name="category_description" rows="3" placeholder="Korte beschrijving of extra info voor dit album"></textarea>
			</label>
			<button type="submit" class="maatlas-admin-button">Album aanmaken</button>
		</form>

		<div class="maatlas-admin-list">
			<?php foreach ($categories as $category): ?>
				<div class="maatlas-admin-list-row">
					<div>
						<strong><?= maatlas_admin_h((string) $category['name']); ?></strong>
						<span><?= (int) ($categoryCounts[(string) $category['id']] ?? 0); ?> gekoppeld</span>
						<?php if (trim((string) ($category['description'] ?? '')) !== ''): ?>
						<p class="maatlas-admin-category-text"><?= nl2br(maatlas_admin_h((string) $category['description'])); ?></p>
						<?php endif; ?>
					</div>
					<div class="maatlas-admin-list-actions">
						<?php $categoryFormId = 'category-save-' . (string) $category['id']; ?>
						<form method="post" class="maatlas-admin-form maatlas-admin-category-edit-form" id="<?= maatlas_admin_h($categoryFormId); ?>">
							<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
							<input type="hidden" name="action" value="rename_category">
							<input type="hidden" name="return_panel" value="gallery-categories">
							<input type="hidden" name="category_id" value="<?= maatlas_admin_h((string) $category['id']); ?>">
							<input type="text" name="category_name" value="<?= maatlas_admin_h((string) $category['name']); ?>" required>
							<textarea name="category_description" rows="3" placeholder="Extra tekst voor dit album"><?= maatlas_admin_h((string) ($category['description'] ?? '')); ?></textarea>
						</form>
						<div class="maatlas-admin-list-button-row">
							<button type="submit" form="<?= maatlas_admin_h($categoryFormId); ?>" class="maatlas-admin-button maatlas-admin-button-icon">
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
									<path d="M5 4h11l3 3v13H5z" fill="none" stroke="currentColor" stroke-width="1.8"/>
									<path d="M8 4v6h8V4" fill="none" stroke="currentColor" stroke-width="1.8"/>
									<path d="M9 16h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
								</svg>
								<span>Opslaan</span>
							</button>
							<form method="post" class="maatlas-admin-list-inline-action" onsubmit="return confirm('Dit album verwijderen?');">
								<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
								<input type="hidden" name="action" value="delete_category">
								<input type="hidden" name="return_panel" value="gallery-categories">
								<input type="hidden" name="category_id" value="<?= maatlas_admin_h((string) $category['id']); ?>">
								<button type="submit" class="maatlas-admin-button maatlas-admin-button-danger maatlas-admin-button-icon">
									<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
										<path d="M4 7h16" />
										<path d="M9 3h6l1 2h4" />
										<path d="M8 7v11c0 1.1.9 2 2 2h4c1.1 0 2-.9 2-2V7" />
										<path d="M10 11v5" />
										<path d="M14 11v5" />
									</svg>
									<span>Verwijderen</span>
								</button>
							</form>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
			</article>
		</section>

		<section id="gallery-library" class="maatlas-admin-section-panel<?= $activePanel !== 'gallery-library' ? ' maatlas-admin-section-hidden' : ''; ?>">
			<article class="maatlas-admin-card">
				<div class="maatlas-admin-library-header">
					<div>
						<p class="maatlas-admin-eyebrow">Afbeeldingen</p>
						<h2>Afbeeldingen</h2>
					</div>
					<form method="get" class="maatlas-admin-filter-form" id="maatlas-admin-library-filter-form">
						<input type="hidden" name="panel" value="gallery-library">
						<label>
							<span>Album</span>
							<select name="category">
								<option value="">Alle albums</option>
								<?php foreach ($categories as $category): ?>
									<option value="<?= maatlas_admin_h((string) $category['id']); ?>"<?= $filterCategory === (string) $category['id'] ? ' selected' : ''; ?>>
										<?= maatlas_admin_h((string) $category['name']); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</label>
						<button type="submit" class="maatlas-admin-button maatlas-admin-button-secondary">Filteren</button>
					</form>
				</div>

				<?php if ($filteredMedia === []): ?>
				<p class="maatlas-admin-help">Er zijn nog geen afbeeldingen in de gekozen selectie.</p>
				<?php else: ?>
				<div class="maatlas-admin-media-grid">
					<?php foreach ($filteredMedia as $media): ?>
					<?php
					$mediaAbsolutePath = MAATLAS_GALLERY_UPLOADS_DIR . '/' . (string) ($media['relative_path'] ?? '');
					$mediaImageInfo = is_file($mediaAbsolutePath) ? @getimagesize($mediaAbsolutePath) : false;
					$mediaWidth = is_array($mediaImageInfo) ? (int) ($mediaImageInfo[0] ?? 0) : 0;
					$mediaHeight = is_array($mediaImageInfo) ? (int) ($mediaImageInfo[1] ?? 0) : 0;
					$mediaFilesizeBytes = (int) ($media['filesize'] ?? 0);
					$mediaFilesizeMb = $mediaFilesizeBytes > 0 ? number_format($mediaFilesizeBytes / 1048576, 2, ',', '.') : '0,00';
					$mediaLatitude = trim((string) ($media['latitude'] ?? ''));
					$mediaLongitude = trim((string) ($media['longitude'] ?? ''));
					$mediaMapsUrl = trim((string) ($media['google_maps_url'] ?? ''));
					?>
					<article class="maatlas-admin-media-card">
						<div class="maatlas-admin-media-side">
							<div class="maatlas-admin-media-thumb">
								<img src="<?= maatlas_admin_h((string) $media['url']); ?>" alt="<?= maatlas_admin_h((string) $media['filename']); ?>">
							</div>
							<div class="maatlas-admin-media-meta">
								<span><?= maatlas_admin_h($mediaFilesizeMb); ?> MB</span>
								<?php if ($mediaWidth > 0 && $mediaHeight > 0): ?>
								<span><?= maatlas_admin_h((string) $mediaWidth); ?> × <?= maatlas_admin_h((string) $mediaHeight); ?> px</span>
								<?php endif; ?>
								<?php if ($mediaLatitude !== '' && $mediaLongitude !== ''): ?>
								<span>GPS: <?= maatlas_admin_h($mediaLatitude); ?>, <?= maatlas_admin_h($mediaLongitude); ?></span>
								<?php endif; ?>
								<?php if ($mediaMapsUrl !== ''): ?>
								<a href="<?= maatlas_admin_h($mediaMapsUrl); ?>" target="_blank" rel="noopener">Open in Google Maps</a>
								<?php endif; ?>
							</div>
							<form method="post" enctype="multipart/form-data" class="maatlas-admin-form maatlas-admin-media-replace-form">
								<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
								<input type="hidden" name="action" value="replace_media">
								<input type="hidden" name="return_panel" value="gallery-library">
								<input type="hidden" name="relative_path" value="<?= maatlas_admin_h((string) $media['relative_path']); ?>">
								<label>
									<span>Vervang door bestand van pc</span>
									<input type="file" name="replacement_file" accept="image/*" required>
								</label>
								<div class="maatlas-admin-actions">
									<button type="submit" class="maatlas-admin-button maatlas-admin-button-secondary">Vervang afbeelding</button>
								</div>
							</form>
						</div>
						<div class="maatlas-admin-media-body">
							<?php $mediaFormId = 'media-save-' . md5((string) $media['relative_path']); ?>
							<div class="maatlas-admin-media-topbar">
								<button type="submit" form="<?= maatlas_admin_h($mediaFormId); ?>" class="maatlas-admin-button maatlas-admin-button-secondary">Opslaan voor deze foto</button>
								<form method="post" class="maatlas-admin-media-delete-form" onsubmit="return confirm('Deze afbeelding verwijderen?');">
									<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
									<input type="hidden" name="action" value="delete_media">
									<input type="hidden" name="return_panel" value="gallery-library">
									<input type="hidden" name="relative_path" value="<?= maatlas_admin_h((string) $media['relative_path']); ?>">
									<button type="submit" class="maatlas-admin-media-delete-button" aria-label="Afbeelding verwijderen" title="Afbeelding verwijderen">
										<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
											<path d="M4 7h16" />
											<path d="M9 3h6l1 2h4" />
											<path d="M8 7v11c0 1.1.9 2 2 2h4c1.1 0 2-.9 2-2V7" />
											<path d="M10 11v5" />
											<path d="M14 11v5" />
										</svg>
									</button>
								</form>
							</div>
							<strong><?= maatlas_admin_h((string) $media['filename']); ?></strong>
							<p>Opslag: assets/uploads</p>
							<div class="maatlas-admin-usage-list">
								<span class="maatlas-admin-usage-title">Publieke pagina&apos;s</span>
								<?php $usageEntries = $publicUsageMap[(string) ($media['relative_path'] ?? '')] ?? []; ?>
								<?php $hasFixedUsage = false; ?>
								<?php $hasPotentialUsage = false; ?>
								<?php foreach ($usageEntries as $usageEntry): ?>
									<?php if (!empty($usageEntry['is_potential'])): ?>
										<?php $hasPotentialUsage = true; ?>
									<?php else: ?>
										<?php $hasFixedUsage = true; ?>
									<?php endif; ?>
								<?php endforeach; ?>
								<?php if ($usageEntries === []): ?>
								<p class="maatlas-admin-usage-empty">Nog niet gekoppeld aan een publieke pagina.</p>
								<?php else: ?>
								<div class="maatlas-admin-usage-summary">
									<?php if ($hasFixedUsage): ?>
									<span class="maatlas-admin-usage-badge maatlas-admin-usage-badge-linked">Vast gelinkt</span>
									<?php endif; ?>
									<?php if ($hasPotentialUsage && !$hasFixedUsage): ?>
									<span class="maatlas-admin-usage-badge maatlas-admin-usage-badge-random">Alleen random mogelijk</span>
									<?php elseif ($hasPotentialUsage): ?>
									<span class="maatlas-admin-usage-badge maatlas-admin-usage-badge-random">Ook random mogelijk</span>
									<?php endif; ?>
								</div>
								<ul>
									<?php foreach ($usageEntries as $usageEntry): ?>
									<li>
										<a href="<?= maatlas_admin_h((string) $usageEntry['href']); ?>" target="_blank" rel="noopener">
											<?= maatlas_admin_h((string) $usageEntry['label']); ?>
										</a>
										<?php if (!empty($usageEntry['is_potential'])): ?>
										<span class="maatlas-admin-usage-badge maatlas-admin-usage-badge-random">random</span>
										<?php else: ?>
										<span class="maatlas-admin-usage-badge maatlas-admin-usage-badge-linked">vast</span>
										<?php endif; ?>
									</li>
									<?php endforeach; ?>
								</ul>
								<?php endif; ?>
							</div>
							<div class="maatlas-admin-tags">
								<?php if ((array) ($media['category_ids'] ?? []) === []): ?>
								<span class="maatlas-admin-tag maatlas-admin-tag-muted">Geen album</span>
								<?php else: ?>
									<?php foreach ((array) $media['category_ids'] as $categoryId): ?>
										<?php if (!isset($categoryMap[(string) $categoryId])) { continue; } ?>
										<span class="maatlas-admin-tag"><?= maatlas_admin_h((string) $categoryMap[(string) $categoryId]['name']); ?></span>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
							<form method="post" class="maatlas-admin-form maatlas-admin-media-form" id="<?= maatlas_admin_h($mediaFormId); ?>">
								<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
								<input type="hidden" name="action" value="update_media_categories">
								<input type="hidden" name="return_panel" value="gallery-library">
								<input type="hidden" name="relative_path" value="<?= maatlas_admin_h((string) $media['relative_path']); ?>">
								<div class="maatlas-admin-check-grid">
									<?php foreach ($categories as $category): ?>
									<label class="maatlas-admin-check-option">
										<input
											type="checkbox"
											name="category_ids[]"
											value="<?= maatlas_admin_h((string) $category['id']); ?>"
											<?= in_array((string) $category['id'], array_map('strval', (array) ($media['category_ids'] ?? [])), true) ? ' checked' : ''; ?>
										>
										<span><?= maatlas_admin_h((string) $category['name']); ?></span>
									</label>
									<?php endforeach; ?>
								</div>
							</form>
						</div>
					</article>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</article>
		</section>
	</div>
</section>

<script>
(function () {
  var storageKey = 'maatlasAdminGalleryScrollY';

  try {
    var savedScrollY = sessionStorage.getItem(storageKey);
    if (savedScrollY !== null) {
      window.requestAnimationFrame(function () {
        window.scrollTo(0, parseInt(savedScrollY, 10) || 0);
        sessionStorage.removeItem(storageKey);
      });
    }
  } catch (error) {
  }

  Array.prototype.forEach.call(document.querySelectorAll('form[method="post"]'), function (form) {
    form.addEventListener('submit', function () {
      try {
        sessionStorage.setItem(storageKey, String(window.scrollY || window.pageYOffset || 0));
      } catch (error) {
      }
    });
  });
})();

(function () {
  var filterForm = document.getElementById('maatlas-admin-library-filter-form');
  if (filterForm) {
    Array.prototype.forEach.call(filterForm.querySelectorAll('select'), function (select) {
      select.addEventListener('change', function () {
        filterForm.submit();
      });
    });
  }
})();

(function () {
  var input = document.getElementById('maatlas-gallery-file-input');
  var preview = document.getElementById('maatlas-gallery-preview');
  var rotationsField = document.getElementById('maatlas-gallery-rotations');
  if (!input || !preview || !rotationsField) {
    return;
  }

  var rotations = [];

  function syncRotations() {
    rotationsField.value = JSON.stringify(rotations);
  }

  function renderPreview() {
    preview.innerHTML = '';

    if (!input.files || input.files.length === 0) {
      preview.innerHTML = '<p class="maatlas-admin-help">Na selectie krijg je hier een preview en rotatieknoppen per foto.</p>';
      syncRotations();
      return;
    }

    Array.prototype.forEach.call(input.files, function (file, index) {
      if (typeof rotations[index] === 'undefined') {
        rotations[index] = 0;
      }

      var card = document.createElement('div');
      card.className = 'maatlas-gallery-preview-card';

      var image = document.createElement('img');
      image.src = URL.createObjectURL(file);
      image.alt = file.name;
      image.style.transform = 'rotate(' + rotations[index] + 'deg)';

      var name = document.createElement('strong');
      name.textContent = file.name;

      var controls = document.createElement('div');
      controls.className = 'maatlas-gallery-preview-actions';

      var leftButton = document.createElement('button');
      leftButton.type = 'button';
      leftButton.textContent = 'Links draaien';
      leftButton.addEventListener('click', function () {
        rotations[index] = (rotations[index] + 270) % 360;
        image.style.transform = 'rotate(' + rotations[index] + 'deg)';
        syncRotations();
      });

      var rightButton = document.createElement('button');
      rightButton.type = 'button';
      rightButton.textContent = 'Rechts draaien';
      rightButton.addEventListener('click', function () {
        rotations[index] = (rotations[index] + 90) % 360;
        image.style.transform = 'rotate(' + rotations[index] + 'deg)';
        syncRotations();
      });

      controls.appendChild(leftButton);
      controls.appendChild(rightButton);

      card.appendChild(image);
      card.appendChild(name);
      card.appendChild(controls);
      preview.appendChild(card);
    });

    syncRotations();
  }

  input.addEventListener('change', function () {
    rotations = [];
    renderPreview();
  });
})();

(function () {
  var navButtons = document.querySelectorAll('[data-admin-panel-target]');
  var panels = document.querySelectorAll('.maatlas-admin-section-panel');
  if (!navButtons.length || !panels.length) {
    return;
  }

  function activatePanel(targetId) {
    panels.forEach(function (panel) {
      panel.classList.toggle('maatlas-admin-section-hidden', panel.id !== targetId);
    });

    navButtons.forEach(function (button) {
      button.classList.toggle('is-current', button.getAttribute('data-admin-panel-target') === targetId);
    });
  }

  activatePanel(<?= json_encode($activePanel, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);

  navButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      var targetId = button.getAttribute('data-admin-panel-target');
      if (targetId) {
        activatePanel(targetId);
      }
    });
  });
})();
</script>
<?php
maatlas_admin_render_footer();
