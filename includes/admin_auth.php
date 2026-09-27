<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function require_admin(): void
{
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'ADMIN') {
        header('Location: login.php');
        exit;
    }
}

function admin_user_name(): string
{
    return $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator';
}

function admin_count(mysqli $conn, string $table): int
{
    $allowed_tables = ['users', 'trains', 'bookings', 'support_tickets'];

    if (!in_array($table, $allowed_tables, true)) {
        return 0;
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
    return $result ? (int) $result->fetch_assoc()['total'] : 0;
}

function admin_query(mysqli $conn, string $query): ?mysqli_result
{
    $result = $conn->query($query);
    return $result ?: null;
}
