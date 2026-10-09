<?php
/**
 * Admin - Dashboard.
 *
 * Landing page of the admin panel: headline counters for users, trains,
 * bookings and support tickets, plus the five most recent bookings and
 * the five most recently added trains.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

// Row counts for the four stat cards.
$stats = [
	'users' => admin_count($conn, 'users'),
	'trains' => admin_count($conn, 'trains'),
	'bookings' => admin_count($conn, 'bookings'),
	'support_tickets' => admin_count($conn, 'support_tickets'),
];
// Last 5 bookings and last 5 added trains for the panels below.
$recent_trains = admin_query($conn, 'SELECT train_id, train_number, train_name FROM trains ORDER BY train_id DESC LIMIT 5');
$recent_bookings = admin_query($conn, 'SELECT pnr, travel_date, total_amount, status FROM bookings ORDER BY booked_at DESC LIMIT 5');

admin_header('Dashboard', 'dashboard');
?>
<!-- Stat cards: users, trains, bookings, support tickets -->
<section class="stats-grid">
	<article class="stat-card"><span class="stat-icon">U</span><p>Total users</p><strong><?= $stats['users'] ?></strong></article>
	<article class="stat-card"><span class="stat-icon">T</span><p>Active trains</p><strong><?= $stats['trains'] ?></strong></article>
	<article class="stat-card"><span class="stat-icon">B</span><p>Total bookings</p><strong><?= $stats['bookings'] ?></strong></article>
	<article class="stat-card"><span class="stat-icon">?</span><p>Support requests</p><strong><?= $stats['support_tickets'] ?></strong></article>
</section>

<!-- Recent bookings and train inventory panels -->
<section class="dashboard-grid">
	<article class="panel">
		<div class="panel-heading"><h2>Recent bookings</h2><a class="text-link" href="booking.php">View all</a></div>
		<?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
			<div class="table-wrap"><table class="data-table"><thead><tr><th>PNR</th><th>Travel date</th><th>Amount</th><th>Status</th></tr></thead><tbody>
				<?php while ($booking = $recent_bookings->fetch_assoc()): ?>
					<tr><td><strong><?= htmlspecialchars($booking['pnr']) ?></strong></td><td><?= htmlspecialchars($booking['travel_date']) ?></td><td>₹<?= number_format((float) $booking['total_amount'], 2) ?></td><td><span class="badge <?= $booking['status'] === 'CANCELLED' ? 'badge-red' : 'badge-green' ?>"><?= htmlspecialchars($booking['status']) ?></span></td></tr>
				<?php endwhile; ?>
			</tbody></table></div>
		<?php else: ?><div class="empty-state">No bookings have been recorded yet.</div><?php endif; ?>
	</article>
	<article class="panel">
		<div class="panel-heading"><h2>Train inventory</h2><a class="text-link" href="trains.php">Manage trains</a></div>
		<?php if ($recent_trains && $recent_trains->num_rows > 0): ?>
			<table class="data-table"><thead><tr><th>Number</th><th>Name</th></tr></thead><tbody>
				<?php while ($train = $recent_trains->fetch_assoc()): ?><tr><td><span class="badge badge-blue"><?= htmlspecialchars($train['train_number']) ?></span></td><td><?= htmlspecialchars($train['train_name']) ?></td></tr><?php endwhile; ?>
			</tbody></table>
		<?php else: ?><div class="empty-state">No trains have been added yet.</div><?php endif; ?>
	</article>
</section>
<?php admin_footer(); ?>
