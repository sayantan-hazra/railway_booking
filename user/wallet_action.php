<?php

session_start();

require_once "../config/db.php";


if (empty($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");

    exit;

}


$userId =
    (int) $_SESSION['user_id'];

$action =
    $_POST['action'] ?? '';


// ==================================================
// ADD MONEY
// ==================================================

if ($action === "add_money") {


    $amount =
        (float) ($_POST['amount'] ?? 0);


    if ($amount <= 0) {

        die("Invalid amount.");

    }


    /*
     * Create wallet if it does not exist.
     */

    $stmt = $conn->prepare("
        INSERT IGNORE INTO wallets
        (user_id, balance)
        VALUES (?, 0)
    ");

    $stmt->bind_param(
        "i",
        $userId
    );

    $stmt->execute();

    $stmt->close();


    /*
     * Start database transaction.
     */

    $conn->begin_transaction();


    try {


        // Get wallet

        $stmt = $conn->prepare("
            SELECT wallet_id, balance
            FROM wallets
            WHERE user_id = ?
            FOR UPDATE
        ");


        $stmt->bind_param(
            "i",
            $userId
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        $wallet =
            $result->fetch_assoc();


        $stmt->close();


        if (!$wallet) {

            throw new Exception(
                "Wallet not found."
            );

        }


        $walletId =
            (int) $wallet['wallet_id'];


        // Update balance

        $stmt = $conn->prepare("
            UPDATE wallets
            SET balance = balance + ?
            WHERE wallet_id = ?
        ");


        $stmt->bind_param(
            "di",
            $amount,
            $walletId
        );


        $stmt->execute();

        $stmt->close();


        // Create transaction

        $description =
            "Wallet Top-up";


        $transactionType =
            "CREDIT";


        $stmt = $conn->prepare("
            INSERT INTO wallet_transactions
            (
                wallet_id,
                transaction_type,
                amount,
                description
            )
            VALUES (?, ?, ?, ?)
        ");


        $stmt->bind_param(
            "isds",
            $walletId,
            $transactionType,
            $amount,
            $description
        );


        $stmt->execute();

        $stmt->close();


        /*
         * Everything successful.
         */

        $conn->commit();


        header(
            "Location: wallet.php"
        );

        exit;


    } catch (Exception $e) {


        $conn->rollback();


        die(
            "Wallet error: " .
            htmlspecialchars(
                $e->getMessage()
            )
        );

    }

}


// ==================================================
// INVALID ACTION
// ==================================================

header(
    "Location: wallet.php"
);

exit;