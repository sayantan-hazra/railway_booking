<?php

session_start();

require_once "../config/db.php";


if (empty($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");

    exit;

}


$userId = (int) $_SESSION['user_id'];

$action = $_POST['action'] ?? '';


// ==================================================
// UPDATE PROFILE
// ==================================================

if ($action === "update_profile") {


    $fullName =
        trim($_POST['full_name'] ?? '');


    $phone =
        trim($_POST['phone'] ?? '');


    if ($fullName === '') {

        die("Full name is required.");

    }


    $stmt = $conn->prepare("
        UPDATE users
        SET full_name = ?, phone = ?
        WHERE user_id = ?
    ");


    $stmt->bind_param(
        "ssi",
        $fullName,
        $phone,
        $userId
    );


    $stmt->execute();

    $stmt->close();


    header(
        "Location: profile.php"
    );

    exit;

}


// ==================================================
// CHANGE PASSWORD
// ==================================================

if ($action === "change_password") {


    $currentPassword =
        $_POST['current_password'] ?? '';


    $newPassword =
        $_POST['new_password'] ?? '';


    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    if (
        $newPassword !==
        $confirmPassword
    ) {

        die("New passwords do not match.");

    }


    if (
        strlen($newPassword) < 6
    ) {

        die(
            "Password must contain at least 6 characters."
        );

    }


    // Get current password

    $stmt = $conn->prepare("
        SELECT password_hash
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");


    $stmt->bind_param(
        "i",
        $userId
    );


    $stmt->execute();

    $result =
        $stmt->get_result();

    $user =
        $result->fetch_assoc();

    $stmt->close();


    if (
        !$user ||
        !password_verify(
            $currentPassword,
            $user['password_hash']
        )
    ) {

        die(
            "Current password is incorrect."
        );

    }


    // Hash new password

    $newPasswordHash =
        password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );


    $stmt = $conn->prepare("
        UPDATE users
        SET password_hash = ?
        WHERE user_id = ?
    ");


    $stmt->bind_param(
        "si",
        $newPasswordHash,
        $userId
    );


    $stmt->execute();

    $stmt->close();


    header(
        "Location: profile.php"
    );

    exit;

}


header(
    "Location: profile.php"
);

exit;