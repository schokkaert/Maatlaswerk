<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$currentAdmin = maatlas_admin_require_login();
$admins = maatlas_admin_load();
$activeAdmins = array_values(array_filter($admins, static fn(array $admin): bool => !empty($admin['active'])));
$hasRealAdmin = !maatlas_admin_is_initial_setup_required();
$showFirstAccessNotice = !$hasRealAdmin;
$setupCompleted = (string) ($_GET['setup'] ?? '') === 'complete';
$activationCompleted = (string) ($_GET['activated'] ?? '') === '1';
$mobileUploadUrl = maatlas_admin_current_host_url('/admin/mobile-upload.php');
$mobileUploadQrUrl = maatlas_admin_qr_image_url($mobileUploadUrl);

maatlas_admin_render_header('Dashboard', $currentAdmin);
?>
<?php if ($setupCompleted): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-success">De nieuwe beheerder is aangemaakt en de tijdelijke account <strong>admin</strong> is verwijderd.</p>
<?php endif; ?>
<?php if ($activationCompleted): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-success">Je account is geactiveerd. Je bent nu aangemeld.</p>
<?php endif; ?>
<?php if (!empty($currentAdmin['is_temporary']) && $showFirstAccessNotice): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-error">Je bent ingelogd met de tijdelijke setup-account. Maak eerst een nieuwe administrator aan. De tijdelijke account wordt daarna automatisch verwijderd.</p>
<?php endif; ?>

<section class="maatlas-admin-grid">
	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Overzicht</p>
		<h2><?= count($admins); ?> administrator<?= count($admins) === 1 ? '' : 's'; ?></h2>
		<p>De adminomgeving werkt lokaal met PHP-sessies en een interne opslagfile. Gebruik dit scherm om administrators toe te voegen, te wijzigen of te deactiveren.</p>
	</article>
	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Actief</p>
		<h2><?= count($activeAdmins); ?> actief</h2>
		<p>Er moet altijd minstens één actieve administrator overblijven. Het systeem blokkeert het uitschakelen van de laatste actieve admin.</p>
	</article>
	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Beheer</p>
		<h2>Administrators</h2>
		<p>Beheer gebruikersnamen, e-mailadressen, rollen, status en wachtwoorden vanuit één beheerscherm.</p>
		<p><a class="maatlas-admin-button" href="/admin/administrators.php">Open administratorbeheer</a></p>
	</article>
	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Galerij</p>
		<h2>Foto&apos;s en albums</h2>
		<p>Upload afbeeldingen rechtstreeks naar <strong>assets/uploads</strong>, roteer ze met preview en deel ze in via albums.</p>
		<p><a class="maatlas-admin-button" href="/admin/gallery.php">Open galerijbeheer</a></p>
	</article>
	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Mobiel</p>
		<h2>Snelle foto-upload</h2>
		<p>Gebruik een eenvoudige pagina op gsm om een foto te nemen, een album te kiezen en die meteen gecomprimeerd naar de site te uploaden.</p>
		<p><a class="maatlas-admin-button" href="/admin/mobile-upload.php">Open mobiele upload</a></p>
		<div class="maatlas-mobile-upload-access">
			<img src="<?= maatlas_admin_h($mobileUploadQrUrl); ?>" alt="QR-code voor mobiele uploadpagina">
			<div>
				<p><strong>Open op smartphone</strong></p>
				<p><a href="<?= maatlas_admin_h($mobileUploadUrl); ?>" target="_blank" rel="noopener noreferrer"><?= maatlas_admin_h($mobileUploadUrl); ?></a></p>
			</div>
		</div>
	</article>
	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Instellingen</p>
		<h2>Contact, GDPR en cookies</h2>
		<p>Beheer het ontvangstadres van het contactformulier, publieke contactgegevens, Google Maps en de privacy-instellingen van de website.</p>
		<p><a class="maatlas-admin-button" href="/admin/settings.php">Open instellingen</a></p>
	</article>
	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Lastenboek</p>
		<h2>Specificaties en posten</h2>
		<p>Beheer de documentkop, rubrieken en technische posten van het lastenboek vanuit één apart beheerscherm.</p>
		<p><a class="maatlas-admin-button" href="/admin/lastenboek.php">Open lastenboek</a></p>
	</article>
</section>

<?php if ($showFirstAccessNotice): ?>
<section class="maatlas-admin-card">
	<p class="maatlas-admin-eyebrow">Inloggegevens</p>
	<h2>Eerste toegang</h2>
	<p>Tijdelijke login: <strong>admin</strong></p>
	<p>Tijdelijk wachtwoord: <strong>admin</strong></p>
	<p>Gebruik deze account alleen voor de eerste login. Maak meteen een nieuwe administrator aan; daarna wordt de tijdelijke account automatisch verwijderd.</p>
</section>
<?php endif; ?>
<?php
maatlas_admin_render_footer();
