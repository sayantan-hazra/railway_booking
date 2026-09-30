<?php
/*
 * RailEase - Booking Checkout & Passenger Details
 * Developed by: SAYAN SUR
 */

session_start();
require_once __DIR__ . '/../config/db.php';

$pageTitle = "Checkout & Passenger Details";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['seat_ids'])) {
    header("Location: search.php");
    exit;
}


$userData = [
    'name'  => $_SESSION['name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? '',
    'email' => $_SESSION['email'] ?? $_SESSION['user_email'] ?? '',
    'phone' => $_SESSION['phone'] ?? $_SESSION['user_phone'] ?? $_SESSION['mobile'] ?? ''
];


$loggedInUserId = 0;
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = (int)$_SESSION['user_id'];
} elseif (isset($_SESSION['id'])) {
    $loggedInUserId = (int)$_SESSION['id'];
} elseif (isset($_SESSION['uid'])) {
    $loggedInUserId = (int)$_SESSION['uid'];
}

if (empty($userData['name']) && $loggedInUserId > 0) {
    $uStmt = $conn->prepare("SELECT name, email, phone FROM users WHERE user_id = ?");
    if ($uStmt) {
        $uStmt->bind_param("i", $loggedInUserId);
        $uStmt->execute();
        $res = $uStmt->get_result()->fetch_assoc();
        if ($res) {
            $userData['name']  = $res['name'] ?? '';
            $userData['email'] = $res['email'] ?? '';
            $userData['phone'] = $res['phone'] ?? '';
        }
        $uStmt->close();
    }
}

$trainId     = (int)$_POST['train_id'];
$coachId     = (int)$_POST['coach_id'];
$journeyDate = trim($_POST['journey_date']);
$from        = trim($_POST['from'] ?? 'HOWRAH');
$to          = trim($_POST['to'] ?? 'NEW DELHI');
$seatIdsRaw  = trim($_POST['seat_ids']);
$baseFare    = (float)$_POST['total_fare'];


$tStmt = $conn->prepare("
    SELECT t.train_number, t.train_name, c.coach_number, c.coach_type 
    FROM coaches c
    JOIN trains t ON c.train_id = t.train_id
    WHERE c.coach_id = ? AND t.train_id = ?
");
$tStmt->bind_param("ii", $coachId, $trainId);
$tStmt->execute();
$trip = $tStmt->get_result()->fetch_assoc();
$tStmt->close();

if (!$trip) {
    echo "Trip details not found!";
    exit;
}


$seatIdArr = array_filter(array_map('intval', explode(',', $seatIdsRaw)));
$placeholders = implode(',', array_fill(0, count($seatIdArr), '?'));
$types = str_repeat('i', count($seatIdArr));

$sStmt = $conn->prepare("SELECT seat_id, seat_number, seat_type FROM seats WHERE seat_id IN ($placeholders) ORDER BY CAST(seat_number AS UNSIGNED) ASC");
$sStmt->bind_param($types, ...$seatIdArr);
$sStmt->execute();
$selectedSeats = $sStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sStmt->close();

$passengerCount = count($selectedSeats);
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
                                               value="<?php echo ($index === 0) ? htmlspecialchars($userData['name']) : ''; ?>" 
                                               placeholder="Enter Full Name" required>
                                    </div>
                                    <div class="input-group">
                                        <label>Age</label>
                                        <input type="number" name="passengers[<?php echo $index; ?>][age]" min="1" max="120" placeholder="Age" required>
                                    </div>
                                    <div class="input-group">
                                        <label>Gender</label>
                                        <select name="passengers[<?php echo $index; ?>][gender]" required>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Travel Preferences & Food Selection -->
                    <div class="checkout-card">
                        <h2 class="checkout-card-title">Catering & Travel Preferences</h2>
                        
                        <div>
                            <label class="checkout-field-label">Catering / Meal Preference (Per Passenger)</label>
                            <select name="meal_preference" id="mealPreference" class="checkout-meal-select" onchange="calculateGrandTotal()">
                                <option value="0" data-name="No Food">No Food / Opt-out (₹0)</option>
                                <option value="150" data-name="Veg Meal">Veg Meal (+₹150 per person)</option>
                                <option value="180" data-name="Non-Veg Meal">Non-Veg Meal (+₹180 per person)</option>
                                <option value="150" data-name="Jain Meal">Jain Meal (+₹150 per person)</option>
                            </select>
                        </div>

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
                                       value="<?php echo htmlspecialchars($userData['email']); ?>" 
                                       placeholder="example@email.com" required>
                            </div>
                            <div class="input-group">
                                <label>Phone Number</label>
                                <input type="tel" name="contact_phone" 
                                       value="<?php echo htmlspecialchars($userData['phone']); ?>" 
                                       placeholder="10-digit mobile number" pattern="[0-9]{10}" required>
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

                    <div class="fare-row"><span>From:</span><strong><?php echo strtoupper(htmlspecialchars($from)); ?></strong></div>
                    <div class="fare-row"><span>To:</span><strong><?php echo strtoupper(htmlspecialchars($to)); ?></strong></div>
                    <div class="fare-row"><span>Date:</span><strong><?php echo htmlspecialchars($journeyDate); ?></strong></div>
                    <div class="fare-row"><span>Coach:</span><strong><?php echo htmlspecialchars($trip['coach_number']); ?> (<?php echo htmlspecialchars($trip['coach_type']); ?>)</strong></div>
                    <div class="fare-row"><span>Passengers:</span><strong><?php echo $passengerCount; ?> Seat(s)</strong></div>
                    <div class="fare-row"><span>Base Ticket Fare:</span><strong>₹<?php echo number_format($baseFare, 2); ?></strong></div>
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
        const baseTicketFare = <?php echo (float)$baseFare; ?>;
        const passengerCount = <?php echo (int)$passengerCount; ?>;

        function calculateGrandTotal() {
            const mealSelect = document.getElementById('mealPreference');
            const mealPricePerPerson = parseFloat(mealSelect.value) || 0;
            const totalMealCost = mealPricePerPerson * passengerCount;

            const insuranceChecked = document.getElementById('insuranceCheckbox').checked;
            const totalInsuranceCost = insuranceChecked ? (0.45 * passengerCount) : 0;

            const grandTotal = baseTicketFare + totalMealCost + totalInsuranceCost;

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