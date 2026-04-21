<?php
declare(strict_types=1);

require __DIR__ . '/../includes/site-settings.php';

header('Content-Type: application/manifest+json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

echo json_encode([
	'name' => 'W&S Maatlaswerk Mobiele Upload',
	'short_name' => 'Upload',
	'description' => "Snelle smartphone-upload voor galerijfoto's van W&S Maatlaswerk.",
	'start_url' => maatlas_site_url('/admin/mobile-upload.php'),
	'scope' => maatlas_site_url('/admin/'),
	'display' => 'standalone',
	'background_color' => '#ffffff',
	'theme_color' => '#B0CD56',
	'icons' => [
		[
			'src' => maatlas_site_url('/assets/uploads/static/MaatLasWerk-13.jpg'),
			'sizes' => '192x192',
			'type' => 'image/jpeg',
		],
		[
			'src' => maatlas_site_url('/assets/uploads/static/MaatLasWerk-13-150x150.jpg'),
			'sizes' => '150x150',
			'type' => 'image/jpeg',
		],
	],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
