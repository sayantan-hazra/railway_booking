<?php
/*
 * RailEase - BookMyShow Train Coach Seat Selection
 * Developed by: SAYAN SUR
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = "Select Seats";

$trainId = isset($_GET['train_id']) ? (int)$_GET['train_id'] : 0;
$coachId = isset($_GET['coach_id']) ? (int)$_GET['coach_id'] : 0;
$journeyDate = isset($_GET['date']) ? trim($_GET['date']) : '';
$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to = isset($_GET['to']) ? trim($_GET['to']) : '';

if ($trainId <= 0 || $coachId <= 0) {
    header("Location: search.php");
    exit;
}

// Fetch train and coach details
$stmt = $conn->prepare("
    SELECT t.train_number, t.train_name, c.coach_type, c.coach_number, c.seat_price
    FROM coaches c
    JOIN trains t ON c.train_id = t.train_id
    WHERE c.coach_id = ? AND t.train_id = ?
");
$stmt->bind_param("ii", $coachId, $trainId);
$stmt->execute();
$details = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$details) {
    echo "Coach or Train details not found!";
    exit;
}

// Pricing logic
$seatPrice = (float) $details['seat_price'];

// Ensure every coach has seven rows with two seats on each side.
$existingSeatStatement = $conn->prepare('SELECT seat_number FROM seats WHERE coach_id = ?');
$existingSeatStatement->bind_param('i', $coachId);
$existingSeatStatement->execute();
$existingSeatNumbers = array_map(
    static fn (array $seat): string => (string) $seat['seat_number'],
    $existingSeatStatement->get_result()->fetch_all(MYSQLI_ASSOC)
);
$existingSeatStatement->close();

$seatInsertStatement = $conn->prepare(
    'INSERT INTO seats (coach_id, seat_number, seat_type) VALUES (?, ?, ?)'
);
$seatTypes = ['WINDOW', 'AISLE', 'AISLE', 'WINDOW'];
for ($seatNumber = 1; $seatNumber <= 28; $seatNumber++) {
    if (!in_array((string) $seatNumber, $existingSeatNumbers, true)) {
        $seatLabel = (string) $seatNumber;
        $seatType = $seatTypes[($seatNumber - 1) % 4];
        $seatInsertStatement->bind_param('iss', $coachId, $seatLabel, $seatType);
        $seatInsertStatement->execute();
    }
}
$seatInsertStatement->close();

// Fetch all seats so reserved seats remain visible in their fixed positions.
$seatsList = [];
$seatStmt = $conn->prepare("SELECT s.seat_id, s.seat_number, s.seat_type,
        CASE WHEN b.booking_id IS NULL THEN 0 ELSE 1 END AS is_booked
        FROM seats s
        LEFT JOIN bookings b ON b.status = 'CONFIRMED'
            AND (
                b.seat_id = s.seat_id
                OR EXISTS (
                    SELECT 1
                    FROM booking_passengers bp
                    WHERE bp.booking_id = b.booking_id AND bp.seat_id = s.seat_id
                )
            )
        WHERE s.coach_id = ?
        ORDER BY s.seat_id ASC");
$seatStmt->bind_param("i", $coachId);
$seatStmt->execute();
$seatRes = $seatStmt->get_result();
while ($sRow = $seatRes->fetch_assoc()) {
    $seatsList[] = $sRow;
}
$seatStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - RailEase</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- Cash-Buster added to prevent CSS caching -->
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
                <a href="../user/wallet.php" class="header-action">
                    <span class="action-icon">₹</span>
                    <span class="action-text">Wallet</span>
                </a>
                <a href="../user/support.php" class="header-action">
                    <span class="action-icon">?</span>
                    <span class="action-text">Support</span>
                </a>
                <a href="../auth/logout.php" class="header-action">
                    <span class="action-icon">&#8594;</span>
                    <span class="action-text">Logout</span>
                </a>
            </div>
        </div>
    </header>

    <main class="seat-booking-container">
        <a href="coach.php?train_id=<?php echo urlencode($trainId); ?>&date=<?php echo urlencode($journeyDate); ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>" class="back-link search-back-link">
            ← Back to Coaches
        </a>

       <div class="train-summary-card">
            <p class="train-summary-eyebrow">
                COACH <?php echo htmlspecialchars($details['coach_number']); ?> (<?php echo htmlspecialchars($details['coach_type']); ?>)
                <?php if (in_array($details['coach_type'], ['1A', '2A', '3A', 'CC', 'EC'])): ?>
                    <span style="background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 4px; font-weight: 700; margin-left: 6px;">❄ AC</span>
                <?php else: ?>
                    <span style="background: #fef3c7; color: #d97706; padding: 2px 8px; border-radius: 4px; font-weight: 700; margin-left: 6px;">NON-AC / GENERAL</span>
                <?php endif; ?>
            </p>
            <h1 class="train-summary-title">
                <?php echo htmlspecialchars($details['train_number']); ?> - <?php echo htmlspecialchars($details['train_name']); ?>
            </h1>
            <p class="train-summary-route">
                <strong>Route:</strong> <?php echo htmlspecialchars($from); ?> → <?php echo htmlspecialchars($to); ?> | <strong>Date:</strong> <?php echo htmlspecialchars($journeyDate); ?>
            </p>
        </div>
        <!-- Legend -->
        <div class="seat-legend">
            <div class="legend-item">
                <div class="legend-box available"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-box selected"></div>
                <span>Selected</span>
            </div>
            <div class="legend-item">
                <div class="legend-box booked"></div>
                <span>Booked</span>
            </div>
        </div>

        <!-- Train Bogie Layout -->
        <div class="train-coach-box">
            <div class="coach-door">▲ ENTRY / DOOR (COACH <?php echo htmlspecialchars($details['coach_number']); ?>) ▲</div>

            <div class="coach-grid-layout">
                <?php if (!empty($seatsList)): ?>
                    <?php 
                    
                    $rows = array_chunk($seatsList, 4);
                    foreach ($rows as $row): 
                    ?>
                        <div class="coach-seat-row">
                            <!-- Left 2 Seats -->
                            <div class="seat-side">
                                <?php if (isset($row[0])): ?>
                                    <div class="seat-item">
                                        <button type="button" class="seat-btn<?php echo $row[0]['is_booked'] ? ' booked' : ''; ?>" data-id="<?php echo $row[0]['seat_id']; ?>" data-num="<?php echo htmlspecialchars($row[0]['seat_number']); ?>" <?php echo $row[0]['is_booked'] ? 'disabled' : 'onclick="selectSeat(this)"'; ?>>
                                            <?php echo htmlspecialchars($row[0]['seat_number']); ?>
                                        </button>
                                        <span class="seat-type-text"><?php echo htmlspecialchars($row[0]['seat_type'] ?? 'Win'); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($row[1])): ?>
                                    <div class="seat-item">
                                        <button type="button" class="seat-btn<?php echo $row[1]['is_booked'] ? ' booked' : ''; ?>" data-id="<?php echo $row[1]['seat_id']; ?>" data-num="<?php echo htmlspecialchars($row[1]['seat_number']); ?>" <?php echo $row[1]['is_booked'] ? 'disabled' : 'onclick="selectSeat(this)"'; ?>>
                                            <?php echo htmlspecialchars($row[1]['seat_number']); ?>
                                        </button>
                                        <span class="seat-type-text"><?php echo htmlspecialchars($row[1]['seat_type'] ?? 'Aisle'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Middle Aisle -->
                            <div class="coach-aisle">AISLE</div>

                            <!-- Right 2 Seats -->
                            <div class="seat-side">
                                <?php if (isset($row[2])): ?>
                                    <div class="seat-item">
                                        <button type="button" class="seat-btn<?php echo $row[2]['is_booked'] ? ' booked' : ''; ?>" data-id="<?php echo $row[2]['seat_id']; ?>" data-num="<?php echo htmlspecialchars($row[2]['seat_number']); ?>" <?php echo $row[2]['is_booked'] ? 'disabled' : 'onclick="selectSeat(this)"'; ?>>
                                            <?php echo htmlspecialchars($row[2]['seat_number']); ?>
                                        </button>
                                        <span class="seat-type-text"><?php echo htmlspecialchars($row[2]['seat_type'] ?? 'SL'); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($row[3])): ?>
                                    <div class="seat-item">
                                        <button type="button" class="seat-btn<?php echo $row[3]['is_booked'] ? ' booked' : ''; ?>" data-id="<?php echo $row[3]['seat_id']; ?>" data-num="<?php echo htmlspecialchars($row[3]['seat_number']); ?>" <?php echo $row[3]['is_booked'] ? 'disabled' : 'onclick="selectSeat(this)"'; ?>>
                                            <?php echo htmlspecialchars($row[3]['seat_number']); ?>
                                        </button>
                                        <span class="seat-type-text"><?php echo htmlspecialchars($row[3]['seat_type'] ?? 'SU'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #64748b;">No seats found.</p>
                <?php endif; ?>
            </div>

            <div class="coach-door">▼ EXIT / DOOR ▼</div>
        </div>
    </main>

    <!-- Bottom Bar -->
    <div class="bottom-checkout-bar">
        <div class="checkout-details">
            <span>Selected Seats: <strong id="lbl-seats">None</strong></span>
            <span>Total Fare: <strong id="lbl-fare" style="color: #2563eb;">₹0</strong></span>
        </div>

        <form action="checkout.php" method="POST">
            <input type="hidden" name="train_id" value="<?php echo htmlspecialchars($trainId); ?>">
            <input type="hidden" name="coach_id" value="<?php echo htmlspecialchars($coachId); ?>">
            <input type="hidden" name="journey_date" value="<?php echo htmlspecialchars($journeyDate); ?>">
            <input type="hidden" name="from" value="<?php echo htmlspecialchars($from); ?>">
            <input type="hidden" name="to" value="<?php echo htmlspecialchars($to); ?>">
            <input type="hidden" name="seat_ids" id="inp-seat-ids" value="">
            <input type="hidden" name="total_fare" id="inp-total-fare" value="0">

            <button type="submit" id="btn-submit" class="btn-proceed" disabled>
                Proceed to Checkout →
            </button>
        </form>
    </div>

    <script>
        const seatPrice = <?php echo $seatPrice; ?>;
        let chosenSeats = [];

        function selectSeat(el) {
            const id = el.getAttribute('data-id');
            const num = el.getAttribute('data-num');

            if (el.classList.contains('selected')) {
                el.classList.remove('selected');
                chosenSeats = chosenSeats.filter(s => s.id !== id);
            } else {
                el.classList.add('selected');
                chosenSeats.push({ id: id, num: num });
            }

            const lblSeats = document.getElementById('lbl-seats');
            const lblFare = document.getElementById('lbl-fare');
            const inpSeatIds = document.getElementById('inp-seat-ids');
            const inpTotalFare = document.getElementById('inp-total-fare');
            const btnSubmit = document.getElementById('btn-submit');

            if (chosenSeats.length > 0) {
                lblSeats.innerText = chosenSeats.map(s => s.num).join(', ');
                const total = chosenSeats.length * seatPrice;
                lblFare.innerText = '₹' + total;
                inpSeatIds.value = chosenSeats.map(s => s.id).join(',');
                inpTotalFare.value = total;
                btnSubmit.disabled = false;
            } else {
                lblSeats.innerText = 'None';
                lblFare.innerText = '₹0';
                inpSeatIds.value = '';
                inpTotalFare.value = 0;
                btnSubmit.disabled = true;
            }
        }
    </script>
</body>
</html>