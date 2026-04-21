<?php

const MAATLAS_SITE_SETTINGS_STORAGE = __DIR__ . '/../admin/storage/site_settings.php';

function maatlas_site_base_path()
{
	static $basePath = null;
	if ($basePath !== null) {
		return $basePath;
	}

	$documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
	$siteRoot = realpath(__DIR__ . '/..');

	if ($documentRoot !== false && $siteRoot !== false) {
		$documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
		$siteRoot = rtrim(str_replace('\\', '/', $siteRoot), '/');

		if (
			$documentRoot !== ''
			&& ($siteRoot === $documentRoot || strncmp($siteRoot, $documentRoot . '/', strlen($documentRoot) + 1) === 0)
		) {
			$relative = trim(substr($siteRoot, strlen($documentRoot)), '/');
			$basePath = $relative !== '' ? '/' . $relative : '';
			return $basePath;
		}
	}

	$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
	$scriptPath = '/' . trim(str_replace('\\', '/', $scriptName), '/');
	$knownRoutePrefixes = [
		'/admin',
		'/about',
		'/services',
		'/contact',
		'/privacy',
		'/cookies',
		'/includes',
	];

	foreach ($knownRoutePrefixes as $prefix) {
		$position = strpos($scriptPath, $prefix . '/');
		if ($position !== false) {
			$basePath = rtrim(substr($scriptPath, 0, $position), '/');
			return $basePath === '/' ? '' : $basePath;
		}
	}

	$directory = trim((string) dirname($scriptPath), '/');
	$basePath = $directory !== '' ? '/' . $directory : '';
	return $basePath;
}

function maatlas_site_url($path = '/')
{
	$path = (string) $path;
	if ($path === '') {
		$path = '/';
	}

	if (preg_match('#^(?:[a-z][a-z0-9+.-]*:|//|#)#i', $path) === 1) {
		return $path;
	}

	$basePath = maatlas_site_base_path();
	$path = '/' . ltrim($path, '/');

	if ($path === '/') {
		return $basePath !== '' ? $basePath . '/' : '/';
	}

	return $basePath . $path;
}

function maatlas_site_request_path()
{
	$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
	$requestPath = '/' . ltrim($requestPath, '/');
	$basePath = maatlas_site_base_path();

	if ($basePath !== '' && ($requestPath === $basePath || strncmp($requestPath, $basePath . '/', strlen($basePath) + 1) === 0)) {
		$requestPath = substr($requestPath, strlen($basePath));
		$requestPath = $requestPath === '' ? '/' : $requestPath;
	}

	return '/' . ltrim($requestPath, '/');
}

function maatlas_site_settings_default()
{
	return [
		'accent_color' => '#B0CD56',
		'back_to_top_enabled' => true,
		'back_to_top_position' => 'bottom-right',
		'back_to_top_margin_x' => '24',
		'back_to_top_margin_y' => '24',
		'vat_number' => '',
		'contact_form_live' => true,
		'contact_test_email' => '',
		'contact_recipient_email' => 'info@maatlaswerk.be',
		'contact_sender_email' => 'info@maatlaswerk.be',
		'public_contact_email' => 'info@maatlaswerk.be',
		'public_phone' => '',
		'public_address' => "W&S Maatlaswerk\nKluisbergen",
		'facebook_url' => 'https://www.facebook.com/p/WS-Maatlaswerk-100054239330706/',
		'instagram_url' => 'https://www.instagram.com/w_s_maatlaswerk/',
		'google_maps_address' => 'Kluisbergen, Belgium',
		'google_maps_embed_url' => 'https://www.google.com/maps?q=Kluisbergen%2C%20Belgium&z=12&output=embed',
		'privacy_contact_email' => 'info@maatlaswerk.be',
		'privacy_retention_months' => '12',
	];
}

function maatlas_site_settings_export(array $data)
{
	return "<?php\nreturn " . var_export($data, true) . ";\n";
}

function maatlas_site_settings_ensure_storage()
{
	$storageDir = dirname(MAATLAS_SITE_SETTINGS_STORAGE);
	if (!is_dir($storageDir)) {
		mkdir($storageDir, 0775, true);
	}

	if (!is_file(MAATLAS_SITE_SETTINGS_STORAGE)) {
		file_put_contents(MAATLAS_SITE_SETTINGS_STORAGE, maatlas_site_settings_export(maatlas_site_settings_default()), LOCK_EX);
	}
}

function maatlas_site_settings_load()
{
	maatlas_site_settings_ensure_storage();
	$stored = require MAATLAS_SITE_SETTINGS_STORAGE;
	if (!is_array($stored)) {
		$stored = [];
	}

	return array_merge(maatlas_site_settings_default(), $stored);
}

function maatlas_site_settings_save(array $settings)
{
	$merged = array_merge(maatlas_site_settings_default(), $settings);
	file_put_contents(MAATLAS_SITE_SETTINGS_STORAGE, maatlas_site_settings_export($merged), LOCK_EX);
}

function maatlas_site_setting(string $key, $default = '')
{
	$settings = maatlas_site_settings_load();
	return $settings[$key] ?? $default;
}

function maatlas_site_sanitize_hex_color($color, $fallback = '#B0CD56')
{
	$color = trim($color);
	if (preg_match('/^#([a-f0-9]{6})$/i', $color) === 1) {
		return strtoupper($color);
	}

	return strtoupper($fallback);
}

function maatlas_site_accent_color($settings = null)
{
	if ($settings === null) {
		$settings = maatlas_site_settings_load();
	}
	return maatlas_site_sanitize_hex_color((string) ($settings['accent_color'] ?? '#B0CD56'));
}

function maatlas_site_google_maps_embed_url($settings = null)
{
	if ($settings === null) {
		$settings = maatlas_site_settings_load();
	}
	$address = trim((string) ($settings['google_maps_address'] ?? ''));
	if ($address !== '') {
		return 'https://www.google.com/maps?q=' . rawurlencode($address) . '&z=12&output=embed';
	}

	$fallbackUrl = trim((string) ($settings['google_maps_embed_url'] ?? ''));
	if ($fallbackUrl !== '') {
		return $fallbackUrl;
	}

	return 'https://www.google.com/maps?q=Kluisbergen%2C%20Belgium&z=12&output=embed';
}

function maatlas_site_render_theme_style($settings = null)
{
	$accent = maatlas_site_accent_color($settings);
	echo '<style>:root{--maatlas-brand-accent:' . htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') . ';}</style>';
}

function maatlas_site_render_public_runtime_settings($settings = null)
{
	if ($settings === null) {
		$settings = maatlas_site_settings_load();
	}
	$defaults = maatlas_site_settings_default();
	$payload = [
		'vatNumber' => trim((string) ($settings['vat_number'] ?? '')),
		'facebookUrl' => trim((string) ($settings['facebook_url'] ?? '')) !== ''
			? trim((string) ($settings['facebook_url'] ?? ''))
			: (string) ($defaults['facebook_url'] ?? ''),
		'instagramUrl' => trim((string) ($settings['instagram_url'] ?? '')) !== ''
			? trim((string) ($settings['instagram_url'] ?? ''))
			: (string) ($defaults['instagram_url'] ?? ''),
		'basePath' => maatlas_site_base_path(),
		'backToTopEnabled' => !empty($settings['back_to_top_enabled']),
		'backToTopPosition' => (string) ($settings['back_to_top_position'] ?? 'bottom-right'),
		'backToTopMarginX' => max(8, min(240, (int) ($settings['back_to_top_margin_x'] ?? ($settings['back_to_top_offset'] ?? 24)))),
		'backToTopMarginY' => max(8, min(240, (int) ($settings['back_to_top_margin_y'] ?? ($settings['back_to_top_bottom'] ?? 24)))),
	];

	echo '<script>window.maatlasSiteSettings=' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>';
}
