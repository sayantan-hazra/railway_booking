<?php
// RailEase - User Homepage
// Phase 1: Static homepage UI
// Database and booking logic will be integrated later.

session_start();
require_once __DIR__ . '/../config/db.php';

$pageTitle = "Home";
$userId = (int) ($_SESSION['user_id'] ?? 0);
$upcomingBooking = null;
$latestBooking = null;

if ($userId > 0) {
    $upcomingStatement = $conn->prepare(
        "SELECT b.booking_id, b.pnr, b.travel_date, b.total_amount, b.status,
                t.train_number, t.train_name,
                fs.station_name AS from_station, ts.station_name AS to_station,
                c.coach_number
         FROM bookings b
         INNER JOIN trains t ON t.train_id = b.train_id
         INNER JOIN stations fs ON fs.station_id = b.from_station_id
         INNER JOIN stations ts ON ts.station_id = b.to_station_id
         LEFT JOIN coaches c ON c.coach_id = b.coach_id
         WHERE b.user_id = ? AND b.status = 'CONFIRMED' AND b.travel_date >= CURDATE()
         ORDER BY b.travel_date ASC, b.booked_at DESC
         LIMIT 1"
    );
    $upcomingStatement->bind_param('i', $userId);
    $upcomingStatement->execute();
    $upcomingBooking = $upcomingStatement->get_result()->fetch_assoc();
    $upcomingStatement->close();

    $latestStatement = $conn->prepare(
        "SELECT b.booking_id, b.pnr, b.travel_date, b.total_amount, b.status,
                t.train_number, t.train_name,
                fs.station_name AS from_station, ts.station_name AS to_station,
                c.coach_number
         FROM bookings b
         INNER JOIN trains t ON t.train_id = b.train_id
         INNER JOIN stations fs ON fs.station_id = b.from_station_id
         INNER JOIN stations ts ON ts.station_id = b.to_station_id
         LEFT JOIN coaches c ON c.coach_id = b.coach_id
         WHERE b.user_id = ?
         ORDER BY b.booked_at DESC, b.booking_id DESC
         LIMIT 1"
    );
    $latestStatement->bind_param('i', $userId);
    $latestStatement->execute();
    $latestBooking = $latestStatement->get_result()->fetch_assoc();
    $latestStatement->close();
}

$homeBooking = $upcomingBooking ?: $latestBooking;
$stations = [];
$stationResult = $conn->query(
    "SELECT station_code, station_name, city
     FROM stations
     ORDER BY station_name"
);

if ($stationResult) {
    while ($station = $stationResult->fetch_assoc()) {
        $stations[] = $station;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?php echo $pageTitle; ?> - RailEase</title>

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Main stylesheet -->
    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="shortcut icon" href="../assets/favicon/favicon.ico">
</head>

<body class="user-body">

    <!-- =========================
         HEADER
    ========================== -->
    <header class="user-header">

        <div class="user-header-inner">

            <!-- RailEase Logo -->
            <a href="home.php" class="brand">
                <img class="brand-logo" src="../assets/white_logo.png" alt="RailEase">
            </a>

            <!-- Header Actions -->
            <div class="header-actions">

                <a href="wallet.php" class="header-action">
                    <span class="action-icon">₹</span>
                    <span class="action-text">Wallet</span>
                </a>

                <a href="support.php" class="header-action">
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


    <!-- =========================
         MAIN CONTENT
    ========================== -->
    <main class="user-main">

        <!-- =========================
             WELCOME SECTION
        ========================== -->
        <section class="welcome-section">

            <div>
                <p class="eyebrow">Welcome to RailEase</p>

                <h1>
                    Plan your journey,
                    <span>travel with ease.</span>
                </h1>

                <p class="welcome-text">
                    Search trains, choose your seat and book your journey
                    from one simple platform.
                </p>
            </div>

        </section>


        <!-- =========================
             TRAIN SEARCH CARD
        ========================== -->
        <section class="search-section">

            <div class="search-card">

                <div class="search-card-header">

                    <div>
                        <p class="eyebrow">Train Booking</p>

                        <h2>
                            Where are you going?
                        </h2>
                    </div>

                </div>


                <form
                    action="../booking/search.php"
                    method="GET"
                    id="trainSearchForm"
                    class="train-search-form"
                >

                    <!-- From Station -->
                    <div class="station-field">

                        <label for="from">
                            From
                        </label>

                        <div class="input-wrapper">
                            <select id="from" name="from" required>
                                <option value="" selected disabled>Select departure station</option>
                                <?php foreach ($stations as $station): ?>
                                    <option value="<?= htmlspecialchars($station['station_code']) ?>">
                                        <?= htmlspecialchars($station['station_name'] . ' (' . $station['station_code'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        </div>

                    </div>


                    <!-- Swap Button -->
                    <button
                        type="button"
                        class="swap-button"
                        id="swapStations"
                        title="Swap stations"
                        aria-label="Swap departure and destination stations"
                    >
                        ⇄
                    </button>


                    <!-- To Station -->
                    <div class="station-field">

                        <label for="to">
                            To
                        </label>

                        <div class="input-wrapper">
                            <select id="to" name="to" required>
                                <option value="" selected disabled>Select destination station</option>
                                <?php foreach ($stations as $station): ?>
                                    <option value="<?= htmlspecialchars($station['station_code']) ?>">
                                        <?= htmlspecialchars($station['station_name'] . ' (' . $station['station_code'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        </div>

                    </div>


                    <!-- Journey Date -->
                    <div class="date-field">

                        <label for="journey_date">
                            Journey Date
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                📅
                            </span>

                            <input
                                type="date"
                                id="journey_date"
                                name="journey_date"
                                required
                            >

                        </div>

                    </div>


                    <!-- Search Button -->
                    <button
                        type="submit"
                        class="button button-primary search-button"
                    >
                        Search Trains
                        <span>→</span>
                    </button>

                </form>

            </div>

        </section>


        <!-- =========================
             PROMOTIONAL BANNER
        ========================== -->
        <section class="promo-section">

            <div class="promo-card">

                <div class="promo-content">

                    <p class="promo-label">
                        TRAVEL SMART
                    </p>

                    <h2>
                        Your journey starts here.
                    </h2>

                    <p>
                        Find available trains, select your preferred
                        coach and seat, and complete your booking easily.
                    </p>

                </div>

                <div class="promo-illustration">
                    🚆
                </div>

            </div>

        </section>


        <!-- =========================
             UPCOMING JOURNEY
        ========================== -->
        <section class="upcoming-section">

            <div class="section-heading">

                <div>
                    <p class="eyebrow">Your Trips</p>

                    <h2><?= $upcomingBooking ? 'Upcoming Journey' : 'Latest Booking' ?></h2>
                </div>

                <a
                    href="manage_booking.php"
                    class="text-link"
                >
                    Manage Booking →
                </a>

            </div>


            <?php if ($homeBooking): ?>
                <article class="booking-card home-booking-card">
                    <div class="home-booking-heading">
                        <p class="eyebrow"><?= $homeBooking['status'] === 'CONFIRMED' ? 'Confirmed trip' : 'Most recent booking' ?></p>
                        <h3><?= htmlspecialchars($homeBooking['train_name']) ?></h3>
                        <span>Train <?= htmlspecialchars($homeBooking['train_number']) ?> · PNR <?= htmlspecialchars($homeBooking['pnr']) ?></span>
                    </div>
                    <div class="home-booking-route">
                        <strong><?= htmlspecialchars($homeBooking['from_station']) ?></strong>
                        <span aria-hidden="true">→</span>
                        <strong><?= htmlspecialchars($homeBooking['to_station']) ?></strong>
                    </div>
                    <div class="home-booking-meta">
                        <span>Date <strong><?= htmlspecialchars($homeBooking['travel_date']) ?></strong></span>
                        <span>Coach <strong><?= htmlspecialchars($homeBooking['coach_number'] ?? '-') ?></strong></span>
                        <span>Amount <strong>₹<?= number_format((float) $homeBooking['total_amount'], 2) ?></strong></span>
                    </div>
                    <div class="home-booking-actions">
                        <span class="badge <?= $homeBooking['status'] === 'CONFIRMED' ? 'badge-green' : 'badge-red' ?>"><?= htmlspecialchars($homeBooking['status']) ?></span>
                        <a class="button button-muted" href="view_ticket.php?id=<?= (int) $homeBooking['booking_id'] ?>">View ticket</a>
                    </div>
                </article>
            <?php else: ?>
                <div class="upcoming-card empty-upcoming">
                    <div class="empty-icon">🚆</div>
                    <div>
                        <h3>No booked journeys</h3>
                        <p>Your booked trains will appear here.</p>
                    </div>
                </div>
            <?php endif; ?>

        </section>

    </main>


    <!-- =========================
         MOBILE BOTTOM NAVIGATION
    ========================== -->
    <nav class="bottom-nav">

        <a
            href="home.php"
            class="bottom-nav-item active"
        >
            <span class="bottom-nav-icon">⌂</span>
            <span>Home</span>
        </a>


        <a
            href="manage_booking.php"
            class="bottom-nav-item"
        >
            <span class="bottom-nav-icon">🎫</span>
            <span>Bookings</span>
        </a>


        <a
            href="profile.php"
            class="bottom-nav-item"
        >
            <span class="bottom-nav-icon">●</span>
            <span>Profile</span>
        </a>

    </nav>


    <!-- Main JavaScript -->
    <script src="../assets/js/script.js"></script>

</body>
</html>