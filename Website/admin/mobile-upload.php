<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

maatlas_admin_require_login();
$settings = maatlas_site_settings_load();
$categories = maatlas_gallery_load_categories();
$message = null;
$error = null;
$uploadedMedia = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = (string) ($_POST['csrf_token'] ?? '');

	if (!maatlas_admin_verify_csrf($token)) {
		$error = 'Ongeldige beveiligingstoken. Herlaad de pagina en probeer opnieuw.';
	} else {
		try {
			$categoryId = trim((string) ($_POST['category_id'] ?? ''));
			$title = trim((string) ($_POST['title'] ?? ''));
			$latitude = (float) ($_POST['latitude'] ?? 0);
			$longitude = (float) ($_POST['longitude'] ?? 0);

			if ($categoryId === '') {
				throw new RuntimeException('Kies eerst een album.');
			}

			if (!isset($_FILES['photo'])) {
				throw new RuntimeException('Neem of kies eerst een foto.');
			}

			if (empty($_POST['latitude']) || empty($_POST['longitude'])) {
				throw new RuntimeException('Sta locatietoegang toe op je toestel zodat de Google Maps-info mee opgeslagen wordt.');
			}

			$uploadedMedia = maatlas_gallery_mobile_upload_photo($categoryId, $_FILES['photo'], $title, $latitude, $longitude);
			$message = 'Foto geupload en gekoppeld aan het gekozen album.';
		} catch (RuntimeException $exception) {
			$error = $exception->getMessage();
		}
	}
}
?>
<!DOCTYPE html>
<html lang="nl-BE">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Snelle upload | Admin | W&amp;S Maatlaswerk</title>
	<link rel="stylesheet" href="<?= maatlas_admin_h(maatlas_admin_url('/admin/style.css?v=20260331-1')); ?>">
	<?php maatlas_site_render_theme_style($settings); ?>
	<link rel="manifest" href="<?= maatlas_admin_h(maatlas_admin_url('/admin/mobile-upload.webmanifest.php?v=20260421-1')); ?>">
	<meta name="theme-color" content="#B0CD56">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<meta name="apple-mobile-web-app-title" content="Maatlas Upload">
	<link rel="apple-touch-icon" href="<?= maatlas_admin_h(maatlas_admin_url('/assets/uploads/static/MaatLasWerk-13.jpg')); ?>">
</head>
<body class="maatlas-mobile-upload-page">
	<div class="maatlas-mobile-simple-shell">
		<div class="maatlas-mobile-simple-topbar">
			<a href="<?= maatlas_admin_h(maatlas_admin_url('/admin/logout.php')); ?>">Afmelden</a>
		</div>

		<section class="maatlas-mobile-simple-card">
			<div class="maatlas-mobile-simple-logo">
				<img src="<?= maatlas_admin_h(maatlas_admin_url('/assets/uploads/static/MaatLasWerk-13.jpg')); ?>" alt="W&amp;S Maatlaswerk logo">
			</div>
			<p class="maatlas-admin-eyebrow">Snelle upload</p>
			<h1 class="maatlas-mobile-simple-title">Foto uploaden</h1>
			<p class="maatlas-mobile-simple-copy">Neem een foto, kies een album en upload meteen naar de website. De foto wordt automatisch gecomprimeerd tot maximaal 500 KB en krijgt ook Google Maps-locatiegegevens mee.</p>

			<?php if ($message !== null): ?>
			<p class="maatlas-admin-alert maatlas-admin-alert-success"><?= maatlas_admin_h($message); ?></p>
			<?php endif; ?>
			<?php if ($error !== null): ?>
			<p class="maatlas-admin-alert maatlas-admin-alert-error"><?= maatlas_admin_h($error); ?></p>
			<?php endif; ?>

			<?php if ($categories === []): ?>
			<p class="maatlas-mobile-simple-copy">Er zijn nog geen albums beschikbaar. Maak eerst een album aan in het galerijbeheer.</p>
			<?php else: ?>
			<form method="post" enctype="multipart/form-data" class="maatlas-admin-form maatlas-mobile-upload-form" id="maatlas-mobile-upload-form">
				<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
				<input type="hidden" name="latitude" id="maatlas-mobile-latitude" value="">
				<input type="hidden" name="longitude" id="maatlas-mobile-longitude" value="">

				<label>
					<span>Album</span>
					<select name="category_id" required>
						<option value="">Kies een album</option>
						<?php foreach ($categories as $category): ?>
						<option value="<?= maatlas_admin_h((string) ($category['id'] ?? '')); ?>"><?= maatlas_admin_h((string) ($category['name'] ?? '')); ?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<label>
					<span>Titel</span>
					<input type="text" name="title" placeholder="Titel">
				</label>

				<div class="maatlas-mobile-upload-picker" id="maatlas-mobile-upload-picker">
					<label class="maatlas-mobile-upload-picker-button" for="maatlas-mobile-photo-input" aria-label="Foto nemen of kiezen" title="Foto nemen of kiezen">
						<span class="maatlas-mobile-upload-picker-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" focusable="false">
								<path d="M5 8h3l1.4-2h5.2L16 8h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2Z"></path>
								<circle cx="12" cy="13" r="3.5"></circle>
							</svg>
						</span>
					</label>
					<input class="maatlas-mobile-upload-input" type="file" name="photo" id="maatlas-mobile-photo-input" accept="image/*" capture="environment" required aria-label="Foto nemen of kiezen">
				</div>

				<div class="maatlas-mobile-upload-preview is-empty" id="maatlas-mobile-upload-preview"></div>

				<div class="maatlas-mobile-upload-status" id="maatlas-mobile-location-status">
					Locatie wordt gecontroleerd...
				</div>

				<div class="maatlas-admin-actions">
					<button type="submit" class="maatlas-admin-button">Uploaden</button>
				</div>
			</form>
			<?php endif; ?>

			<?php if ($uploadedMedia !== null): ?>
			<div class="maatlas-mobile-upload-result">
				<strong>Laatste upload</strong>
				<p><?= maatlas_admin_h((string) ($uploadedMedia['filename'] ?? '')); ?></p>
				<?php if (!empty($uploadedMedia['title'])): ?>
				<p><?= maatlas_admin_h((string) $uploadedMedia['title']); ?></p>
				<?php endif; ?>
				<p><a href="<?= maatlas_admin_h((string) ($uploadedMedia['url'] ?? '#')); ?>" target="_blank" rel="noopener noreferrer">Open afbeelding</a></p>
				<p><a href="<?= maatlas_admin_h((string) ($uploadedMedia['google_maps_url'] ?? '#')); ?>" target="_blank" rel="noopener noreferrer">Open Google Maps</a></p>
			</div>
			<?php endif; ?>
		</section>
	</div>

<script>
(function () {
  var fileInput = document.getElementById('maatlas-mobile-photo-input');
  var preview = document.getElementById('maatlas-mobile-upload-preview');
  var picker = document.getElementById('maatlas-mobile-upload-picker');
  var latitudeField = document.getElementById('maatlas-mobile-latitude');
  var longitudeField = document.getElementById('maatlas-mobile-longitude');
  var locationStatus = document.getElementById('maatlas-mobile-location-status');
  var reverseLookupBase = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=18&addressdetails=1';

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function renderLocationState(title, latitude, longitude, addressLine, toneClass) {
    if (!locationStatus) {
      return;
    }

    var classes = ['maatlas-mobile-upload-status'];
    if (toneClass) {
      classes.push(toneClass);
    }

    var coordinatesMarkup = '';
    if (latitude && longitude) {
      coordinatesMarkup = '<div class="maatlas-mobile-upload-location-line"><strong>GPS:</strong> ' + escapeHtml(latitude) + ', ' + escapeHtml(longitude) + '</div>';
    }

    var addressMarkup = '';
    if (addressLine) {
      addressMarkup = '<div class="maatlas-mobile-upload-location-line"><strong>Adres:</strong> ' + escapeHtml(addressLine) + '</div>';
    }

    locationStatus.className = classes.join(' ');
    locationStatus.innerHTML = '<div class="maatlas-mobile-upload-location-title">' + escapeHtml(title) + '</div>' + coordinatesMarkup + addressMarkup;
  }

  function buildAddressLine(data) {
    if (!data || !data.address) {
      return '';
    }

    var address = data.address;
    var street = address.road || address.pedestrian || address.footway || address.cycleway || address.path || '';
    var number = address.house_number || '';
    var city = address.city || address.town || address.village || address.municipality || address.hamlet || '';
    var parts = [];

    if (street || number) {
      parts.push((street + ' ' + number).trim());
    }
    if (city) {
      parts.push(city);
    }

    return parts.join(', ');
  }

  function fetchAddress(latitude, longitude) {
    var url = reverseLookupBase + '&lat=' + encodeURIComponent(latitude) + '&lon=' + encodeURIComponent(longitude);

    fetch(url, {
      headers: {
        'Accept': 'application/json'
      }
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('lookup_failed');
      }
      return response.json();
    }).then(function (data) {
      var addressLine = buildAddressLine(data);
      if (addressLine) {
        renderLocationState('Locatie klaar voor upload.', latitude, longitude, addressLine, 'is-success');
      } else {
        renderLocationState('Locatie klaar voor upload.', latitude, longitude, '', 'is-success');
      }
    }).catch(function () {
      renderLocationState('Locatie klaar voor upload.', latitude, longitude, 'Adres kon niet automatisch bepaald worden.', 'is-success');
    });
  }

  if (fileInput && preview) {
    fileInput.addEventListener('change', function () {
      var file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
      if (!file) {
        preview.classList.add('is-empty');
        preview.innerHTML = '';
        if (picker) {
          picker.classList.remove('is-hidden');
        }
        return;
      }

      var imageUrl = URL.createObjectURL(file);
      preview.classList.remove('is-empty');
      if (picker) {
        picker.classList.add('is-hidden');
      }
      preview.innerHTML = '<img src="' + imageUrl + '" alt="Preview van de gekozen foto">';
    });
  }

  if (!locationStatus) {
    return;
  }

  renderLocationState('Wacht op locatietoestemming...', '', '', '', '');

  if (navigator.permissions && navigator.permissions.query) {
    navigator.permissions.query({ name: 'geolocation' }).then(function (result) {
      if (result.state === 'denied') {
        renderLocationState('Locatietoegang staat uit. Zet GPS-toegang aan om coördinaten en adres mee op te slaan.', '', '', '', 'is-error');
      }
    }).catch(function () {
      return null;
    });
  }

  if (!navigator.geolocation) {
    renderLocationState('Locatie is niet beschikbaar op dit toestel of in deze browser.', '', '', '', 'is-error');
    return;
  }

  navigator.geolocation.getCurrentPosition(function (position) {
    var latitude = position && position.coords ? Number(position.coords.latitude || 0).toFixed(6) : '';
    var longitude = position && position.coords ? Number(position.coords.longitude || 0).toFixed(6) : '';

    latitudeField.value = latitude;
    longitudeField.value = longitude;
    renderLocationState('Locatie gevonden. Adres wordt opgezocht...', latitude, longitude, '', '');
    fetchAddress(latitude, longitude);
  }, function () {
    renderLocationState('Sta locatietoegang toe zodat de Google Maps-info mee opgeslagen wordt.', '', '', '', 'is-error');
  }, {
    enableHighAccuracy: true,
    timeout: 12000,
    maximumAge: 30000
  });

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= maatlas_admin_h(maatlas_admin_url('/admin/mobile-upload-sw.js')); ?>').catch(function () {
      return null;
    });
  }
})();
</script>
</body>
</html>
