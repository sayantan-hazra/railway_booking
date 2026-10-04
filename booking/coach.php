<?php
/*
 * RailEase - Coach Selection
 * Developed by: SAYAN SUR
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = "Select Coach";

$trainId     = isset($_GET['train_id']) ? (int)$_GET['train_id'] : 0;
$journeyDate = isset($_GET['date']) ? trim($_GET['date']) : (isset($_GET['journey_date']) ? trim($_GET['journey_date']) : date('Y-m-d'));
$from        = isset($_GET['from']) ? trim($_GET['from']) : '';
$to          = isset($_GET['to']) ? trim($_GET['to']) : '';

if ($trainId <= 0) {
    header("Location: search.php");
    exit;
}

// Fetch train details
$stmt = $conn->prepare("SELECT train_id, train_number, train_name FROM trains WHERE train_id = ?");
$stmt->bind_param("i", $trainId);
$stmt->execute();
$train = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$train) {
    die("Train not found in database for ID: " . htmlspecialchars($trainId));
}

$routeStatement = $conn->prepare(
    'SELECT fs.city AS from_city, ts.city AS to_city
     FROM train_stops fstop
     INNER JOIN stations fs ON fs.station_id = fstop.station_id AND fs.station_code = ?
     INNER JOIN train_stops tstop ON tstop.train_id = fstop.train_id
     INNER JOIN stations ts ON ts.station_id = tstop.station_id AND ts.station_code = ?
     WHERE fstop.train_id = ? AND fstop.stop_order < tstop.stop_order
     LIMIT 1'
);
$routeStatement->bind_param('ssi', $from, $to, $trainId);
$routeStatement->execute();
$route = $routeStatement->get_result()->fetch_assoc();
$routeStatement->close();
$fromCity = $route['from_city'] ?? $from;
$toCity = $route['to_city'] ?? $to;

// Fetch coaches for this train
$coachesStmt = $conn->prepare("
    SELECT coach_id, coach_number, coach_type, seat_price
    FROM coaches
    WHERE train_id = ?
    ORDER BY coach_number ASC
");
$coachesStmt->bind_param("i", $trainId);
$coachesStmt->execute();
$coachesRes = $coachesStmt->get_result();
$coachesList = $coachesRes->fetch_all(MYSQLI_ASSOC);
$coachesStmt->close();


function getCoachDetails($type) {
    switch (strtoupper(trim($type))) {
        case '1A':
            return ['title' => '1st Class AC (1A)', 'price' => '₹2,400', 'is_ac' => true, 'desc' => 'Spacious coupe with full privacy'];
        case '2A':
            return ['title' => '2 Tier AC (2A)', 'price' => '₹1,500', 'is_ac' => true, 'desc' => 'Air-conditioned 2-tier berths with curtains'];
        case '3A':
            return ['title' => '3 Tier AC (3A)', 'price' => '₹1,050', 'is_ac' => true, 'desc' => 'Air-conditioned 3-tier comfortable berths'];
        case 'CC':
            return ['title' => 'AC Chair Car (CC)', 'price' => '₹850', 'is_ac' => true, 'desc' => 'Air-conditioned comfortable reclining seats'];
        case 'SL':
            return ['title' => 'Sleeper Class (SL)', 'price' => '₹450', 'is_ac' => false, 'desc' => 'Standard non-AC sleeper berths'];
        case 'GEN':
        case '2S':
            return ['title' => 'General / Second Sitting', 'price' => '₹220', 'is_ac' => false, 'desc' => 'Budget friendly reserved sitting'];
        default:
            return ['title' => $type . ' Class', 'price' => '₹500', 'is_ac' => false, 'desc' => 'Standard train seating'];
    }
}
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
                <img class="brand-logo" src="../assets/logo.png" alt="RailEase">
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

    <main class="trains-container">
        <a href="trains.php?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&journey_date=<?php echo urlencode($journeyDate); ?>" class="back-link search-back-link">← Back to Trains</a>

        <!-- Summary Bar -->
        <div class="train-search-summary">
            <div class="train-summary-col">
                <span class="summary-label">TRAIN SELECTED</span>
                <h3 class="summary-title">
                    #<?php echo htmlspecialchars($train['train_number']); ?> - <?php echo htmlspecialchars($train['train_name']); ?>
                </h3>
            </div>
            <div class="train-summary-col">
                <span class="summary-label">ROUTE & DATE</span>
                <h3 class="summary-title date-highlight">
                    <span class="coach-route-city"><?php echo htmlspecialchars($fromCity); ?></span>
                    <span aria-hidden="true">➔</span>
                    <span class="coach-route-city"><?php echo htmlspecialchars($toCity); ?></span>
                    <span class="coach-route-date">(<?php echo htmlspecialchars($journeyDate); ?>)</span>
                </h3>
            </div>
        </div>

        <h2 class="trains-heading">Select Travel Class & Coach</h2>

        <!-- Coaches Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 1.5rem;">
            <?php if (!empty($coachesList)): ?>
                <?php foreach ($coachesList as $coach): ?>
                    <?php
                        $info = getCoachDetails($coach['coach_type']);
                        $info['price'] = '₹' . number_format((float) $coach['seat_price'], 2);
                        $badgeClass = $info['is_ac'] ? 'ac-tag' : 'non-ac-tag';
                    ?>
                    <div class="train-card-item" style="display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <span class="train-badge-num" style="font-size: 0.85rem;">
                                    COACH <?php echo htmlspecialchars($coach['coach_number']); ?>
                                </span>
                                <span class="<?php echo $badgeClass; ?>">
                                    <?php echo $info['is_ac'] ? '❄ AC' : 'NON-AC'; ?>
                                </span>
                            </div>

                            <h3 style="font-size: 1.15rem; color: #0f172a; margin: 0 0 0.35rem 0;">
                                <?php echo htmlspecialchars($info['title']); ?>
                            </h3>
                            <p style="font-size: 0.8rem; color: #64748b; margin: 0 0 1rem 0; min-height: 2.2em;">
                                <?php echo htmlspecialchars($info['desc']); ?>
                            </p>
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1rem; border-top: 1px dashed #e2e8f0; padding-top: 0.75rem;">
                                <span style="font-size: 0.85rem; color: #64748b;">Fare per seat:</span>
                                <span style="font-size: 1.35rem; font-weight: 700; color: #16a34a;"><?php echo $info['price']; ?></span>
                            </div>

                            <a href="seats.php?train_id=<?php echo urlencode($trainId); ?>&coach_id=<?php echo urlencode($coach['coach_id']); ?>&date=<?php echo urlencode($journeyDate); ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>" class="btn-select-coach" style="display: block; width: 100%; box-sizing: border-box;">
                                Select Seats →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-trains-card" style="grid-column: 1 / -1;">
                    <p>No coaches found for this train.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>