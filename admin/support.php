<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

$tickets = admin_query($conn, 'SELECT s.ticket_id, s.subject, s.message, s.status, s.created_at, u.full_name FROM support_tickets s LEFT JOIN users u ON u.user_id = s.user_id ORDER BY s.created_at DESC');

admin_header('Support', 'support');
?>

<section class="panel">
    <div class="panel-heading">
        <h2>Support requests</h2>
        <span class="muted">
            <?= $tickets ? $tickets->num_rows : 0 ?> 
            tickets</span>
        </div>
<?php if ($tickets && $tickets->num_rows > 0): ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Subject</th>
                <th>Customer</th>
                <th>Message</th>
                <th>Status</th>
                <th>Received</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($ticket = $tickets->fetch_assoc()): ?>
                <tr><td>
                    <strong><?= htmlspecialchars($ticket['subject']) ?></strong>
                </td><td>
                    <?= htmlspecialchars($ticket['full_name'] ?? 'Unknown') ?>
                </td><td>
                    <?= htmlspecialchars(strlen($ticket['message']) > 70 ? substr($ticket['message'], 0, 70) . '...' : $ticket['message']) ?>
                </td><td>
                    <span class="badge <?= $ticket['status'] === 'RESOLVED' ? 'badge-green' : 'badge-blue' ?>"><?= htmlspecialchars($ticket['status']) ?>
                </span></td>
                <td>
                    <?= htmlspecialchars(date('d M Y', strtotime($ticket['created_at']))) ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <div class="empty-state">No support requests have arrived.</div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>