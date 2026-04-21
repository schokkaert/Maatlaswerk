<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$currentAdmin = maatlas_admin_require_login();
$admins = maatlas_admin_load();
$message = null;
$error = null;
$editingId = isset($_GET['edit']) ? (string) $_GET['edit'] : '';
$editingAdmin = $editingId !== '' ? maatlas_admin_find_by_id($editingId) : null;

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
			$active = isset($_POST['active']);

			if ($username === '' || $fullName === '' || $email === '') {
				$error = 'Gebruikersnaam, naam en e-mail zijn verplicht.';
			} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$error = 'Het e-mailadres is niet geldig.';
			} elseif ($action === 'create' && $password !== $passwordConfirmation) {
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
				} elseif ($action === 'create' && strlen($password) < 8) {
					$error = 'Geef voor een nieuwe administrator een wachtwoord van minstens 8 tekens.';
				} else {
					if ($action === 'create') {
						maatlas_admin_create([
							'id' => 'admin-' . bin2hex(random_bytes(4)),
							'username' => $username,
							'full_name' => $fullName,
							'email' => $email,
							'role' => $role === 'superadmin' ? 'superadmin' : 'admin',
							'active' => $active,
							'password_hash' => password_hash($password, PASSWORD_DEFAULT),
							'is_temporary' => false,
							'created_at' => date('c'),
							'updated_at' => date('c'),
							'last_login_at' => null,
						]);
						$message = 'Administrator toegevoegd.';
					} else {
						$existing = maatlas_admin_find_by_id($id);
						if ($existing === null) {
							$error = 'Administrator niet gevonden.';
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
								if (strlen($password) < 8) {
									$error = 'Een nieuw wachtwoord moet minstens 8 tekens lang zijn.';
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

			if ($existing === null) {
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

		$admins = maatlas_admin_load();
		if ($editingId !== '') {
			$editingAdmin = maatlas_admin_find_by_id($editingId);
		}
	}
}

maatlas_admin_render_header('Administrators', $currentAdmin);
?>
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
				<select name="role">
					<option value="admin"<?= (($editingAdmin['role'] ?? 'admin') === 'admin') ? ' selected' : ''; ?>>admin</option>
					<option value="superadmin"<?= (($editingAdmin['role'] ?? '') === 'superadmin') ? ' selected' : ''; ?>>superadmin</option>
				</select>
			</label>
			<label>
				<span><?= $editingAdmin === null ? 'Wachtwoord' : 'Nieuw wachtwoord'; ?></span>
				<div class="maatlas-admin-password-row">
					<input id="admin-password" type="password" name="password" <?= $editingAdmin === null ? 'required' : ''; ?>>
					<button type="button" class="maatlas-admin-toggle-password" data-password-toggle="admin-password" aria-pressed="false">Toon wachtwoord</button>
				</div>
			</label>
			<label>
				<span><?= $editingAdmin === null ? 'Herhaal wachtwoord' : 'Herhaal nieuw wachtwoord'; ?></span>
				<input id="admin-password-confirmation" type="password" name="password_confirmation" <?= $editingAdmin === null ? 'required' : ''; ?>>
			</label>
			<label class="maatlas-admin-checkbox">
				<input type="checkbox" name="active" value="1"<?= (($editingAdmin['active'] ?? true) ? ' checked' : ''); ?>>
				<span>Administrator is actief</span>
			</label>
			<div class="maatlas-admin-actions">
				<button type="submit" class="maatlas-admin-button"><?= $editingAdmin === null ? 'Toevoegen' : 'Opslaan'; ?></button>
				<?php if ($editingAdmin !== null): ?>
				<a class="maatlas-admin-button maatlas-admin-button-secondary" href="/admin/administrators.php">Nieuwe administrator</a>
				<?php endif; ?>
			</div>
		</form>
	</article>

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
						<td><?= !empty($admin['active']) ? 'actief' : 'inactief'; ?></td>
						<td><?= maatlas_admin_h($admin['last_login_at'] ? date('d/m/Y H:i', strtotime((string) $admin['last_login_at'])) : 'nog niet'); ?></td>
						<td>
							<div class="maatlas-admin-table-actions">
								<a href="/admin/administrators.php?edit=<?= maatlas_admin_h((string) $admin['id']); ?>">Bewerken</a>
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
</section>
<?php
maatlas_admin_render_footer();
