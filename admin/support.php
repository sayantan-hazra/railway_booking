<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $status = $_POST['status'] ?? 'OPEN';
    $adminReply = trim($_POST['admin_reply'] ?? '');
    $allowedStatuses = ['OPEN', 'IN_PROGRESS', 'RESOLVED'];

    if (!in_array($status, $allowedStatuses, true)) {
        $error = 'Invalid support status.';
    } else {
        if ($adminReply !== '' && $status === 'OPEN') {
            $status = 'RESOLVED';
        }
        if ($status === 'RESOLVED') {
            $statement = $conn->prepare(
                'UPDATE support_tickets SET status = ?, admin_reply = ?, resolved_at = CURRENT_TIMESTAMP WHERE ticket_id = ?'
            );
            $statement->bind_param('ssi', $status, $adminReply, $ticketId);
        } else {
            $statement = $conn->prepare(
                'UPDATE support_tickets SET status = ?, admin_reply = ?, resolved_at = NULL WHERE ticket_id = ?'
            );
            $statement->bind_param('ssi', $status, $adminReply, $ticketId);
        }
        if ($statement->execute()) {
            $message = 'Support ticket updated successfully.';
        } else {
            $error = 'Could not update the support ticket.';
        }
        $statement->close();
    }
}

$tickets = admin_query($conn, 'SELECT s.ticket_id, s.subject, s.message, s.admin_reply, s.status, s.created_at, s.resolved_at, u.full_name, b.pnr FROM support_tickets s LEFT JOIN users u ON u.user_id = s.user_id LEFT JOIN bookings b ON b.booking_id = s.booking_id ORDER BY s.created_at DESC');

admin_header('Support', 'support');
?>

<?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

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
                <th>Reply and status</th>
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
                    <form method="post" class="stack-form">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="ticket_id" value="<?= (int) $ticket['ticket_id'] ?>">
                        <select name="status" aria-label="Ticket status">
                            <?php foreach (['OPEN', 'IN_PROGRESS', 'RESOLVED'] as $status): ?>
                                <option value="<?= $status ?>" <?= $ticket['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="admin_reply" rows="3" placeholder="Write a reply"><?= htmlspecialchars($ticket['admin_reply'] ?? '') ?></textarea>
                        <button class="button button-primary" type="submit">Save response</button>
                    </form>
                </td>
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