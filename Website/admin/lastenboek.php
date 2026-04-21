<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$currentAdmin = maatlas_admin_require_login();
$document = maatlas_lastenboek_load();
$message = null;
$error = null;
$editingId = isset($_GET['edit']) ? (string) $_GET['edit'] : '';
$editingItem = $editingId !== '' ? maatlas_lastenboek_find_item($editingId) : null;

if ((string) ($_GET['download'] ?? '') === 'txt') {
	$filename = 'maatlaswerk-lastenboek-' . date('Ymd-His') . '.txt';
	header('Content-Type: text/plain; charset=UTF-8');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('X-Content-Type-Options: nosniff');
	echo maatlas_lastenboek_to_text($document);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = (string) ($_POST['csrf_token'] ?? '');
	$action = (string) ($_POST['action'] ?? '');

	if (!maatlas_admin_verify_csrf($token)) {
		$error = 'Ongeldige beveiligingstoken. Herlaad de pagina en probeer opnieuw.';
	} else {
		try {
			if ($action === 'save_meta') {
				maatlas_lastenboek_update_meta([
					'document_title' => (string) ($_POST['document_title'] ?? ''),
					'project_name' => (string) ($_POST['project_name'] ?? ''),
					'client_name' => (string) ($_POST['client_name'] ?? ''),
					'reference' => (string) ($_POST['reference'] ?? ''),
					'version' => (string) ($_POST['version'] ?? '1.0'),
					'introduction' => (string) ($_POST['introduction'] ?? ''),
				]);
				$message = 'Lastenboekgegevens opgeslagen.';
			}

			if ($action === 'load_template') {
				maatlas_lastenboek_load_template();
				$message = 'Basislastenboek geladen.';
			}

			if ($action === 'create_item' || $action === 'update_item') {
				$editingItem = maatlas_lastenboek_upsert_item([
					'id' => (string) ($_POST['id'] ?? ''),
					'rubric' => (string) ($_POST['rubric'] ?? ''),
					'code' => (string) ($_POST['code'] ?? ''),
					'title' => (string) ($_POST['title'] ?? ''),
					'content' => (string) ($_POST['content'] ?? ''),
					'status' => (string) ($_POST['status'] ?? 'concept'),
					'position' => (string) ($_POST['position'] ?? '1'),
				]);
				$editingId = (string) ($editingItem['id'] ?? '');
				$message = $action === 'create_item' ? 'Lastenboekitem toegevoegd.' : 'Lastenboekitem bijgewerkt.';
			}

			if ($action === 'delete_item') {
				$id = (string) ($_POST['id'] ?? '');
				if ($id === '') {
					throw new RuntimeException('Geen item geselecteerd om te verwijderen.');
				}

				maatlas_lastenboek_delete_item($id);
				if ($editingId === $id) {
					$editingId = '';
					$editingItem = null;
				}
				$message = 'Lastenboekitem verwijderd.';
			}
		} catch (RuntimeException $exception) {
			$error = $exception->getMessage();
		}
	}

	$document = maatlas_lastenboek_load();
	if ($editingId !== '') {
		$editingItem = maatlas_lastenboek_find_item($editingId);
	}
}

maatlas_admin_render_header('Technisch lastenboek', $currentAdmin);
?>
<?php if ($message !== null): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-success"><?= maatlas_admin_h($message); ?></p>
<?php endif; ?>
<?php if ($error !== null): ?>
<p class="maatlas-admin-alert maatlas-admin-alert-error"><?= maatlas_admin_h($error); ?></p>
<?php endif; ?>

<section class="maatlas-admin-grid maatlas-admin-grid-wide">
	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow">Document</p>
		<h2>Technische documentatie</h2>
		<p>Hier beheer je het technisch lastenboek van de website zelf: opbouw, modules, galerij, formulieren, instellingen en publicatie. Je kan hieronder ook een basisdocument voor de website laden.</p>
		<p><a class="maatlas-admin-button maatlas-admin-button-secondary" href="/admin/lastenboek.php?download=txt">Download volledig lastenboek als TXT</a></p>
		<form method="post" class="maatlas-admin-form">
			<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
			<input type="hidden" name="action" value="save_meta">
			<label>
				<span>Documenttitel</span>
				<input type="text" name="document_title" value="<?= maatlas_admin_h((string) ($document['meta']['document_title'] ?? '')); ?>" required>
			</label>
			<label>
				<span>Website / domein</span>
				<input type="text" name="project_name" value="<?= maatlas_admin_h((string) ($document['meta']['project_name'] ?? '')); ?>" placeholder="Bijv. maatlaswerk.digisteps.be">
			</label>
			<label>
				<span>Beheerder / eigenaar</span>
				<input type="text" name="client_name" value="<?= maatlas_admin_h((string) ($document['meta']['client_name'] ?? '')); ?>">
			</label>
			<label>
				<span>Technische referentie</span>
				<input type="text" name="reference" value="<?= maatlas_admin_h((string) ($document['meta']['reference'] ?? '')); ?>">
			</label>
			<label>
				<span>Versie</span>
				<input type="text" name="version" value="<?= maatlas_admin_h((string) ($document['meta']['version'] ?? '1.0')); ?>">
			</label>
			<label>
				<span>Scope / samenvatting</span>
				<textarea name="introduction" rows="6" placeholder="Omschrijf hier de technische context en de werking van de website."><?= maatlas_admin_h((string) ($document['meta']['introduction'] ?? '')); ?></textarea>
			</label>
			<div class="maatlas-admin-actions">
				<button type="submit" class="maatlas-admin-button">Opslaan</button>
			</div>
		</form>
		<form method="post" class="maatlas-admin-form maatlas-admin-template-form">
			<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
			<input type="hidden" name="action" value="load_template">
			<div class="maatlas-admin-actions">
				<button type="submit" class="maatlas-admin-button maatlas-admin-button-secondary">Laad technisch basislastenboek</button>
			</div>
		</form>
	</article>

	<article class="maatlas-admin-card">
		<p class="maatlas-admin-eyebrow"><?= $editingItem === null ? 'Nieuw hoofdstuk' : 'Hoofdstuk wijzigen'; ?></p>
		<h2><?= $editingItem === null ? 'Onderdeel toevoegen' : 'Onderdeel bewerken'; ?></h2>
		<form method="post" class="maatlas-admin-form">
			<input type="hidden" name="csrf_token" value="<?= maatlas_admin_h(maatlas_admin_csrf_token()); ?>">
			<input type="hidden" name="action" value="<?= $editingItem === null ? 'create_item' : 'update_item'; ?>">
			<input type="hidden" name="id" value="<?= maatlas_admin_h((string) ($editingItem['id'] ?? '')); ?>">
			<div class="maatlas-admin-inline-grid">
				<label>
					<span>Rubriek</span>
					<input type="text" name="rubric" value="<?= maatlas_admin_h((string) ($editingItem['rubric'] ?? '')); ?>" placeholder="Bijv. Galerij of Admin">
				</label>
				<label>
					<span>Code / onderdeel</span>
					<input type="text" name="code" value="<?= maatlas_admin_h((string) ($editingItem['code'] ?? '')); ?>" placeholder="Bijv. 05.02">
				</label>
			</div>
			<label>
				<span>Titel</span>
				<input type="text" name="title" value="<?= maatlas_admin_h((string) ($editingItem['title'] ?? '')); ?>" required>
			</label>
			<label>
				<span>Inhoud</span>
				<textarea name="content" rows="8" required placeholder="Beschrijf hier de technische opbouw, werking, afhankelijkheden of het beheer van dit onderdeel."><?= maatlas_admin_h((string) ($editingItem['content'] ?? '')); ?></textarea>
			</label>
			<div class="maatlas-admin-inline-grid">
				<label>
					<span>Status</span>
					<select name="status">
						<?php foreach (['concept' => 'Concept', 'te-bekijken' => 'Te bekijken', 'goedgekeurd' => 'Goedgekeurd'] as $statusValue => $statusLabel): ?>
						<option value="<?= maatlas_admin_h($statusValue); ?>"<?= (($editingItem['status'] ?? 'concept') === $statusValue) ? ' selected' : ''; ?>><?= maatlas_admin_h($statusLabel); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span>Volgorde</span>
					<input type="number" min="1" name="position" value="<?= maatlas_admin_h((string) ($editingItem['position'] ?? (count((array) ($document['items'] ?? [])) + 1))); ?>">
				</label>
			</div>
			<div class="maatlas-admin-actions">
				<button type="submit" class="maatlas-admin-button"><?= $editingItem === null ? 'Toevoegen' : 'Opslaan'; ?></button>
				<?php if ($editingItem !== null): ?>
				<a class="maatlas-admin-button maatlas-admin-button-secondary" href="/admin/lastenboek.php">Nieuw item</a>
				<?php endif; ?>
			</div>
		</form>
	</article>

	<article class="maatlas-admin-card maatlas-admin-card-span-2">
		<p class="maatlas-admin-eyebrow">Overzicht</p>
		<h2>Hoofdstukken en onderdelen</h2>
		<?php if (($document['items'] ?? []) === []): ?>
		<p>Er staan nog geen onderdelen in dit technisch lastenboek. Voeg hierboven eerst een eerste hoofdstuk toe of laad het basisdocument.</p>
		<?php else: ?>
		<div class="maatlas-admin-list">
			<?php foreach ((array) $document['items'] as $item): ?>
			<div class="maatlas-admin-list-row maatlas-admin-spec-row">
				<div>
					<strong>
						<?php if (trim((string) ($item['code'] ?? '')) !== ''): ?>
						<?= maatlas_admin_h((string) $item['code']); ?> -
						<?php endif; ?>
						<?= maatlas_admin_h((string) ($item['title'] ?? '')); ?>
					</strong>
					<?php if (trim((string) ($item['rubric'] ?? '')) !== ''): ?>
					<span><?= maatlas_admin_h((string) $item['rubric']); ?></span>
					<?php endif; ?>
					<p class="maatlas-admin-category-text"><?= nl2br(maatlas_admin_h((string) ($item['content'] ?? ''))); ?></p>
				</div>
				<div class="maatlas-admin-list-actions">
					<span class="maatlas-admin-badge">Status: <?= maatlas_admin_h((string) ($item['status'] ?? 'concept')); ?></span>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</article>
</section>

<?php
maatlas_admin_render_footer();
