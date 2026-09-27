<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

$message = '';
$error = '';
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$train_number = trim($_POST['train_number'] ?? '');
	$train_name = trim($_POST['train_name'] ?? '');

	if ($action === 'delete') {
		$train_id = (int) ($_POST['train_id'] ?? 0);
		$statement = $conn->prepare('DELETE FROM trains WHERE train_id = ?');
		$statement->bind_param('i', $train_id);
		$statement->execute();
		$message = 'Train deleted successfully.';
	} elseif ($train_number === '' || $train_name === '') {
		$error = 'Train number and train name are required.';
	} elseif ($action === 'update') {
		$train_id = (int) ($_POST['train_id'] ?? 0);
		$statement = $conn->prepare('UPDATE trains SET train_number = ?, train_name = ? WHERE train_id = ?');
		$statement->bind_param('ssi', $train_number, $train_name, $train_id);
		$message = $statement->execute() ? 'Train updated successfully.' : 'Could not update that train.';
		if ($statement->errno === 1062) {
			$error = 'That train number already exists.';
			$message = '';
		}
	} else {
		$statement = $conn->prepare('INSERT INTO trains (train_number, train_name) VALUES (?, ?)');
		$statement->bind_param('ss', $train_number, $train_name);
		$message = $statement->execute() ? 'Train added successfully.' : 'Could not add that train.';
		if ($statement->errno === 1062) {
			$error = 'That train number already exists.';
			$message = '';
		}
	}
}

if (isset($_GET['edit'])) {
	$train_id = (int) $_GET['edit'];
	$statement = $conn->prepare('SELECT train_id, train_number, train_name FROM trains WHERE train_id = ?');
	$statement->bind_param('i', $train_id);
	$statement->execute();
	$editing = $statement->get_result()->fetch_assoc();
}

$trains = admin_query($conn, 'SELECT train_id, train_number, train_name FROM trains ORDER BY train_id DESC');
admin_header('Trains', 'trains');
?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<section class="panel" style="margin-bottom:20px">
	<div class="panel-heading"><h2><?= $editing ? 'Edit train' : 'Add a train' ?></h2><?php if ($editing): ?><a class="text-link" href="trains.php">Cancel edit</a><?php endif; ?></div>
	<form method="post">
		<div class="form-grid">
			<div><label for="train_number">Train number</label><input id="train_number" name="train_number" value="<?= htmlspecialchars($editing['train_number'] ?? '') ?>" placeholder="e.g. 12345" required></div>
			<div><label for="train_name">Train name</label><input id="train_name" name="train_name" value="<?= htmlspecialchars($editing['train_name'] ?? '') ?>" placeholder="e.g. Rajdhani Express" required></div>
		</div>
		<?php if ($editing): ?><input type="hidden" name="action" value="update"><input type="hidden" name="train_id" value="<?= (int) $editing['train_id'] ?>"><?php endif; ?>
		<div class="form-actions"><button class="button button-primary" type="submit"><?= $editing ? 'Save changes' : 'Add train' ?></button></div>
	</form>
</section>
<section class="panel">
	<div class="panel-heading"><h2>Train directory</h2><span class="muted"><?= $trains ? $trains->num_rows : 0 ?> records</span></div>
	<?php if ($trains && $trains->num_rows > 0): ?><div class="table-wrap"><table class="data-table"><thead><tr><th>Number</th><th>Train name</th><th>Actions</th></tr></thead><tbody>
		<?php while ($train = $trains->fetch_assoc()): ?><tr><td><span class="badge badge-blue"><?= htmlspecialchars($train['train_number']) ?></span></td><td><?= htmlspecialchars($train['train_name']) ?></td><td><div class="action-links"><a href="?edit=<?= (int) $train['train_id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this train?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="train_id" value="<?= (int) $train['train_id'] ?>"><button class="button button-danger" type="submit">Delete</button></form></div></td></tr><?php endwhile; ?>
	</tbody></table></div><?php else: ?><div class="empty-state">Your train directory is empty.</div><?php endif; ?>
</section>
<?php admin_footer(); ?>
