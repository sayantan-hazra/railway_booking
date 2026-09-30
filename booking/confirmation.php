<?php
/*
 * RailEase - Booking Confirmation & Premium Boarding Pass
 * Developed by: SAYAN SUR
 */

session_start();
require_once __DIR__ . '/../config/db.php';

$pageTitle = "Boarding Pass - RailEase";

// Handle form submit from checkout.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['seat_ids'])) {
    $userId       = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    $trainId      = (int)$_POST['train_id'];
    $coachId      = (int)$_POST['coach_id'];
    $journeyDate  = trim($_POST['journey_date']);
    $totalFare    = (float)$_POST['total_fare'];
    $contactEmail = trim($_POST['contact_email']);
    $contactPhone = trim($_POST['contact_phone']);
    $fromCity     = isset($_POST['from']) ? trim($_POST['from']) : 'HOWRAH';
    $toCity       = isset($_POST['to']) ? trim($_POST['to']) : 'NEW DELHI';
    $passengers   = isset($_POST['passengers']) ? $_POST['passengers'] : [];

    // Unique Indian Railway Style PNR
    $pnr = 'RE' . mt_rand(10000000, 99999999);

    $conn->begin_transaction();
    try {
        $colRes = $conn->query("SHOW COLUMNS FROM bookings");
        $existingCols = [];
        while ($colRow = $colRes->fetch_assoc()) {
            $existingCols[] = $colRow['Field'];
        }

        $dataToInsert = [];
        if (in_array('user_id', $existingCols))       $dataToInsert['user_id'] = $userId;
        if (in_array('train_id', $existingCols))      $dataToInsert['train_id'] = $trainId;
        if (in_array('coach_id', $existingCols))      $dataToInsert['coach_id'] = $coachId;
        if (in_array('pnr', $existingCols))           $dataToInsert['pnr'] = $pnr;
        
        if (in_array('from_station_id', $existingCols)) $dataToInsert['from_station_id'] = 1;
        if (in_array('to_station_id', $existingCols))   $dataToInsert['to_station_id'] = 2;

        if (in_array('journey_date', $existingCols))     $dataToInsert['journey_date'] = $journeyDate;
        elseif (in_array('booking_date', $existingCols)) $dataToInsert['booking_date'] = $journeyDate;
        elseif (in_array('travel_date', $existingCols))  $dataToInsert['travel_date'] = $journeyDate;

        if (in_array('total_fare', $existingCols))       $dataToInsert['total_fare'] = $totalFare;
        elseif (in_array('fare', $existingCols))         $dataToInsert['fare'] = $totalFare;
        elseif (in_array('total_amount', $existingCols)) $dataToInsert['total_amount'] = $totalFare;
        elseif (in_array('amount', $existingCols))       $dataToInsert['amount'] = $totalFare;

        if (in_array('contact_email', $existingCols))    $dataToInsert['contact_email'] = $contactEmail;
        if (in_array('contact_phone', $existingCols))    $dataToInsert['contact_phone'] = $contactPhone;
        if (in_array('booking_status', $existingCols))   $dataToInsert['booking_status'] = 'Confirmed';
        elseif (in_array('status', $existingCols))       $dataToInsert['status'] = 'Confirmed';

        $colNames = implode(', ', array_keys($dataToInsert));
        $placeholders = implode(', ', array_fill(0, count($dataToInsert), '?'));
        
        $types = '';
        $values = array_values($dataToInsert);
        foreach ($values as $val) {
            if (is_int($val)) $types .= 'i';
            elseif (is_float($val)) $types .= 'd';
            else $types .= 's';
        }

        $bStmt = $conn->prepare("INSERT INTO bookings ($colNames) VALUES ($placeholders)");
        $bStmt->bind_param($types, ...$values);
        $bStmt->execute();
        $bookingId = $conn->insert_id;
        $bStmt->close();

        // Passengers
        $pStmt = $conn->prepare("
            INSERT INTO passengers (booking_id, seat_id, passenger_name, age, gender) 
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($passengers as $p) {
            $seatId = (int)$p['seat_id'];
            $pName  = trim($p['name']);
            $pAge   = (int)$p['age'];
            $pGen   = trim($p['gender']);
            $pStmt->bind_param("iisis", $bookingId, $seatId, $pName, $pAge, $pGen);
            $pStmt->execute();
        }
        $pStmt->close();

        $conn->commit();

        header("Location: confirmation.php?pnr=" . urlencode($pnr) . "&date=" . urlencode($journeyDate) . "&fare=" . urlencode($totalFare) . "&from=" . urlencode($fromCity) . "&to=" . urlencode($toCity));
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Booking Error: " . htmlspecialchars($e->getMessage()));
    }
}

// Fetch booking by PNR
$pnr = isset($_GET['pnr']) ? trim($_GET['pnr']) : '';
$journeyDateDisplay = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
$totalFareDisplay   = isset($_GET['fare']) ? (float)$_GET['fare'] : 0;
$sourceCity         = isset($_GET['from']) && !empty($_GET['from']) ? trim($_GET['from']) : 'HOWRAH';
$destCity           = isset($_GET['to']) && !empty($_GET['to']) ? trim($_GET['to']) : 'NEW DELHI';

if (empty($pnr)) {
    header("Location: search.php");
    exit;
}

// SELECT query theke t.source o t.destination bad dewa hoyeche
$stmt = $conn->prepare("
    SELECT b.*, t.train_number, t.train_name, c.coach_number, c.coach_type
    FROM bookings b
    JOIN trains t ON b.train_id = t.train_id
    JOIN coaches c ON b.coach_id = c.coach_id
    WHERE b.pnr = ?
");
$stmt->bind_param("s", $pnr);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Ticket not found for PNR: " . htmlspecialchars($pnr));
}

$fareToPrint = isset($booking['total_fare']) ? $booking['total_fare'] : (isset($booking['fare']) ? $booking['fare'] : $totalFareDisplay);

// Passengers with seats
$pStmt = $conn->prepare("
    SELECT p.passenger_name, p.age, p.gender, s.seat_number, s.seat_type
    FROM passengers p
    JOIN seats s ON p.seat_id = s.seat_id
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
                        <th>Seat No.</th>
                        <th>Berth Position</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($passengerList as $p): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($p['passenger_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['age']); ?> yrs, <?php echo htmlspecialchars($p['gender']); ?></td>
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
        <a href="search.php" class="action-btn-home">
            Book Another Ticket
        </a>
    </div>

</body>
</html>