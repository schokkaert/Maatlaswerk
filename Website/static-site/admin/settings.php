<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$currentAdmin = maatlas_admin_require_login();
$settings = maatlas_site_settings_load();
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = (string) ($_POST['csrf_token'] ?? '');
	$action = (string) ($_POST['action'] ?? '');

	if (!maatlas_admin_verify_csrf($token)) {
		$error = 'Ongeldige beveiligingstoken. Herlaad de pagina en probeer opnieuw.';
	} else {
		if ($action === 'save-layout') {
			$settings['accent_color'] = maatlas_site_sanitize_hex_color((string) ($_POST['accent_color'] ?? '#B0CD56'));
			$allowedPositions = [
				'top-left',
				'top-center',
				'top-right',
				'middle-left',
				'middle-center',
				'middle-right',
				'bottom-left',
				'bottom-center',
				'bottom-right',
			];
			$backToTopPosition = (string) ($_POST['back_to_top_position'] ?? 'bottom-right');
			if (!in_array($backToTopPosition, $allowedPositions, true)) {
				$backToTopPosition = 'bottom-right';
			}
			$backToTopMarginX = (int) ($_POST['back_to_top_margin_x'] ?? 24);
			$backToTopMarginY = (int) ($_POST['back_to_top_margin_y'] ?? 24);

			if ($backToTopMarginX < 8 || $backToTopMarginX > 240 || $backToTopMarginY < 8 || $backToTopMarginY > 240) {
				$error = 'De marges voor de ga-naar-boven-knop moeten tussen 8 en 240 pixels liggen.';
			} else {
				$settings['back_to_top_enabled'] = isset($_POST['back_to_top_enabled']);
				$settings['back_to_top_position'] = $backToTopPosition;
				$settings['back_to_top_margin_x'] = (string) $backToTopMarginX;
				$settings['back_to_top_margin_y'] = (string) $backToTopMarginY;
				maatlas_site_settings_save($settings);
				$message = 'Layout-instellingen opgeslagen.';
			}
		} else {
			$vatNumber = strtoupper(trim((string) ($_POST['vat_number'] ?? '')));
			$contactFormLive = isset($_POST['contact_form_live']);
			$contactTestEmail = trim((string) ($_POST['contact_test_email'] ?? ''));
			$contactRecipientEmail = trim((string) ($_POST['contact_recipient_email'] ?? ''));
			$contactSenderEmail = trim((string) ($_POST['contact_sender_email'] ?? ''));
			$publicContactEmail = trim((string) ($_POST['public_contact_email'] ?? ''));
			$publicPhone = trim((string) ($_POST['public_phone'] ?? ''));
			$publicAddress = trim((string) ($_POST['public_address'] ?? ''));
			$facebookUrl = trim((string) ($_POST['facebook_url'] ?? ''));
			$instagramUrl = trim((string) ($_POST['instagram_url'] ?? ''));
			$googleMapsAddress = trim((string) ($_POST['google_maps_address'] ?? ''));
			$privacyContactEmail = trim((string) ($_POST['privacy_contact_email'] ?? ''));
			$privacyRetentionMonths = trim((string) ($_POST['privacy_retention_months'] ?? '12'));

			if ($contactRecipientEmail === '' || !filter_var($contactRecipientEmail, FILTER_VALIDATE_EMAIL)) {
				$error = 'Geef een geldig ontvangstadres op voor het contactformulier.';
			} elseif ($contactTestEmail !== '' && !filter_var($contactTestEmail, FILTER_VALIDATE_EMAIL)) {
				$error = 'Het test-e-mailadres is niet geldig.';
			} elseif (!$contactFormLive && $contactTestEmail === '') {
				$error = 'Geef een test-e-mailadres op wanneer live verzenden uit staat.';
			} elseif ($contactSenderEmail === '' || !filter_var($contactSenderEmail, FILTER_VALIDATE_EMAIL)) {
				$error = 'Geef een geldig afzenderadres op voor websiteberichten.';
			} elseif ($publicContactEmail !== '' && !filter_var($publicContactEmail, FILTER_VALIDATE_EMAIL)) {
				$error = 'Het publieke contactadres is niet geldig.';
			} elseif ($facebookUrl !== '' && !filter_var($facebookUrl, FILTER_VALIDATE_URL)) {
				$error = 'De Facebook-link is niet geldig.';
			} elseif ($instagramUrl !== '' && !filter_var($instagramUrl, FILTER_VALIDATE_URL)) {
				$error = 'De Instagram-link is niet geldig.';
			} elseif ($privacyContactEmail === '' || !filter_var($privacyContactEmail, FILTER_VALIDATE_EMAIL)) {
				$error = 'Geef een geldig privacy-contactadres op.';
			} elseif ($googleMapsAddress === '') {
				$error = 'Geef een adres op voor Google Maps.';
			} elseif (!ctype_digit($privacyRetentionMonths) || (int) $privacyRetentionMonths < 1 || (int) $privacyRetentionMonths > 120) {
				$error = 'De bewaartermijn moet tussen 1 en 120 maanden liggen.';
			} else {
				$settings['vat_number'] = $vatNumber;
				$settings['contact_form_live'] = $contactFormLive;
				$settings['contact_test_email'] = $contactTestEmail;
				$settings['contact_recipient_email'] = $contactRecipientEmail;
				$settings['contact_sender_email'] = $contactSenderEmail;
				$settings['public_contact_email'] = $publicContactEmail;
				$settings['public_phone'] = $publicPhone;
				$settings['public_address'] = $publicAddress;
				$settings['facebook_url'] = $facebookUrl;
				$settings['instagram_url'] = $instagramUrl;
				$settings['google_maps_address'] = $googleMapsAddress;
				$settings['google_maps_embed_url'] = maatlas_site_google_maps_embed_url(['google_maps_address' => $googleMapsAddress]);
				$settings['privacy_contact_email'] = $privacyContactEmail;
				$settings['privacy_retention_months'] = $privacyRetentionMonths;
				maatlas_site_settings_save($settings);
				$message = 'Administratieve instellingen opgeslagen.';
			}
		}
	}
}

maatlas_admin_render_header('Instellingen', $currentAdmin);
?>
<?php if ($message !== null): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-success"><?= maatlas_admin_h($message); ?></p>
<?php endif; ?>
<?php if ($error !== null): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-error"><?= maatlas_admin_h($error); ?></p>
<?php endif; ?>

<section class="maatlas-admin-grid maatlas-admin-grid-wide">
	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Site-layout</p>
		<h2>Visuele instellingen</h2>
		<form method="post" class="maatlas-admin-form">
			<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
			<input type="hidden" name="action" value="save-layout">
			<label>
				<span>Accentkleur website</span>
				<input type="color" name="accent_color" value="<?= maatlas_admin_h(maatlas_site_accent_color($settings)); ?>">
			</label>
			<label class="maatlas-admin-checkbox">
				<input type="checkbox" name="back_to_top_enabled" value="1"<?= !empty($settings['back_to_top_enabled']) ? ' checked' : ''; ?>>
				<span>Toon ga-naar-boven-knop</span>
			</label>
			<fieldset class="maatlas-admin-fieldset">
				<legend>Positie op de pagina</legend>
				<p class="maatlas-admin-help">Kies een vakje in het 3x3-raster en stel daarna de horizontale en verticale marge in.</p>
				<div class="maatlas-admin-position-grid">
					<?php foreach ([
						'top-left' => 'Linksboven',
						'top-center' => 'Midden boven',
						'top-right' => 'Rechtsboven',
						'middle-left' => 'Links midden',
						'middle-center' => 'Centrum',
						'middle-right' => 'Rechts midden',
						'bottom-left' => 'Linksonder',
						'bottom-center' => 'Midden onder',
						'bottom-right' => 'Rechtsonder',
					] as $positionValue => $positionLabel): ?>
					<label class="maatlas-admin-position-option">
						<input type="radio" name="back_to_top_position" value="<?= maatlas_admin_h($positionValue); ?>" aria-label="<?= maatlas_admin_h($positionLabel); ?>" title="<?= maatlas_admin_h($positionLabel); ?>"<?= (($settings['back_to_top_position'] ?? 'bottom-right') === $positionValue) ? ' checked' : ''; ?>>
						<span aria-hidden="true"></span>
					</label>
					<?php endforeach; ?>
				</div>
			</fieldset>
			<div class="maatlas-admin-inline-grid">
				<label>
					<span>Marge X (px)</span>
					<input type="number" name="back_to_top_margin_x" min="8" max="240" value="<?= maatlas_admin_h((string) ($settings['back_to_top_margin_x'] ?? '24')); ?>">
				</label>
				<label>
					<span>Marge Y (px)</span>
					<input type="number" name="back_to_top_margin_y" min="8" max="240" value="<?= maatlas_admin_h((string) ($settings['back_to_top_margin_y'] ?? '24')); ?>">
				</label>
			</div>
			<div class="maatlas-admin-actions">
				<button type="submit" class="maatlas-admin-button">Opslaan</button>
			</div>
		</form>
	</article>

	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Administratief</p>
		<h2>Administratieve instellingen</h2>
		<form method="post" class="maatlas-admin-form">
			<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
			<input type="hidden" name="action" value="save-admin">
			<label>
				<span>BTW-nummer</span>
				<input type="text" name="vat_number" value="<?= maatlas_admin_h((string) ($settings['vat_number'] ?? '')); ?>" placeholder="BE 0123.456.789">
			</label>
			<label class="maatlas-admin-checkbox">
				<input type="checkbox" name="contact_form_live" value="1"<?= !empty($settings['contact_form_live']) ? ' checked' : ''; ?>>
				<span>Contactformulier live verzenden</span>
			</label>
			<p class="maatlas-admin-help">Aangevinkt: berichten worden verzonden naar het ontvangstadres. Uitgevinkt: berichten gaan alleen naar het test-e-mailadres.</p>
			<label>
				<span>Test-e-mailadres</span>
				<input type="email" name="contact_test_email" value="<?= maatlas_admin_h((string) ($settings['contact_test_email'] ?? '')); ?>">
			</label>
			<label>
				<span>Ontvangstadres contactformulier</span>
				<input type="email" name="contact_recipient_email" required value="<?= maatlas_admin_h((string) ($settings['contact_recipient_email'] ?? '')); ?>">
			</label>
			<label>
				<span>Afzenderadres website</span>
				<input type="email" name="contact_sender_email" required value="<?= maatlas_admin_h((string) ($settings['contact_sender_email'] ?? '')); ?>">
			</label>
			<label>
				<span>Publiek e-mailadres</span>
				<input type="email" name="public_contact_email" value="<?= maatlas_admin_h((string) ($settings['public_contact_email'] ?? '')); ?>">
			</label>
			<label>
				<span>Publiek telefoonnummer</span>
				<input type="text" name="public_phone" value="<?= maatlas_admin_h((string) ($settings['public_phone'] ?? '')); ?>">
			</label>
			<label>
				<span>Adres / locatie</span>
				<textarea name="public_address" rows="4"><?= maatlas_admin_h((string) ($settings['public_address'] ?? '')); ?></textarea>
			</label>
			<label>
				<span>Facebook-link</span>
				<input type="url" name="facebook_url" value="<?= maatlas_admin_h((string) ($settings['facebook_url'] ?? '')); ?>" placeholder="https://www.facebook.com/...">
			</label>
			<label>
				<span>Instagram-link</span>
				<input type="url" name="instagram_url" value="<?= maatlas_admin_h((string) ($settings['instagram_url'] ?? '')); ?>" placeholder="https://www.instagram.com/...">
			</label>
			<label>
				<span>Adres voor Google Maps</span>
				<input type="text" name="google_maps_address" value="<?= maatlas_admin_h((string) ($settings['google_maps_address'] ?? '')); ?>">
			</label>
			<label>
				<span>Privacy-contactadres</span>
				<input type="email" name="privacy_contact_email" required value="<?= maatlas_admin_h((string) ($settings['privacy_contact_email'] ?? '')); ?>">
			</label>
			<label>
				<span>Bewaartermijn contactaanvragen in maanden</span>
				<input type="number" name="privacy_retention_months" min="1" max="120" value="<?= maatlas_admin_h((string) ($settings['privacy_retention_months'] ?? '12')); ?>">
			</label>
			<div class="maatlas-admin-actions">
				<button type="submit" class="maatlas-admin-button">Opslaan</button>
			</div>
		</form>
	</article>

	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Gebruik</p>
		<h2>Waarvoor worden deze waarden gebruikt?</h2>
		<p>De layout-instellingen sturen de visuele opmaak van de website, zoals het accentkleurgebruik in de navigatie, andere interface-elementen en de positie van de ga-naar-boven-knop.</p>
		<p>De administratieve instellingen sturen het ontvangstadres van het contactformulier, testmodus of live status, de publieke contactgegevens, het btw-nummer in de footer, Google Maps en de privacyteksten.</p>
		<p>De privacy-instellingen voeden automatisch de privacyverklaring, het cookiebeleid en de GDPR-melding onder het contactformulier.</p>
	</article>
</section>

<?php
maatlas_admin_render_footer();
