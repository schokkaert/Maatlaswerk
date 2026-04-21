<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$admin = maatlas_admin_find_by_activation_token($token);
$message = null;
$error = null;

if ($token === '' || $admin === null) {
	$error = 'Deze activatielink is ongeldig.';
} elseif (!empty($admin['active'])) {
	$message = 'Deze beheerder is al actief. Je kunt aanmelden via de loginpagina.';
} elseif (maatlas_admin_is_activation_expired($admin)) {
	$error = 'Deze activatielink is verlopen. Vraag een beheerder om een nieuwe activatiemail te verzenden.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$csrfToken = (string) ($_POST['csrf_token'] ?? '');
	$password = (string) ($_POST['password'] ?? '');
	$passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

	if (!maatlas_admin_verify_csrf($csrfToken)) {
		$error = 'Ongeldige beveiligingstoken. Herlaad de pagina en probeer opnieuw.';
	} elseif ($password !== $passwordConfirmation) {
		$error = 'De twee wachtwoorden zijn niet gelijk.';
	} elseif (!maatlas_admin_password_is_safe($password, (string) $admin['username'], 12)) {
		$error = 'Gebruik minstens 12 tekens en kies een ander wachtwoord dan admin of je gebruikersnaam.';
	} else {
		$admin['active'] = true;
		$admin['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
		$admin['activation_token_hash'] = null;
		$admin['activation_expires_at'] = null;
		$admin['activated_at'] = date('c');
		$admin['updated_at'] = date('c');
		$admin['last_login_at'] = date('c');
		maatlas_admin_update($admin);
		maatlas_admin_login($admin);
		header('Location: /admin/?activated=1');
		exit;
	}
}

maatlas_admin_render_header('Account activeren');
?>
<section class="maatlas-admin-auth-card">
	<p class="maatlas-admin-eyebrow">Activatie</p>
	<h2>Beheerdersaccount activeren</h2>
	<?php if ($message !== null): ?>
	<p class="maatlas-admin-alert maatlas-admin-alert-success"><?= maatlas_admin_h($message); ?></p>
	<p><a class="maatlas-admin-button" href="/admin/login.php">Naar login</a></p>
	<?php elseif ($error !== null && ($token === '' || $admin === null || !empty($admin['active']) || ($admin !== null && maatlas_admin_is_activation_expired($admin)))): ?>
	<p class="maatlas-admin-alert maatlas-admin-alert-error"><?= maatlas_admin_h($error); ?></p>
	<p><a class="maatlas-admin-button maatlas-admin-button-secondary" href="/admin/login.php">Naar login</a></p>
	<?php else: ?>
	<?php if ($error !== null): ?>
	<p class="maatlas-admin-alert maatlas-admin-alert-error"><?= maatlas_admin_h($error); ?></p>
	<?php endif; ?>
	<p>Welkom <?= maatlas_admin_h((string) $admin['full_name']); ?>. Kies een wachtwoord om je account te activeren.</p>
	<form method="post" class="maatlas-admin-form">
		<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
		<input type="hidden" name="token" value="<?= maatlas_admin_h($token); ?>">
		<label>
			<span>Gebruikersnaam</span>
			<input type="text" value="<?= maatlas_admin_h((string) $admin['username']); ?>" readonly>
		</label>
		<label>
			<span>Wachtwoord</span>
			<div class="maatlas-admin-password-row">
				<input id="activation-password" type="password" name="password" autocomplete="new-password" required>
				<button type="button" class="maatlas-admin-toggle-password" data-password-toggle="activation-password" aria-pressed="false">Toon wachtwoord</button>
			</div>
		</label>
		<label>
			<span>Herhaal wachtwoord</span>
			<input type="password" name="password_confirmation" autocomplete="new-password" required>
		</label>
		<p class="maatlas-admin-help">Gebruik minstens 12 tekens. Gebruik niet <strong>admin</strong> en ook niet je gebruikersnaam als wachtwoord.</p>
		<button type="submit" class="maatlas-admin-button">Account activeren</button>
	</form>
	<?php endif; ?>
</section>
<?php
maatlas_admin_render_footer();
