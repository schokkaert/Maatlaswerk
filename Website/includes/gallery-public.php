<?php

const MAATLAS_PUBLIC_GALLERY_CATEGORIES_STORAGE = __DIR__ . '/../admin/storage/gallery_categories.php';
const MAATLAS_PUBLIC_GALLERY_MEDIA_STORAGE = __DIR__ . '/../admin/storage/gallery_media.php';
const MAATLAS_PUBLIC_UPLOADS_DIR = __DIR__ . '/../assets/uploads';

function maatlas_public_str_starts_with($haystack, $needle)
{
	if ($needle === '') {
		return true;
	}

	return substr($haystack, 0, strlen($needle)) === $needle;
}

function maatlas_public_str_contains($haystack, $needle)
{
	if ($needle === '') {
		return true;
	}

	return strpos($haystack, $needle) !== false;
}

function maatlas_public_load_php_array($path)
{
	if (!is_file($path)) {
		return [];
	}

	$data = require $path;
	return is_array($data) ? $data : [];
}

function maatlas_public_slugify($value)
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

function maatlas_public_normalize_relative_path($path)
{
	$path = trim(str_replace('\\', '/', $path), '/');
	if ($path === '') {
		return '';
	}

	$parts = array_values(array_filter(explode('/', $path), static function ($part) {
		return $part !== '';
	}));
	foreach ($parts as $part) {
		if ($part === '.' || $part === '..') {
			return '';
		}
	}

	return implode('/', $parts);
}

function maatlas_public_media_url($relativePath)
{
	$relativePath = maatlas_public_normalize_relative_path($relativePath);
	$segments = array_map('rawurlencode', explode('/', $relativePath));
	return '/assets/uploads/' . implode('/', $segments);
}

function maatlas_public_resized_variant_meta($filename)
{
	$stem = strtolower((string) pathinfo($filename, PATHINFO_FILENAME));

	if (preg_match('/^(.*)-(\d+)x(\d+)$/', $stem, $matches) === 1) {
		return [
			'group_stem' => trim((string) $matches[1], '-'),
			'is_resized' => true,
			'area' => (int) $matches[2] * (int) $matches[3],
		];
	}

	return [
		'group_stem' => $stem,
		'is_resized' => false,
		'area' => 0,
	];
}

function maatlas_public_filter_preferred_media(array $mediaItems)
{
	$grouped = [];

	foreach ($mediaItems as $media) {
		$directory = strtolower((string) ($media['directory'] ?? ''));
		$meta = maatlas_public_resized_variant_meta((string) ($media['filename'] ?? ''));
		$groupKey = $directory . '|' . (string) $meta['group_stem'];
		$media['_variant_meta'] = $meta;
		$grouped[$groupKey][] = $media;
	}

	$preferred = [];
	foreach ($grouped as $items) {
		usort($items, static function (array $left, array $right) {
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
		$preferred[] = $winner;
	}

	usort($preferred, static function (array $left, array $right) {
		return strcmp((string) $right['modified_at'], (string) $left['modified_at']);
	});

	return $preferred;
}

function maatlas_public_gallery_categories()
{
	$categories = maatlas_public_load_php_array(MAATLAS_PUBLIC_GALLERY_CATEGORIES_STORAGE);
	$byId = [];
	$bySlug = [];

	foreach ($categories as $category) {
		$id = (string) ($category['id'] ?? '');
		$slug = (string) ($category['slug'] ?? '');
		if ($id === '' || $slug === '') {
			continue;
		}

		$byId[$id] = $category;
		$bySlug[$slug] = $category;
	}

	return ['all' => array_values($byId), 'by_id' => $byId, 'by_slug' => $bySlug];
}

function maatlas_public_gallery_media()
{
	$mediaItems = maatlas_public_load_php_array(MAATLAS_PUBLIC_GALLERY_MEDIA_STORAGE);
	$valid = [];

	foreach ($mediaItems as $media) {
		$relativePath = maatlas_public_normalize_relative_path((string) ($media['relative_path'] ?? ''));
		if ($relativePath === '' || maatlas_public_str_starts_with($relativePath, 'static/')) {
			continue;
		}

		$absolutePath = MAATLAS_PUBLIC_UPLOADS_DIR . '/' . $relativePath;
		if (!is_file($absolutePath)) {
			continue;
		}

		$directory = dirname($relativePath);
		$valid[] = [
			'id' => (string) ($media['id'] ?? ''),
			'relative_path' => $relativePath,
			'directory' => $directory === '.' ? '' : $directory,
			'filename' => basename($relativePath),
			'url' => maatlas_public_media_url($relativePath),
			'title' => trim((string) ($media['title'] ?? '')),
			'category_ids' => array_values(array_map('strval', (array) ($media['category_ids'] ?? []))),
			'latitude' => (string) ($media['latitude'] ?? ''),
			'longitude' => (string) ($media['longitude'] ?? ''),
			'google_maps_url' => trim((string) ($media['google_maps_url'] ?? '')),
			'filesize' => is_file($absolutePath) ? (int) filesize($absolutePath) : 0,
			'uploaded_at' => (string) ($media['uploaded_at'] ?? ''),
			'modified_at' => (string) ($media['modified_at'] ?? ''),
		];
	}

	return maatlas_public_filter_preferred_media($valid);
}

function maatlas_public_filter_media_by_category_slugs(array $mediaItems, array $categories, array $slugs)
{
	$wantedIds = [];
	foreach ($slugs as $slug) {
		$normalized = maatlas_public_slugify($slug);
		if ($normalized !== '' && isset($categories['by_slug'][$normalized]['id'])) {
			$wantedIds[] = (string) $categories['by_slug'][$normalized]['id'];
		}
	}

	if ($wantedIds === []) {
		return [];
	}

	return array_values(array_filter($mediaItems, static function (array $media) use ($wantedIds) {
		foreach ((array) $media['category_ids'] as $categoryId) {
			if (in_array((string) $categoryId, $wantedIds, true)) {
				return true;
			}
		}
		return false;
	}));
}

function maatlas_public_filter_media_by_directory_keywords(array $mediaItems, array $keywords)
{
	$matches = [];
	foreach ($mediaItems as $media) {
		$directory = maatlas_public_slugify((string) ($media['directory'] ?? ''));
		foreach ($keywords as $keyword) {
			$needle = maatlas_public_slugify($keyword);
			if ($needle !== '' && maatlas_public_str_contains($directory, $needle)) {
				$matches[] = $media;
				break;
			}
		}
	}

	return $matches;
}

function maatlas_public_filter_media_by_directory_prefixes(array $mediaItems, array $prefixes)
{
	$normalizedPrefixes = [];
	foreach ($prefixes as $prefix) {
		$normalizedPrefix = maatlas_public_normalize_relative_path((string) $prefix);
		if ($normalizedPrefix !== '') {
			$normalizedPrefixes[] = strtolower($normalizedPrefix);
		}
	}

	if ($normalizedPrefixes === []) {
		return [];
	}

	return array_values(array_filter($mediaItems, static function (array $media) use ($normalizedPrefixes) {
		$directory = maatlas_public_normalize_relative_path((string) ($media['directory'] ?? ''));
		$directory = strtolower($directory);

		foreach ($normalizedPrefixes as $prefix) {
			if ($directory === $prefix || maatlas_public_str_starts_with($directory, $prefix . '/')) {
				return true;
			}
		}

		return false;
	}));
}

function maatlas_public_label_for_media(array $media, array $categories)
{
	foreach ((array) ($media['category_ids'] ?? []) as $categoryId) {
		if (isset($categories['by_id'][(string) $categoryId]['name'])) {
			return (string) $categories['by_id'][(string) $categoryId]['name'];
		}
	}

	$name = pathinfo((string) ($media['filename'] ?? ''), PATHINFO_FILENAME);
	$name = str_replace(['-', '_'], ' ', $name);
	$name = preg_replace('/\s+/', ' ', $name) ?? $name;
	return ucwords(trim($name));
}

function maatlas_public_pick_random_media(array $mediaItems)
{
	if ($mediaItems === []) {
		return null;
	}

	return $mediaItems[array_rand($mediaItems)];
}

function maatlas_public_take_unique_media(array $mediaItems, $limit, array $excludePaths = [])
{
	$selected = [];
	$used = array_fill_keys($excludePaths, true);

	foreach ($mediaItems as $media) {
		$relativePath = (string) ($media['relative_path'] ?? '');
		if ($relativePath === '' || isset($used[$relativePath])) {
			continue;
		}

		$selected[] = $media;
		$used[$relativePath] = true;

		if (count($selected) >= $limit) {
			break;
		}
	}

	return $selected;
}

function maatlas_public_media_by_category_id(array $mediaItems, $categoryId)
{
	if ($categoryId === '') {
		return [];
	}

	return array_values(array_filter($mediaItems, static function (array $media) use ($categoryId) {
		return in_array($categoryId, array_map('strval', (array) ($media['category_ids'] ?? [])), true);
	}));
}

function maatlas_public_category_detail_url($categorySlug)
{
	$categorySlug = maatlas_public_slugify($categorySlug);
	return '/services/detail.php?category=' . rawurlencode($categorySlug);
}

function maatlas_public_pick_random_item(array $items)
{
	if ($items === []) {
		return [];
	}

	$maxIndex = count($items) - 1;
	try {
		$randomIndex = random_int(0, $maxIndex);
	} catch (Throwable $exception) {
		$randomIndex = mt_rand(0, $maxIndex);
	}

	return (array) ($items[$randomIndex] ?? $items[0]);
}

function maatlas_public_gallery_category_entries(array $mediaItems, array $categories, $limit = 0)
{
	$entries = [];

	foreach ((array) ($categories['all'] ?? []) as $category) {
		$categoryId = (string) ($category['id'] ?? '');
		$categorySlug = (string) ($category['slug'] ?? '');
		$categoryName = trim((string) ($category['name'] ?? ''));
		if ($categoryId === '' || $categorySlug === '' || $categoryName === '') {
			continue;
		}

		$categoryMedia = maatlas_public_media_by_category_id($mediaItems, $categoryId);
		if ($categoryMedia === []) {
			continue;
		}

		$coverMedia = maatlas_public_pick_random_item($categoryMedia);
		$entries[] = [
			'id' => $categoryId,
			'slug' => $categorySlug,
			'name' => $categoryName,
			'description' => trim((string) ($category['description'] ?? '')),
			'detail_url' => maatlas_public_category_detail_url($categorySlug),
			'cover_media' => $coverMedia,
			'media_items' => $categoryMedia,
			'media_count' => count($categoryMedia),
			'latest_modified_at' => (string) ($coverMedia['modified_at'] ?? ''),
		];
	}

	usort($entries, static function (array $left, array $right): int {
		$dateCompare = strcmp((string) ($right['latest_modified_at'] ?? ''), (string) ($left['latest_modified_at'] ?? ''));
		if ($dateCompare !== 0) {
			return $dateCompare;
		}

		return strnatcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
	});

	if ($limit > 0) {
		return array_slice($entries, 0, $limit);
	}

	return $entries;
}

function maatlas_public_find_category_by_slug(array $categories, $slug)
{
	$slug = maatlas_public_slugify($slug);
	if ($slug === '' || !isset($categories['by_slug'][$slug])) {
		return null;
	}

	return (array) $categories['by_slug'][$slug];
}

function maatlas_public_select_single_media(array $mediaItems, array $categories, array $preferredCategorySlugs, array $preferredDirectoryKeywords, array $excludePaths = [])
{
	$candidateGroups = [
		maatlas_public_filter_media_by_category_slugs($mediaItems, $categories, $preferredCategorySlugs),
		$mediaItems,
	];

	foreach ($candidateGroups as $group) {
		foreach ($group as $media) {
			$relativePath = (string) ($media['relative_path'] ?? '');
			if ($relativePath === '' || in_array($relativePath, $excludePaths, true)) {
				continue;
			}
			return $media;
		}
	}

	return null;
}

function maatlas_public_register_usage(array &$usageMap, $relativePath, $href, $label, $isPotential = false)
{
	$relativePath = maatlas_public_normalize_relative_path($relativePath);
	if ($relativePath === '') {
		return;
	}

	if (!isset($usageMap[$relativePath])) {
		$usageMap[$relativePath] = [];
	}

	$entryKey = $href . '|' . $label . '|' . ($isPotential ? '1' : '0');
	$usageMap[$relativePath][$entryKey] = [
		'href' => $href,
		'label' => $label,
		'is_potential' => $isPotential,
	];
}

function maatlas_public_gallery_usage_map($mediaItems = null, $categories = null)
{
	$mediaItems = $mediaItems ?? maatlas_public_gallery_media();
	$categories = $categories ?? maatlas_public_gallery_categories();
	$usageMap = [];

	$projectMedia = $mediaItems;

	foreach ($projectMedia as $media) {
		$relativePath = (string) ($media['relative_path'] ?? '');
		maatlas_public_register_usage($usageMap, $relativePath, '/', 'Home hero', true);
	}

	$homeShowcaseMedia = maatlas_public_filter_media_by_category_slugs($mediaItems, $categories, ['home', 'homepage', 'uitgelicht', 'featured']);
	if ($homeShowcaseMedia === []) {
		$homeShowcaseMedia = $projectMedia;
	}

	foreach (maatlas_public_take_unique_media($homeShowcaseMedia, 6) as $media) {
		maatlas_public_register_usage($usageMap, (string) ($media['relative_path'] ?? ''), '/', 'Home realisaties');
	}

	$aboutMedia = maatlas_public_filter_media_by_category_slugs($mediaItems, $categories, ['over-ons', 'about', 'realisaties', 'projecten']);
	if ($aboutMedia === []) {
		$aboutMedia = $mediaItems;
	}

	foreach (maatlas_public_take_unique_media($aboutMedia, 6) as $media) {
		maatlas_public_register_usage($usageMap, (string) ($media['relative_path'] ?? ''), '/about/', 'Over ons galerij');
	}

	foreach (maatlas_public_gallery_category_entries($mediaItems, $categories) as $categoryEntry) {
		$coverMedia = (array) ($categoryEntry['cover_media'] ?? []);
		$coverPath = (string) ($coverMedia['relative_path'] ?? '');
		if ($coverPath !== '') {
			maatlas_public_register_usage(
				$usageMap,
				$coverPath,
				'/services/',
				'Diensten overzicht: ' . (string) ($categoryEntry['name'] ?? '')
			);
		}

		foreach ((array) ($categoryEntry['media_items'] ?? []) as $media) {
			maatlas_public_register_usage(
				$usageMap,
				(string) ($media['relative_path'] ?? ''),
				(string) ($categoryEntry['detail_url'] ?? '/services/'),
				'Diensten: ' . (string) ($categoryEntry['name'] ?? '')
			);
		}
	}

	foreach ($usageMap as $relativePath => $entries) {
		$usageMap[$relativePath] = array_values($entries);
		usort($usageMap[$relativePath], static function (array $left, array $right): int {
			if ((bool) $left['is_potential'] !== (bool) $right['is_potential']) {
				return (bool) $left['is_potential'] ? 1 : -1;
			}

			return strcmp((string) $left['label'], (string) $right['label']);
		});
	}

	return $usageMap;
}
