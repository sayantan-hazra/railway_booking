<!-- <------ SAYANTAN PAL ------ -->
 <?php

/*
 * RailEase - Train Search
 *
 * TEMPORARY UI VERSION
 *
 * The actual database search logic will be integrated
 * later with the train-search module.
 */

$pageTitle = "Search Trains";


// Get search values from URL

$from = isset($_GET["from"])
    ? trim($_GET["from"])
    : "";

$to = isset($_GET["to"])
    ? trim($_GET["to"])
    : "";

$journeyDate = isset($_GET["journey_date"])
    ? trim($_GET["journey_date"])
    : "";


// Basic validation

$searchSubmitted =
    $from !== "" &&
    $to !== "" &&
    $journeyDate !== "";


// Demo train data
// Temporary only - NOT database data.

$demoTrains = [

    [
        "number" => "RAIL101",
        "name" => "RailEase Express",
        "departure" => "06:30 AM",
        "arrival" => "11:45 AM",
        "duration" => "5h 15m",
        "fare" => "₹450"
    ],

    [
        "number" => "RAIL202",
        "name" => "City Intercity",
        "departure" => "09:15 AM",
        "arrival" => "02:40 PM",
        "duration" => "5h 25m",
        "fare" => "₹520"
    ],

    [
        "number" => "RAIL303",
        "name" => "Eastern Express",
        "departure" => "04:20 PM",
        "arrival" => "09:10 PM",
        "duration" => "4h 50m",
        "fare" => "₹480"
    ]

];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo $pageTitle; ?> - RailEase
    </title>

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Main CSS -->
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

            <a
                href="../user/home.php"
                class="brand"
            >
                <span class="brand-mark">R</span>
                <span>RailEase</span>
            </a>


            <div class="header-actions">

                <a
                    href="../user/wallet.php"
                    class="header-action"
                >
                    <span class="action-icon">₹</span>
                    <span class="action-text">Wallet</span>
                </a>


                <a
                    href="../user/support.php"
                    class="header-action"
                >
                    <span class="action-icon">?</span>
                    <span class="action-text">Support</span>
                </a>

            </div>

        </div>

    </header>


    <!-- =========================
         MAIN
    ========================== -->

    <main class="user-main">


        <!-- Page Heading -->

        <section class="search-results-heading">

            <a
                href="../user/home.php"
                class="back-link search-back-link"
            >
                ← Back to Home
            </a>


            <p class="eyebrow">
                Train Search
            </p>


            <h1>
                Available Trains
            </h1>


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


            <!-- =========================
                 SEARCH FORM
            ========================== -->

            <section class="search-card">

                <form
                    action="search.php"
                    method="GET"
                    class="train-search-form"
                >

                    <div class="station-field">

                        <label for="from">
                            From
                        </label>

                        <input
                            type="text"
                            id="from"
                            name="from"
                            value="<?php echo htmlspecialchars($from); ?>"
                            placeholder="Departure station"
                            required
                        >

                    </div>


                    <div class="station-field">

                        <label for="to">
                            To
                        </label>

                        <input
                            type="text"
                            id="to"
                            name="to"
                            value="<?php echo htmlspecialchars($to); ?>"
                            placeholder="Destination station"
                            required
                        >

                    </div>


                    <div class="date-field">

                        <label for="journey_date">
                            Journey Date
                        </label>

                        <input
                            type="date"
                            id="journey_date"
                            name="journey_date"
                            value="<?php echo htmlspecialchars($journeyDate); ?>"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="button button-primary search-button"
                    >
                        Search Trains →
                    </button>

                </form>

            </section>


        <?php else: ?>


            <!-- =========================
                 SEARCH RESULTS
            ========================== -->

            <section class="train-results">

                <?php foreach ($demoTrains as $train): ?>

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


                            <span class="badge badge-green">
                                Available
                            </span>

                        </div>


                        <div class="train-details">


                            <div class="train-time">

                                <strong>
                                    <?php echo htmlspecialchars($train["departure"]); ?>
                                </strong>

                                <span>
                                    Departure
                                </span>

                            </div>


                            <div class="train-duration">

                                <span>
                                    <?php echo htmlspecialchars($train["duration"]); ?>
                                </span>

                                <div class="duration-line"></div>

                            </div>


                            <div class="train-time">

                                <strong>
                                    <?php echo htmlspecialchars($train["arrival"]); ?>
                                </strong>

                                <span>
                                    Arrival
                                </span>

                            </div>

                        </div>


                        <div class="train-card-bottom">

                            <div>

                                <span class="muted">
                                    Starting from
                                </span>

                                <strong class="train-fare">
                                    <?php echo htmlspecialchars($train["fare"]); ?>
                                </strong>

                            </div>


                            <!-- Temporary -->
                            <button
                                type="button"
                                class="button button-primary"
                                onclick="alert('Train selection will be connected to the booking flow later.')"
                            >
                                Select Train
                            </button>

                        </div>


                    </article>

                <?php endforeach; ?>

            </section>


        <?php endif; ?>


    </main>


    <!-- =========================
         MOBILE NAV
    ========================== -->

    <nav class="bottom-nav">

        <a
            href="../user/home.php"
            class="bottom-nav-item"
        >
            <span class="bottom-nav-icon">⌂</span>
            <span>Home</span>
        </a>


        <a
            href="../user/manage_booking.php"
            class="bottom-nav-item"
        >
            <span class="bottom-nav-icon">🎫</span>
            <span>Bookings</span>
        </a>


        <a
            href="../user/profile.php"
            class="bottom-nav-item"
        >
            <span class="bottom-nav-icon">●</span>
            <span>Profile</span>
        </a>

    </nav>


    <script src="../assets/js/script.js"></script>

</body>

</html>