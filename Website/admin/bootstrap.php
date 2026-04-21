<?php
declare(strict_types=1);

$sessionSavePath = (string) ini_get('session.save_path');
$sessionPathParts = $sessionSavePath !== '' ? explode(';', $sessionSavePath) : [];
$resolvedSessionPath = $sessionPathParts !== [] ? end($sessionPathParts) : '';

if (!is_string($resolvedSessionPath) || $resolvedSessionPath === '' || !is_dir($resolvedSessionPath) || !is_writable($resolvedSessionPath)) {
	$fallbackSessionPath = __DIR__ . '/storage/sessions';
	if (!is_dir($fallbackSessionPath)) {
		mkdir($fallbackSessionPath, 0775, true);
	}
	session_save_path($fallbackSessionPath);
}

session_start();

require_once __DIR__ . '/../includes/site-settings.php';

const MAATLAS_ADMIN_STORAGE = __DIR__ . '/storage/admins.php';
const MAATLAS_GALLERY_CATEGORIES_STORAGE = __DIR__ . '/storage/gallery_categories.php';
const MAATLAS_GALLERY_MEDIA_STORAGE = __DIR__ . '/storage/gallery_media.php';
const MAATLAS_LASTENBOEK_STORAGE = __DIR__ . '/storage/lastenboek.php';
const MAATLAS_GALLERY_UPLOADS_DIR = __DIR__ . '/../assets/uploads';
const MAATLAS_ADMIN_TEMPORARY_USERNAME = 'admin';
const MAATLAS_ADMIN_TEMPORARY_PASSWORD = 'admin';

function maatlas_admin_storage_default(): array
{
	return [
		[
			'id' => 'admin-1',
			'username' => MAATLAS_ADMIN_TEMPORARY_USERNAME,
			'full_name' => 'Tijdelijke Admin',
			'email' => 'admin@maatlaswerk.be',
			'role' => 'superadmin',
			'active' => true,
			'password_hash' => password_hash(MAATLAS_ADMIN_TEMPORARY_PASSWORD, PASSWORD_DEFAULT),
			'is_temporary' => true,
			'activation_token_hash' => null,
			'activation_expires_at' => null,
			'activated_at' => null,
			'created_at' => date('c'),
			'updated_at' => date('c'),
			'last_login_at' => null,
		],
	];
}

function maatlas_gallery_categories_default(): array
{
	return [];
}

function maatlas_gallery_media_default(): array
{
	return [];
}

function maatlas_lastenboek_default(): array
{
	return [
		'meta' => [
			'document_title' => 'Technisch lastenboek website W&S Maatlaswerk',
			'project_name' => '',
			'client_name' => '',
			'reference' => '',
			'version' => '1.0',
			'introduction' => 'Technische documentatie van de website, met focus op opbouw, beheer, publicatie, galerij, formulieren en adminfuncties.',
		],
		'items' => [],
	];
}

function maatlas_admin_php_array_export(array $data): string
{
	return "<?php\nreturn " . var_export(array_values($data), true) . ";\n";
}

function maatlas_admin_ensure_storage_file(string $path, array $default): void
{
	$storageDir = dirname($path);
	if (!is_dir($storageDir)) {
		mkdir($storageDir, 0775, true);
	}

	if (!is_file($path)) {
		file_put_contents($path, maatlas_admin_php_array_export($default), LOCK_EX);
	}
}

function maatlas_admin_ensure_storage(): void
{
	maatlas_admin_ensure_storage_file(MAATLAS_ADMIN_STORAGE, maatlas_admin_storage_default());
	maatlas_admin_ensure_storage_file(MAATLAS_GALLERY_CATEGORIES_STORAGE, maatlas_gallery_categories_default());
	maatlas_admin_ensure_storage_file(MAATLAS_GALLERY_MEDIA_STORAGE, maatlas_gallery_media_default());
	maatlas_admin_ensure_storage_file(MAATLAS_LASTENBOEK_STORAGE, [maatlas_lastenboek_default()]);
}

function maatlas_admin_load(): array
{
	maatlas_admin_ensure_storage();
	$admins = require MAATLAS_ADMIN_STORAGE;
	return is_array($admins) ? $admins : [];
}

function maatlas_admin_save(array $admins): void
{
	file_put_contents(MAATLAS_ADMIN_STORAGE, maatlas_admin_php_array_export($admins), LOCK_EX);
}

function maatlas_admin_real_count(): int
{
	$count = 0;
	foreach (maatlas_admin_load() as $admin) {
		if (empty($admin['is_temporary'])) {
			$count++;
		}
	}

	return $count;
}

function maatlas_admin_is_initial_setup_required(): bool
{
	return maatlas_admin_real_count() === 0;
}

function maatlas_admin_is_temporary(array $admin): bool
{
	return !empty($admin['is_temporary']);
}

function maatlas_admin_delete_temporary_accounts(): void
{
	$admins = array_values(array_filter(
		maatlas_admin_load(),
		static fn(array $admin): bool => empty($admin['is_temporary'])
	));
	maatlas_admin_save($admins);
}

function maatlas_admin_find_by_username(string $username): ?array
{
	foreach (maatlas_admin_load() as $admin) {
		if (strcasecmp((string) $admin['username'], $username) === 0) {
			return $admin;
		}
	}

	return null;
}

function maatlas_admin_find_by_id(string $id): ?array
{
	foreach (maatlas_admin_load() as $admin) {
		if ((string) $admin['id'] === $id) {
			return $admin;
		}
	}

	return null;
}

function maatlas_admin_find_by_activation_token(string $token): ?array
{
	if ($token === '') {
		return null;
	}

	$tokenHash = hash('sha256', $token);
	foreach (maatlas_admin_load() as $admin) {
		if (!empty($admin['activation_token_hash']) && hash_equals((string) $admin['activation_token_hash'], $tokenHash)) {
			return $admin;
		}
	}

	return null;
}

function maatlas_admin_update(array $updatedAdmin): void
{
	$admins = maatlas_admin_load();
	foreach ($admins as $index => $admin) {
		if ((string) $admin['id'] === (string) $updatedAdmin['id']) {
			$admins[$index] = $updatedAdmin;
			maatlas_admin_save($admins);
			return;
		}
	}
}

function maatlas_admin_create(array $data): void
{
	$admins = maatlas_admin_load();
	$admins[] = $data;
	maatlas_admin_save($admins);
}

function maatlas_admin_delete(string $id): void
{
	$admins = array_values(array_filter(
		maatlas_admin_load(),
		static fn(array $admin): bool => (string) $admin['id'] !== $id
	));
	maatlas_admin_save($admins);
}

function maatlas_admin_active_count(): int
{
	$count = 0;
	foreach (maatlas_admin_load() as $admin) {
		if (!empty($admin['active'])) {
			$count++;
		}
	}

	return $count;
}

function maatlas_admin_login(array $admin): void
{
	$_SESSION['maatlas_admin_id'] = $admin['id'];
}

function maatlas_admin_logout(): void
{
	$_SESSION = [];
	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
	}
	session_destroy();
}

function maatlas_admin_current(): ?array
{
	$adminId = $_SESSION['maatlas_admin_id'] ?? null;
	if (!is_string($adminId) || $adminId === '') {
		return null;
	}

	return maatlas_admin_find_by_id($adminId);
}

function maatlas_admin_require_login(): array
{
	$current = maatlas_admin_current();
	if ($current === null || empty($current['active'])) {
		header('Location: /admin/login.php');
		exit;
	}

	$currentPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
	$allowedSetupPaths = [
		'/admin/administrators.php',
		'/admin/logout.php',
	];
	if (
		maatlas_admin_is_initial_setup_required()
		&& maatlas_admin_is_temporary($current)
		&& !in_array($currentPath, $allowedSetupPaths, true)
	) {
		header('Location: /admin/administrators.php?setup=1');
		exit;
	}

	return $current;
}

function maatlas_admin_csrf_token(): string
{
	if (empty($_SESSION['maatlas_admin_csrf'])) {
		$_SESSION['maatlas_admin_csrf'] = bin2hex(random_bytes(16));
	}

	return $_SESSION['maatlas_admin_csrf'];
}

function maatlas_admin_verify_csrf(?string $token): bool
{
	$sessionToken = $_SESSION['maatlas_admin_csrf'] ?? '';
	return is_string($token) && $token !== '' && hash_equals($sessionToken, $token);
}

function maatlas_admin_h(?string $value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function maatlas_admin_is_activation_expired(array $admin): bool
{
	$expiresAt = trim((string) ($admin['activation_expires_at'] ?? ''));
	if ($expiresAt === '') {
		return true;
	}

	$expiresTimestamp = strtotime($expiresAt);
	return $expiresTimestamp === false || $expiresTimestamp < time();
}

function maatlas_admin_password_is_safe(string $password, string $username, int $minimumLength = 12): bool
{
	if (strlen($password) < $minimumLength) {
		return false;
	}

	$lowerPassword = strtolower($password);
	$lowerUsername = strtolower($username);
	return $lowerPassword !== MAATLAS_ADMIN_TEMPORARY_PASSWORD && $lowerPassword !== $lowerUsername;
}

function maatlas_admin_mail_sender(): string
{
	$settings = maatlas_site_settings_load();
	$candidates = [
		(string) ($settings['contact_sender_email'] ?? ''),
		(string) ($settings['public_contact_email'] ?? ''),
		(string) ($settings['privacy_contact_email'] ?? ''),
		'info@maatlaswerk.be',
	];

	foreach ($candidates as $candidate) {
		$candidate = trim($candidate);
		if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
			return $candidate;
		}
	}

	return 'info@maatlaswerk.be';
}

function maatlas_admin_send_mail(string $to, string $subject, array $bodyLines): bool
{
	if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
		return false;
	}

	$sender = maatlas_admin_mail_sender();
	$headers = [
		'MIME-Version: 1.0',
		'Content-Type: text/plain; charset=UTF-8',
		'From: W&S Maatlaswerk <' . $sender . '>',
		'Reply-To: ' . $sender,
	];

	return mail($to, preg_replace('/[\r\n]+/', ' ', $subject) ?? $subject, implode("\n", $bodyLines), implode("\r\n", $headers));
}

function maatlas_admin_send_account_created_mail(array $admin, array $createdBy): bool
{
	return maatlas_admin_send_mail(
		(string) $admin['email'],
		'Je beheerdersaccount voor W&S Maatlaswerk is aangemaakt',
		[
			'Hallo ' . (string) $admin['full_name'],
			'',
			'Er is een beheerdersaccount voor jou aangemaakt op de website van W&S Maatlaswerk.',
			'',
			'Gebruikersnaam: ' . (string) $admin['username'],
			'Status: actief',
			'Aangemaakt door: ' . (string) ($createdBy['full_name'] ?? $createdBy['username'] ?? 'beheerder'),
			'',
			'Je wachtwoord wordt niet per e-mail verstuurd. Vraag het wachtwoord rechtstreeks aan de beheerder die de account heeft aangemaakt.',
			'',
			'Login: ' . maatlas_admin_current_host_url('/admin/login.php'),
		]
	);
}

function maatlas_admin_send_activation_mail(array $admin, array $createdBy, string $activationUrl): bool
{
	return maatlas_admin_send_mail(
		(string) $admin['email'],
		'Activeer je beheerdersaccount voor W&S Maatlaswerk',
		[
			'Hallo ' . (string) $admin['full_name'],
			'',
			'Er is een beheerdersaccount voor jou aangemaakt op de website van W&S Maatlaswerk.',
			'',
			'Gebruikersnaam: ' . (string) $admin['username'],
			'Aangemaakt door: ' . (string) ($createdBy['full_name'] ?? $createdBy['username'] ?? 'beheerder'),
			'',
			'Activeer je account en kies zelf een wachtwoord via deze link:',
			$activationUrl,
			'',
			'Deze link vervalt op: ' . date('d/m/Y H:i', strtotime((string) ($admin['activation_expires_at'] ?? 'now'))),
		]
	);
}

function maatlas_gallery_slugify(string $value): string
{
	$value = trim($value);
	if ($value === '') {
		return '';
	}

	if (function_exists('iconv')) {
		$converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
		if (is_string($converted) && $converted !== '') {
			$value = $converted;
		}
	}

	$value = strtolower($value);
	$value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
	return trim($value, '-');
}

function maatlas_gallery_normalize_relative_path(string $path): string
{
	$path = trim(str_replace('\\', '/', $path), '/');
	if ($path === '') {
		return '';
	}

	$parts = array_values(array_filter(explode('/', $path), static fn(string $part): bool => $part !== ''));
	foreach ($parts as $part) {
		if ($part === '.' || $part === '..') {
			throw new RuntimeException('Ongeldig pad opgegeven.');
		}
	}

	return implode('/', $parts);
}

function maatlas_gallery_protected_roots(): array
{
	return ['static'];
}

function maatlas_gallery_is_protected_relative(string $relativePath): bool
{
	$relativePath = maatlas_gallery_normalize_relative_path($relativePath);
	if ($relativePath === '') {
		return false;
	}

	$firstSegment = explode('/', $relativePath)[0];
	return in_array($firstSegment, maatlas_gallery_protected_roots(), true);
}

function maatlas_gallery_absolute_path(string $relativePath = ''): string
{
	$relativePath = maatlas_gallery_normalize_relative_path($relativePath);
	return $relativePath === '' ? MAATLAS_GALLERY_UPLOADS_DIR : MAATLAS_GALLERY_UPLOADS_DIR . '/' . $relativePath;
}

function maatlas_gallery_relative_url(string $relativePath): string
{
	$relativePath = maatlas_gallery_normalize_relative_path($relativePath);
	$segments = array_map('rawurlencode', explode('/', $relativePath));
	return '/assets/uploads/' . implode('/', $segments);
}

function maatlas_gallery_load_categories(): array
{
	maatlas_admin_ensure_storage();
	$categories = require MAATLAS_GALLERY_CATEGORIES_STORAGE;
	if (!is_array($categories)) {
		return [];
	}

	foreach ($categories as $index => $category) {
		$categories[$index]['description'] = trim((string) ($category['description'] ?? ''));
	}

	return $categories;
}

function maatlas_gallery_save_categories(array $categories): void
{
	file_put_contents(MAATLAS_GALLERY_CATEGORIES_STORAGE, maatlas_admin_php_array_export($categories), LOCK_EX);
}

function maatlas_gallery_load_media_storage(): array
{
	maatlas_admin_ensure_storage();
	$media = require MAATLAS_GALLERY_MEDIA_STORAGE;
	return is_array($media) ? $media : [];
}

function maatlas_gallery_save_media_storage(array $media): void
{
	file_put_contents(MAATLAS_GALLERY_MEDIA_STORAGE, maatlas_admin_php_array_export($media), LOCK_EX);
}

function maatlas_gallery_resized_variant_meta(string $filename): array
{
	$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	$stem = strtolower((string) pathinfo($filename, PATHINFO_FILENAME));

	if (preg_match('/^(.*)-(\d+)x(\d+)$/', $stem, $matches) === 1) {
		return [
			'group_stem' => trim((string) $matches[1], '-'),
			'is_resized' => true,
			'width' => (int) $matches[2],
			'height' => (int) $matches[3],
			'area' => (int) $matches[2] * (int) $matches[3],
			'extension' => $extension,
		];
	}

	return [
		'group_stem' => $stem,
		'is_resized' => false,
		'width' => 0,
		'height' => 0,
		'area' => 0,
		'extension' => $extension,
	];
}

function maatlas_gallery_filter_preferred_media(array $files): array
{
	$grouped = [];

	foreach ($files as $relativePath => $fileInfo) {
		$directory = strtolower((string) ($fileInfo['directory'] ?? ''));
		$meta = maatlas_gallery_resized_variant_meta((string) ($fileInfo['filename'] ?? ''));
		$groupKey = $directory . '|' . $meta['group_stem'];

		$fileInfo['_variant_meta'] = $meta;
		$grouped[$groupKey][] = $fileInfo;
	}

	$preferred = [];
	foreach ($grouped as $items) {
		usort($items, static function (array $left, array $right): int {
			$leftMeta = (array) ($left['_variant_meta'] ?? []);
			$rightMeta = (array) ($right['_variant_meta'] ?? []);

			if (($leftMeta['is_resized'] ?? false) !== ($rightMeta['is_resized'] ?? false)) {
				return ($leftMeta['is_resized'] ?? false) ? 1 : -1;
			}

			$sizeCompare = ((int) ($right['filesize'] ?? 0)) <=> ((int) ($left['filesize'] ?? 0));
			if ($sizeCompare !== 0) {
				return $sizeCompare;
			}

			$areaCompare = ((int) ($rightMeta['area'] ?? 0)) <=> ((int) ($leftMeta['area'] ?? 0));
			if ($areaCompare !== 0) {
				return $areaCompare;
			}

			return strnatcasecmp((string) ($left['relative_path'] ?? ''), (string) ($right['relative_path'] ?? ''));
		});

		$winner = $items[0];
		unset($winner['_variant_meta']);
		$preferred[(string) $winner['relative_path']] = $winner;
	}

	ksort($preferred, SORT_NATURAL | SORT_FLAG_CASE);
	return $preferred;
}

function maatlas_gallery_create_category(string $name, string $description = ''): array
{
	$name = trim($name);
	$description = trim($description);
	if ($name === '') {
		throw new RuntimeException('Geef een naam op voor de categorie.');
	}

	$slug = maatlas_gallery_slugify($name);
	if ($slug === '') {
		throw new RuntimeException('De categorienaam is ongeldig.');
	}

	$categories = maatlas_gallery_load_categories();
	foreach ($categories as $category) {
		if ((string) $category['slug'] === $slug) {
			throw new RuntimeException('Deze categorie bestaat al.');
		}
	}

	$category = [
		'id' => 'cat-' . bin2hex(random_bytes(4)),
		'name' => $name,
		'slug' => $slug,
		'description' => $description,
		'created_at' => date('c'),
		'updated_at' => date('c'),
	];

	$categories[] = $category;
	maatlas_gallery_save_categories($categories);
	return $category;
}

function maatlas_gallery_update_category(string $id, string $name, string $description = ''): void
{
	$name = trim($name);
	$description = trim($description);
	if ($name === '') {
		throw new RuntimeException('Geef een naam op voor de categorie.');
	}

	$slug = maatlas_gallery_slugify($name);
	if ($slug === '') {
		throw new RuntimeException('De categorienaam is ongeldig.');
	}

	$categories = maatlas_gallery_load_categories();
	$found = false;

	foreach ($categories as $index => $category) {
		if ((string) $category['id'] !== $id && (string) $category['slug'] === $slug) {
			throw new RuntimeException('Deze categorienaam bestaat al.');
		}

		if ((string) $category['id'] === $id) {
			$categories[$index]['name'] = $name;
			$categories[$index]['slug'] = $slug;
			$categories[$index]['description'] = $description;
			$categories[$index]['updated_at'] = date('c');
			$found = true;
		}
	}

	if (!$found) {
		throw new RuntimeException('Categorie niet gevonden.');
	}

	maatlas_gallery_save_categories($categories);
}

function maatlas_gallery_delete_category(string $id): void
{
	$categories = array_values(array_filter(
		maatlas_gallery_load_categories(),
		static fn(array $category): bool => (string) $category['id'] !== $id
	));
	maatlas_gallery_save_categories($categories);

	$mediaItems = maatlas_gallery_load_media_storage();
	foreach ($mediaItems as $index => $media) {
		$mediaItems[$index]['category_ids'] = array_values(array_filter(
			(array) ($media['category_ids'] ?? []),
			static fn(string $categoryId): bool => $categoryId !== $id
		));
	}
	maatlas_gallery_save_media_storage($mediaItems);
}

function maatlas_gallery_scan_files(): array
{
	$result = [];

	if (!is_dir(MAATLAS_GALLERY_UPLOADS_DIR)) {
		return $result;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(MAATLAS_GALLERY_UPLOADS_DIR, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator as $file) {
		if (!$file instanceof SplFileInfo || !$file->isFile()) {
			continue;
		}

		$relative = maatlas_gallery_normalize_relative_path(substr(str_replace('\\', '/', $file->getPathname()), strlen(str_replace('\\', '/', MAATLAS_GALLERY_UPLOADS_DIR))));
		if ($relative === '' || maatlas_gallery_is_protected_relative($relative)) {
			continue;
		}

		$extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
		if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
			continue;
		}

		$directory = dirname($relative);
		$result[$relative] = [
			'relative_path' => $relative,
			'directory' => $directory === '.' ? '' : $directory,
			'filename' => basename($relative),
			'url' => maatlas_gallery_relative_url($relative),
			'filesize' => (int) $file->getSize(),
			'modified_at' => date('c', (int) $file->getMTime()),
		];
	}

	return maatlas_gallery_filter_preferred_media($result);
}

function maatlas_gallery_sync_media(): array
{
	$storedMedia = maatlas_gallery_load_media_storage();
	$storedByPath = [];
	foreach ($storedMedia as $media) {
		if (!empty($media['relative_path'])) {
			$storedByPath[(string) $media['relative_path']] = $media;
		}
	}

	$scannedFiles = maatlas_gallery_scan_files();
	$changed = false;
	$syncedMedia = [];

	foreach ($scannedFiles as $relativePath => $fileInfo) {
		$existing = $storedByPath[$relativePath] ?? null;
		if ($existing === null) {
			$changed = true;
			$existing = [
				'id' => 'media-' . bin2hex(random_bytes(4)),
				'relative_path' => $relativePath,
				'category_ids' => [],
				'uploaded_at' => date('c'),
			];
		}

		$existing['relative_path'] = $relativePath;
		$existing['directory'] = $fileInfo['directory'];
		$existing['filename'] = $fileInfo['filename'];
		$existing['url'] = $fileInfo['url'];
		$existing['filesize'] = (int) ($fileInfo['filesize'] ?? 0);
		$existing['modified_at'] = $fileInfo['modified_at'];
		$existing['category_ids'] = array_values(array_unique(array_map('strval', (array) ($existing['category_ids'] ?? []))));
		$syncedMedia[] = $existing;
		unset($storedByPath[$relativePath]);
	}

	if ($storedByPath !== []) {
		$changed = true;
	}

	usort($syncedMedia, static function (array $left, array $right): int {
		return strcmp((string) $left['relative_path'], (string) $right['relative_path']);
	});

	if ($changed || count($syncedMedia) !== count($storedMedia)) {
		maatlas_gallery_save_media_storage($syncedMedia);
	}

	return $syncedMedia;
}

function maatlas_gallery_find_media(string $relativePath): ?array
{
	$relativePath = maatlas_gallery_normalize_relative_path($relativePath);
	foreach (maatlas_gallery_sync_media() as $media) {
		if ((string) $media['relative_path'] === $relativePath) {
			return $media;
		}
	}

	return null;
}

function maatlas_gallery_save_media_item(array $updatedMedia): void
{
	$mediaItems = maatlas_gallery_load_media_storage();
	foreach ($mediaItems as $index => $media) {
		if ((string) $media['relative_path'] === (string) $updatedMedia['relative_path']) {
			$mediaItems[$index] = $updatedMedia;
			maatlas_gallery_save_media_storage($mediaItems);
			return;
		}
	}

	$mediaItems[] = $updatedMedia;
	maatlas_gallery_save_media_storage($mediaItems);
}

function maatlas_gallery_remove_media_item(string $relativePath): void
{
	$relativePath = maatlas_gallery_normalize_relative_path($relativePath);
	$mediaItems = array_values(array_filter(
		maatlas_gallery_load_media_storage(),
		static fn(array $media): bool => (string) $media['relative_path'] !== $relativePath
	));
	maatlas_gallery_save_media_storage($mediaItems);
}

function maatlas_gallery_list_directories(): array
{
	$directories = [''];
	if (is_dir(MAATLAS_GALLERY_UPLOADS_DIR)) {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(MAATLAS_GALLERY_UPLOADS_DIR, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			if (!$item instanceof SplFileInfo || !$item->isDir()) {
				continue;
			}

			$relative = maatlas_gallery_normalize_relative_path(substr(str_replace('\\', '/', $item->getPathname()), strlen(str_replace('\\', '/', MAATLAS_GALLERY_UPLOADS_DIR))));
			if ($relative === '' || maatlas_gallery_is_protected_relative($relative)) {
				continue;
			}

			$directories[] = $relative;
		}
	}

	$directories = array_values(array_unique($directories));
	usort($directories, static fn(string $left, string $right): int => strnatcasecmp($left, $right));
	return $directories;
}

function maatlas_gallery_create_directory(string $parentDirectory, string $name): string
{
	$parentDirectory = maatlas_gallery_normalize_relative_path($parentDirectory);
	if (maatlas_gallery_is_protected_relative($parentDirectory)) {
		throw new RuntimeException('In deze map kunnen geen galerijmappen aangemaakt worden.');
	}

	$segment = maatlas_gallery_slugify($name);
	if ($segment === '') {
		throw new RuntimeException('Geef een geldige mapnaam op.');
	}

	$newRelative = $parentDirectory === '' ? $segment : $parentDirectory . '/' . $segment;
	$absolutePath = maatlas_gallery_absolute_path($newRelative);
	if (is_dir($absolutePath)) {
		throw new RuntimeException('Deze map bestaat al.');
	}

	if (!mkdir($absolutePath, 0775, true) && !is_dir($absolutePath)) {
		throw new RuntimeException('De map kon niet worden aangemaakt.');
	}

	return $newRelative;
}

function maatlas_gallery_rename_directory(string $directory, string $newName): string
{
	$directory = maatlas_gallery_normalize_relative_path($directory);
	if ($directory === '' || maatlas_gallery_is_protected_relative($directory)) {
		throw new RuntimeException('Deze map kan niet hernoemd worden.');
	}

	$newSegment = maatlas_gallery_slugify($newName);
	if ($newSegment === '') {
		throw new RuntimeException('Geef een geldige nieuwe mapnaam op.');
	}

	$parent = dirname($directory);
	$parent = $parent === '.' ? '' : $parent;
	$newRelative = $parent === '' ? $newSegment : $parent . '/' . $newSegment;

	$source = maatlas_gallery_absolute_path($directory);
	$target = maatlas_gallery_absolute_path($newRelative);

	if (!is_dir($source)) {
		throw new RuntimeException('De gekozen map bestaat niet.');
	}

	if (is_dir($target)) {
		throw new RuntimeException('Er bestaat al een map met die naam.');
	}

	if (!rename($source, $target)) {
		throw new RuntimeException('De map kon niet hernoemd worden.');
	}

	$mediaItems = maatlas_gallery_load_media_storage();
	foreach ($mediaItems as $index => $media) {
		$relativePath = (string) ($media['relative_path'] ?? '');
		if ($relativePath === $directory || str_starts_with($relativePath, $directory . '/')) {
			$mediaItems[$index]['relative_path'] = $newRelative . substr($relativePath, strlen($directory));
			$mediaItems[$index]['directory'] = dirname((string) $mediaItems[$index]['relative_path']);
			if ($mediaItems[$index]['directory'] === '.') {
				$mediaItems[$index]['directory'] = '';
			}
			$mediaItems[$index]['url'] = maatlas_gallery_relative_url((string) $mediaItems[$index]['relative_path']);
		}
	}
	maatlas_gallery_save_media_storage($mediaItems);

	return $newRelative;
}

function maatlas_gallery_delete_directory(string $directory): void
{
	$directory = maatlas_gallery_normalize_relative_path($directory);
	if ($directory === '' || maatlas_gallery_is_protected_relative($directory)) {
		throw new RuntimeException('Deze map kan niet verwijderd worden.');
	}

	$absolutePath = maatlas_gallery_absolute_path($directory);
	if (!is_dir($absolutePath)) {
		throw new RuntimeException('De gekozen map bestaat niet.');
	}

	$contents = scandir($absolutePath);
	if ($contents === false) {
		throw new RuntimeException('De map kon niet gelezen worden.');
	}

	$visibleContents = array_values(array_diff($contents, ['.', '..']));
	if ($visibleContents !== []) {
		throw new RuntimeException('Verwijder eerst alle bestanden en submappen uit deze map.');
	}

	if (!rmdir($absolutePath)) {
		throw new RuntimeException('De map kon niet verwijderd worden.');
	}
}

function maatlas_gallery_unique_filename(string $directory, string $originalName): string
{
	$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
	$baseName = pathinfo($originalName, PATHINFO_FILENAME);
	$slug = maatlas_gallery_slugify($baseName);
	if ($slug === '') {
		$slug = 'afbeelding';
	}

	$candidate = $slug . ($extension !== '' ? '.' . $extension : '');
	$counter = 2;

	while (file_exists(maatlas_gallery_absolute_path($directory === '' ? $candidate : $directory . '/' . $candidate))) {
		$candidate = $slug . '-' . $counter . ($extension !== '' ? '.' . $extension : '');
		$counter++;
	}

	return $candidate;
}

function maatlas_gallery_rotate_uploaded_image(string $absolutePath, int $degrees): ?string
{
	$normalizedDegrees = (($degrees % 360) + 360) % 360;
	if ($normalizedDegrees === 0) {
		return null;
	}

	if (!extension_loaded('gd')) {
		return 'Roteren is niet beschikbaar omdat de GD-extensie ontbreekt op de server.';
	}

	$imageInfo = @getimagesize($absolutePath);
	if (!is_array($imageInfo) || empty($imageInfo[2])) {
		return 'Het geuploade bestand is geen geldige afbeelding.';
	}

	$imageType = (int) $imageInfo[2];
	$source = null;

	switch ($imageType) {
		case IMAGETYPE_JPEG:
			$source = @imagecreatefromjpeg($absolutePath);
			break;
		case IMAGETYPE_PNG:
			$source = @imagecreatefrompng($absolutePath);
			break;
		case IMAGETYPE_GIF:
			$source = @imagecreatefromgif($absolutePath);
			break;
		case IMAGETYPE_WEBP:
			if (function_exists('imagecreatefromwebp')) {
				$source = @imagecreatefromwebp($absolutePath);
			}
			break;
	}

	if (!$source) {
		return 'Deze afbeelding kon niet geroteerd worden.';
	}

	$rotationAngle = 360 - $normalizedDegrees;
	if ($rotationAngle === 360) {
		$rotationAngle = 0;
	}

	$background = imagecolorallocatealpha($source, 0, 0, 0, 127);
	$rotated = imagerotate($source, $rotationAngle, $background);
	if (!$rotated) {
		imagedestroy($source);
		return 'Roteren van de afbeelding is mislukt.';
	}

	imagealphablending($rotated, false);
	imagesavealpha($rotated, true);

	$saved = false;
	switch ($imageType) {
		case IMAGETYPE_JPEG:
			$saved = imagejpeg($rotated, $absolutePath, 90);
			break;
		case IMAGETYPE_PNG:
			$saved = imagepng($rotated, $absolutePath);
			break;
		case IMAGETYPE_GIF:
			$saved = imagegif($rotated, $absolutePath);
			break;
		case IMAGETYPE_WEBP:
			if (function_exists('imagewebp')) {
				$saved = imagewebp($rotated, $absolutePath, 90);
			}
			break;
	}

	imagedestroy($source);
	imagedestroy($rotated);

	return $saved ? null : 'De afbeelding kon niet opgeslagen worden na rotatie.';
}

function maatlas_gallery_upload_files(string $directory, array $files, array $categoryIds, array $rotations): array
{
	$directory = '';

	$targetDirectory = maatlas_gallery_absolute_path($directory);
	if (!is_dir($targetDirectory)) {
		throw new RuntimeException('De gekozen uploadmap bestaat niet.');
	}

	$allowedCategoryIds = array_column(maatlas_gallery_load_categories(), 'id');
	$categoryIds = array_values(array_intersect(array_map('strval', $categoryIds), $allowedCategoryIds));

	$uploadedPaths = [];

	if (!isset($files['name']) || !is_array($files['name'])) {
		throw new RuntimeException('Er werden geen bestanden ontvangen.');
	}

	$fileCount = count($files['name']);
	for ($index = 0; $index < $fileCount; $index++) {
		$error = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
		if ($error === UPLOAD_ERR_NO_FILE) {
			continue;
		}
		if ($error !== UPLOAD_ERR_OK) {
			throw new RuntimeException('Een van de bestanden kon niet geupload worden.');
		}

		$tmpName = (string) ($files['tmp_name'][$index] ?? '');
		$originalName = (string) ($files['name'][$index] ?? '');
		if ($tmpName === '' || !is_uploaded_file($tmpName)) {
			throw new RuntimeException('Uploadvalidatie mislukt.');
		}

		$imageType = @exif_imagetype($tmpName);
		if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
			throw new RuntimeException('Alleen jpg, png, gif en webp zijn toegestaan.');
		}

		$filename = maatlas_gallery_unique_filename($directory, $originalName);
		$relativePath = $directory === '' ? $filename : $directory . '/' . $filename;
		$absolutePath = maatlas_gallery_absolute_path($relativePath);

		if (!move_uploaded_file($tmpName, $absolutePath)) {
			throw new RuntimeException('Een bestand kon niet naar de server verplaatst worden.');
		}

		$rotation = isset($rotations[$index]) ? (int) $rotations[$index] : 0;
		$rotationError = maatlas_gallery_rotate_uploaded_image($absolutePath, $rotation);
		if ($rotationError !== null) {
			throw new RuntimeException($rotationError);
		}

		$mediaItem = [
			'id' => 'media-' . bin2hex(random_bytes(4)),
			'relative_path' => $relativePath,
			'directory' => $directory,
			'filename' => basename($relativePath),
			'url' => maatlas_gallery_relative_url($relativePath),
			'category_ids' => $categoryIds,
			'uploaded_at' => date('c'),
			'modified_at' => date('c'),
		];
		maatlas_gallery_save_media_item($mediaItem);
		$uploadedPaths[] = $relativePath;
	}

	if ($uploadedPaths === []) {
		throw new RuntimeException('Selecteer minstens één afbeelding.');
	}

	return $uploadedPaths;
}

function maatlas_gallery_find_category_by_id(string $id): ?array
{
	foreach (maatlas_gallery_load_categories() as $category) {
		if ((string) ($category['id'] ?? '') === $id) {
			return $category;
		}
	}

	return null;
}

function maatlas_gallery_ensure_directory_exists(string $relativeDirectory): void
{
	$relativeDirectory = maatlas_gallery_normalize_relative_path($relativeDirectory);
	if ($relativeDirectory === '' || maatlas_gallery_is_protected_relative($relativeDirectory)) {
		throw new RuntimeException('De doelmap voor upload is niet geldig.');
	}

	$absoluteDirectory = maatlas_gallery_absolute_path($relativeDirectory);
	if (is_dir($absoluteDirectory)) {
		return;
	}

	if (!mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
		throw new RuntimeException('De uploadmap kon niet aangemaakt worden.');
	}
}

function maatlas_gallery_create_image_from_upload(string $tmpName, int $imageType)
{
	switch ($imageType) {
		case IMAGETYPE_JPEG:
			return @imagecreatefromjpeg($tmpName);
		case IMAGETYPE_PNG:
			return @imagecreatefrompng($tmpName);
		case IMAGETYPE_GIF:
			return @imagecreatefromgif($tmpName);
		case IMAGETYPE_WEBP:
			return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpName) : false;
		default:
			return false;
	}
}

function maatlas_gallery_normalize_orientation($source, string $tmpName, int $imageType)
{
	if ($imageType !== IMAGETYPE_JPEG || !function_exists('exif_read_data')) {
		return $source;
	}

	$exif = @exif_read_data($tmpName);
	$orientation = (int) ($exif['Orientation'] ?? 1);
	if ($orientation === 1) {
		return $source;
	}

	switch ($orientation) {
		case 3:
			$rotated = imagerotate($source, 180, 0);
			break;
		case 6:
			$rotated = imagerotate($source, -90, 0);
			break;
		case 8:
			$rotated = imagerotate($source, 90, 0);
			break;
		default:
			$rotated = $source;
			break;
	}

	if ($rotated && $rotated !== $source) {
		imagedestroy($source);
		return $rotated;
	}

	return $source;
}

function maatlas_gallery_mobile_upload_photo(string $categoryId, array $file, string $title, float $latitude, float $longitude): array
{
	if (!extension_loaded('gd')) {
		throw new RuntimeException('Mobiele foto-upload vereist de GD-extensie op de server.');
	}

	$category = maatlas_gallery_find_category_by_id($categoryId);
	if ($category === null) {
		throw new RuntimeException('Kies een geldige categorie.');
	}

	$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
	if ($error !== UPLOAD_ERR_OK) {
		throw new RuntimeException('De foto kon niet geupload worden.');
	}

	$tmpName = (string) ($file['tmp_name'] ?? '');
	$originalName = (string) ($file['name'] ?? 'foto.jpg');
	if ($tmpName === '' || !is_uploaded_file($tmpName)) {
		throw new RuntimeException('Uploadvalidatie mislukt.');
	}

	$imageType = @exif_imagetype($tmpName);
	if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
		throw new RuntimeException('Alleen jpg, png, gif en webp zijn toegestaan.');
	}

	if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
		throw new RuntimeException('De locatiegegevens zijn ongeldig.');
	}

	$source = maatlas_gallery_create_image_from_upload($tmpName, $imageType);
	if (!$source) {
		throw new RuntimeException('De afbeelding kon niet gelezen worden.');
	}

	$source = maatlas_gallery_normalize_orientation($source, $tmpName, $imageType);
	$sourceWidth = imagesx($source);
	$sourceHeight = imagesy($source);
	$maxWidth = 1600;
	$maxHeight = 1600;
	$scale = min(1, $maxWidth / max(1, $sourceWidth), $maxHeight / max(1, $sourceHeight));
	$targetWidth = max(1, (int) round($sourceWidth * $scale));
	$targetHeight = max(1, (int) round($sourceHeight * $scale));

	$canvas = imagecreatetruecolor($targetWidth, $targetHeight);
	if (!$canvas) {
		imagedestroy($source);
		throw new RuntimeException('De afbeelding kon niet voorbereid worden voor upload.');
	}

	$background = imagecolorallocate($canvas, 255, 255, 255);
	imagefill($canvas, 0, 0, $background);
	imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
	imagedestroy($source);

	$encoded = null;
	$quality = 88;
	while ($quality >= 40) {
		ob_start();
		imagejpeg($canvas, null, $quality);
		$candidate = (string) ob_get_clean();
		if ($candidate !== '' && strlen($candidate) <= 500 * 1024) {
			$encoded = $candidate;
			break;
		}
		$encoded = $candidate;
		$quality -= 6;
	}

	if ($encoded === null || $encoded === '') {
		imagedestroy($canvas);
		throw new RuntimeException('De afbeelding kon niet gecomprimeerd worden.');
	}

	while (strlen($encoded) > 500 * 1024 && $targetWidth > 640 && $targetHeight > 640) {
		$targetWidth = (int) round($targetWidth * 0.88);
		$targetHeight = (int) round($targetHeight * 0.88);
		$resizedCanvas = imagecreatetruecolor($targetWidth, $targetHeight);
		if (!$resizedCanvas) {
			break;
		}
		$background = imagecolorallocate($resizedCanvas, 255, 255, 255);
		imagefill($resizedCanvas, 0, 0, $background);
		imagecopyresampled($resizedCanvas, $canvas, 0, 0, 0, 0, $targetWidth, $targetHeight, imagesx($canvas), imagesy($canvas));
		imagedestroy($canvas);
		$canvas = $resizedCanvas;

		$quality = 82;
		while ($quality >= 36) {
			ob_start();
			imagejpeg($canvas, null, $quality);
			$candidate = (string) ob_get_clean();
			if ($candidate !== '' && strlen($candidate) <= 500 * 1024) {
				$encoded = $candidate;
				break 2;
			}
			$encoded = $candidate;
			$quality -= 6;
		}
	}

	imagedestroy($canvas);

	if (strlen($encoded) > 500 * 1024) {
		throw new RuntimeException('De afbeelding blijft te groot. Neem een iets kleinere foto of probeer opnieuw.');
	}

	$baseName = trim($title) !== '' ? $title : pathinfo($originalName, PATHINFO_FILENAME);
	$filename = maatlas_gallery_unique_filename('', $baseName . '.jpg');
	$relativePath = $filename;
	$absolutePath = maatlas_gallery_absolute_path($relativePath);

	if (file_put_contents($absolutePath, $encoded, LOCK_EX) === false) {
		throw new RuntimeException('De foto kon niet op de server opgeslagen worden.');
	}

	$mapsUrl = 'https://www.google.com/maps?q=' . rawurlencode(number_format($latitude, 6, '.', '') . ',' . number_format($longitude, 6, '.', ''));
	$mediaItem = [
		'id' => 'media-' . bin2hex(random_bytes(4)),
		'relative_path' => $relativePath,
		'directory' => '',
		'filename' => basename($relativePath),
		'url' => maatlas_gallery_relative_url($relativePath),
		'category_ids' => [$categoryId],
		'title' => trim($title),
		'latitude' => number_format($latitude, 6, '.', ''),
		'longitude' => number_format($longitude, 6, '.', ''),
		'google_maps_url' => $mapsUrl,
		'uploaded_at' => date('c'),
		'modified_at' => date('c'),
		'filesize' => strlen($encoded),
	];

	maatlas_gallery_save_media_item($mediaItem);
	return $mediaItem;
}

function maatlas_gallery_delete_file(string $relativePath): void
{
	$relativePath = maatlas_gallery_normalize_relative_path($relativePath);
	if ($relativePath === '' || maatlas_gallery_is_protected_relative($relativePath)) {
		throw new RuntimeException('Dit bestand kan niet verwijderd worden.');
	}

	$absolutePath = maatlas_gallery_absolute_path($relativePath);
	if (!is_file($absolutePath)) {
		throw new RuntimeException('Bestand niet gevonden.');
	}

	if (!unlink($absolutePath)) {
		throw new RuntimeException('Het bestand kon niet verwijderd worden.');
	}

	maatlas_gallery_remove_media_item($relativePath);
}

function maatlas_gallery_move_media(string $relativePath, string $targetDirectory): array
{
	$relativePath = maatlas_gallery_normalize_relative_path($relativePath);
	$targetDirectory = maatlas_gallery_normalize_relative_path($targetDirectory);

	if ($relativePath === '' || maatlas_gallery_is_protected_relative($relativePath)) {
		throw new RuntimeException('Deze afbeelding kan niet verplaatst worden.');
	}

	if ($targetDirectory !== '' && maatlas_gallery_is_protected_relative($targetDirectory)) {
		throw new RuntimeException('Verplaatsen naar deze map is niet toegestaan.');
	}

	$media = maatlas_gallery_find_media($relativePath);
	if ($media === null) {
		throw new RuntimeException('Afbeelding niet gevonden.');
	}

	$sourceAbsolutePath = maatlas_gallery_absolute_path($relativePath);
	if (!is_file($sourceAbsolutePath)) {
		throw new RuntimeException('Bestand niet gevonden.');
	}

	$currentDirectory = maatlas_gallery_normalize_relative_path((string) ($media['directory'] ?? ''));
	if ($currentDirectory === $targetDirectory) {
		return $media;
	}

	if ($targetDirectory !== '') {
		maatlas_gallery_ensure_directory_exists($targetDirectory);
	}

	$targetFilename = maatlas_gallery_unique_filename($targetDirectory, (string) ($media['filename'] ?? basename($relativePath)));
	$newRelativePath = $targetDirectory === '' ? $targetFilename : $targetDirectory . '/' . $targetFilename;
	$targetAbsolutePath = maatlas_gallery_absolute_path($newRelativePath);

	if (!@rename($sourceAbsolutePath, $targetAbsolutePath)) {
		throw new RuntimeException('De afbeelding kon niet verplaatst worden.');
	}

	$media['relative_path'] = $newRelativePath;
	$media['directory'] = $targetDirectory;
	$media['filename'] = basename($newRelativePath);
	$media['url'] = maatlas_gallery_relative_url($newRelativePath);
	$media['modified_at'] = date('c');

	maatlas_gallery_remove_media_item($relativePath);
	maatlas_gallery_save_media_item($media);

	return $media;
}

function maatlas_gallery_replace_media(string $relativePath, array $file): array
{
	$relativePath = maatlas_gallery_normalize_relative_path($relativePath);
	if ($relativePath === '' || maatlas_gallery_is_protected_relative($relativePath)) {
		throw new RuntimeException('Deze afbeelding kan niet vervangen worden.');
	}

	$media = maatlas_gallery_find_media($relativePath);
	if ($media === null) {
		throw new RuntimeException('Afbeelding niet gevonden.');
	}

	$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
	if ($error !== UPLOAD_ERR_OK) {
		throw new RuntimeException('Kies eerst een geldig bestand van je pc.');
	}

	$tmpName = (string) ($file['tmp_name'] ?? '');
	if ($tmpName === '' || !is_uploaded_file($tmpName)) {
		throw new RuntimeException('Uploadvalidatie mislukt.');
	}

	$imageType = @exif_imagetype($tmpName);
	if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
		throw new RuntimeException('Alleen jpg, png, gif en webp zijn toegestaan.');
	}

	$extensionMap = [
		IMAGETYPE_JPEG => 'jpg',
		IMAGETYPE_PNG => 'png',
		IMAGETYPE_GIF => 'gif',
		IMAGETYPE_WEBP => 'webp',
	];
	$newExtension = $extensionMap[$imageType] ?? strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
	if ($newExtension === '') {
		$newExtension = strtolower((string) pathinfo((string) ($media['filename'] ?? basename($relativePath)), PATHINFO_EXTENSION));
	}

	$currentDirectory = '';
	$currentStem = (string) pathinfo((string) ($media['filename'] ?? basename($relativePath)), PATHINFO_FILENAME);
	$targetFilename = $currentStem . ($newExtension !== '' ? '.' . $newExtension : '');
	$targetRelativePath = $currentDirectory === '' ? $targetFilename : $currentDirectory . '/' . $targetFilename;

	if ($targetRelativePath !== $relativePath && file_exists(maatlas_gallery_absolute_path($targetRelativePath))) {
		$targetFilename = maatlas_gallery_unique_filename($currentDirectory, $targetFilename);
		$targetRelativePath = $currentDirectory === '' ? $targetFilename : $currentDirectory . '/' . $targetFilename;
	}

	$targetAbsolutePath = maatlas_gallery_absolute_path($targetRelativePath);
	$tempAbsolutePath = $targetAbsolutePath . '.uploadtmp';

	if (!move_uploaded_file($tmpName, $tempAbsolutePath)) {
		throw new RuntimeException('Het nieuwe bestand kon niet naar de server verplaatst worden.');
	}

	$sourceAbsolutePath = maatlas_gallery_absolute_path($relativePath);
	if (is_file($sourceAbsolutePath) && !@unlink($sourceAbsolutePath)) {
		@unlink($tempAbsolutePath);
		throw new RuntimeException('Het oude bestand kon niet vervangen worden.');
	}

	if (!@rename($tempAbsolutePath, $targetAbsolutePath)) {
		@unlink($tempAbsolutePath);
		throw new RuntimeException('Het nieuwe bestand kon niet opgeslagen worden.');
	}

	$media['relative_path'] = $targetRelativePath;
	$media['directory'] = $currentDirectory;
	$media['filename'] = basename($targetRelativePath);
	$media['url'] = maatlas_gallery_relative_url($targetRelativePath);
	$media['modified_at'] = date('c');
	$media['filesize'] = is_file($targetAbsolutePath) ? (int) filesize($targetAbsolutePath) : 0;

	if ($targetRelativePath !== $relativePath) {
		maatlas_gallery_remove_media_item($relativePath);
	}
	maatlas_gallery_save_media_item($media);

	return $media;
}

function maatlas_gallery_update_media_categories(string $relativePath, array $categoryIds): void
{
	$media = maatlas_gallery_find_media($relativePath);
	if ($media === null) {
		throw new RuntimeException('Afbeelding niet gevonden.');
	}

	$allowedCategoryIds = array_column(maatlas_gallery_load_categories(), 'id');
	$media['category_ids'] = array_values(array_intersect(array_map('strval', $categoryIds), $allowedCategoryIds));
	$media['modified_at'] = date('c');
	maatlas_gallery_save_media_item($media);
}

function maatlas_gallery_count_media_by_directory(array $mediaItems): array
{
	$counts = [];
	foreach ($mediaItems as $media) {
		$directory = (string) ($media['directory'] ?? '');
		$counts[$directory] = ($counts[$directory] ?? 0) + 1;
	}

	return $counts;
}

function maatlas_gallery_count_media_by_category(array $mediaItems): array
{
	$counts = [];
	foreach ($mediaItems as $media) {
		foreach ((array) ($media['category_ids'] ?? []) as $categoryId) {
			$counts[$categoryId] = ($counts[$categoryId] ?? 0) + 1;
		}
	}

	return $counts;
}

function maatlas_lastenboek_load(): array
{
	maatlas_admin_ensure_storage();
	$stored = require MAATLAS_LASTENBOEK_STORAGE;
	$document = is_array($stored) && isset($stored[0]) && is_array($stored[0]) ? $stored[0] : [];
	$defaults = maatlas_lastenboek_default();

	$meta = array_merge($defaults['meta'], is_array($document['meta'] ?? null) ? $document['meta'] : []);
	$items = array_values(array_filter(
		is_array($document['items'] ?? null) ? $document['items'] : [],
		static fn(mixed $item): bool => is_array($item)
	));

	usort($items, static function (array $left, array $right): int {
		$positionCompare = ((int) ($left['position'] ?? 0)) <=> ((int) ($right['position'] ?? 0));
		if ($positionCompare !== 0) {
			return $positionCompare;
		}

		return strcmp((string) ($left['created_at'] ?? ''), (string) ($right['created_at'] ?? ''));
	});

	return [
		'meta' => $meta,
		'items' => $items,
	];
}

function maatlas_lastenboek_save(array $document): void
{
	$defaults = maatlas_lastenboek_default();
	$meta = array_merge($defaults['meta'], is_array($document['meta'] ?? null) ? $document['meta'] : []);
	$items = array_values(array_filter(
		is_array($document['items'] ?? null) ? $document['items'] : [],
		static fn(mixed $item): bool => is_array($item)
	));

	file_put_contents(
		MAATLAS_LASTENBOEK_STORAGE,
		maatlas_admin_php_array_export([['meta' => $meta, 'items' => $items]]),
		LOCK_EX
	);
}

function maatlas_lastenboek_update_meta(array $meta): void
{
	$document = maatlas_lastenboek_load();
	$document['meta'] = [
		'document_title' => trim((string) ($meta['document_title'] ?? '')),
		'project_name' => trim((string) ($meta['project_name'] ?? '')),
		'client_name' => trim((string) ($meta['client_name'] ?? '')),
		'reference' => trim((string) ($meta['reference'] ?? '')),
		'version' => trim((string) ($meta['version'] ?? '1.0')),
		'introduction' => trim((string) ($meta['introduction'] ?? '')),
	];

	if ($document['meta']['document_title'] === '') {
		$document['meta']['document_title'] = 'Technisch lastenboek website W&S Maatlaswerk';
	}

	if ($document['meta']['version'] === '') {
		$document['meta']['version'] = '1.0';
	}

	maatlas_lastenboek_save($document);
}

function maatlas_lastenboek_find_item(string $id): ?array
{
	foreach (maatlas_lastenboek_load()['items'] as $item) {
		if ((string) ($item['id'] ?? '') === $id) {
			return $item;
		}
	}

	return null;
}

function maatlas_lastenboek_upsert_item(array $data): array
{
	$document = maatlas_lastenboek_load();
	$id = trim((string) ($data['id'] ?? ''));
	$rubric = trim((string) ($data['rubric'] ?? ''));
	$code = trim((string) ($data['code'] ?? ''));
	$title = trim((string) ($data['title'] ?? ''));
	$content = trim((string) ($data['content'] ?? ''));
	$status = trim((string) ($data['status'] ?? 'concept'));
	$position = max(1, (int) ($data['position'] ?? (count($document['items']) + 1)));

	if ($title === '' || $content === '') {
		throw new RuntimeException('Titel en inhoud zijn verplicht voor een lastenboekitem.');
	}

	$allowedStatuses = ['concept', 'te-bekijken', 'goedgekeurd'];
	if (!in_array($status, $allowedStatuses, true)) {
		$status = 'concept';
	}

	$item = [
		'id' => $id !== '' ? $id : 'lb-' . bin2hex(random_bytes(4)),
		'rubric' => $rubric,
		'code' => $code,
		'title' => $title,
		'content' => $content,
		'status' => $status,
		'position' => $position,
		'created_at' => date('c'),
		'updated_at' => date('c'),
	];

	$updated = false;
	foreach ($document['items'] as $index => $existingItem) {
		if ((string) ($existingItem['id'] ?? '') !== $item['id']) {
			continue;
		}

		$item['created_at'] = (string) ($existingItem['created_at'] ?? date('c'));
		$document['items'][$index] = $item;
		$updated = true;
		break;
	}

	if (!$updated) {
		$document['items'][] = $item;
	}

	maatlas_lastenboek_save($document);
	return $item;
}

function maatlas_lastenboek_delete_item(string $id): void
{
	$document = maatlas_lastenboek_load();
	$document['items'] = array_values(array_filter(
		$document['items'],
		static fn(array $item): bool => (string) ($item['id'] ?? '') !== $id
	));
	maatlas_lastenboek_save($document);
}

function maatlas_lastenboek_template_items(): array
{
	$now = date('c');
	return [
		[
			'id' => 'lb-template-1',
			'rubric' => 'Algemeen',
			'code' => '01.01',
			'title' => 'Doel en scope van de website',
			'content' => 'Deze website is opgezet als statische/publieke bedrijfswebsite met een licht PHP-beheerluik. De site ondersteunt voorstelling van diensten, dynamische galerijweergave per categorie, contactaanvragen, privacy- en cookie-informatie en intern beheer via de adminomgeving.',
			'status' => 'concept',
			'position' => 1,
			'created_at' => $now,
			'updated_at' => $now,
		],
		[
			'id' => 'lb-template-2',
			'rubric' => 'Structuur',
			'code' => '02.01',
			'title' => 'Bestandsstructuur en pagina-opbouw',
			'content' => 'De publieke site bestaat hoofdzakelijk uit `index.php`, `about/index.php`, `services/index.php`, `services/detail.php`, `contact/index.php`, `privacy/index.php` en `cookies/index.php`. Gedeelde logica zit in `includes/`, styling en interactie in `assets/themes/bluehost-blueprint/`.',
			'status' => 'concept',
			'position' => 2,
			'created_at' => $now,
			'updated_at' => $now,
		],
		[
			'id' => 'lb-template-3',
			'rubric' => 'Admin',
			'code' => '03.01',
			'title' => 'Beheeromgeving en toegangscontrole',
			'content' => 'De adminomgeving op `/admin/` is beveiligd met login en sessiebeheer. Binnen deze omgeving kunnen administrators, galerijcategorieen, afbeeldingen, instellingen, mobiele uploads en technische documentatie beheerd worden. Wachtwoorden worden gehasht opgeslagen.',
			'status' => 'concept',
			'position' => 3,
			'created_at' => $now,
			'updated_at' => $now,
		],
		[
			'id' => 'lb-template-admin-workflow',
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
			'created_at' => $now,
			'updated_at' => $now,
		],
		[
			'id' => 'lb-template-admin-interface',
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
			'created_at' => $now,
			'updated_at' => $now,
		],
		[
			'id' => 'lb-template-4',
			'rubric' => 'Galerij',
			'code' => '04.01',
			'title' => 'Galerij, categorieen en dynamische inhoud',
			'content' => 'Afbeeldingen worden beheerd onder `assets/uploads` en gekoppeld aan categorieen via de admin. De publieke site leest deze metadata uit de opslagbestanden in `admin/storage/` en bouwt daaruit dynamisch albumoverzichten, detailpagina’s en willekeurige projectbeelden op.',
			'status' => 'concept',
			'position' => 6,
			'created_at' => $now,
			'updated_at' => $now,
		],
		[
			'id' => 'lb-template-5',
			'rubric' => 'Formulieren',
			'code' => '05.01',
			'title' => 'Contactformulier en mailafhandeling',
			'content' => 'Het contactformulier op `/contact/` leest zijn ontvanger, afzender, testmodus en publieke contactgegevens uit de admininstellingen. In testmodus worden berichten doorgestuurd naar een testadres; in live modus naar het effectieve ontvangstadres.',
			'status' => 'concept',
			'position' => 7,
			'created_at' => $now,
			'updated_at' => $now,
		],
		[
			'id' => 'lb-template-6',
			'rubric' => 'Compliance',
			'code' => '06.01',
			'title' => 'Privacy, cookies en externe diensten',
			'content' => 'De website bevat afzonderlijke pagina’s voor privacy en cookies. Externe diensten zoals Google Maps worden publiek vermeld, en de contactpagina bevat de nodige privacytoelichting en toestemmingsverwijzing conform de ingestelde sitegegevens.',
			'status' => 'concept',
			'position' => 8,
			'created_at' => $now,
			'updated_at' => $now,
		],
		[
			'id' => 'lb-template-7',
			'rubric' => 'Publicatie',
			'code' => '07.01',
			'title' => 'Upload, publicatie en onderhoud',
			'content' => 'Wijzigingen worden lokaal uitgevoerd in `Website/` en daarna gericht geüpload naar de server. Voor onderhoud is het belangrijk dat alleen gewijzigde bestanden worden gepubliceerd en dat oude, overbodige exports of testmappen niet opnieuw worden meegezet.',
			'status' => 'concept',
			'position' => 9,
			'created_at' => $now,
			'updated_at' => $now,
		],
	];
}

function maatlas_lastenboek_load_template(): void
{
	$document = maatlas_lastenboek_load();
	$document['items'] = maatlas_lastenboek_template_items();
	if (trim((string) ($document['meta']['introduction'] ?? '')) === '') {
		$document['meta']['introduction'] = 'Technische documentatie van de website, met focus op opbouw, beheer, publicatie, galerij, formulieren en adminfuncties.';
	}
	maatlas_lastenboek_save($document);
}

function maatlas_admin_current_host_url(string $path = '/'): string
{
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
	$path = '/' . ltrim($path, '/');
	if ($host === '') {
		return $path;
	}

	return $scheme . '://' . $host . $path;
}

function maatlas_admin_qr_image_url(string $targetUrl): string
{
	return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($targetUrl);
}

function maatlas_admin_public_navigation(): array
{
	return [
		['label' => 'Home', 'href' => '/'],
		['label' => 'Over ons', 'href' => '/about/'],
		['label' => 'Diensten', 'href' => '/services/'],
		['label' => 'Contact', 'href' => '/contact/'],
	];
}

function maatlas_admin_render_public_shell_header(): void
{
	?>
<div class="maatlas-site-shell maatlas-site-shell-header">
	<div class="maatlas-shell-inner maatlas-shell-header-inner">
		<a class="maatlas-shell-brand" href="/">
			<img class="maatlas-shell-brand-logo" src="/assets/uploads/static/MaatLasWerk-13.jpg?v=20260329-3" alt="W&amp;S Maatlaswerk logo">
			<span class="maatlas-shell-brand-text">
				<strong>W&amp;S Maatlaswerk</strong>
				<small>Metaal en glas op maat in Kluisbergen</small>
			</span>
		</a>
		<nav class="maatlas-shell-nav" aria-label="Hoofdnavigatie">
			<?php foreach (maatlas_admin_public_navigation() as $link): ?>
			<a class="maatlas-shell-link" href="<?= maatlas_admin_h((string) $link['href']); ?>"><?= maatlas_admin_h((string) $link['label']); ?></a>
			<?php endforeach; ?>
		</nav>
	</div>
</div>
	<?php
}

function maatlas_admin_render_public_shell_footer(): void
{
	$settings = maatlas_site_settings_load();
	$vatNumber = trim((string) ($settings['vat_number'] ?? ''));
	?>
<div class="maatlas-site-shell maatlas-site-shell-footer">
	<div class="maatlas-shell-inner maatlas-shell-footer-inner">
		<div class="maatlas-shell-footer-copy">
			<strong>W&amp;S Maatlaswerk</strong>
			<p>Maatwerk in staal, inox, aluminium en glas voor particuliere en professionele projecten.</p>
			<p class="maatlas-shell-footer-materials">Materialen met profielen van<br><a href="https://www.forstersystems.be" target="_blank" rel="noopener noreferrer">Forster Systems</a>. <a href="https://www.forstersystems.be/dealers/binnenschrijnwerk-buitenschrijnwerk-9690-kluisbergen/ws-maatlaswerken" target="_blank" rel="noopener noreferrer">Bekijk onze dealerfiche</a>.</p>
			<?php if ($vatNumber !== ''): ?>
			<p>BTW: <?= maatlas_admin_h($vatNumber); ?></p>
			<?php endif; ?>
			<div class="maatlas-shell-footer-legal">
				<a class="maatlas-shell-legal-link" href="/privacy/">Privacyverklaring</a>
				<a class="maatlas-shell-legal-link" href="/cookies/">Cookiebeleid</a>
			</div>
		</div>
		<nav class="maatlas-shell-footer-nav" aria-label="Footer navigatie">
			<?php foreach (maatlas_admin_public_navigation() as $link): ?>
			<a class="maatlas-shell-link" href="<?= maatlas_admin_h((string) $link['href']); ?>"><?= maatlas_admin_h((string) $link['label']); ?></a>
			<?php endforeach; ?>
		</nav>
		<div class="maatlas-shell-footer-socials">
			<a class="maatlas-shell-social" href="https://www.facebook.com/p/WS-Maatlaswerk-100054239330706/" rel="noopener noreferrer">Facebook</a>
			<a class="maatlas-shell-social" href="https://www.instagram.com/w_s_maatlaswerk/" rel="noopener noreferrer">Instagram</a>
		</div>
		<a class="maatlas-shell-footer-admin" href="/admin/">Admin</a>
	</div>
</div>
	<?php
}

function maatlas_admin_render_header(string $title, ?array $currentAdmin = null): void
{
	$hasSidebarLayout = $currentAdmin !== null;
	$GLOBALS['maatlas_admin_has_sidebar_layout'] = $hasSidebarLayout;
	$currentPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
	$sidebarSubmenu = $GLOBALS['maatlas_admin_sidebar_submenu'] ?? [];
	if (!is_array($sidebarSubmenu)) {
		$sidebarSubmenu = [];
	}
	$navigationItems = [
		['label' => 'Dashboard', 'href' => '/admin/'],
		['label' => 'Beheerders', 'href' => '/admin/administrators.php'],
		['label' => 'Galerij', 'href' => '/admin/gallery.php'],
		['label' => 'Mobiele upload', 'href' => '/admin/mobile-upload.php'],
		['label' => 'Lastenboek', 'href' => '/admin/lastenboek.php'],
		['label' => 'Instellingen', 'href' => '/admin/settings.php'],
	];
	?>
<!DOCTYPE html>
<html lang="nl-BE">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= maatlas_admin_h($title); ?> | Admin | W&amp;S Maatlaswerk</title>
	<link rel="stylesheet" href="/assets/themes/bluehost-blueprint/style.css?ver=2.0.4">
	<link rel="stylesheet" href="/admin/style.css?v=20260331-1">
	<?php maatlas_site_render_theme_style(); ?>
	<?php if (!empty($GLOBALS['maatlas_admin_extra_head'])): ?>
	<?= $GLOBALS['maatlas_admin_extra_head']; ?>
	<?php endif; ?>
</head>
<body class="maatlas-admin-page">
<?php maatlas_admin_render_public_shell_header(); ?>
<?php if ($currentAdmin !== null): ?>
<div class="maatlas-admin-floating-status" role="status" aria-live="polite">
	<div class="maatlas-admin-floating-status-copy">
		<strong>U bent aangemeld</strong>
		<span>Gebruiker: <?= maatlas_admin_h((string) $currentAdmin['username']); ?> | Rol: <?= maatlas_admin_h((string) $currentAdmin['role']); ?></span>
	</div>
	<a href="/admin/logout.php">Uitloggen</a>
</div>
<?php endif; ?>
<div class="maatlas-admin-shell<?= $hasSidebarLayout ? ' maatlas-admin-shell-layout' : ''; ?>">
	<?php if ($hasSidebarLayout): ?>
	<aside class="maatlas-admin-sidebar">
		<div class="maatlas-admin-sidebar-card">
			<p class="maatlas-admin-eyebrow">Admin menu</p>
			<h2>W&amp;S Maatlaswerk</h2>
			<p class="maatlas-admin-sidebar-copy">Kies links wat je wilt beheren.</p>
			<div class="maatlas-admin-current-user maatlas-admin-sidebar-user">
				<strong><?= maatlas_admin_h((string) $currentAdmin['full_name']); ?></strong>
				<span><?= maatlas_admin_h((string) $currentAdmin['role']); ?></span>
			</div>
			<nav class="maatlas-admin-sidebar-nav" aria-label="Admin navigatie">
				<?php foreach ($navigationItems as $navigationItem): ?>
					<?php
					$isCurrent = $currentPath === $navigationItem['href']
						|| ($navigationItem['href'] === '/admin/' && ($currentPath === '/admin' || $currentPath === '/admin/index.php'));
					?>
					<div class="maatlas-admin-sidebar-nav-item">
						<?php if ($isCurrent && $navigationItem['href'] === '/admin/gallery.php' && $sidebarSubmenu !== []): ?>
						<button
							type="button"
							class="maatlas-admin-sidebar-toggle is-current"
							data-submenu-toggle="gallery"
							aria-expanded="true"
							aria-controls="maatlas-admin-gallery-submenu"
						>
							<span><?= maatlas_admin_h($navigationItem['label']); ?></span>
							<span class="maatlas-admin-sidebar-toggle-icon" aria-hidden="true"></span>
						</button>
						<?php else: ?>
						<a class="<?= $isCurrent ? 'is-current' : ''; ?>" href="<?= maatlas_admin_h($navigationItem['href']); ?>"><?= maatlas_admin_h($navigationItem['label']); ?></a>
						<?php endif; ?>
						<?php if ($isCurrent && $navigationItem['href'] === '/admin/gallery.php' && $sidebarSubmenu !== []): ?>
						<div class="maatlas-admin-sidebar-submenu" id="maatlas-admin-gallery-submenu">
							<?php foreach ($sidebarSubmenu as $submenuItem): ?>
								<?php
								$submenuHref = (string) ($submenuItem['href'] ?? '#');
								$submenuLabel = (string) ($submenuItem['label'] ?? '');
								$submenuCurrent = !empty($submenuItem['is_current']);
								?>
								<a class="<?= $submenuCurrent ? 'is-current' : ''; ?>" href="<?= maatlas_admin_h($submenuHref); ?>"><?= maatlas_admin_h($submenuLabel); ?></a>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</nav>
			<a class="maatlas-admin-sidebar-logout" href="/admin/logout.php">Uitloggen</a>
		</div>
	</aside>
	<div class="maatlas-admin-content">
		<header class="maatlas-admin-page-header">
			<p class="maatlas-admin-eyebrow">Beheeromgeving</p>
			<h1><?= maatlas_admin_h($title); ?></h1>
		</header>
	<?php else: ?>
	<header class="maatlas-admin-header">
		<div>
			<p class="maatlas-admin-eyebrow">Beheeromgeving</p>
			<h1><?= maatlas_admin_h($title); ?></h1>
		</div>
		<?php if ($currentAdmin !== null): ?>
		<div class="maatlas-admin-header-side">
			<div class="maatlas-admin-current-user">
				<strong><?= maatlas_admin_h((string) $currentAdmin['full_name']); ?></strong>
				<span><?= maatlas_admin_h((string) $currentAdmin['role']); ?></span>
			</div>
			<nav class="maatlas-admin-nav" aria-label="Admin navigatie">
				<a href="/admin/">Dashboard</a>
				<a href="/admin/administrators.php">Beheerders</a>
				<a href="/admin/gallery.php">Galerij</a>
				<a href="/admin/mobile-upload.php">Mobiele upload</a>
				<a href="/admin/lastenboek.php">Lastenboek</a>
				<a href="/admin/settings.php">Instellingen</a>
				<a href="/admin/logout.php">Uitloggen</a>
			</nav>
		</div>
		<?php endif; ?>
	</header>
	<?php endif; ?>
	<main class="maatlas-admin-main">
	<?php
}

function maatlas_admin_render_footer(): void
{
	$hasSidebarLayout = !empty($GLOBALS['maatlas_admin_has_sidebar_layout']);
	?>
	</main>
<?php if ($hasSidebarLayout): ?>
</div>
<?php endif; ?>
</div>
<?php maatlas_admin_render_public_shell_footer(); ?>
<script>
(function () {
  var toggles = document.querySelectorAll('[data-password-toggle]');
  toggles.forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      var targetId = toggle.getAttribute('data-password-toggle');
      var input = targetId ? document.getElementById(targetId) : null;
      if (!input) {
        return;
      }

      var nextType = input.type === 'password' ? 'text' : 'password';
      input.type = nextType;
      toggle.textContent = nextType === 'password' ? 'Toon wachtwoord' : 'Verberg wachtwoord';
      toggle.setAttribute('aria-pressed', nextType === 'text' ? 'true' : 'false');
    });
  });
})();

(function () {
  var submenuToggle = document.querySelector('[data-submenu-toggle="gallery"]');
  var submenu = document.getElementById('maatlas-admin-gallery-submenu');
  if (!submenuToggle || !submenu) {
    return;
  }

  submenuToggle.addEventListener('click', function () {
    var isExpanded = submenuToggle.getAttribute('aria-expanded') === 'true';
    submenuToggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
    submenu.classList.toggle('is-collapsed', isExpanded);
  });
})();
</script>
</body>
</html>
	<?php
}
