<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$userId = (int) $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$subject = trim($_POST['subject'] ?? '');
	$ticketMessage = trim($_POST['message'] ?? '');
	$bookingId = (int) ($_POST['booking_id'] ?? 0);

	if ($subject === '' || $ticketMessage === '') {
		$error = 'Subject and message are required.';
	} elseif (strlen($subject) > 180) {
		$error = 'Subject must be 180 characters or fewer.';
	} else {
		$bookingCheck = $conn->prepare('SELECT booking_id FROM bookings WHERE booking_id = ? AND user_id = ?');
		$bookingCheck->bind_param('ii', $bookingId, $userId);
		$bookingCheck->execute();
		$bookingExists = $bookingId > 0 && $bookingCheck->get_result()->num_rows > 0;
		$bookingCheck->close();

		if ($bookingId > 0 && !$bookingExists) {
			$error = 'That booking does not belong to your account.';
		} else {
			$existingStatement = $conn->prepare(
				"SELECT ticket_id FROM support_tickets
				  WHERE user_id = ? AND subject = ?
				 ORDER BY ticket_id DESC LIMIT 1"
			);
			$existingStatement->bind_param('is', $userId, $subject);
			$existingStatement->execute();
			$existingTicket = $existingStatement->get_result()->fetch_assoc();
			$existingStatement->close();

			if ($existingTicket) {
				$ticketId = (int) $existingTicket['ticket_id'];
				if ($bookingId > 0) {
					$statement = $conn->prepare(
						"UPDATE support_tickets
						 SET booking_id = ?, message = ?, status = 'OPEN', admin_reply = NULL,
							 resolved_at = NULL, created_at = CURRENT_TIMESTAMP
						 WHERE ticket_id = ? AND user_id = ?"
					);
					$statement->bind_param('isii', $bookingId, $ticketMessage, $ticketId, $userId);
				} else {
					$statement = $conn->prepare(
						"UPDATE support_tickets
						 SET booking_id = NULL, message = ?, status = 'OPEN', admin_reply = NULL,
							 resolved_at = NULL, created_at = CURRENT_TIMESTAMP
						 WHERE ticket_id = ? AND user_id = ?"
					);
					$statement->bind_param('sii', $ticketMessage, $ticketId, $userId);
				}
				$successMessage = 'Your existing support request has been updated.';
			} else {
				if ($bookingId > 0) {
					$statement = $conn->prepare(
						'INSERT INTO support_tickets (user_id, booking_id, subject, message) VALUES (?, ?, ?, ?)'
					);
					$statement->bind_param('iiss', $userId, $bookingId, $subject, $ticketMessage);
				} else {
					$statement = $conn->prepare(
						'INSERT INTO support_tickets (user_id, booking_id, subject, message) VALUES (?, NULL, ?, ?)'
					);
					$statement->bind_param('iss', $userId, $subject, $ticketMessage);
				}
				$successMessage = 'Your support request has been sent.';
			}
			if ($statement->execute()) {
				$message = $successMessage;
			} else {
				$error = 'Could not save your support request.';
			}
			$statement->close();
		}
	}
}

$bookingStatement = $conn->prepare(
	'SELECT b.booking_id, b.pnr, b.travel_date, t.train_name
	 FROM bookings b
	 INNER JOIN trains t ON t.train_id = b.train_id
	 WHERE b.user_id = ?
	 ORDER BY b.travel_date DESC'
);
$bookingStatement->bind_param('i', $userId);
$bookingStatement->execute();
$bookings = $bookingStatement->get_result()->fetch_all(MYSQLI_ASSOC);
$bookingStatement->close();

$ticketStatement = $conn->prepare(
	'SELECT ticket_id, booking_id, subject, message, admin_reply, status, created_at, resolved_at
	 FROM support_tickets
	 WHERE user_id = ?
	 ORDER BY created_at DESC'
);
$ticketStatement->bind_param('i', $userId);
$ticketStatement->execute();
$tickets = $ticketStatement->get_result()->fetch_all(MYSQLI_ASSOC);
$ticketStatement->close();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Support | RailEase</title>
	<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="user-body">
	<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

	<main class="user-main">
		<section class="welcome-section">
			<p class="eyebrow">Help centre</p>
			<h1>How can we help?</h1>
			<p class="welcome-text">Send a request and follow its progress from your account.</p>
		</section>

		<?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
		<?php if ($error !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

		<section class="panel" style="margin-bottom: 20px">
			<div class="panel-heading"><h2>Send a request</h2></div>
			<form method="post" class="stack-form">
				<label for="subject">Subject</label>
				<input id="subject" name="subject" maxlength="180" placeholder="What do you need help with?" required>

				<label for="booking_id">Related booking</label>
				<select id="booking_id" name="booking_id">
					<option value="0">No specific booking</option>
					<?php foreach ($bookings as $booking): ?>
						<option value="<?= (int) $booking['booking_id'] ?>">
							<?= htmlspecialchars($booking['pnr'] . ' - ' . $booking['train_name'] . ' (' . $booking['travel_date'] . ')') ?>
						</option>
					<?php endforeach; ?>
				</select>

				<label for="message">Message</label>
				<textarea id="message" name="message" rows="6" placeholder="Describe the issue or question" required></textarea>

				<div class="form-actions"><button class="button button-primary" type="submit">Send request</button></div>
			</form>
		</section>

		<section class="panel">
			<div class="panel-heading"><h2>My requests</h2><span class="muted"><?= count($tickets) ?> tickets</span></div>
			<?php if ($tickets): ?>
				<div class="table-wrap">
					<table class="data-table">
						<thead><tr><th>Subject</th><th>Status</th><th>Message</th><th>Admin reply</th><th>Created</th></tr></thead>
						<tbody>
							<?php foreach ($tickets as $ticket): ?>
								<tr>
									<td><strong><?= htmlspecialchars($ticket['subject']) ?></strong></td>
									<td><span class="badge <?= $ticket['status'] === 'RESOLVED' ? 'badge-green' : 'badge-blue' ?>"><?= htmlspecialchars($ticket['status']) ?></span></td>
									<td><?= nl2br(htmlspecialchars($ticket['message'])) ?></td>
									<td><?= $ticket['admin_reply'] ? nl2br(htmlspecialchars($ticket['admin_reply'])) : '<span class="muted">Awaiting response</span>' ?></td>
									<td><?= htmlspecialchars(date('d M Y', strtotime($ticket['created_at']))) ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else: ?>
				<div class="empty-state">You have not sent a support request yet.</div>
			<?php endif; ?>
		</section>
	</main>
</body>
</html>
