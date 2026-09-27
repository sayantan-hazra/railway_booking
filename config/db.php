<?php

$host = "localhost";
$dbname = "railway_booking";
$username = "root";
$password = ""; // Default XAMPP setup

// Create database connection
$conn = new mysqli(
    $host,
    $username,
    $password,
    $dbname
);

// Check connection
if ($conn->connect_error) {
    error_log($conn->connect_error);
    exit("Database connection failed.");
}

// Set character encoding
$conn->set_charset("utf8mb4");

?>