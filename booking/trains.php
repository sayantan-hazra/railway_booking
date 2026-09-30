<?php
/*
 * RailEase - Available Trains List
 * Developed by: SAYAN SUR
 */

require_once __DIR__ . '/../config/db.php';

$pageTitle = "Available Trains";

$from        = isset($_GET['from']) ? trim($_GET['from']) : '';
$to          = isset($_GET['to']) ? trim($_GET['to']) : '';
$journeyDate = isset($_GET['journey_date']) ? trim($_GET['journey_date']) : (isset($_GET['date']) ? trim($_GET['date']) : '');

if (empty($from) || empty($to)) {
    header("Location: search.php");
    exit;
}

// Fetch corresponding trains from database
$stmt = $conn->prepare("
    SELECT train_id, train_number, train_name, source, destination, departure_time, arrival_time, duration 
    FROM trains 
    WHERE LOWER(source) LIKE LOWER(?) AND LOWER(destination) LIKE LOWER(?)
");
$fromParam = "%$from%";
$toParam   = "%$to%";
$stmt->bind_param("ss", $fromParam, $toParam);
$stmt->execute();
$trainsResult = $stmt->get_result();
$trainList = $trainsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
                <a href="../user/wallet.php" class="header-action">
                    <span class="action-icon">₹</span>
                    <span class="action-text">Wallet</span>
                </a>
                <a href="../user/support.php" class="header-action">
                    <span class="action-icon">?</span>
                    <span class="action-text">Support</span>
                </a>
            </div>
        </div>
    </header>

    <main class="trains-container">
        <a href="search.php" class="back-link search-back-link">← Modify Search</a>

        <!-- Search Header Bar -->
        <div class="train-search-summary">
            <div class="train-summary-col">
                <span class="summary-label">ROUTE</span>
                <h3 class="summary-title">
                    <?php echo htmlspecialchars($from); ?> ➔ <?php echo htmlspecialchars($to); ?>
                </h3>
            </div>
            <div class="train-summary-col">
                <span class="summary-label">JOURNEY DATE</span>
                <h3 class="summary-title date-highlight">
                    <?php echo htmlspecialchars($journeyDate ? $journeyDate : date('Y-m-d')); ?>
                </h3>
            </div>
        </div>

        <h2 class="trains-heading">
            Available Trains (<?php echo count($trainList); ?>)
        </h2>

        <!-- Trains List -->
        <?php if (!empty($trainList)): ?>
            <?php foreach ($trainList as $train): ?>
                <div class="train-card-item">
                    <!-- Train Name & Number -->
                    <div>
                        <span class="train-badge-num">
                            #<?php echo htmlspecialchars($train['train_number']); ?>
                        </span>
                        <h3 class="train-name-title">
                            <?php echo htmlspecialchars($train['train_name']); ?>
                        </h3>
                        <span class="train-sub-text">Runs Daily</span>
                    </div>

                    <!-- Timings & Duration -->
                    <div class="train-time-flow">
                        <div class="time-box">
                            <h4><?php echo htmlspecialchars(substr($train['departure_time'], 0, 5)); ?></h4>
                            <span><?php echo htmlspecialchars($train['source']); ?></span>
                        </div>

                        <div class="duration-indicator">
                            <span><?php echo htmlspecialchars($train['duration'] ?? '12h 30m'); ?></span>
                            <div class="duration-line"></div>
                        </div>

                        <div class="time-box">
                            <h4><?php echo htmlspecialchars(substr($train['arrival_time'], 0, 5)); ?></h4>
                            <span><?php echo htmlspecialchars($train['destination']); ?></span>
                        </div>
                    </div>

                    <!-- Action Link to coach.php -->
                    <div class="train-action-cell">
                        <a href="coach.php?train_id=<?php echo urlencode($train['train_id']); ?>&date=<?php echo urlencode($journeyDate); ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>" class="btn-select-coach">
                            Select Coach →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-trains-card">
                <p>No trains found for this route.</p>
                <a href="search.php" class="btn-select-coach">Try Different Route</a>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>