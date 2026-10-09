<?php
/**
 * Admin - Bookings management.
 *
 * Lists every booking in the system, lets an admin cancel a confirmed
 * booking (POST action=cancel) and inspect a single booking's full
 * details (?view=<id>) including its train timetable and passengers.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

// Flash messages rendered at the top of the page.
$message = '';
$error = '';

// Handle the "Cancel booking" action submitted from the overview table.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    // Only CONFIRMED bookings can be cancelled; affected_rows guards against double submits.
    $statement = $conn->prepare("UPDATE bookings SET status = 'CANCELLED' WHERE booking_id = ? AND status = 'CONFIRMED'");
    $statement->bind_param('i', $booking_id);

    if ($statement->execute() && $statement->affected_rows === 1) {
        $message = 'Booking cancelled successfully.';
    } else {
        $error = 'This booking is already cancelled or could not be found.';
    }
}

// Every booking (newest first) for the overview table.
$bookings = admin_query($conn, 'SELECT b.booking_id, b.pnr, u.full_name, t.train_number, t.train_name, b.travel_date, b.total_amount, b.status FROM bookings b LEFT JOIN users u ON u.user_id = b.user_id LEFT JOIN trains t ON t.train_id = b.train_id ORDER BY b.booked_at DESC');
$viewing = null;

// ?view=<id> - a specific booking was opened: load its full details.
if (isset($_GET['view'])) {
    $viewId = (int) $_GET['view'];
    $viewStatement = $conn->prepare(
        'SELECT b.*, u.full_name, u.username, u.email, u.phone,
                t.train_number, t.train_name, c.coach_number, c.coach_type,
                fs.station_name AS from_station, ts.station_name AS to_station
         FROM bookings b
         LEFT JOIN users u ON u.user_id = b.user_id
         LEFT JOIN trains t ON t.train_id = b.train_id
         LEFT JOIN coaches c ON c.coach_id = b.coach_id
         LEFT JOIN stations fs ON fs.station_id = b.from_station_id
         LEFT JOIN stations ts ON ts.station_id = b.to_station_id
         WHERE b.booking_id = ?'
    );
    $viewStatement->bind_param('i', $viewId);
    $viewStatement->execute();
    $viewing = $viewStatement->get_result()->fetch_assoc();
    $viewStatement->close();

    if ($viewing) {
        // Timetable of the booked train, in stop order.
        $routeStatement = $conn->prepare(
            'SELECT s.station_name, s.station_code, ts.arrival_time, ts.departure_time
             FROM train_stops ts
             INNER JOIN stations s ON s.station_id = ts.station_id
             WHERE ts.train_id = ?
             ORDER BY ts.stop_order'
        );
        $routeStatement->bind_param('i', $viewing['train_id']);
        $routeStatement->execute();
        $viewing['route'] = $routeStatement->get_result()->fetch_all(MYSQLI_ASSOC);
        $routeStatement->close();

        // Passenger rows for this booking with their assigned seat and chosen meal.
        $passengerStatement = $conn->prepare(
            'SELECT bp.passenger_name, bp.age, bp.gender, s.seat_number, s.seat_type, m.meal_name
             FROM booking_passengers bp
             LEFT JOIN seats s ON s.seat_id = bp.seat_id
             LEFT JOIN meals m ON m.meal_id = bp.meal_id
             WHERE bp.booking_id = ?
             ORDER BY CAST(s.seat_number AS UNSIGNED)'
        );
        $passengerStatement->bind_param('i', $viewId);
        $passengerStatement->execute();
        $viewing['passengers'] = $passengerStatement->get_result()->fetch_all(MYSQLI_ASSOC);
        $passengerStatement->close();
    }
}

admin_header('Bookings', 'bookings');
?>

<!-- Flash messages (feedback after cancelling a booking) -->
<?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Bookings overview table -->
<section class="panel">
    <div class="panel-heading">
        <h2>All bookings</h2>
        <span class="muted"><?= $bookings ? $bookings->num_rows : 0 ?> records</span>
    </div>
<?php 
if ($bookings && $bookings->num_rows > 0): 
?>
<div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>PNR</th>
        <th>Passenger</th>
        <th>Train</th>
        <th>Travel date</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
</thead>
<tbody>
    <?php 
    // Render one table row per booking.
    while ($booking = $bookings->fetch_assoc()): ?>
    <tr><td><strong><?= htmlspecialchars($booking['pnr']) ?></strong></td><td>
        <?= htmlspecialchars($booking['full_name'] ?? 'Unknown') ?>
    </td><td>
        <?= htmlspecialchars(trim(($booking['train_number'] ?? '') . ' ' . ($booking['train_name'] ?? ''))) ?>
    </td><td>
        <?= htmlspecialchars($booking['travel_date']) ?>
    </td><td>
        ₹<?= number_format((float) $booking['total_amount'], 2) ?>
        </td><td>
        <span class="badge <?= $booking['status'] === 'CANCELLED' ? 'badge-red' : 'badge-green' ?>">
            <?= htmlspecialchars($booking['status']) ?></span>
        </td><td>
            <div class="action-links">
                <a class="text-link" href="?view=<?= (int) $booking['booking_id'] ?>">More details</a>
                <?php if ($booking['status'] === 'CONFIRMED'): ?>
                    <form method="post" onsubmit="return confirm('Cancel this booking?');">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="booking_id" value="<?= (int) $booking['booking_id'] ?>">
                        <button class="button button-danger" type="submit">Cancel</button>
                    </form>
                <?php else: ?>
                    <span class="muted">No action</span>
                <?php endif; ?>
            </div>
        </td></tr><?php endwhile; ?></tbody></table></div><?php else: ?><div class="empty-state">No bookings have been made yet.</div><?php endif; ?>
</section>

<!-- Details panel for the booking opened via ?view=<id> -->
<?php if ($viewing): ?>
<section class="panel booking-details-panel">
    <div class="panel-heading">
        <h2>Booking details: <?= htmlspecialchars($viewing['pnr']) ?></h2>
        <a class="text-link" href="booking.php">Close details</a>
    </div>
    <div class="booking-detail-grid">
        <div><span class="muted">Booked by</span><strong><?= htmlspecialchars($viewing['full_name'] ?? 'Unknown') ?></strong></div>
        <div><span class="muted">Username</span><strong><?= htmlspecialchars($viewing['username'] ?? '-') ?></strong></div>
        <div><span class="muted">Email</span><strong><?= htmlspecialchars($viewing['email'] ?? '-') ?></strong></div>
        <div><span class="muted">Phone</span><strong><?= htmlspecialchars($viewing['phone'] ?? '-') ?></strong></div>
        <div><span class="muted">Train</span><strong><?= htmlspecialchars(($viewing['train_number'] ?? '') . ' - ' . ($viewing['train_name'] ?? '-')) ?></strong></div>
        <div><span class="muted">Route</span><strong><?= htmlspecialchars(($viewing['from_station'] ?? '-') . ' → ' . ($viewing['to_station'] ?? '-')) ?></strong></div>
        <div><span class="muted">Coach</span><strong><?= htmlspecialchars(($viewing['coach_number'] ?? '-') . ' (' . ($viewing['coach_type'] ?? '-') . ')') ?></strong></div>
        <div><span class="muted">Travel date</span><strong><?= htmlspecialchars($viewing['travel_date']) ?></strong></div>
        <div><span class="muted">Ticket fare</span><strong>₹<?= number_format((float) $viewing['ticket_fare'], 2) ?></strong></div>
        <div><span class="muted">Meal fare</span><strong>₹<?= number_format((float) $viewing['meal_fare'], 2) ?></strong></div>
        <div><span class="muted">Total amount</span><strong>₹<?= number_format((float) $viewing['total_amount'], 2) ?></strong></div>
        <div><span class="muted">Status</span><strong><?= htmlspecialchars($viewing['status']) ?></strong></div>
    </div>

    <!-- Timetable of the booked train, in stop order -->
    <h3 class="booking-details-heading">Train timetable</h3>
    <?php if ($viewing['route']): ?>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Stop</th><th>Arrival</th><th>Departure</th></tr></thead><tbody>
            <?php foreach ($viewing['route'] as $stop): ?>
                <tr><td><strong><?= htmlspecialchars($stop['station_name']) ?></strong> <span class="muted">(<?= htmlspecialchars($stop['station_code']) ?>)</span></td><td><?= htmlspecialchars($stop['arrival_time'] ?: '-') ?></td><td><?= htmlspecialchars($stop['departure_time'] ?: '-') ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?><p class="muted">No timetable stops recorded.</p><?php endif; ?>

    <!-- Passengers on this booking, with seat number and meal -->
    <h3 class="booking-details-heading">Passengers</h3>
    <?php if ($viewing['passengers']): ?>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Name</th><th>Age</th><th>Gender</th><th>Seat</th><th>Food</th></tr></thead><tbody>
            <?php foreach ($viewing['passengers'] as $passenger): ?>
                <tr><td><strong><?= htmlspecialchars($passenger['passenger_name']) ?></strong></td><td><?= (int) $passenger['age'] ?></td><td><?= htmlspecialchars($passenger['gender']) ?></td><td><?= htmlspecialchars($passenger['seat_number'] ?? '-') ?></td><td><?= htmlspecialchars($passenger['meal_name'] ?? 'No food') ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?><p class="muted">No passenger detail rows recorded.</p><?php endif; ?>
</section>
<?php endif; ?>

<?php admin_footer(); ?>