<!-- saikat -->
 <?php

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

$userId = (int) $_SESSION["user_id"];

$bookingId = (int) ($_GET["id"] ?? 0);

$stmt = $conn->prepare(
    "SELECT
        b.*,

        t.train_number,
        t.train_name,

        fs.station_name AS from_station,
        ts.station_name AS to_station,

        c.coach_number,
        c.coach_type,

        s.seat_number

     FROM bookings b

     JOIN trains t
        ON b.train_id = t.train_id

     JOIN stations fs
        ON b.from_station_id = fs.station_id

     JOIN stations ts
        ON b.to_station_id = ts.station_id

     LEFT JOIN coaches c
        ON b.coach_id = c.coach_id

     LEFT JOIN seats s
        ON b.seat_id = s.seat_id

     WHERE b.booking_id = ?
     AND b.user_id = ?
     LIMIT 1"
);

$stmt->bind_param(
    "ii",
    $bookingId,
    $userId
);

$stmt->execute();

$booking = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

if (!$booking) {

    exit("Ticket not found.");
}

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <title>
        Print Ticket
    </title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #fff;
            padding: 30px;
        }

        .ticket {
            max-width: 750px;
            margin: auto;
            border: 2px solid #222;
            padding: 30px;
        }

        h1 {
            text-align: center;
        }

        .row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #ddd;
            padding: 12px 0;
        }

        .label {
            font-weight: bold;
        }

        .print-button {
            margin-top: 25px;
            padding: 10px 20px;
            cursor: pointer;
        }

        @media print {

            .print-button {
                display: none;
            }

        }

    </style>

</head>

<body>

<div class="ticket">

    <h1>🚆 RailEase</h1>

    <h2>
        <?php echo htmlspecialchars(
            $booking["train_name"]
        ); ?>
    </h2>

    <div class="row">

        <span class="label">
            Train Number
        </span>

        <span>
            <?php echo htmlspecialchars(
                $booking["train_number"]
            ); ?>
        </span>

    </div>

    <div class="row">

        <span class="label">
            PNR
        </span>

        <span>
            <?php echo htmlspecialchars(
                $booking["pnr"]
            ); ?>
        </span>

    </div>

    <div class="row">

        <span class="label">
            From
        </span>

        <span>
            <?php echo htmlspecialchars(
                $booking["from_station"]
            ); ?>
        </span>

    </div>

    <div class="row">

        <span class="label">
            To
        </span>

        <span>
            <?php echo htmlspecialchars(
                $booking["to_station"]
            ); ?>
        </span>

    </div>

    <div class="row">

        <span class="label">
            Journey Date
        </span>

        <span>
            <?php echo htmlspecialchars(
                $booking["travel_date"]
            ); ?>
        </span>

    </div>

    <div class="row">

        <span class="label">
            Coach
        </span>

        <span>
            <?php echo htmlspecialchars(
                $booking["coach_number"] ?? "-"
            ); ?>
        </span>

    </div>

    <div class="row">

        <span class="label">
            Seat
        </span>

        <span>
            <?php echo htmlspecialchars(
                $booking["seat_number"] ?? "-"
            ); ?>
        </span>

    </div>

    <div class="row">

        <span class="label">
            Class
        </span>

        <span>
            <?php echo htmlspecialchars(
                $booking["coach_type"] ?? "-"
            ); ?>
        </span>

    </div>

    <div class="row">

        <span class="label">
            Amount
        </span>

        <span>
            ₹<?php echo number_format(
                $booking["total_amount"],
                2
            ); ?>
        </span>

    </div>

    <div class="row">

        <span class="label">
            Status
        </span>

        <span>
            <?php echo htmlspecialchars(
                $booking["status"]
            ); ?>
        </span>

    </div>

    <button
        class="print-button"
        onclick="window.print()"
    >
        Print Ticket
    </button>

</div>

</body>

</html>