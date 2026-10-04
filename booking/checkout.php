<?php
/*
 * RailEase - Booking Checkout & Passenger Details
 * Developed by: SAYAN SUR
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = "Checkout & Passenger Details";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['seat_ids'])) {
    header("Location: search.php");
    exit;
}


$loggedInUserId = (int) $_SESSION['user_id'];
$userStatement = $conn->prepare('SELECT full_name, email, phone FROM users WHERE user_id = ?');
$userStatement->bind_param('i', $loggedInUserId);
$userStatement->execute();
$userData = $userStatement->get_result()->fetch_assoc();
$userStatement->close();

if (!$userData) {
    header('Location: ../auth/login.php');
    exit;
}

$trainId     = (int)$_POST['train_id'];
$coachId     = (int)$_POST['coach_id'];
$journeyDate = trim($_POST['journey_date']);
$from        = trim($_POST['from'] ?? '');
$to          = trim($_POST['to'] ?? '');
$seatIdsRaw  = trim($_POST['seat_ids']);
$seatIdArr   = array_values(array_unique(array_filter(array_map('intval', explode(',', $seatIdsRaw)))));

if (!$seatIdArr || $trainId <= 0 || $coachId <= 0 || $from === '' || $to === '') {
    header('Location: search.php');
    exit;
}


$tStmt = $conn->prepare("
    SELECT t.train_number, t.train_name, c.coach_number, c.coach_type, c.seat_price,
           fs.station_id AS from_station_id, ts.station_id AS to_station_id,
           fs.station_name AS from_station_name, ts.station_name AS to_station_name
    FROM coaches c
    JOIN trains t ON c.train_id = t.train_id
    JOIN train_stops fstop ON fstop.train_id = t.train_id
    JOIN stations fs ON fs.station_id = fstop.station_id AND fs.station_code = ?
    JOIN train_stops tstop ON tstop.train_id = t.train_id
    JOIN stations ts ON ts.station_id = tstop.station_id AND ts.station_code = ?
    WHERE c.coach_id = ? AND t.train_id = ? AND fstop.stop_order < tstop.stop_order
");
$tStmt->bind_param('ssii', $from, $to, $coachId, $trainId);
$tStmt->execute();
$trip = $tStmt->get_result()->fetch_assoc();
$tStmt->close();

if (!$trip) {
    echo "Trip details not found!";
    exit;
}

$fromStationId = (int) $trip['from_station_id'];
$toStationId = (int) $trip['to_station_id'];
$fromLabel = $trip['from_station_name'];
$toLabel = $trip['to_station_name'];

$placeholders = implode(',', array_fill(0, count($seatIdArr), '?'));
$types = 'i' . str_repeat('i', count($seatIdArr));
$seatParams = array_merge([$coachId], $seatIdArr);

$sStmt = $conn->prepare("SELECT s.seat_id, s.seat_number, s.seat_type
        FROM seats s
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
            )
        ORDER BY CAST(s.seat_number AS UNSIGNED) ASC");
$sStmt->bind_param($types, ...$seatParams);
$sStmt->execute();
$selectedSeats = $sStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sStmt->close();

if (count($selectedSeats) !== count($seatIdArr)) {
    exit('One or more selected seats are no longer available. Please choose again.');
}

$passengerCount = count($selectedSeats);
$baseFare = (float) $trip['seat_price'] * $passengerCount;

$mealStatement = $conn->prepare(
    'SELECT m.meal_id, m.meal_name, m.price
     FROM train_meals tm
     INNER JOIN meals m ON m.meal_id = tm.meal_id
     WHERE tm.train_id = ?
     ORDER BY m.meal_name'
);
$mealStatement->bind_param('i', $trainId);
$mealStatement->execute();
$mealOptions = $mealStatement->get_result()->fetch_all(MYSQLI_ASSOC);
$mealStatement->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - RailEase</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="user-body">

    <!-- HEADER -->
    <header class="user-header">
        <div class="user-header-inner">
            <a href="../user/home.php" class="brand">
                <span class="brand-mark">R</span>
                <span>RailEase</span>
            </a>
            <div class="header-actions">
                <a href="../user/wallet.php" class="header-action"><span class="action-icon">₹</span><span class="action-text">Wallet</span></a>
                <a href="../user/support.php" class="header-action"><span class="action-icon">?</span><span class="action-text">Support</span></a>
                <a href="../auth/logout.php" class="header-action">
                    <span class="action-icon">&#8594;</span>
                    <span class="action-text">Logout</span>
                </a>
            </div>
        </div>
    </header>

    <main class="checkout-container">
        <a href="javascript:history.back()" class="back-link search-back-link">← Back to Seat Selection</a>

        <div class="checkout-grid">
            <!-- Left Column: Forms -->
            <div>
                <form action="confirmation.php" method="POST" id="bookingForm">
                    <input type="hidden" name="train_id" value="<?php echo $trainId; ?>">
                    <input type="hidden" name="coach_id" value="<?php echo $coachId; ?>">
                    <input type="hidden" name="journey_date" value="<?php echo htmlspecialchars($journeyDate); ?>">
                    <input type="hidden" name="from" value="<?php echo htmlspecialchars($from); ?>">
                    <input type="hidden" name="to" value="<?php echo htmlspecialchars($to); ?>">
                    <input type="hidden" name="seat_ids" value="<?php echo htmlspecialchars($seatIdsRaw); ?>">
                    <input type="hidden" name="total_fare" id="hiddenTotalFare" value="<?php echo $baseFare; ?>">

                    <!-- Passenger Details -->
                    <div class="checkout-card">
                        <h2 class="checkout-card-title">Passenger Details</h2>
                        <p class="checkout-note">Children below 6 years receive 50% off the ticket fare.</p>
                        
                        <?php foreach ($selectedSeats as $index => $seat): ?>
                            <div class="passenger-box">
                                <p class="passenger-label">
                                    Passenger <?php echo $index + 1; ?> (Seat: <?php echo htmlspecialchars($seat['seat_number']); ?> - <?php echo htmlspecialchars($seat['seat_type']); ?>)
                                </p>
                                <input type="hidden" name="passengers[<?php echo $index; ?>][seat_id]" value="<?php echo $seat['seat_id']; ?>">
                                <div class="passenger-row">
                                    <div class="input-group">
                                        <label>Full Name</label>
                                        <input type="text" name="passengers[<?php echo $index; ?>][name]" 
                                                       value="<?php echo ($index === 0) ? htmlspecialchars($userData['full_name']) : ''; ?>" 
                                                       <?php echo $index === 0 ? 'readonly' : ''; ?>
                                               placeholder="Enter Full Name" required>
                                    </div>
                                    <div class="input-group">
                                        <label>Age</label>
                                        <input type="number" name="passengers[<?php echo $index; ?>][age]" class="passenger-age" min="0" max="120" placeholder="Age" required oninput="calculateGrandTotal()">
                                    </div>
                                    <div class="input-group">
                                        <label>Gender</label>
                                        <select name="passengers[<?php echo $index; ?>][gender]" required>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label>Food</label>
                                        <select name="passengers[<?php echo $index; ?>][meal_id]" class="passenger-meal" onchange="calculateGrandTotal()">
                                            <option value="0" data-price="0">No food</option>
                                            <?php foreach ($mealOptions as $meal): ?>
                                                <option value="<?= (int) $meal['meal_id'] ?>" data-price="<?= htmlspecialchars($meal['price']) ?>">
                                                    <?= htmlspecialchars($meal['meal_name']) ?> (+₹<?= number_format((float) $meal['price'], 2) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Travel Preferences -->
                    <div class="checkout-card">
                        <h2 class="checkout-card-title">Travel Preferences</h2>

                        <div class="checkout-addon-group">
                            <label class="checkout-addon-item">
                                <input type="checkbox" name="auto_upgrade" value="1" checked>
                                <span>Consider for <strong>Free Auto-Upgradation</strong> to higher class (if vacant)</span>
                            </label>

                            <label class="checkout-addon-item">
                                <input type="checkbox" name="travel_insurance" id="insuranceCheckbox" value="1" checked onchange="calculateGrandTotal()">
                                <span>Travel Insurance (₹0.45 per passenger)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="checkout-card">
                        <h2 class="checkout-card-title">Contact Information</h2>
                        <div class="contact-row">
                            <div class="input-group">
                                <label>Email Address</label>
                                <input type="email" name="contact_email" 
                                        value="<?php echo htmlspecialchars($userData['email']); ?>" readonly required>
                            </div>
                            <div class="input-group">
                                <label>Phone Number</label>
                                <input type="tel" name="contact_phone" 
                                        value="<?php echo htmlspecialchars($userData['phone']); ?>" readonly required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-confirm" id="btnSubmitPay">
                        Pay ₹<?php echo number_format($baseFare, 2); ?> & Book Ticket
                    </button>
                </form>
            </div>

            <!-- Right Column: Trip Summary -->
            <div>
                <div class="checkout-card">
                    <h3 class="checkout-card-title journey-summary-title">Journey Summary</h3>
                    <p class="journey-train-title"><?php echo htmlspecialchars($trip['train_name']); ?></p>
                    <p class="journey-train-sub">Train #<?php echo htmlspecialchars($trip['train_number']); ?></p>

                    <div class="fare-row"><span>From:</span><strong><?php echo htmlspecialchars($fromLabel); ?></strong></div>
                    <div class="fare-row"><span>To:</span><strong><?php echo htmlspecialchars($toLabel); ?></strong></div>
                    <div class="fare-row"><span>Date:</span><strong><?php echo htmlspecialchars($journeyDate); ?></strong></div>
                    <div class="fare-row"><span>Coach:</span><strong><?php echo htmlspecialchars($trip['coach_number']); ?> (<?php echo htmlspecialchars($trip['coach_type']); ?>)</strong></div>
                    <div class="fare-row"><span>Passengers:</span><strong><?php echo $passengerCount; ?> Seat(s)</strong></div>
                    <div class="fare-row"><span>Base Ticket Fare:</span><strong id="baseTicketFareText">₹<?php echo number_format($baseFare, 2); ?></strong></div>
                    <div class="fare-row display-none" id="mealSummaryRow"><span>Meals:</span><strong id="mealFareText">₹0.00</strong></div>
                    <div class="fare-row" id="insuranceSummaryRow"><span>Insurance:</span><strong id="insuranceFareText">₹<?php echo number_format($passengerCount * 0.45, 2); ?></strong></div>

                    <div class="fare-total">
                        <span>Total Payable</span>
                        <span id="displayTotalPayable">₹<?php echo number_format($baseFare + ($passengerCount * 0.45), 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Dynamic Total Calculator Script -->
    <script>
        const seatPrice = <?php echo (float) $trip['seat_price']; ?>;
        const passengerCount = <?php echo (int) $passengerCount; ?>;

        function calculateGrandTotal() {
            let ticketFare = 0;
            let totalMealCost = 0;

            document.querySelectorAll('.passenger-box').forEach((passengerBox) => {
                const age = parseInt(passengerBox.querySelector('.passenger-age').value, 10);
                const ticketMultiplier = age >= 0 && age < 6 ? 0.5 : 1;
                ticketFare += seatPrice * ticketMultiplier;

                const mealSelect = passengerBox.querySelector('.passenger-meal');
                totalMealCost += parseFloat(mealSelect.selectedOptions[0].dataset.price) || 0;
            });

            const insuranceChecked = document.getElementById('insuranceCheckbox').checked;
            const totalInsuranceCost = insuranceChecked ? (0.45 * passengerCount) : 0;

            const grandTotal = ticketFare + totalMealCost + totalInsuranceCost;
            document.getElementById('baseTicketFareText').innerText = '₹' + ticketFare.toFixed(2);

            // Breakdown Update
            const mealRow = document.getElementById('mealSummaryRow');
            if (totalMealCost > 0) {
                mealRow.classList.remove('display-none');
                mealRow.style.display = 'flex';
                document.getElementById('mealFareText').innerText = '₹' + totalMealCost.toFixed(2);
            } else {
                mealRow.classList.add('display-none');
                mealRow.style.display = 'none';
            }

            const insuranceRow = document.getElementById('insuranceSummaryRow');
            if (insuranceChecked) {
                insuranceRow.classList.remove('display-none');
                insuranceRow.style.display = 'flex';
                document.getElementById('insuranceFareText').innerText = '₹' + totalInsuranceCost.toFixed(2);
            } else {
                insuranceRow.classList.add('display-none');
                insuranceRow.style.display = 'none';
            }

            // Update Total and Submit Button
            const formattedTotal = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('displayTotalPayable').innerText = formattedTotal;
            document.getElementById('btnSubmitPay').innerText = 'Pay ' + formattedTotal + ' & Book Ticket';
            document.getElementById('hiddenTotalFare').value = grandTotal.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', calculateGrandTotal);
    </script>
</body>
</html>