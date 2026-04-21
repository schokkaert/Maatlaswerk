<?php

require __DIR__ . '/../includes/site-settings.php';

$settings = maatlas_site_settings_load();
$publicContactEmail = trim((string) ($settings['public_contact_email'] ?? ''));
$privacyContactEmail = trim((string) ($settings['privacy_contact_email'] ?? $publicContactEmail));
$publicPhone = trim((string) ($settings['public_phone'] ?? ''));
$publicAddress = trim((string) ($settings['public_address'] ?? ''));
$privacyRetentionMonths = max(1, (int) ($settings['privacy_retention_months'] ?? 12));
?>
<!DOCTYPE html>
<html lang="nl-BE">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Privacyverklaring | W&amp;S Maatlaswerk</title>
	<meta name="description" content="Privacyverklaring van W&amp;S Maatlaswerk over de verwerking van persoonsgegevens via de website en het contactformulier.">
	<link rel="stylesheet" href="/assets/themes/bluehost-blueprint/style.css?ver=2.0.2">
	<?php maatlas_site_render_theme_style($settings); ?>
	<link rel="icon" href="/assets/uploads/static/MaatLasWerk-13-150x150.jpg" sizes="32x32">
	<link rel="icon" href="/assets/uploads/static/MaatLasWerk-13.jpg" sizes="192x192">
</head>
<body>
<div class="site-site-blocks">
	<main class="maatlas-main">
		<section class="maatlas-page-section">
			<div class="maatlas-contact-card">
				<p class="maatlas-eyebrow">Privacy</p>
				<h1>Privacyverklaring</h1>
				<p class="maatlas-lead">Hier lees je hoe W&amp;S Maatlaswerk persoonsgegevens verwerkt via deze website en het contactformulier.</p>
			</div>
		</section>

		<section class="maatlas-page-section">
			<div class="maatlas-legal-grid">
				<article class="maatlas-contact-panel">
					<h2>Wie verwerkt jouw gegevens?</h2>
					<p>Verwerkingsverantwoordelijke: <strong>W&amp;S Maatlaswerk</strong>.</p>
					<?php if ($publicAddress !== ''): ?>
					<p><strong>Adres</strong><br><?= nl2br(htmlspecialchars($publicAddress, ENT_QUOTES, 'UTF-8')); ?></p>
					<?php endif; ?>
					<?php if ($publicContactEmail !== ''): ?>
					<p><strong>E-mail</strong><br><a href="mailto:<?= htmlspecialchars($publicContactEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($publicContactEmail, ENT_QUOTES, 'UTF-8'); ?></a></p>
					<?php endif; ?>
					<?php if ($publicPhone !== ''): ?>
					<p><strong>Telefoon</strong><br><?= htmlspecialchars($publicPhone, ENT_QUOTES, 'UTF-8'); ?></p>
					<?php endif; ?>
				</article>

				<article class="maatlas-contact-panel">
					<h2>Welke gegevens verwerken we?</h2>
					<ul>
						<li>Gegevens die je zelf invult in het contactformulier, zoals naam, e-mail, telefoonnummer, onderwerp en bericht.</li>
						<li>Technische gegevens die nodig zijn voor de beveiliging en werking van de website, zoals serverlogs en IP-adres op het moment van verzending.</li>
					</ul>
				</article>

				<article class="maatlas-contact-panel">
					<h2>Waarom verwerken we die gegevens?</h2>
					<ul>
						<li>Om jouw aanvraag te beantwoorden en contact met je op te nemen.</li>
						<li>Om misbruik, spam en technische storingen op de website te beperken.</li>
						<li>Om onze wettelijke verplichtingen na te leven wanneer dat nodig is.</li>
					</ul>
				</article>

				<article class="maatlas-contact-panel">
					<h2>Rechtsgrond</h2>
					<p>Voor contactaanvragen verwerken we jouw gegevens omdat je ons zelf contacteert en omdat dit nodig is voor precontractuele stappen of om jouw vraag te beantwoorden. Voor beveiliging en serverlogs steunen we op ons gerechtvaardigd belang om de website veilig te houden.</p>
				</article>

				<article class="maatlas-contact-panel">
					<h2>Hoe lang bewaren we jouw gegevens?</h2>
					<p>Contactaanvragen bewaren we maximaal <strong><?= $privacyRetentionMonths; ?> maanden</strong>, tenzij een offerte, opdracht, garantie, geschil of wettelijke verplichting een langere bewaartermijn vereist.</p>
				</article>

				<article class="maatlas-contact-panel">
					<h2>Met wie delen we gegevens?</h2>
					<p>We delen persoonsgegevens niet voor marketingdoeleinden. Gegevens kunnen wel verwerkt worden door onze hosting- of e-mailprovider voor het technisch verzenden en ontvangen van berichten.</p>
				</article>

				<article class="maatlas-contact-panel">
					<h2>Jouw rechten</h2>
					<p>Je hebt het recht op inzage, rechtzetting, verwijdering, beperking, bezwaar en overdraagbaarheid voor zover de AVG dat voorziet.</p>
					<p>Voor vragen of verzoeken kan je terecht via <a href="mailto:<?= htmlspecialchars($privacyContactEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($privacyContactEmail, ENT_QUOTES, 'UTF-8'); ?></a>. Je kan ook klacht indienen bij de Gegevensbeschermingsautoriteit.</p>
				</article>

				<article class="maatlas-contact-panel">
					<h2>Externe inhoud</h2>
					<p>Op de contactpagina is een Google Maps-kaart ingesloten. Daardoor wordt er bij het laden van die pagina verbinding gemaakt met Google en kunnen er cookies of andere technische gegevens door die externe dienst verwerkt worden.</p>
				</article>
			</div>
		</section>
	</main>
</div>
<?php maatlas_site_render_public_runtime_settings($settings); ?>
<script src="/assets/themes/bluehost-blueprint/site-shell.js?v=20260421-1"></script>
</body>
</html>
