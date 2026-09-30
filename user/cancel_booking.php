<!-- saikat -->
 <?php

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: manage_booking.php");

    exit;
}

$userId = (int) $_SESSION["user_id"];

$bookingId = (int) ($_POST["booking_id"] ?? 0);

if ($bookingId <= 0) {

    header(
        "Location: manage_booking.php?error=Invalid booking"
    );

    exit;
}

$conn->begin_transaction();

try {

    // Get booking
    $stmt = $conn->prepare(
        "SELECT booking_id, total_amount, status
         FROM bookings
         WHERE booking_id = ?
         AND user_id = ?
         FOR UPDATE"
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

        throw new Exception(
            "Booking not found."
        );
    }


    if ($booking["status"] !== "CONFIRMED") {

        throw new Exception(
            "Booking is already cancelled."
        );
    }


    $refundAmount = (float) $booking["total_amount"];


    // Cancel booking
    $update = $conn->prepare(
        "UPDATE bookings
         SET status = 'CANCELLED'
         WHERE booking_id = ?
         AND user_id = ?
         AND status = 'CONFIRMED'"
    );

    $update->bind_param(
        "ii",
        $bookingId,
        $userId
    );

    $update->execute();

    $update->close();


    // Make sure wallet exists
    $walletCreate = $conn->prepare(
        "INSERT IGNORE INTO wallets
         (user_id, balance)
         VALUES (?, 0)"
    );

    $walletCreate->bind_param(
        "i",
        $userId
    );

    $walletCreate->execute();

    $walletCreate->close();


    // Refund
    $wallet = $conn->prepare(
        "UPDATE wallets
         SET balance = balance + ?
         WHERE user_id = ?"
    );

    $wallet->bind_param(
        "di",
        $refundAmount,
        $userId
    );

    $wallet->execute();

    $wallet->close();


    // Record transaction
    $description =
        "Refund for cancelled booking #" .
        $bookingId;

    $transaction = $conn->prepare(
        "INSERT INTO wallet_transactions
        (
            wallet_id,
            transaction_type,
            amount,
            description,
            booking_id
        )

        SELECT
            wallet_id,
            'CREDIT',
            ?,
            ?,
            ?
        FROM wallets
        WHERE user_id = ?"
    );

    $transaction->bind_param(
        "dsii",
        $refundAmount,
        $description,
        $bookingId,
        $userId
    );

    $transaction->execute();

    $transaction->close();


    $conn->commit();

    header(
        "Location: manage_booking.php?cancelled=1"
    );

    exit;

} catch (Exception $e) {

    $conn->rollback();

    header(
        "Location: manage_booking.php?error=" .
        urlencode($e->getMessage())
    );

    exit;
}