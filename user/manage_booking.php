<?php

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

$userId = (int) $_SESSION["user_id"];

function getBookings($conn, $userId, $condition)
{
    $sql = "
        SELECT
            b.booking_id,
            b.pnr,
            b.travel_date,
            b.total_amount,
            b.status,

            t.train_number,
            t.train_name,

            fs.station_name AS from_station,
            ts.station_name AS to_station,

            c.coach_number,
            c.coach_type,

            COALESCE(
                (
                    SELECT GROUP_CONCAT(
                        DISTINCT ps.seat_number
                        ORDER BY CAST(ps.seat_number AS UNSIGNED)
                        SEPARATOR ', '
                    )
                    FROM booking_passengers bp
                    INNER JOIN seats ps ON ps.seat_id = bp.seat_id
                    WHERE bp.booking_id = b.booking_id
                ),
                s.seat_number,
                '-'
            ) AS seat_numbers,

            COALESCE(
                NULLIF(
                    (
                        SELECT COUNT(*)
                        FROM booking_passengers bp
                        WHERE bp.booking_id = b.booking_id
                    ),
                    0
                ),
                1
            ) AS passenger_count

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

        WHERE b.user_id = ?
        AND $condition

        ORDER BY b.travel_date DESC
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $userId);

    $stmt->execute();

    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

$upcoming = getBookings(
    $conn,
    $userId,
    "b.status = 'CONFIRMED'
     AND b.travel_date >= CURDATE()"
);

$past = getBookings(
    $conn,
    $userId,
    "b.status = 'CONFIRMED'
     AND b.travel_date < CURDATE()"
);

$cancelled = getBookings(
    $conn,
    $userId,
    "b.status = 'CANCELLED'"
);

$notice = $_GET['cancelled'] ?? '';
$error = $_GET['error'] ?? '';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Booking - RailEase</title>

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

<?php require_once __DIR__ . "/../includes/navbar.php"; ?>

<main class="user-main">

    <section class="welcome-section">

        <p class="eyebrow">
            Your Trips
        </p>

        <h1>
            Manage Booking
        </h1>

        <p>
            View, print and cancel your railway bookings.
        </p>

    </section>


    <!-- UPCOMING BOOKINGS -->

    <section class="upcoming-section">

        <?php if ($notice === '1'): ?><div class="alert alert-success">Booking cancelled and refund added to your wallet.</div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="section-heading">

            <div>

                <p class="eyebrow">
                    Confirmed Trips
                </p>

                <h2>
                    Upcoming Bookings
                </h2>

            </div>

        </div>

        <?php if (!empty($upcoming)): ?>

            <?php foreach ($upcoming as $booking): ?>

                <div class="booking-card">

                    <h3>
                        <?php echo htmlspecialchars(
                            $booking["train_name"]
                        ); ?>
                    </h3>

                    <p>
                        Train No:
                        <?php echo htmlspecialchars(
                            $booking["train_number"]
                        ); ?>
                    </p>

                    <p>
                        <?php echo htmlspecialchars(
                            $booking["from_station"]
                        ); ?>

                        →

                        <?php echo htmlspecialchars(
                            $booking["to_station"]
                        ); ?>
                    </p>

                    <p>
                        Date:
                        <?php echo htmlspecialchars(
                            $booking["travel_date"]
                        ); ?>
                    </p>

                    <p>
                        Coach:
                        <?php echo htmlspecialchars(
                            $booking["coach_number"] ?? "-"
                        ); ?>
                    </p>

                    <p>
                        Seats:
                        <?php echo htmlspecialchars(
                            $booking["seat_numbers"]
                        ); ?>
                    </p>

                    <p>
                        Passengers:
                        <?php echo (int) $booking['passenger_count']; ?>
                    </p>

                    <p>
                        PNR:
                        <?php echo htmlspecialchars(
                            $booking["pnr"]
                        ); ?>
                    </p>

                    <p>
                        Amount:
                        ₹<?php echo number_format(
                            $booking["total_amount"],
                            2
                        ); ?>
                    </p>

                    <p>
                        Status:
                        <strong>
                            <?php echo htmlspecialchars(
                                $booking["status"]
                            ); ?>
                        </strong>
                    </p>

                    <a
                        href="view_ticket.php?id=<?php echo $booking["booking_id"]; ?>"
                    >
                        View Ticket
                    </a>

                    |

                    <a
                        href="print_ticket.php?id=<?php echo $booking["booking_id"]; ?>"
                        target="_blank"
                    >
                        Print Ticket
                    </a>

                    |

                    <form
                        action="cancel_booking.php"
                        method="POST"
                        style="display:inline;"
                        onsubmit="return confirm('Cancel this booking?');"
                    >

                        <input
                            type="hidden"
                            name="booking_id"
                            value="<?php echo $booking["booking_id"]; ?>"
                        >

                        <button type="submit">
                            Cancel Booking
                        </button>

                    </form>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>
                No upcoming bookings.
            </p>

        <?php endif; ?>

    </section>


    <!-- PAST BOOKINGS -->

    <section class="upcoming-section">

        <h2>
            Past Bookings
        </h2>

        <?php if (!empty($past)): ?>

            <?php foreach ($past as $booking): ?>

                <div class="booking-card">

                    <h3>
                        <?php echo htmlspecialchars(
                            $booking["train_name"]
                        ); ?>
                    </h3>

                    <p>
                        PNR:
                        <?php echo htmlspecialchars(
                            $booking["pnr"]
                        ); ?>
                    </p>

                    <p>
                        <?php echo htmlspecialchars(
                            $booking["from_station"]
                        ); ?>

                        →

                        <?php echo htmlspecialchars(
                            $booking["to_station"]
                        ); ?>
                    </p>

                    <p>
                        Date:
                        <?php echo htmlspecialchars(
                            $booking["travel_date"]
                        ); ?>
                    </p>

                    <p>
                        Amount:
                        ₹<?php echo number_format(
                            $booking["total_amount"],
                            2
                        ); ?>
                    </p>

                    <a
                        href="view_ticket.php?id=<?php echo $booking["booking_id"]; ?>"
                    >
                        View Ticket
                    </a>

                    |

                    <a
                        href="print_ticket.php?id=<?php echo $booking["booking_id"]; ?>"
                        target="_blank"
                    >
                        Print Ticket
                    </a>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>
                No past bookings.
            </p>

        <?php endif; ?>

    </section>


    <!-- CANCELLED BOOKINGS -->

    <section class="upcoming-section">

        <h2>
            Cancelled Bookings
        </h2>

        <?php if (!empty($cancelled)): ?>

            <?php foreach ($cancelled as $booking): ?>

                <div class="booking-card">

                    <h3>
                        <?php echo htmlspecialchars(
                            $booking["train_name"]
                        ); ?>
                    </h3>

                    <p>
                        PNR:
                        <?php echo htmlspecialchars(
                            $booking["pnr"]
                        ); ?>
                    </p>

                    <p>
                        <?php echo htmlspecialchars(
                            $booking["from_station"]
                        ); ?>

                        →

                        <?php echo htmlspecialchars(
                            $booking["to_station"]
                        ); ?>
                    </p>

                    <p>
                        Date:
                        <?php echo htmlspecialchars(
                            $booking["travel_date"]
                        ); ?>
                    </p>

                    <p>
                        Refund:
                        ₹<?php echo number_format(
                            $booking["total_amount"],
                            2
                        ); ?>
                    </p>

                    <strong>
                        CANCELLED
                    </strong>

                    <br><br>

                    <a
                        href="view_ticket.php?id=<?php echo $booking["booking_id"]; ?>"
                    >
                        View Ticket
                    </a>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>
                No cancelled bookings.
            </p>

        <?php endif; ?>

    </section>

</main>

</body>
</html>