<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$currentAdmin = maatlas_admin_require_login();
$admins = maatlas_admin_load();
$isInitialSetup = maatlas_admin_is_initial_setup_required() && maatlas_admin_is_temporary($currentAdmin);
$message = null;
$error = null;
$editingId = isset($_GET['edit']) ? (string) $_GET['edit'] : '';
$editingAdmin = $editingId !== '' ? maatlas_admin_find_by_id($editingId) : null;

if ($isInitialSetup) {
	$editingId = '';
	$editingAdmin = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = (string) ($_POST['csrf_token'] ?? '');
	$action = (string) ($_POST['action'] ?? '');

	if (!maatlas_admin_verify_csrf($token)) {
		$error = 'Ongeldige beveiligingstoken. Herlaad de pagina en probeer opnieuw.';
	} else {
		if ($action === 'create' || $action === 'update') {
			$id = (string) ($_POST['id'] ?? '');
			$username = trim((string) ($_POST['username'] ?? ''));
			$fullName = trim((string) ($_POST['full_name'] ?? ''));
			$email = trim((string) ($_POST['email'] ?? ''));
			$role = trim((string) ($_POST['role'] ?? 'admin'));
			$password = (string) ($_POST['password'] ?? '');
			$passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
			$activationMode = (string) ($_POST['activation_mode'] ?? 'direct');
			if (!in_array($activationMode, ['direct', 'confirm'], true)) {
				$activationMode = 'direct';
			}
			$active = $isInitialSetup || ($action === 'create' && $activationMode === 'direct') || ($action === 'update' && isset($_POST['active']));

			if ($isInitialSetup && $action !== 'create') {
				$error = 'Maak eerst een nieuwe beheerder aan.';
			} elseif ($username === '' || $fullName === '' || $email === '') {
				$error = 'Gebruikersnaam, naam en e-mail zijn verplicht.';
			} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$error = 'Het e-mailadres is niet geldig.';
			} elseif ($isInitialSetup && strcasecmp($username, MAATLAS_ADMIN_TEMPORARY_USERNAME) === 0) {
				$error = 'Kies een andere gebruikersnaam dan admin.';
			} elseif ($action === 'create' && ($isInitialSetup || $activationMode === 'direct') && $password !== $passwordConfirmation) {
				$error = 'De twee wachtwoorden zijn niet gelijk.';
			} else {
				$duplicate = null;
				foreach (maatlas_admin_load() as $admin) {
					if (strcasecmp((string) $admin['username'], $username) === 0 && (string) $admin['id'] !== $id) {
						$duplicate = $admin;
						break;
					}
				}

				if ($duplicate !== null) {
					$error = 'Deze gebruikersnaam bestaat al.';
				} elseif ($action === 'create' && ($isInitialSetup || $activationMode === 'direct') && !maatlas_admin_password_is_safe($password, $username, 12)) {
					$error = 'Gebruik minstens 12 tekens en kies een ander wachtwoord dan admin of je gebruikersnaam.';
				} else {
					if ($action === 'create') {
						$activationToken = null;
						$activationUrl = null;
						if (!$isInitialSetup && $activationMode === 'confirm') {
							$activationToken = bin2hex(random_bytes(32));
							$activationUrl = maatlas_admin_current_host_url('/admin/activate.php?token=' . rawurlencode($activationToken));
						}

						$newAdmin = [
							'id' => 'admin-' . bin2hex(random_bytes(4)),
							'username' => $username,
							'full_name' => $fullName,
							'email' => $email,
							'role' => $isInitialSetup ? 'superadmin' : ($role === 'superadmin' ? 'superadmin' : 'admin'),
							'active' => $active,
							'password_hash' => ($isInitialSetup || $activationMode === 'direct') ? password_hash($password, PASSWORD_DEFAULT) : '',
							'is_temporary' => false,
							'activation_token_hash' => $activationToken !== null ? hash('sha256', $activationToken) : null,
							'activation_expires_at' => $activationToken !== null ? date('c', strtotime('+7 days')) : null,
							'activated_at' => ($isInitialSetup || $activationMode === 'direct') ? date('c') : null,
							'created_at' => date('c'),
							'updated_at' => date('c'),
							'last_login_at' => null,
						];
						maatlas_admin_create($newAdmin);
						if ($isInitialSetup) {
							$newAdmin['last_login_at'] = date('c');
							$newAdmin['updated_at'] = date('c');
							maatlas_admin_update($newAdmin);
							maatlas_admin_login($newAdmin);
							maatlas_admin_delete_temporary_accounts();
							header('Location: /admin/?setup=complete');
							exit;
						}
						if ($activationMode === 'confirm' && $activationUrl !== null) {
							$mailSent = maatlas_admin_send_activation_mail($newAdmin, $currentAdmin, $activationUrl);
							$message = $mailSent
								? 'Beheerder toegevoegd. Er is een activatiemail verzonden.'
								: 'Beheerder toegevoegd, maar de activatiemail kon niet worden verzonden. Open de beheerder opnieuw om een nieuwe uitnodiging te sturen.';
						} else {
							$mailSent = maatlas_admin_send_account_created_mail($newAdmin, $currentAdmin);
							$message = $mailSent
								? 'Administrator toegevoegd en per e-mail op de hoogte gebracht.'
								: 'Administrator toegevoegd, maar de e-mailmelding kon niet worden verzonden.';
						}
					} else {
						$existing = maatlas_admin_find_by_id($id);
						if ($existing === null) {
							$error = 'Administrator niet gevonden.';
						} elseif ($active && empty($existing['password_hash']) && $password === '') {
							$error = 'Geef een wachtwoord op om deze beheerder direct te activeren, of verzend opnieuw een activatiemail.';
						} elseif (!$active && !empty($existing['active']) && maatlas_admin_active_count() <= 1) {
							$error = 'De laatste actieve administrator kan niet gedeactiveerd worden.';
						} else {
							$wasTemporary = !empty($existing['is_temporary']);
							$hadPasswordChange = $password !== '';
							$existing['username'] = $username;
							$existing['full_name'] = $fullName;
							$existing['email'] = $email;
							$existing['role'] = $role === 'superadmin' ? 'superadmin' : 'admin';
							$existing['active'] = $active;
							$existing['is_temporary'] = (bool) ($existing['is_temporary'] ?? false);
							$existing['updated_at'] = date('c');

							if ($password !== '') {
								if (!maatlas_admin_password_is_safe($password, $username, 12)) {
									$error = 'Gebruik minstens 12 tekens en kies een ander wachtwoord dan admin of je gebruikersnaam.';
								} elseif ($password !== $passwordConfirmation) {
									$error = 'De twee wachtwoorden zijn niet gelijk.';
								} else {
									$existing['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
								}
							}

							if ($error === null) {
								if (
									$wasTemporary
									&& (
										$username !== 'setup-admin'
										|| $fullName !== 'Tijdelijke Setup Admin'
										|| $email !== 'setup@maatlaswerk.be'
										|| $hadPasswordChange
									)
								) {
									$existing['is_temporary'] = false;
								}

								if ($active) {
									$existing['activation_token_hash'] = null;
									$existing['activation_expires_at'] = null;
									$existing['activated_at'] = $existing['activated_at'] ?? date('c');
								}

								maatlas_admin_update($existing);
								$message = 'Administrator bijgewerkt.';
								$editingId = $existing['id'];
								$editingAdmin = $existing;
							}
						}
					}
				}
			}
		}

		if ($action === 'delete') {
			$id = (string) ($_POST['id'] ?? '');
			$existing = maatlas_admin_find_by_id($id);

			if ($isInitialSetup) {
				$error = 'Maak eerst een nieuwe beheerder aan. De tijdelijke admin wordt daarna automatisch verwijderd.';
			} elseif ($existing === null) {
				$error = 'Administrator niet gevonden.';
			} elseif ((string) $existing['id'] === (string) $currentAdmin['id']) {
				$error = 'Je kunt je eigen account niet verwijderen.';
			} elseif (!empty($existing['active']) && maatlas_admin_active_count() <= 1) {
				$error = 'De laatste actieve administrator kan niet verwijderd worden.';
			} else {
				maatlas_admin_delete($id);
				$message = 'Administrator verwijderd.';
				if ($editingId === $id) {
					$editingId = '';
					$editingAdmin = null;
				}
			}
		}

		if ($action === 'resend-activation') {
			$id = (string) ($_POST['id'] ?? '');
			$existing = maatlas_admin_find_by_id($id);

			if ($isInitialSetup) {
				$error = 'Maak eerst een nieuwe beheerder aan.';
			} elseif ($existing === null) {
				$error = 'Administrator niet gevonden.';
			} elseif (!empty($existing['active'])) {
				$error = 'Deze administrator is al actief.';
			} elseif (empty($existing['email']) || !filter_var((string) $existing['email'], FILTER_VALIDATE_EMAIL)) {
				$error = 'Deze administrator heeft geen geldig e-mailadres.';
			} else {
				$activationToken = bin2hex(random_bytes(32));
				$existing['activation_token_hash'] = hash('sha256', $activationToken);
				$existing['activation_expires_at'] = date('c', strtotime('+7 days'));
				$existing['updated_at'] = date('c');
				maatlas_admin_update($existing);

				$activationUrl = maatlas_admin_current_host_url('/admin/activate.php?token=' . rawurlencode($activationToken));
				$mailSent = maatlas_admin_send_activation_mail($existing, $currentAdmin, $activationUrl);
				$message = $mailSent
					? 'Nieuwe activatiemail verzonden.'
					: 'De activatiemail kon niet worden verzonden.';
			}
		}

		$admins = maatlas_admin_load();
		if ($editingId !== '') {
			$editingAdmin = maatlas_admin_find_by_id($editingId);
		}
	}
}

maatlas_admin_render_header('Administrators', $currentAdmin);
?>
<?php if ($isInitialSetup): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-error">Je bent aangemeld met de tijdelijke account <strong>admin/admin</strong>. Maak nu een eigen beheerder aan. Daarna logt het systeem je automatisch in met die nieuwe beheerder en wordt de tijdelijke account verwijderd.</p>
<?php endif; ?>
<?php if ($message !== null): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-success"><?= maatlas_admin_h($message); ?></p>
<?php endif; ?>
<?php if ($error !== null): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-error"><?= maatlas_admin_h($error); ?></p>
<?php endif; ?>

<section class="maatlas-admin-grid maatlas-admin-grid-wide">
	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow"><?= $editingAdmin === null ? 'Nieuwe administrator' : 'Administrator wijzigen'; ?></p>
		<h2><?= $editingAdmin === null ? 'Toevoegen' : 'Bewerken'; ?></h2>
		<form method="post" class="maatlas-admin-form">
			<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
			<input type="hidden" name="action" value="<?= $editingAdmin === null ? 'create' : 'update'; ?>">
			<input type="hidden" name="id" value="<?= maatlas_admin_h((string) ($editingAdmin['id'] ?? '')); ?>">
			<label>
				<span>Gebruikersnaam</span>
				<input type="text" name="username" required value="<?= maatlas_admin_h((string) ($editingAdmin['username'] ?? '')); ?>">
			</label>
			<label>
				<span>Volledige naam</span>
				<input type="text" name="full_name" required value="<?= maatlas_admin_h((string) ($editingAdmin['full_name'] ?? '')); ?>">
			</label>
			<label>
				<span>E-mail</span>
				<input type="email" name="email" required value="<?= maatlas_admin_h((string) ($editingAdmin['email'] ?? '')); ?>">
			</label>
			<label>
				<span>Rol</span>
				<select name="role"<?= $isInitialSetup ? ' disabled' : ''; ?>>
					<option value="admin"<?= (($editingAdmin['role'] ?? 'admin') === 'admin') ? ' selected' : ''; ?>>admin</option>
					<option value="superadmin"<?= ($isInitialSetup || (($editingAdmin['role'] ?? '') === 'superadmin')) ? ' selected' : ''; ?>>superadmin</option>
				</select>
				<?php if ($isInitialSetup): ?>
				<input type="hidden" name="role" value="superadmin">
				<?php endif; ?>
			</label>
			<?php if ($editingAdmin === null && !$isInitialSetup): ?>
			<fieldset class="maatlas-admin-fieldset">
				<legend>Activatie</legend>
				<label class="maatlas-admin-checkbox">
					<input type="radio" name="activation_mode" value="direct" checked>
					<span>Direct activeren en zelf een startwachtwoord invullen</span>
				</label>
				<label class="maatlas-admin-checkbox">
					<input type="radio" name="activation_mode" value="confirm">
					<span>Activatie pas na bevestiging via e-mail</span>
				</label>
				<p class="maatlas-admin-help">Bij e-mailbevestiging ontvangt de nieuwe beheerder een link en kiest die zelf een wachtwoord. De link is 7 dagen geldig.</p>
			</fieldset>
			<?php endif; ?>
			<label>
				<span><?= $editingAdmin === null ? ($isInitialSetup ? 'Wachtwoord' : 'Startwachtwoord bij directe activatie') : 'Nieuw wachtwoord'; ?></span>
				<div class="maatlas-admin-password-row">
					<input id="admin-password" type="password" name="password" <?= ($editingAdmin === null && $isInitialSetup) ? 'required' : ''; ?>>
					<button type="button" class="maatlas-admin-toggle-password" data-password-toggle="admin-password" aria-pressed="false">Toon wachtwoord</button>
				</div>
			</label>
			<label>
				<span><?= $editingAdmin === null ? ($isInitialSetup ? 'Herhaal wachtwoord' : 'Herhaal startwachtwoord') : 'Herhaal nieuw wachtwoord'; ?></span>
				<input id="admin-password-confirmation" type="password" name="password_confirmation" <?= ($editingAdmin === null && $isInitialSetup) ? 'required' : ''; ?>>
			</label>
			<?php if ($editingAdmin === null && !$isInitialSetup): ?>
			<p class="maatlas-admin-help">Laat de wachtwoordvelden leeg wanneer je kiest voor activatie via e-mail.</p>
			<?php endif; ?>
			<?php if ($editingAdmin !== null): ?>
			<label class="maatlas-admin-checkbox">
				<input type="checkbox" name="active" value="1"<?= (($editingAdmin['active'] ?? true) ? ' checked' : ''); ?>>
				<span>Administrator is actief</span>
			</label>
			<?php endif; ?>
			<?php if ($isInitialSetup): ?>
			<p class="maatlas-admin-help">Gebruik minstens 12 tekens. Gebruik niet opnieuw <strong>admin</strong> en gebruik ook niet je gebruikersnaam als wachtwoord.</p>
			<?php endif; ?>
			<div class="maatlas-admin-actions">
				<button type="submit" class="maatlas-admin-button"><?= $editingAdmin === null ? 'Toevoegen' : 'Opslaan'; ?></button>
				<?php if ($editingAdmin !== null && !$isInitialSetup): ?>
				<a class="maatlas-admin-button maatlas-admin-button-secondary" href="/admin/administrators.php">Nieuwe administrator</a>
				<?php endif; ?>
			</div>
		</form>
	</article>

	<?php if (!$isInitialSetup): ?>
	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Overzicht</p>
		<h2>Bestaande administrators</h2>
		<div class="maatlas-admin-table-wrap">
			<table class="maatlas-admin-table">
				<thead>
					<tr>
						<th>Gebruikersnaam</th>
						<th>Naam</th>
						<th>Rol</th>
						<th>Status</th>
						<th>Laatste login</th>
						<th>Acties</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($admins as $admin): ?>
					<tr>
						<td><?= maatlas_admin_h((string) $admin['username']); ?></td>
						<td><?= maatlas_admin_h((string) $admin['full_name']); ?></td>
						<td><?= maatlas_admin_h((string) $admin['role']); ?></td>
						<td>
							<?php if (!empty($admin['active'])): ?>
							actief
							<?php elseif (!empty($admin['activation_token_hash'])): ?>
							wacht op bevestiging<?= maatlas_admin_is_activation_expired($admin) ? ' (vervallen)' : ''; ?>
							<?php else: ?>
							inactief
							<?php endif; ?>
						</td>
						<td><?= maatlas_admin_h($admin['last_login_at'] ? date('d/m/Y H:i', strtotime((string) $admin['last_login_at'])) : 'nog niet'); ?></td>
						<td>
							<div class="maatlas-admin-table-actions">
								<a href="/admin/administrators.php?edit=<?= maatlas_admin_h((string) $admin['id']); ?>">Bewerken</a>
								<?php if (empty($admin['active']) && empty($admin['is_temporary'])): ?>
								<form method="post">
									<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
									<input type="hidden" name="action" value="resend-activation">
									<input type="hidden" name="id" value="<?= maatlas_admin_h((string) $admin['id']); ?>">
									<button type="submit">Activatiemail</button>
								</form>
								<?php endif; ?>
								<?php if ((string) $admin['id'] !== (string) $currentAdmin['id']): ?>
								<form method="post" onsubmit="return confirm('Deze administrator verwijderen?');">
									<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="id" value="<?= maatlas_admin_h((string) $admin['id']); ?>">
									<button type="submit">Verwijderen</button>
								</form>
								<?php endif; ?>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</article>
	<?php endif; ?>
</section>
<?php
maatlas_admin_render_footer();
