<?php

require_once "db.php";

echo "<h2>Railway Booking - Database Test</h2>";

// Check database connection
if ($conn->ping()) {
    echo "<p style='color: green;'>
            ✅ Database connection successful!
          </p>";
} else {
    echo "<p style='color: red;'>
            ❌ Database connection failed!
          </p>";
}

// Display database name
$result = $conn->query("SELECT DATABASE() AS db_name");

if ($result) {
    $row = $result->fetch_assoc();

    echo "<p>Connected Database: <b>"
        . htmlspecialchars($row['db_name'])
        . "</b></p>";
}

// Check MySQL server version
$result = $conn->query("SELECT VERSION() AS version");

if ($result) {
    $row = $result->fetch_assoc();

    echo "<p>MySQL Version: <b>"
        . htmlspecialchars($row['version'])
        . "</b></p>";
}

$conn->close();

?>