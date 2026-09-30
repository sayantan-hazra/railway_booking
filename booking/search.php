<?php
/*
 * RailEase - Train Search Logic
 * Developed by: SAYAN SUR
 */

require_once __DIR__ . '/../config/db.php';

$pageTitle = "Search Trains";

// Get search values from URL
$from = isset($_GET["from"]) ? trim($_GET["from"]) : "";
$to = isset($_GET["to"]) ? trim($_GET["to"]) : "";
$journeyDate = isset($_GET["journey_date"]) ? trim($_GET["journey_date"]) : "";

// Basic validation
$searchSubmitted = $from !== "" && $to !== "" && $journeyDate !== "";

$trainResults = [];
$searchError = "";

// Database search logic
if ($searchSubmitted) {
    $sql = "SELECT 
                t.train_id,
                t.train_number,
                t.train_name,
                TIME_FORMAT(s1.departure_time, '%h:%i %p') AS departure,
                TIME_FORMAT(s2.arrival_time, '%h:%i %p') AS arrival,
                TIMEDIFF(s2.arrival_time, s1.departure_time) AS duration_raw
            FROM trains t
            JOIN train_stops s1 ON t.train_id = s1.train_id
            JOIN stations st1 ON s1.station_id = st1.station_id
            JOIN train_stops s2 ON t.train_id = s2.train_id
            JOIN stations st2 ON s2.station_id = st2.station_id
            WHERE (st1.station_name LIKE ? OR st1.station_code LIKE ? OR st1.city LIKE ?)
              AND (st2.station_name LIKE ? OR st2.station_code LIKE ? OR st2.city LIKE ?)
              AND s1.stop_order < s2.stop_order";

    if ($stmt = $conn->prepare($sql)) {
        $fromParam = "%$from%";
        $toParam = "%$to%";
        $stmt->bind_param("ssssss", $fromParam, $fromParam, $fromParam, $toParam, $toParam, $toParam);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $durationFormatted = "N/A";
            if (!empty($row['duration_raw'])) {
                $parts = explode(':', $row['duration_raw']);
                $durationFormatted = (int)$parts[0] . 'h ' . (int)$parts[1] . 'm';
            }

            $trainResults[] = [
                "train_id"  => $row["train_id"],
                "number"    => $row["train_number"],
                "name"      => $row["train_name"],
                "departure" => $row["departure"] ?? "N/A",
                "arrival"   => $row["arrival"] ?? "N/A",
                "duration"  => $durationFormatted,
                "fare"      => "₹500"
            ];
        }
        $stmt->close();
    } else {
        $searchError = "Unable to process train search at this moment.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - RailEase</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
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

    <!-- MAIN -->
    <main class="user-main">

        <!-- Page Heading -->
        <section class="search-results-heading">
            <a href="../user/home.php" class="back-link search-back-link">
                ← Back to Home
            </a>

            <p class="eyebrow">Train Search</p>
            <h1>Available Trains</h1>

            <?php if ($searchSubmitted): ?>
                <p class="search-route">
                    <?php echo htmlspecialchars($from); ?>
                    <span>→</span>
                    <?php echo htmlspecialchars($to); ?>
                    <span class="search-date">
                        <?php echo htmlspecialchars($journeyDate); ?>
                    </span>
                </p>
            <?php else: ?>
                <p class="muted">
                    Enter your journey details to search for trains.
                </p>
            <?php endif; ?>
        </section>

        <?php if (!$searchSubmitted): ?>

            <!-- SEARCH FORM -->
            <section class="search-card">
                <form action="search.php" method="GET" class="train-search-form-custom">
                    
                    <div class="field-group">
                        <label for="from">From</label>
                        <input type="text" id="from" name="from" value="<?php echo htmlspecialchars($from); ?>" placeholder="e.g. Howrah or HWH" required>
                    </div>

                    <div class="field-group">
                        <label for="to">To</label>
                        <input type="text" id="to" name="to" value="<?php echo htmlspecialchars($to); ?>" placeholder="e.g. New Delhi or NDLS" required>
                    </div>

                    <div class="field-group">
                        <label for="journey_date">Journey Date</label>
                        <input type="date" id="journey_date" name="journey_date" value="<?php echo htmlspecialchars($journeyDate); ?>" required>
                    </div>

                    <button type="submit" class="button button-primary search-btn-custom">
                        Search Trains →
                    </button>

                </form>
            </section>

        <?php else: ?>

            <!-- SEARCH RESULTS -->
            <section class="train-results">

                <?php if (!empty($searchError)): ?>
                    <p class="error-msg"><?php echo htmlspecialchars($searchError); ?></p>
                <?php elseif (!empty($trainResults)): ?>
                    <?php foreach ($trainResults as $train): ?>
                        <article class="train-card">
                            <div class="train-card-top">
                                <div>
                                    <p class="train-number">
                                        <?php echo htmlspecialchars($train["number"]); ?>
                                    </p>
                                    <h2>
                                        <?php echo htmlspecialchars($train["name"]); ?>
                                    </h2>
                                </div>
                                <span class="badge badge-green">Available</span>
                            </div>

                            <div class="train-details">
                                <div class="train-time">
                                    <strong><?php echo htmlspecialchars($train["departure"]); ?></strong>
                                    <span>Departure</span>
                                </div>

                                <div class="train-duration">
                                    <span><?php echo htmlspecialchars($train["duration"]); ?></span>
                                    <div class="duration-line"></div>
                                </div>

                                <div class="train-time">
                                    <strong><?php echo htmlspecialchars($train["arrival"]); ?></strong>
                                    <span>Arrival</span>
                                </div>
                            </div>

                            <div class="train-card-bottom">
                                <div>
                                    <span class="muted">Starting from</span>
                                    <strong class="train-fare">
                                        <?php echo htmlspecialchars($train["fare"]); ?>
                                    </strong>
                                </div>

                                <a href="coach.php?train_id=<?php echo urlencode($train['train_id']); ?>&date=<?php echo urlencode($journeyDate); ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>" class="button button-primary">
                                    Select Train
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results" style="text-align: center; padding: 2rem;">
                        <p>No trains found between <strong><?php echo htmlspecialchars($from); ?></strong> and <strong><?php echo htmlspecialchars($to); ?></strong>.</p>
                        <br>
                        <a href="search.php" class="button button-secondary">Try Another Search</a>
                    </div>
                <?php endif; ?>

            </section>

        <?php endif; ?>

    </main>

    <!-- MOBILE NAV -->
    <nav class="bottom-nav">
        <a href="../user/home.php" class="bottom-nav-item">
            <span class="bottom-nav-icon">⌂</span>
            <span>Home</span>
        </a>
        <a href="../user/manage_booking.php" class="bottom-nav-item">
            <span class="bottom-nav-icon">🎫</span>
            <span>Bookings</span>
        </a>
        <a href="../user/profile.php" class="bottom-nav-item">
            <span class="bottom-nav-icon">●</span>
            <span>Profile</span>
        </a>
    </nav>

    <script src="../assets/js/script.js"></script>
</body>

</html>