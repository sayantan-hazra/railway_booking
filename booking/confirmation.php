<?php
/*
 * RailEase - Booking Confirmation & Premium Boarding Pass
 * Developed by: SAYAN SUR
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = "Boarding Pass - RailEase";
$userId = (int) $_SESSION['user_id'];

// Create a booking only when checkout submits the form. GET requests render an existing PNR.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['seat_ids'])) {
        header('Location: search.php');
        exit;
    }

$trainId = (int) ($_POST['train_id'] ?? 0);
$coachId = (int) ($_POST['coach_id'] ?? 0);
$journeyDate = trim($_POST['journey_date'] ?? '');
$fromCode = trim($_POST['from'] ?? '');
$toCode = trim($_POST['to'] ?? '');
$seatIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $_POST['seat_ids'])))));
$passengers = array_values($_POST['passengers'] ?? []);

$tripStatement = $conn->prepare(
    'SELECT c.seat_price, fs.station_id AS from_station_id, ts.station_id AS to_station_id
     FROM coaches c
     INNER JOIN train_stops fstop ON fstop.train_id = c.train_id
     INNER JOIN stations fs ON fs.station_id = fstop.station_id AND fs.station_code = ?
     INNER JOIN train_stops tstop ON tstop.train_id = c.train_id
     INNER JOIN stations ts ON ts.station_id = tstop.station_id AND ts.station_code = ?
     WHERE c.coach_id = ? AND c.train_id = ? AND fstop.stop_order < tstop.stop_order'
);
$tripStatement->bind_param('ssii', $fromCode, $toCode, $coachId, $trainId);
$tripStatement->execute();
$trip = $tripStatement->get_result()->fetch_assoc();
$tripStatement->close();

if (!$trip || count($passengers) !== count($seatIds)) {
    exit('The selected journey details are no longer valid. Please start the search again.');
}

$placeholders = implode(',', array_fill(0, count($seatIds), '?'));
$seatStatement = $conn->prepare("SELECT s.seat_id FROM seats s
    WHERE s.coach_id = ? AND s.seat_id IN ($placeholders)
      AND NOT EXISTS (
          SELECT 1 FROM bookings b
          WHERE b.status = 'CONFIRMED'
            AND (
                b.seat_id = s.seat_id
                OR EXISTS (
                    SELECT 1 FROM booking_passengers bp
                    WHERE bp.booking_id = b.booking_id AND bp.seat_id = s.seat_id
                )
            )
      )");
$seatTypes = 'i' . str_repeat('i', count($seatIds));
$seatParams = array_merge([$coachId], $seatIds);
$seatStatement->bind_param($seatTypes, ...$seatParams);
$seatStatement->execute();
$availableSeatCount = $seatStatement->get_result()->num_rows;
$seatStatement->close();

if ($availableSeatCount !== count($seatIds)) {
    exit('One or more selected seats are no longer available. Please choose again.');
}

$mealStatement = $conn->prepare(
    'SELECT m.meal_id, m.price
     FROM train_meals tm
     INNER JOIN meals m ON m.meal_id = tm.meal_id
     WHERE tm.train_id = ?'
);
$mealStatement->bind_param('i', $trainId);
$mealStatement->execute();
$mealPrices = [];
foreach ($mealStatement->get_result()->fetch_all(MYSQLI_ASSOC) as $meal) {
    $mealPrices[(int) $meal['meal_id']] = (float) $meal['price'];
}
$mealStatement->close();

$ticketFare = 0.0;
$mealFare = 0.0;
foreach ($passengers as $passenger) {
    $passengerAge = (int) ($passenger['age'] ?? 0);
    $passengerMealId = (int) ($passenger['meal_id'] ?? 0);
    if ($passengerAge < 0 || $passengerAge > 120 || ($passengerMealId > 0 && !array_key_exists($passengerMealId, $mealPrices))) {
        exit('Passenger age or meal selection is invalid.');
    }
    $ticketFare += (float) $trip['seat_price'] * ($passengerAge < 6 ? 0.5 : 1);
    $mealFare += $mealPrices[$passengerMealId] ?? 0;
}

$insuranceFare = isset($_POST['travel_insurance']) ? 0.45 * count($seatIds) : 0;
$totalFare = $ticketFare + $mealFare + $insuranceFare;

$walletStatement = $conn->prepare('SELECT wallet_id, balance FROM wallets WHERE user_id = ? LIMIT 1');
$walletStatement->bind_param('i', $userId);
$walletStatement->execute();
$walletRow = $walletStatement->get_result()->fetch_assoc();
$walletStatement->close();
$walletId = $walletRow ? (int) $walletRow['wallet_id'] : 0;
$walletBalance = $walletRow ? (float) $walletRow['balance'] : 0.0;

if ($walletBalance < $totalFare) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Transaction Cancelled - RailEase</title>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
        <!-- Favicon -->
        <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
        <link rel="shortcut icon" href="../assets/favicon/favicon.ico">
    </head>
    <body class="auth-page">
        <main class="auth-card" style="text-align: center;">
            <h1>Transaction Cancelled</h1>
            <div class="alert alert-error">
                <strong>Payment declined:</strong> your wallet balance is lower than the total payable amount.
            </div>
            <p class="muted">
                Total payable: ₹<?php echo number_format($totalFare, 2); ?> &middot;
                Wallet balance: ₹<?php echo number_format($walletBalance, 2); ?>
            </p>
            <a class="button button-primary" href="../user/wallet.php">Add Money to Wallet</a>
            <br><br>
            <a class="back-link" href="../booking/search.php">Start a New Search</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

$firstMealId = (int) ($passengers[0]['meal_id'] ?? 0);
$mealValue = $firstMealId > 0 ? $firstMealId : null;
$pnr = 'RE' . mt_rand(10000000, 99999999);

$conn->begin_transaction();
try {
    $firstSeatId = $seatIds[0];
    if ($mealValue !== null) {
        $bookingStatement = $conn->prepare(
            'INSERT INTO bookings
             (pnr, user_id, train_id, from_station_id, to_station_id, coach_id, seat_id, meal_id, travel_date, ticket_fare, meal_fare, total_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'CONFIRMED\')'
        );
        $bookingStatement->bind_param('siiiiiiisddd', $pnr, $userId, $trainId, $trip['from_station_id'], $trip['to_station_id'], $coachId, $firstSeatId, $mealValue, $journeyDate, $ticketFare, $mealFare, $totalFare);
    } else {
        $bookingStatement = $conn->prepare(
            'INSERT INTO bookings
             (pnr, user_id, train_id, from_station_id, to_station_id, coach_id, seat_id, meal_id, travel_date, ticket_fare, meal_fare, total_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, \'CONFIRMED\')'
        );
        $bookingStatement->bind_param('s' . 'iiiiii' . 'sddd', $pnr, $userId, $trainId, $trip['from_station_id'], $trip['to_station_id'], $coachId, $firstSeatId, $journeyDate, $ticketFare, $mealFare, $totalFare);
    }
    $bookingStatement->execute();
    $bookingId = $conn->insert_id;
    $bookingStatement->close();

    $passengerWithMealStatement = $conn->prepare(
        'INSERT INTO booking_passengers (booking_id, seat_id, meal_id, passenger_name, age, gender) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $passengerWithoutMealStatement = $conn->prepare(
        'INSERT INTO booking_passengers (booking_id, seat_id, meal_id, passenger_name, age, gender) VALUES (?, ?, NULL, ?, ?, ?)'
    );
    foreach ($passengers as $index => $passenger) {
        $passengerName = trim($passenger['name'] ?? '');
        $passengerAge = (int) ($passenger['age'] ?? 0);
        $passengerGender = trim($passenger['gender'] ?? '');
        if ($passengerName === '' || $passengerAge < 0 || $passengerGender === '') {
            throw new RuntimeException('Passenger details are incomplete.');
        }
        $passengerSeatId = $seatIds[$index];
        $passengerMealId = (int) ($passenger['meal_id'] ?? 0);
        if ($passengerMealId > 0) {
            $passengerWithMealStatement->bind_param('iiisis', $bookingId, $passengerSeatId, $passengerMealId, $passengerName, $passengerAge, $passengerGender);
            $passengerWithMealStatement->execute();
        } else {
            $passengerWithoutMealStatement->bind_param('iisis', $bookingId, $passengerSeatId, $passengerName, $passengerAge, $passengerGender);
            $passengerWithoutMealStatement->execute();
        }
    }
    $passengerWithMealStatement->close();
    $passengerWithoutMealStatement->close();

    $walletDeductStatement = $conn->prepare('UPDATE wallets SET balance = balance - ? WHERE user_id = ?');
    $walletDeductStatement->bind_param('di', $totalFare, $userId);
    $walletDeductStatement->execute();
    $walletDeductStatement->close();

    $walletTransactionStatement = $conn->prepare(
        'INSERT INTO wallet_transactions (wallet_id, transaction_type, amount, description, booking_id)
         VALUES (?, \'DEBIT\', ?, ?, ?)'
    );
    $transactionDescription = 'Payment for ticket booking #' . $bookingId;
    $walletTransactionStatement->bind_param('idsi', $walletId, $totalFare, $transactionDescription, $bookingId);
    $walletTransactionStatement->execute();
    $walletTransactionStatement->close();

    $conn->commit();

    header('Location: confirmation.php?pnr=' . urlencode($pnr));
    exit;
} catch (Throwable $exception) {
    $conn->rollback();
    error_log('RailEase booking failed: ' . $exception->getMessage());
    exit('Booking could not be completed. Please try again.');
}
}

// Fetch booking by PNR
$pnr = isset($_GET['pnr']) ? trim($_GET['pnr']) : '';

if (empty($pnr)) {
    header("Location: search.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT b.*, t.train_number, t.train_name, c.coach_number, c.coach_type,
           fs.station_name AS from_station, ts.station_name AS to_station
    FROM bookings b
    JOIN trains t ON b.train_id = t.train_id
    JOIN coaches c ON b.coach_id = c.coach_id
    JOIN stations fs ON b.from_station_id = fs.station_id
    JOIN stations ts ON b.to_station_id = ts.station_id
    WHERE b.pnr = ? AND b.user_id = ?
");
$stmt->bind_param("si", $pnr, $userId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Ticket not found for PNR: " . htmlspecialchars($pnr));
}

$journeyDateDisplay = $booking['travel_date'];
$sourceCity = $booking['from_station'];
$destCity = $booking['to_station'];
$fareToPrint = $booking['total_amount'];

// Passengers with seats
$pStmt = $conn->prepare("
    SELECT p.passenger_name, p.age, p.gender, m.meal_name, s.seat_number, s.seat_type
    FROM booking_passengers p
    JOIN seats s ON p.seat_id = s.seat_id
    LEFT JOIN meals m ON p.meal_id = m.meal_id
    WHERE p.booking_id = ?
    ORDER BY CAST(s.seat_number AS UNSIGNED) ASC
");
$pStmt->bind_param("i", $booking['booking_id']);
$pStmt->execute();
$passengerList = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pStmt->close();

// Dynamic QR Code API
$qrData = "PNR: " . $booking['pnr'] . " | Train: " . $booking['train_number'] . " | Date: " . $journeyDateDisplay;
$qrUrl  = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="shortcut icon" href="../assets/favicon/favicon.ico">
</head>
<body class="ticket-page-bg">

    <div class="ticket-card-container">
        <!-- Banner Header -->
        <div class="ticket-head-banner">
            <div>
                <span class="brand-badge">✦ RailEase Express</span>
                <h1 class="train-title-main"><?php echo htmlspecialchars($booking['train_name']); ?></h1>
                <div class="train-no-tag">Train Number: #<?php echo htmlspecialchars($booking['train_number']); ?></div>
            </div>
            <div class="status-badge-premium">
                ✔ CONFIRMED
            </div>
        </div>

        <!-- PNR Ribbon -->
        <div class="pnr-ribbon-bar">
            <div class="pnr-box-meta">
                <span>PNR NUMBER</span>
                <h2><?php echo htmlspecialchars($booking['pnr']); ?></h2>
            </div>
            <div class="date-box-meta">
                <span>DATE OF JOURNEY</span>
                <h3><?php echo htmlspecialchars($journeyDateDisplay); ?></h3>
            </div>
        </div>

        <!-- Main Ticket Details -->
        <div class="ticket-body-content">
            
            <!-- Route Visualizer -->
            <div class="route-flight-bar">
                <div class="station-node">
                    <span>FROM</span>
                    <h4><?php echo strtoupper(htmlspecialchars($sourceCity)); ?></h4>
                </div>
                <div class="train-mid-path">
                    <span class="path-icon">🚆</span>
                    <div class="path-line"></div>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">DIRECT ROUTE</span>
                </div>
                <div class="station-node" style="text-align: right;">
                    <span>TO</span>
                    <h4><?php echo strtoupper(htmlspecialchars($destCity)); ?></h4>
                </div>
            </div>

            <!-- Meta Tiles -->
            <div class="journey-info-tiles">
                <div class="tile-box">
                    <span>COACH NUMBER</span>
                    <strong>Coach <?php echo htmlspecialchars($booking['coach_number']); ?></strong>
                </div>
                <div class="tile-box">
                    <span>TRAVEL CLASS</span>
                    <strong><?php echo htmlspecialchars($booking['coach_type']); ?> Class</strong>
                </div>
                <div class="tile-box fare-tile">
                    <span>TOTAL FARE PAID</span>
                    <strong>₹<?php echo number_format($fareToPrint, 2); ?></strong>
                </div>
            </div>

            <!-- Passenger List -->
            <h4 class="pass-section-header">Passenger Boarding Details</h4>
            <table class="pass-board-table">
                <thead>
                    <tr>
                        <th>Passenger Name</th>
                        <th>Age & Gender</th>
                        <th>Food</th>
                        <th>Seat No.</th>
                        <th>Berth Position</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($passengerList as $p): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($p['passenger_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['age']); ?> yrs, <?php echo htmlspecialchars($p['gender']); ?></td>
                            <td><?php echo htmlspecialchars($p['meal_name'] ?? 'No food'); ?></td>
                            <td><span class="seat-pill-tag"><?php echo htmlspecialchars($p['seat_number']); ?></span></td>
                            <td><?php echo htmlspecialchars($p['seat_type']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

        <!-- Ticket Perforation Notch -->
        <div class="ticket-perforation">
            <div class="dashed-divider-line"></div>
        </div>

        <!-- Footer / QR & Security -->
        <div class="ticket-foot-bar">
            <div class="guidelines-col">
                <strong style="font-size: 0.82rem; color: #0f172a;">TRAVEL INSTRUCTIONS</strong>
                <ul>
                    <li>Carry an original Government ID (Aadhaar, Voter ID, Passport) during travel.</li>
                    <li>Please report at the platform at least 20 minutes prior to departure.</li>
                    <li>This is a valid computer-generated E-Ticket pass.</li>
                </ul>
            </div>
            <div class="qr-code-box">
                <img src="<?php echo $qrUrl; ?>" alt="Ticket QR Code">
                <span>Scan to Verify</span>
            </div>
        </div>
    </div>

    <!-- Printable Floating Action Buttons -->
    <div class="ticket-floating-actions">
        <button onclick="window.print()" class="action-btn-print">
            🖨 Print / Save PDF
        </button>
        <a href="../user/home.php" class="action-btn-home">
            Book Another Ticket
        </a>
    </div>

</body>
</html>