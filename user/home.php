<?php
// RailEase - User Homepage
// Phase 1: Static homepage UI
// Database and booking logic will be integrated later.

$pageTitle = "Home";
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
</head>

<body class="user-body">

    <!-- =========================
         HEADER
    ========================== -->
    <header class="user-header">

        <div class="user-header-inner">

            <!-- RailEase Logo -->
            <a href="home.php" class="brand">
                <span class="brand-mark">R</span>
                <span>RailEase</span>
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

                            <span class="input-icon">
                                🚉
                            </span>

                            <input
                                type="text"
                                id="from"
                                name="from"
                                placeholder="Departure station"
                                autocomplete="off"
                                required
                            >

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

                            <span class="input-icon">
                                📍
                            </span>

                            <input
                                type="text"
                                id="to"
                                name="to"
                                placeholder="Destination station"
                                autocomplete="off"
                                required
                            >

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

                    <h2>
                        Upcoming Journey
                    </h2>
                </div>

                <a
                    href="manage_booking.php"
                    class="text-link"
                >
                    Manage Booking →
                </a>

            </div>


            <!-- Empty state for now -->
            <!-- Later this will come from the database -->

            <div class="upcoming-card empty-upcoming">

                <div class="empty-icon">
                    🚆
                </div>

                <div>
                    <h3>
                        No upcoming journeys
                    </h3>

                    <p>
                        Your confirmed bookings will appear here.
                    </p>
                </div>

            </div>

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