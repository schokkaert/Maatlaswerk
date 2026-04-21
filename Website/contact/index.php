<?php

require __DIR__ . '/../includes/site-settings.php';

$settings = maatlas_site_settings_load();
$publicContactEmail = trim((string) ($settings['public_contact_email'] ?? ''));
$publicPhone = trim((string) ($settings['public_phone'] ?? ''));
$publicAddress = trim((string) ($settings['public_address'] ?? ''));
$contactFormLive = !empty($settings['contact_form_live']);
$contactTestEmail = trim((string) ($settings['contact_test_email'] ?? ''));
$recipientEmail = trim((string) ($settings['contact_recipient_email'] ?? ''));
$senderEmail = trim((string) ($settings['contact_sender_email'] ?? ''));
$privacyContactEmail = trim((string) ($settings['privacy_contact_email'] ?? $publicContactEmail));
$privacyRetentionMonths = max(1, (int) ($settings['privacy_retention_months'] ?? 12));
$mapEmbedUrl = maatlas_site_google_maps_embed_url($settings);
$mapAddress = trim((string) ($settings['google_maps_address'] ?? $publicAddress));

$formData = [
	'name' => '',
	'email' => '',
	'phone' => '',
	'subject' => '',
	'message' => '',
];
$successMessage = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$formData['name'] = trim((string) ($_POST['name'] ?? ''));
	$formData['email'] = trim((string) ($_POST['email'] ?? ''));
	$formData['phone'] = trim((string) ($_POST['phone'] ?? ''));
	$formData['subject'] = trim((string) ($_POST['subject'] ?? ''));
	$formData['message'] = trim((string) ($_POST['message'] ?? ''));
	$privacyAccepted = isset($_POST['privacy_consent']);
	$honeypot = trim((string) ($_POST['website'] ?? ''));

	if ($honeypot !== '') {
		$successMessage = 'Bedankt. Jouw bericht werd ontvangen.';
	} elseif ($formData['name'] === '' || $formData['email'] === '' || $formData['message'] === '') {
		$errorMessage = 'Naam, e-mail en bericht zijn verplicht.';
	} elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
		$errorMessage = 'Geef een geldig e-mailadres op.';
	} elseif (!$privacyAccepted) {
		$errorMessage = 'Bevestig eerst dat je de privacyverklaring hebt gelezen.';
	} else {
		$targetEmail = $contactFormLive ? $recipientEmail : $contactTestEmail;
		if ($targetEmail === '' || !filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
		$errorMessage = 'Het ontvangstadres voor contactberichten is nog niet correct ingesteld in de admin.';
		} else {
		$cleanSubject = preg_replace('/[\r\n]+/', ' ', $formData['subject']) ?? '';
		$currentHost = trim((string) ($_SERVER['HTTP_HOST'] ?? 'de website'));
		$mailSubject = 'Nieuwe contactaanvraag via ' . ($currentHost !== '' ? $currentHost : 'de website');
		if ($cleanSubject !== '') {
			$mailSubject .= ' - ' . $cleanSubject;
		}

		$headerSender = filter_var($senderEmail, FILTER_VALIDATE_EMAIL) ? $senderEmail : $targetEmail;
		$replyTo = $formData['email'];
		$mailHeaders = [
			'MIME-Version: 1.0',
			'Content-Type: text/plain; charset=UTF-8',
			'From: W&S Maatlaswerk <' . $headerSender . '>',
			'Reply-To: ' . $replyTo,
		];

		$mailBodyLines = [
			'Nieuwe contactaanvraag via de website',
			'',
			'Naam: ' . $formData['name'],
			'E-mail: ' . $formData['email'],
			'Telefoon: ' . ($formData['phone'] !== '' ? $formData['phone'] : '-'),
			'Onderwerp: ' . ($cleanSubject !== '' ? $cleanSubject : '-'),
			'',
			'Bericht:',
			$formData['message'],
			'',
			'Verzonden op: ' . date('d/m/Y H:i'),
			'IP-adres: ' . (string) ($_SERVER['REMOTE_ADDR'] ?? 'onbekend'),
		];
		if (!$contactFormLive) {
			$mailBodyLines[] = 'Status formulier: TESTMODUS';
		}

		$mailSent = mail($targetEmail, $mailSubject, implode("\n", $mailBodyLines), implode("\r\n", $mailHeaders));
		if ($mailSent) {
			$successMessage = 'Bedankt. Jouw bericht werd verzonden.';
			$formData = [
				'name' => '',
				'email' => '',
				'phone' => '',
				'subject' => '',
				'message' => '',
			];
		} else {
			$errorMessage = 'Het bericht kon niet verzonden worden. Probeer later opnieuw of neem rechtstreeks contact op.';
		}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="nl-BE">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Contact | W&amp;S Maatlaswerk</title>
	<meta name="description" content="Neem contact op met W&amp;S Maatlaswerk in Kluisbergen voor maatwerk in staal, inox, aluminium en glas.">
	<link rel="stylesheet" href="/assets/themes/bluehost-blueprint/style.css?ver=2.0.4">
	<?php maatlas_site_render_theme_style($settings); ?>
	<link rel="icon" href="/assets/uploads/static/MaatLasWerk-13-150x150.jpg" sizes="32x32">
	<link rel="icon" href="/assets/uploads/static/MaatLasWerk-13.jpg" sizes="192x192">
	<link rel="apple-touch-icon" href="/assets/uploads/static/MaatLasWerk-13.jpg">
	<meta name="msapplication-TileImage" content="/assets/uploads/static/MaatLasWerk-13.jpg">
</head>
<body>
<div class="site-site-blocks">
	<main class="maatlas-main maatlas-contact-page">
		<section class="maatlas-page-section">
			<div class="maatlas-contact-card">
				<p class="maatlas-eyebrow">Contact</p>
				<h1>Bespreek jouw project in staal.</h1>
				<p class="maatlas-lead">Van stalen ramen en deuren tot balustrades, poorten en verfijnd maatwerk: we bekijken graag samen wat technisch en esthetisch het beste werkt.</p>
			</div>
		</section>

		<section class="maatlas-page-section">
			<div class="maatlas-contact-layout">
				<article class="maatlas-contact-panel maatlas-contact-form-panel">
					<p class="maatlas-eyebrow">Invulformulier</p>
					<h2>Vertel ons wat je wil laten maken.</h2>
					<p>Geef hieronder jouw gegevens en een korte omschrijving van het project door. Voeg gerust afmetingen, stijlwensen of timing toe.</p>
					<?php if ($successMessage !== null): ?>
					<p class="maatlas-public-alert maatlas-public-alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
					<?php endif; ?>
					<?php if ($errorMessage !== null): ?>
					<p class="maatlas-public-alert maatlas-public-alert-error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
					<?php endif; ?>
					<form class="maatlas-contact-form" action="/contact/index.php" method="post">
						<div class="maatlas-form-grid">
							<label class="maatlas-form-field">
								<span>Naam</span>
								<input type="text" name="name" autocomplete="name" placeholder="Jouw naam" required value="<?= htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8'); ?>">
							</label>
							<label class="maatlas-form-field">
								<span>E-mail</span>
								<input type="email" name="email" autocomplete="email" placeholder="jij@email.be" required value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>">
							</label>
							<label class="maatlas-form-field">
								<span>Telefoon</span>
								<input type="tel" name="phone" autocomplete="tel" placeholder="Optioneel" value="<?= htmlspecialchars($formData['phone'], ENT_QUOTES, 'UTF-8'); ?>">
							</label>
							<label class="maatlas-form-field">
								<span>Onderwerp</span>
								<input type="text" name="subject" placeholder="Bijvoorbeeld stalen binnendeur" value="<?= htmlspecialchars($formData['subject'], ENT_QUOTES, 'UTF-8'); ?>">
							</label>
						</div>
						<label class="maatlas-form-field maatlas-form-field-hidden" aria-hidden="true">
							<span>Website</span>
							<input type="text" name="website" tabindex="-1" autocomplete="off">
						</label>
						<label class="maatlas-form-field">
							<span>Bericht</span>
							<textarea name="message" rows="7" placeholder="Omschrijf kort jouw project, afmetingen, stijl of gewenste uitvoering." required><?= htmlspecialchars($formData['message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
						</label>
						<div class="maatlas-contact-privacy-box">
							<strong>Privacy bij jouw aanvraag</strong>
							<p>We gebruiken jouw gegevens alleen om je aanvraag te behandelen. Meer uitleg vind je in onze <a href="/privacy/">privacyverklaring</a> en het <a href="/cookies/">cookiebeleid</a>.</p>
						</div>
						<label class="maatlas-contact-consent">
							<input type="checkbox" name="privacy_consent" value="1" required>
							<span>Ik heb de <a href="/privacy/">privacyverklaring</a> gelezen en geef toestemming om mijn gegevens te gebruiken voor het beantwoorden van mijn aanvraag.</span>
						</label>
						<p class="maatlas-contact-legal-note">We gebruiken jouw gegevens alleen om jouw aanvraag te behandelen en bewaren contactaanvragen maximaal <?= $privacyRetentionMonths; ?> maanden, tenzij een lopende opdracht of wettelijke verplichting een langere bewaring vereist. Voor privacyvragen kan je terecht via <a href="mailto:<?= htmlspecialchars($privacyContactEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($privacyContactEmail, ENT_QUOTES, 'UTF-8'); ?></a>.</p>
						<div class="maatlas-button-row maatlas-contact-form-actions">
							<button class="maatlas-button" type="submit">Verzenden</button>
						</div>
					</form>
				</article>

				<div class="maatlas-contact-side">
					<article class="maatlas-contact-panel maatlas-contact-map-card">
						<p class="maatlas-eyebrow">Locatie</p>
						<h2><?= htmlspecialchars($mapAddress !== '' ? $mapAddress : 'Regio Kluisbergen', ENT_QUOTES, 'UTF-8'); ?></h2>
						<div class="maatlas-contact-map-frame">
							<iframe src="<?= htmlspecialchars($mapEmbedUrl, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Google Maps kaart"></iframe>
						</div>
						<p class="maatlas-contact-legal-note">Op deze pagina is een Google Maps-kaart ingesloten. Daardoor kan Google technische gegevens verwerken zodra de kaart geladen wordt. Meer info vind je in onze <a href="/privacy/">privacyverklaring</a> en het <a href="/cookies/">cookiebeleid</a>.</p>
					</article>

					<div class="maatlas-contact-grid">
						<article class="maatlas-contact-panel">
							<h2>Hoe starten we?</h2>
							<p>Stuur ons enkele foto's, afmetingen of een plan door. Op basis daarvan denken we mee over uitvoering, profilering en afwerking.</p>
						</article>
						<article class="maatlas-contact-panel">
							<h2>Rechtstreeks bereikbaar</h2>
							<?php if ($publicContactEmail !== ''): ?>
							<p><strong>E-mail</strong><br><a href="mailto:<?= htmlspecialchars($publicContactEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($publicContactEmail, ENT_QUOTES, 'UTF-8'); ?></a></p>
							<?php endif; ?>
							<?php if ($publicPhone !== ''): ?>
							<p><strong>Telefoon</strong><br><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $publicPhone) ?? $publicPhone, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($publicPhone, ENT_QUOTES, 'UTF-8'); ?></a></p>
							<?php endif; ?>
							<?php if ($publicAddress !== ''): ?>
							<p><strong>Adres</strong><br><?= nl2br(htmlspecialchars($publicAddress, ENT_QUOTES, 'UTF-8')); ?></p>
							<?php endif; ?>
						</article>
					</div>
				</div>
			</div>
		</section>
	</main>
</div>
<?php maatlas_site_render_public_runtime_settings($settings); ?>
<script src="/assets/themes/bluehost-blueprint/site-shell.js?v=20260421-3"></script>
</body>
</html>
