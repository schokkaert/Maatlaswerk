<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (maatlas_admin_current() !== null) {
	header('Location: /admin/');
	exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim((string) ($_POST['username'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');
	$token = (string) ($_POST['csrf_token'] ?? '');

	if (!maatlas_admin_verify_csrf($token)) {
		$error = 'Ongeldige beveiligingstoken. Probeer opnieuw.';
	} else {
		$admin = maatlas_admin_find_by_username($username);
		if ($admin === null || empty($admin['active']) || !password_verify($password, (string) $admin['password_hash'])) {
			$error = 'De combinatie van gebruikersnaam en wachtwoord klopt niet.';
		} else {
			$admin['last_login_at'] = date('c');
			$admin['updated_at'] = date('c');
			maatlas_admin_update($admin);
			maatlas_admin_login($admin);
			header('Location: /admin/');
			exit;
		}
	}
}

maatlas_admin_render_header('Admin login');
?>
<section class="maatlas-admin-auth-card">
	<p class="maatlas-admin-eyebrow">Aanmelden</p>
	<h2>Log in op de beheeromgeving</h2>
	<?php if ($error !== null): ?>
	<p class="maatlas-admin-alert maatlas-admin-alert-error"><?= maatlas_admin_h($error); ?></p>
	<?php endif; ?>
	<form method="post" class="maatlas-admin-form">
		<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
		<label>
			<span>Gebruikersnaam</span>
			<input type="text" name="username" autocomplete="username" required>
		</label>
		<label>
			<span>Wachtwoord</span>
			<div class="maatlas-admin-password-row">
				<input id="login-password" type="password" name="password" autocomplete="current-password" required>
				<button type="button" class="maatlas-admin-toggle-password" data-password-toggle="login-password" aria-pressed="false">Toon wachtwoord</button>
			</div>
		</label>
		<button type="submit" class="maatlas-admin-button">Inloggen</button>
	</form>
</section>
<?php
maatlas_admin_render_footer();
