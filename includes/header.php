<!-- <------ SAYANTAN PAL ---- -- -->

<?php
/*
 * RailEase - Shared Header
 *
 * Usage:
 * require_once "../includes/header.php";
 *
 * Optional:
 * $pageTitle = "Home";
 */

if (!isset($pageTitle)) {
    $pageTitle = "RailEase";
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

    <meta
        name="description"
        content="RailEase - Railway Ticket Booking System"
    >

    <title>
        <?php echo htmlspecialchars($pageTitle); ?> - RailEase
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

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="shortcut icon" href="../assets/favicon/favicon.ico">

</head>

<body>