<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $statement = $conn->prepare("UPDATE bookings SET status = 'CANCELLED' WHERE booking_id = ? AND status = 'CONFIRMED'");
    $statement->bind_param('i', $booking_id);

    if ($statement->execute() && $statement->affected_rows === 1) {
        $message = 'Booking cancelled successfully.';
    } else {
        $error = 'This booking is already cancelled or could not be found.';
    }
}

$bookings = admin_query($conn, 'SELECT b.booking_id, b.pnr, u.full_name, t.train_number, t.train_name, b.travel_date, b.total_amount, b.status FROM bookings b LEFT JOIN users u ON u.user_id = b.user_id LEFT JOIN trains t ON t.train_id = b.train_id ORDER BY b.booked_at DESC');

admin_header('Bookings', 'bookings');
?>

<?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

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
            <?php if ($booking['status'] === 'CONFIRMED'): ?>
                <form method="post" onsubmit="return confirm('Cancel this booking?');">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="booking_id" value="<?= (int) $booking['booking_id'] ?>">
                    <button class="button button-danger" type="submit">Cancel</button>
                </form>
            <?php else: ?>
                <span class="muted">No action</span>
            <?php endif; ?>
        </td></tr><?php endwhile; ?></tbody></table></div><?php else: ?><div class="empty-state">No bookings have been made yet.</div><?php endif; ?>
</section>

<?php admin_footer(); ?>