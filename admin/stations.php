<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

$message = '';
$error = '';
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $station_id = (int) ($_POST['station_id'] ?? 0);
    $station_code = strtoupper(trim($_POST['station_code'] ?? ''));
    $station_name = trim($_POST['station_name'] ?? '');
    $city = trim($_POST['city'] ?? '');

    if ($action === 'delete') {
        $statement = $conn->prepare('DELETE FROM stations WHERE station_id = ?');
        $statement->bind_param('i', $station_id);
        if ($statement->execute()) {
            $message = 'Station deleted successfully.';
        } else {
            $error = 'This station is being used by a train route or booking and cannot be deleted.';
        }
    } elseif ($station_code === '' || $station_name === '' || $city === '') {
        $error = 'Station code, station name, and city are required.';
    } elseif ($action === 'update') {
        $statement = $conn->prepare('UPDATE stations SET station_code = ?, station_name = ?, city = ? WHERE station_id = ?');
        $statement->bind_param('sssi', $station_code, $station_name, $city, $station_id);
        if ($statement->execute()) {
            $message = 'Station updated successfully.';
        } elseif ($statement->errno === 1062) {
            $error = 'That station code already exists.';
        } else {
            $error = 'Could not update the station.';
        }
    } else {
        $statement = $conn->prepare('INSERT INTO stations (station_code, station_name, city) VALUES (?, ?, ?)');
        $statement->bind_param('sss', $station_code, $station_name, $city);
        if ($statement->execute()) {
            $message = 'Station added successfully.';
        } elseif ($statement->errno === 1062) {
            $error = 'That station code already exists.';
        } else {
            $error = 'Could not add the station.';
        }
    }
}

if (isset($_GET['edit'])) {
    $station_id = (int) $_GET['edit'];
    $statement = $conn->prepare('SELECT station_id, station_code, station_name, city FROM stations WHERE station_id = ?');
    $statement->bind_param('i', $station_id);
    $statement->execute();
    $editing = $statement->get_result()->fetch_assoc();
}

$stations = admin_query($conn, 'SELECT station_id, station_code, station_name, city FROM stations ORDER BY station_code');
admin_header('Stations', 'stations');
?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<section class="panel" style="margin-bottom: 20px">
    <div class="panel-heading"><h2><?= $editing ? 'Edit station' : 'Add a station' ?></h2><?php if ($editing): ?><a class="text-link" href="stations.php">Cancel edit</a><?php endif; ?></div>
    <form method="post">
        <div class="form-grid">
            <div><label for="station_code">Station code</label><input id="station_code" name="station_code" value="<?= htmlspecialchars($editing['station_code'] ?? '') ?>" placeholder="e.g. HWH" maxlength="10" required></div>
            <div><label for="station_name">Station name</label><input id="station_name" name="station_name" value="<?= htmlspecialchars($editing['station_name'] ?? '') ?>" placeholder="e.g. Howrah Junction" required></div>
            <div><label for="city">City</label><input id="city" name="city" value="<?= htmlspecialchars($editing['city'] ?? '') ?>" placeholder="e.g. Kolkata" required></div>
        </div>
        <?php if ($editing): ?><input type="hidden" name="action" value="update"><input type="hidden" name="station_id" value="<?= (int) $editing['station_id'] ?>"><?php endif; ?>
        <div class="form-actions"><button class="button button-primary" type="submit"><?= $editing ? 'Save changes' : 'Add station' ?></button></div>
    </form>
</section>

<section class="panel"><div class="panel-heading"><h2>Station directory</h2><span class="muted"><?= $stations ? $stations->num_rows : 0 ?> records</span></div>
<?php if ($stations && $stations->num_rows > 0): ?><div class="table-wrap"><table class="data-table"><thead><tr><th>Code</th><th>Station</th><th>City</th><th>Actions</th></tr></thead><tbody>
<?php while ($station = $stations->fetch_assoc()): ?><tr><td><span class="badge badge-blue"><?= htmlspecialchars($station['station_code']) ?></span></td><td><strong><?= htmlspecialchars($station['station_name']) ?></strong></td><td><?= htmlspecialchars($station['city']) ?></td><td><div class="action-links"><a href="?edit=<?= (int) $station['station_id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this station?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="station_id" value="<?= (int) $station['station_id'] ?>"><button class="button button-danger" type="submit">Delete</button></form></div></td></tr><?php endwhile; ?>
</tbody></table></div><?php else: ?><div class="empty-state">No stations have been added yet.</div><?php endif; ?></section>
<?php admin_footer(); ?>