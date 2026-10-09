<?php
/**
 * Admin - Coach management (add / edit / delete).
 *
 * A coach belongs to a train and carries a coach type (2S, SL, 3A, 2A),
 * a coach number and a seat price. Deletion is refused while any booking
 * still references the coach or one of its seats.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

// Flash messages and the coach currently being edited (null = add mode).
$message = '';
$error = '';
$editing = null;

// Handle add / update / delete form submissions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$coach_id = (int) ($_POST['coach_id'] ?? 0);
	$train_id = (int) ($_POST['train_id'] ?? 0);
	$coach_type = trim($_POST['coach_type'] ?? '');
	$coach_number = trim($_POST['coach_number'] ?? '');
	$seat_price = (float) ($_POST['seat_price'] ?? 0);

	// Delete: refuse while a booking still references this coach or one of its seats.
	if ($action === 'delete') {
		$usageStatement = $conn->prepare(
			'SELECT COUNT(*) AS total
			 FROM bookings b
			 LEFT JOIN seats s ON s.seat_id = b.seat_id
			 WHERE b.coach_id = ? OR s.coach_id = ?'
		);
		$usageStatement->bind_param('ii', $coach_id, $coach_id);
		$usageStatement->execute();
		$usageCount = (int) $usageStatement->get_result()->fetch_assoc()['total'];
		$usageStatement->close();

		if ($usageCount > 0) {
			$error = 'This coach cannot be deleted because it is referenced by a booking.';
		} else {
			try {
				// Remove the coach's seats first, then the coach itself, in one transaction.
				$conn->begin_transaction();
				$seatStatement = $conn->prepare('DELETE FROM seats WHERE coach_id = ?');
				$seatStatement->bind_param('i', $coach_id);
				$seatStatement->execute();
				$seatStatement->close();

				$coachStatement = $conn->prepare('DELETE FROM coaches WHERE coach_id = ?');
				$coachStatement->bind_param('i', $coach_id);
				$coachStatement->execute();
				$coachStatement->close();
				$conn->commit();
				$message = 'Coach deleted successfully.';
			} catch (mysqli_sql_exception $exception) {
				$conn->rollback();
				$error = 'This coach could not be deleted because it is still in use.';
			}
		}
	// Shared validation for add/update: train, coach type, coach number and a price are required.
	} elseif ($train_id <= 0 || $coach_type === '' || $coach_number === '') {
		$error = 'Train, coach type, coach number, and a valid seat price are required.';
	// Update the existing coach identified by coach_id.
	} elseif ($action === 'update') {
		$statement = $conn->prepare(
			'UPDATE coaches SET train_id = ?, coach_type = ?, coach_number = ?, seat_price = ? WHERE coach_id = ?'
		);
		$statement->bind_param('issdi', $train_id, $coach_type, $coach_number, $seat_price, $coach_id);

		if ($statement->execute()) {
			$message = 'Coach updated successfully.';
		} else {
			$error = 'Could not update the coach.';
		}
	} else {
		// No action given - insert a brand-new coach.
		$statement = $conn->prepare(
			'INSERT INTO coaches (train_id, coach_type, coach_number, seat_price) VALUES (?, ?, ?, ?)'
		);
		$statement->bind_param('issd', $train_id, $coach_type, $coach_number, $seat_price);

		if ($statement->execute()) {
			$message = 'Coach added successfully.';
		} else {
			$error = 'Could not add the coach.';
		}
	}
}

// ?edit=<id> - load the coach being edited so the form shows its current values.
if (isset($_GET['edit'])) {
	$coach_id = (int) $_GET['edit'];
	$statement = $conn->prepare(
		'SELECT coach_id, train_id, coach_type, coach_number, seat_price FROM coaches WHERE coach_id = ?'
	);
	$statement->bind_param('i', $coach_id);
	$statement->execute();
	$editing = $statement->get_result()->fetch_assoc();
}

// Trains for the parent-train dropdown and all coaches for the directory table.
$trains = admin_query($conn, 'SELECT train_id, train_number, train_name FROM trains ORDER BY train_number');
$coaches = admin_query(
	$conn,
	'SELECT c.coach_id, c.coach_type, c.coach_number, c.seat_price, t.train_number, t.train_name
	 FROM coaches c
	 INNER JOIN trains t ON t.train_id = c.train_id
	 ORDER BY t.train_number, c.coach_number'
);

admin_header('Coaches', 'coaches');
?>

<!-- Flash messages -->
<?php if ($message !== ''): ?>
	<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
	<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Add / edit coach form (doubles as the edit form when ?edit=<id> is set) -->
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
					<select id="coach_type" name="coach_type" required>
						<option value="">Select coach type</option>
						<?php foreach (['2S', 'SL', '3A', '2A'] as $coachType): ?>
							<option value="<?= $coachType ?>" <?= ($editing['coach_type'] ?? '') === $coachType ? 'selected' : '' ?>><?= $coachType ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="coach_number">Coach number</label>
					<input id="coach_number" name="coach_number" value="<?= htmlspecialchars($editing['coach_number'] ?? '') ?>" placeholder="e.g. B1 or S1" required>
				</div>
				<div>
					<label for="seat_price">Seat price</label>
					<input id="seat_price" name="seat_price" type="number" min="0" step="0.01" value="<?= htmlspecialchars($editing['seat_price'] ?? '500') ?>" required>
				</div>
			</div>

			<!-- Hidden fields switch this form from "add" to "update" mode. -->
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

<!-- Coach directory table -->
<section class="panel">
	<div class="panel-heading">
		<h2>Coach directory</h2>
		<span class="muted"><?= $coaches ? $coaches->num_rows : 0 ?> records</span>
	</div>

	<?php if ($coaches && $coaches->num_rows > 0): ?>
		<div class="table-wrap">
			<table class="data-table">
				<thead><tr><th>Train</th><th>Coach number</th><th>Type</th><th>Seat price</th><th>Actions</th></tr></thead>
				<tbody>
					<?php while ($coach = $coaches->fetch_assoc()): ?>
						<tr>
							<td><strong><?= htmlspecialchars($coach['train_number']) ?></strong><br><span class="muted"><?= htmlspecialchars($coach['train_name']) ?></span></td>
							<td><span class="badge badge-blue"><?= htmlspecialchars($coach['coach_number']) ?></span></td>
							<td><?= htmlspecialchars($coach['coach_type']) ?></td>
							<td>₹<?= number_format((float) $coach['seat_price'], 2) ?></td>
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
