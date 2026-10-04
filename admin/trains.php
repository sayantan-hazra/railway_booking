<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

$message = '';
$error = '';
$editing = null;
$viewing = null;
$syncTrainDetails = static function (mysqli $conn, int $trainId, int $fromStationId, int $toStationId, array $viaStationIds, array $mealIds, string $fromArrival, string $fromDeparture, string $toArrival, string $toDeparture, array $viaArrivals, array $viaDepartures): bool {
	$deleteMeals = $conn->prepare('DELETE FROM train_meals WHERE train_id = ?');
	$deleteMeals->bind_param('i', $trainId);
	$deleteMeals->execute();
	$deleteMeals->close();

	$mealStatement = $conn->prepare('INSERT INTO train_meals (train_id, meal_id) VALUES (?, ?)');
	foreach ($mealIds as $mealId) {
		$mealStatement->bind_param('ii', $trainId, $mealId);
		if (!$mealStatement->execute()) {
			$mealStatement->close();
			return false;
		}
	}
	$mealStatement->close();

	$deleteStops = $conn->prepare('DELETE FROM train_stops WHERE train_id = ?');
	$deleteStops->bind_param('i', $trainId);
	$deleteStops->execute();
	$deleteStops->close();

	$stopStatement = $conn->prepare(
		'INSERT INTO train_stops (train_id, station_id, stop_order, arrival_time, departure_time) VALUES (?, ?, ?, ?, ?)'
	);
	$routeStops = [['id' => $fromStationId, 'arrival' => $fromArrival, 'departure' => $fromDeparture]];
	foreach ($viaStationIds as $index => $stationId) {
		$routeStops[] = [
			'id' => $stationId,
			'arrival' => $viaArrivals[$index] ?? '',
			'departure' => $viaDepartures[$index] ?? '',
		];
	}
	$routeStops[] = ['id' => $toStationId, 'arrival' => $toArrival, 'departure' => $toDeparture];
	$stopOrder = 1;
	$stopsSaved = true;
	foreach ($routeStops as $stop) {
		$stationId = (int) $stop['id'];
		$arrival = $stop['arrival'] !== '' ? $stop['arrival'] : null;
		$departure = $stop['departure'] !== '' ? $stop['departure'] : null;
		$stopStatement->bind_param('iiiss', $trainId, $stationId, $stopOrder, $arrival, $departure);
		$stopsSaved = $stopStatement->execute() && $stopsSaved;
		$stopOrder++;
	}
	$stopStatement->close();

	return $stopsSaved;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$train_number = trim($_POST['train_number'] ?? '');
	$train_name = trim($_POST['train_name'] ?? '');
	$from_station_id = (int) ($_POST['from_station_id'] ?? 0);
	$to_station_id = (int) ($_POST['to_station_id'] ?? 0);
	$via_station_ids = array_values(array_unique(array_filter(array_map('intval', $_POST['via_station_ids'] ?? []))));
	$from_arrival = trim($_POST['from_arrival'] ?? '');
	$from_departure = trim($_POST['from_departure'] ?? '');
	$to_arrival = trim($_POST['to_arrival'] ?? '');
	$to_departure = trim($_POST['to_departure'] ?? '');
	$via_arrivals = $_POST['via_arrival'] ?? [];
	$via_departures = $_POST['via_departure'] ?? [];
	$meal_ids = array_values(array_filter(array_map('intval', $_POST['meal_ids'] ?? [])));

	if ($action === 'delete') {
		$train_id = (int) ($_POST['train_id'] ?? 0);
		$usageStatement = $conn->prepare('SELECT COUNT(*) AS total FROM bookings WHERE train_id = ?');
		$usageStatement->bind_param('i', $train_id);
		$usageStatement->execute();
		$usageCount = (int) $usageStatement->get_result()->fetch_assoc()['total'];
		$usageStatement->close();

		if ($usageCount > 0) {
			$error = 'This train cannot be deleted because it is referenced by a booking.';
		} else {
			try {
				$conn->begin_transaction();
				$seatStatement = $conn->prepare(
					'DELETE s FROM seats s INNER JOIN coaches c ON c.coach_id = s.coach_id WHERE c.train_id = ?'
				);
				$seatStatement->bind_param('i', $train_id);
				$seatStatement->execute();
				$seatStatement->close();

				$coachStatement = $conn->prepare('DELETE FROM coaches WHERE train_id = ?');
				$coachStatement->bind_param('i', $train_id);
				$coachStatement->execute();
				$coachStatement->close();

				$stopStatement = $conn->prepare('DELETE FROM train_stops WHERE train_id = ?');
				$stopStatement->bind_param('i', $train_id);
				$stopStatement->execute();
				$stopStatement->close();

				$trainStatement = $conn->prepare('DELETE FROM trains WHERE train_id = ?');
				$trainStatement->bind_param('i', $train_id);
				$trainStatement->execute();
				$trainStatement->close();
				$conn->commit();
				$message = 'Train deleted successfully.';
			} catch (mysqli_sql_exception $exception) {
				$conn->rollback();
				$error = 'This train could not be deleted because it is still in use.';
			}
		}
	} elseif ($train_number === '' || $train_name === '' || $from_station_id <= 0 || $to_station_id <= 0 || $from_station_id === $to_station_id || in_array($from_station_id, $via_station_ids, true) || in_array($to_station_id, $via_station_ids, true)) {
		$error = 'Train number, train name, different origin and destination stations are required.';
	} elseif ($action === 'update') {
		$train_id = (int) ($_POST['train_id'] ?? 0);
		$statement = $conn->prepare('UPDATE trains SET train_number = ?, train_name = ? WHERE train_id = ?');
		$statement->bind_param('ssi', $train_number, $train_name, $train_id);
		$message = $statement->execute() && $syncTrainDetails($conn, $train_id, $from_station_id, $to_station_id, $via_station_ids, $meal_ids, $from_arrival, $from_departure, $to_arrival, $to_departure, $via_arrivals, $via_departures)
			? 'Train updated successfully.' : 'Could not update that train.';
		if ($statement->errno === 1062) {
			$error = 'That train number already exists.';
			$message = '';
		}
	} else {
		$statement = $conn->prepare('INSERT INTO trains (train_number, train_name) VALUES (?, ?)');
		$statement->bind_param('ss', $train_number, $train_name);
		$message = $statement->execute() && $syncTrainDetails($conn, $conn->insert_id, $from_station_id, $to_station_id, $via_station_ids, $meal_ids, $from_arrival, $from_departure, $to_arrival, $to_departure, $via_arrivals, $via_departures)
			? 'Train added successfully.' : 'Could not add that train.';
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
	$statement->close();

	if ($editing) {
		$routeStatement = $conn->prepare(
			'SELECT station_id, arrival_time, departure_time FROM train_stops WHERE train_id = ? ORDER BY stop_order'
		);
		$routeStatement->bind_param('i', $train_id);
		$routeStatement->execute();
		$route = $routeStatement->get_result()->fetch_all(MYSQLI_ASSOC);
		$routeStatement->close();
		$routeStationIds = array_map(static fn (array $stop): int => (int) $stop['station_id'], $route);
		$editing['from_station_id'] = $routeStationIds[0] ?? 0;
		$editing['to_station_id'] = $routeStationIds[count($routeStationIds) - 1] ?? 0;
		$editing['from_arrival'] = $route[0]['arrival_time'] ?? '';
		$editing['from_departure'] = $route[0]['departure_time'] ?? '';
		$editing['to_arrival'] = $route[count($route) - 1]['arrival_time'] ?? '';
		$editing['to_departure'] = $route[count($route) - 1]['departure_time'] ?? '';
		$editing['via_station_ids'] = count($routeStationIds) > 2
			? array_slice($routeStationIds, 1, -1)
			: [];
		$editing['via_arrivals'] = count($route) > 2
			? array_column(array_slice($route, 1, -1), 'arrival_time')
			: [];
		$editing['via_departures'] = count($route) > 2
			? array_column(array_slice($route, 1, -1), 'departure_time')
			: [];

		$mealStatement = $conn->prepare('SELECT meal_id FROM train_meals WHERE train_id = ?');
		$mealStatement->bind_param('i', $train_id);
		$mealStatement->execute();
		$editing['meal_ids'] = array_map(
			static fn (array $meal): int => (int) $meal['meal_id'],
			$mealStatement->get_result()->fetch_all(MYSQLI_ASSOC)
		);
		$mealStatement->close();
	}
}

if (isset($_GET['view'])) {
	$viewId = (int) $_GET['view'];
	$viewStatement = $conn->prepare('SELECT train_id, train_number, train_name FROM trains WHERE train_id = ?');
	$viewStatement->bind_param('i', $viewId);
	$viewStatement->execute();
	$viewing = $viewStatement->get_result()->fetch_assoc();
	$viewStatement->close();

	if ($viewing) {
		$routeStatement = $conn->prepare(
			'SELECT s.station_name, s.station_code, s.city, ts.arrival_time, ts.departure_time
			 FROM train_stops ts
			 INNER JOIN stations s ON s.station_id = ts.station_id
			 WHERE ts.train_id = ?
			 ORDER BY ts.stop_order'
		);
		$routeStatement->bind_param('i', $viewId);
		$routeStatement->execute();
		$viewing['route'] = $routeStatement->get_result()->fetch_all(MYSQLI_ASSOC);
		$routeStatement->close();

		$mealStatement = $conn->prepare(
			'SELECT m.meal_name, m.price
			 FROM train_meals tm
			 INNER JOIN meals m ON m.meal_id = tm.meal_id
			 WHERE tm.train_id = ?
			 ORDER BY m.meal_name'
		);
		$mealStatement->bind_param('i', $viewId);
		$mealStatement->execute();
		$viewing['meals'] = $mealStatement->get_result()->fetch_all(MYSQLI_ASSOC);
		$mealStatement->close();
	}
}

$stations = admin_query($conn, 'SELECT station_id, station_code, station_name, city FROM stations ORDER BY station_name');
$meals = admin_query($conn, 'SELECT meal_id, meal_name, price FROM meals ORDER BY meal_name');
$stationOptions = $stations ? $stations->fetch_all(MYSQLI_ASSOC) : [];
$mealOptions = $meals ? $meals->fetch_all(MYSQLI_ASSOC) : [];
	$trains = admin_query($conn, "SELECT t.train_id, t.train_number, t.train_name,
	GROUP_CONCAT(CONCAT(s.station_code, ' ', COALESCE(DATE_FORMAT(ts.arrival_time, '%H:%i'), '--:--'), '/', COALESCE(DATE_FORMAT(ts.departure_time, '%H:%i'), '--:--')) ORDER BY ts.stop_order SEPARATOR ' -> ') AS timetable
	FROM trains t
	LEFT JOIN train_stops ts ON ts.train_id = t.train_id
	LEFT JOIN stations s ON s.station_id = ts.station_id
	GROUP BY t.train_id, t.train_number, t.train_name
	ORDER BY t.train_id DESC");
admin_header('Trains', 'trains');
?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($viewing): ?>
<section class="panel" style="margin-bottom:20px">
	<div class="panel-heading">
		<h2><?= htmlspecialchars($viewing['train_name']) ?> details</h2>
		<div class="action-links"><a class="text-link" href="?edit=<?= (int) $viewing['train_id'] ?>">Edit train</a><a class="text-link" href="trains.php">Close</a></div>
	</div>
	<div class="form-grid">
		<div><span class="muted">Train number</span><strong><?= htmlspecialchars($viewing['train_number']) ?></strong></div>
		<div><span class="muted">Timetable</span><strong><?= $viewing['route'] ? htmlspecialchars(implode(' → ', array_map(static fn (array $stop): string => $stop['station_name'] . ' (' . $stop['station_code'] . ') ' . ($stop['arrival_time'] ?: '--:--') . '/' . ($stop['departure_time'] ?: '--:--'), $viewing['route']))) : 'No stations assigned' ?></strong></div>
		<div><span class="muted">Meals</span><strong><?= $viewing['meals'] ? htmlspecialchars(implode(', ', array_map(static fn (array $meal): string => $meal['meal_name'] . ' (₹' . number_format((float) $meal['price'], 2) . ')', $viewing['meals']))) : 'No meals assigned' ?></strong></div>
	</div>
</section>
<?php endif; ?>
<section class="panel" style="margin-bottom:20px">
	<div class="panel-heading"><h2><?= $editing ? 'Edit train' : 'Add a train' ?></h2><?php if ($editing): ?><a class="text-link" href="trains.php">Cancel edit</a><?php endif; ?></div>
	<form method="post">
		<div class="form-grid">
			<div><label for="train_number">Train number</label><input id="train_number" name="train_number" value="<?= htmlspecialchars($editing['train_number'] ?? '') ?>" placeholder="e.g. 12345" required></div>
			<div><label for="train_name">Train name</label><input id="train_name" name="train_name" value="<?= htmlspecialchars($editing['train_name'] ?? '') ?>" placeholder="e.g. Rajdhani Express" required></div>
			<div>
				<label for="from_station_id">From station</label>
				<select id="from_station_id" name="from_station_id" required>
					<option value="">Select origin station</option>
					<?php foreach ($stationOptions as $station): ?>
						<option value="<?= (int) $station['station_id'] ?>" <?= ((int) ($editing['from_station_id'] ?? 0) === (int) $station['station_id']) ? 'selected' : '' ?>>
							<?= htmlspecialchars($station['station_name'] . ' (' . $station['station_code'] . ') - ' . $station['city']) ?>
						</option>
					<?php endforeach; ?>
				</select>
				<div class="time-fields">
					<input type="time" name="from_arrival" value="<?= htmlspecialchars($editing['from_arrival'] ?? '') ?>" aria-label="Origin arrival time">
					<input type="time" name="from_departure" value="<?= htmlspecialchars($editing['from_departure'] ?? '') ?>" aria-label="Origin departure time">
				</div>
			</div>
			<div>
				<label for="to_station_id">To station</label>
				<select id="to_station_id" name="to_station_id" required>
					<option value="">Select destination station</option>
					<?php foreach ($stationOptions as $station): ?>
						<option value="<?= (int) $station['station_id'] ?>" <?= ((int) ($editing['to_station_id'] ?? 0) === (int) $station['station_id']) ? 'selected' : '' ?>>
							<?= htmlspecialchars($station['station_name'] . ' (' . $station['station_code'] . ') - ' . $station['city']) ?>
						</option>
					<?php endforeach; ?>
				</select>
				<div class="time-fields">
					<input type="time" name="to_arrival" value="<?= htmlspecialchars($editing['to_arrival'] ?? '') ?>" aria-label="Destination arrival time">
					<input type="time" name="to_departure" value="<?= htmlspecialchars($editing['to_departure'] ?? '') ?>" aria-label="Destination departure time">
				</div>
			</div>
			<div>
				<label for="via_station_ids">Sub-stations</label>
				<div id="via-station-fields">
					<?php $viaValues = $editing['via_station_ids'] ?? [0]; foreach ($viaValues as $viaIndex => $selectedVia): ?>
						<div class="repeatable-row">
							<select name="via_station_ids[]">
								<option value="">Select a sub-station</option>
								<?php foreach ($stationOptions as $station): ?>
									<option value="<?= (int) $station['station_id'] ?>" <?= (int) $selectedVia === (int) $station['station_id'] ? 'selected' : '' ?>><?= htmlspecialchars($station['station_name'] . ' (' . $station['station_code'] . ')') ?></option>
								<?php endforeach; ?>
							</select>
							<input type="time" name="via_arrival[]" value="<?= htmlspecialchars($editing['via_arrivals'][$viaIndex] ?? '') ?>" aria-label="Sub-station arrival time">
							<input type="time" name="via_departure[]" value="<?= htmlspecialchars($editing['via_departures'][$viaIndex] ?? '') ?>" aria-label="Sub-station departure time">
							<button type="button" class="button button-muted remove-repeatable">Remove</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button button-muted" id="add-via-station">Add sub-station</button>
			</div>
			<div>
				<label for="meal_ids">Meals available on this train</label>
				<div id="meal-fields">
					<?php $mealValues = $editing['meal_ids'] ?? [0]; foreach ($mealValues as $selectedMeal): ?>
						<div class="repeatable-row">
							<select name="meal_ids[]">
								<option value="">Select a meal</option>
								<?php foreach ($mealOptions as $meal): ?>
									<option value="<?= (int) $meal['meal_id'] ?>" <?= (int) $selectedMeal === (int) $meal['meal_id'] ? 'selected' : '' ?>><?= htmlspecialchars($meal['meal_name']) ?> (₹<?= number_format((float) $meal['price'], 2) ?>)</option>
								<?php endforeach; ?>
							</select>
							<button type="button" class="button button-muted remove-repeatable">Remove</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button button-muted" id="add-meal">Add meal</button>
			</div>
		</div>
		<?php if ($editing): ?><input type="hidden" name="action" value="update"><input type="hidden" name="train_id" value="<?= (int) $editing['train_id'] ?>"><?php endif; ?>
		<div class="form-actions"><button class="button button-primary" type="submit"><?= $editing ? 'Save changes' : 'Add train' ?></button></div>
	</form>
</section>
<section class="panel">
	<div class="panel-heading"><h2>Train directory</h2><span class="muted"><?= $trains ? $trains->num_rows : 0 ?> records</span></div>
	<?php if ($trains && $trains->num_rows > 0): ?><div class="table-wrap"><table class="data-table"><thead><tr><th>Number</th><th>Train name</th><th>Actions</th></tr></thead><tbody>
		<?php while ($train = $trains->fetch_assoc()): ?><tr><td><span class="badge badge-blue"><?= htmlspecialchars($train['train_number']) ?></span></td><td><strong><?= htmlspecialchars($train['train_name']) ?></strong><br><a class="text-link" href="?view=<?= (int) $train['train_id'] ?>">View details</a></td><td><?= htmlspecialchars($train['timetable'] ?? 'No stops assigned') ?></td><td><div class="action-links"><a href="?edit=<?= (int) $train['train_id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this train?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="train_id" value="<?= (int) $train['train_id'] ?>"><button class="button button-danger" type="submit">Delete</button></form></div></td></tr><?php endwhile; ?>
	</tbody></table></div><?php else: ?><div class="empty-state">Your train directory is empty.</div><?php endif; ?>
</section>
<script>
	function setupRepeatableFields(containerId, addButtonId) {
		const container = document.getElementById(containerId);
		const addButton = document.getElementById(addButtonId);

		addButton.addEventListener('click', () => {
			const row = container.querySelector('.repeatable-row').cloneNode(true);
			row.querySelector('select').value = '';
			row.querySelectorAll('input[type="time"]').forEach((input) => input.value = '');
			container.appendChild(row);
		});

		container.addEventListener('click', (event) => {
			if (!event.target.classList.contains('remove-repeatable')) return;

			const rows = container.querySelectorAll('.repeatable-row');
			if (rows.length === 1) {
				rows[0].querySelector('select').value = '';
				rows[0].querySelectorAll('input[type="time"]').forEach((input) => input.value = '');
				return;
			}
			event.target.closest('.repeatable-row').remove();
		});
	}

	setupRepeatableFields('via-station-fields', 'add-via-station');
	setupRepeatableFields('meal-fields', 'add-meal');
</script>
<?php admin_footer(); ?>
