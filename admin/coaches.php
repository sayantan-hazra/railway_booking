<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

$message = '';
$error = '';
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$coach_id = (int) ($_POST['coach_id'] ?? 0);
	$train_id = (int) ($_POST['train_id'] ?? 0);
	$coach_type = trim($_POST['coach_type'] ?? '');
	$coach_number = trim($_POST['coach_number'] ?? '');

	if ($action === 'delete') {
		$statement = $conn->prepare('DELETE FROM coaches WHERE coach_id = ?');
		$statement->bind_param('i', $coach_id);

		if ($statement->execute()) {
			$message = 'Coach deleted successfully.';
		} else {
			$error = 'This coach cannot be deleted because it is being used by another record.';
		}
	} elseif ($train_id <= 0 || $coach_type === '' || $coach_number === '') {
		$error = 'Train, coach type, and coach number are required.';
	} elseif ($action === 'update') {
		$statement = $conn->prepare(
			'UPDATE coaches SET train_id = ?, coach_type = ?, coach_number = ? WHERE coach_id = ?'
		);
		$statement->bind_param('issi', $train_id, $coach_type, $coach_number, $coach_id);

		if ($statement->execute()) {
			$message = 'Coach updated successfully.';
		} else {
			$error = 'Could not update the coach.';
		}
	} else {
		$statement = $conn->prepare(
			'INSERT INTO coaches (train_id, coach_type, coach_number) VALUES (?, ?, ?)'
		);
		$statement->bind_param('iss', $train_id, $coach_type, $coach_number);

		if ($statement->execute()) {
			$message = 'Coach added successfully.';
		} else {
			$error = 'Could not add the coach.';
		}
	}
}

if (isset($_GET['edit'])) {
	$coach_id = (int) $_GET['edit'];
	$statement = $conn->prepare(
		'SELECT coach_id, train_id, coach_type, coach_number FROM coaches WHERE coach_id = ?'
	);
	$statement->bind_param('i', $coach_id);
	$statement->execute();
	$editing = $statement->get_result()->fetch_assoc();
}

$trains = admin_query($conn, 'SELECT train_id, train_number, train_name FROM trains ORDER BY train_number');
$coaches = admin_query(
	$conn,
	'SELECT c.coach_id, c.coach_type, c.coach_number, t.train_number, t.train_name
	 FROM coaches c
	 INNER JOIN trains t ON t.train_id = c.train_id
	 ORDER BY t.train_number, c.coach_number'
);

admin_header('Coaches', 'coaches');
?>

<?php if ($message !== ''): ?>
	<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
	<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<section class="panel" style="margin-bottom: 20px">
	<div class="panel-heading">
		<h2><?= $editing ? 'Edit coach' : 'Add a coach' ?></h2>
		<?php if ($editing): ?><a class="text-link" href="coaches.php">Cancel edit</a><?php endif; ?>
	</div>

	<?php if ($trains && $trains->num_rows > 0): ?>
		<form method="post">
			<div class="form-grid">
				<div>
					<label for="train_id">Train</label>
					<select id="train_id" name="train_id" required>
						<option value="">Select a train</option>
						<?php while ($train = $trains->fetch_assoc()): ?>
							<option value="<?= (int) $train['train_id'] ?>" <?= ((int) ($editing['train_id'] ?? 0) === (int) $train['train_id']) ? 'selected' : '' ?>>
								<?= htmlspecialchars($train['train_number'] . ' - ' . $train['train_name']) ?>
							</option>
						<?php endwhile; ?>
					</select>
				</div>
				<div>
					<label for="coach_type">Coach type</label>
					<input id="coach_type" name="coach_type" value="<?= htmlspecialchars($editing['coach_type'] ?? '') ?>" placeholder="e.g. 3A, SL, CC" required>
				</div>
				<div>
					<label for="coach_number">Coach number</label>
					<input id="coach_number" name="coach_number" value="<?= htmlspecialchars($editing['coach_number'] ?? '') ?>" placeholder="e.g. B1 or S1" required>
				</div>
			</div>

			<?php if ($editing): ?>
				<input type="hidden" name="action" value="update">
				<input type="hidden" name="coach_id" value="<?= (int) $editing['coach_id'] ?>">
			<?php endif; ?>

			<div class="form-actions">
				<button class="button button-primary" type="submit"><?= $editing ? 'Save changes' : 'Add coach' ?></button>
			</div>
		</form>
	<?php else: ?>
		<div class="empty-state">Add a train first before creating coaches.</div>
	<?php endif; ?>
</section>

<section class="panel">
	<div class="panel-heading">
		<h2>Coach directory</h2>
		<span class="muted"><?= $coaches ? $coaches->num_rows : 0 ?> records</span>
	</div>

	<?php if ($coaches && $coaches->num_rows > 0): ?>
		<div class="table-wrap">
			<table class="data-table">
				<thead><tr><th>Train</th><th>Coach number</th><th>Type</th><th>Actions</th></tr></thead>
				<tbody>
					<?php while ($coach = $coaches->fetch_assoc()): ?>
						<tr>
							<td><strong><?= htmlspecialchars($coach['train_number']) ?></strong><br><span class="muted"><?= htmlspecialchars($coach['train_name']) ?></span></td>
							<td><span class="badge badge-blue"><?= htmlspecialchars($coach['coach_number']) ?></span></td>
							<td><?= htmlspecialchars($coach['coach_type']) ?></td>
							<td>
								<div class="action-links">
									<a href="?edit=<?= (int) $coach['coach_id'] ?>">Edit</a>
									<form method="post" onsubmit="return confirm('Delete this coach?');">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="coach_id" value="<?= (int) $coach['coach_id'] ?>">
										<button class="button button-danger" type="submit">Delete</button>
									</form>
								</div>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	<?php else: ?>
		<div class="empty-state">No coaches have been added yet.</div>
	<?php endif; ?>
</section>

<?php admin_footer(); ?>
