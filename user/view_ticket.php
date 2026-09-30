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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Ticket - <?php echo htmlspecialchars($booking["pnr"]); ?>
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<main class="user-main">

    <div class="booking-card">

        <h1>
            🚆 RailEase Ticket
        </h1>

        <hr>

        <h2>
            <?php echo htmlspecialchars(
                $booking["train_name"]
            ); ?>
        </h2>

        <p>
            Train No:
            <?php echo htmlspecialchars(
                $booking["train_number"]
            ); ?>
        </p>

        <p>
            <strong>PNR:</strong>
            <?php echo htmlspecialchars(
                $booking["pnr"]
            ); ?>
        </p>

        <p>
            <strong>From:</strong>
            <?php echo htmlspecialchars(
                $booking["from_station"]
            ); ?>
        </p>

        <p>
            <strong>To:</strong>
            <?php echo htmlspecialchars(
                $booking["to_station"]
            ); ?>
        </p>

        <p>
            <strong>Date:</strong>
            <?php echo htmlspecialchars(
                $booking["travel_date"]
            ); ?>
        </p>

        <p>
            <strong>Coach:</strong>
            <?php echo htmlspecialchars(
                $booking["coach_number"] ?? "-"
            ); ?>
        </p>

        <p>
            <strong>Class:</strong>
            <?php echo htmlspecialchars(
                $booking["coach_type"] ?? "-"
            ); ?>
        </p>

        <p>
            <strong>Seat:</strong>
            <?php echo htmlspecialchars(
                $booking["seat_number"] ?? "-"
            ); ?>
        </p>

        <p>
            <strong>Amount:</strong>
            ₹<?php echo number_format(
                $booking["total_amount"],
                2
            ); ?>
        </p>

        <p>
            <strong>Status:</strong>

            <?php echo htmlspecialchars(
                $booking["status"]
            ); ?>

        </p>

        <br>

        <a
            href="print_ticket.php?id=<?php echo $bookingId; ?>"
            target="_blank"
        >
            Print Ticket
        </a>

        |

        <a href="manage_booking.php">
            Back
        </a>

    </div>

</main>

</body>

</html>