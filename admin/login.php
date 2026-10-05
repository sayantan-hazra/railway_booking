<?php

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'ADMIN') {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identity === '' || $password === '') {
        $error = 'Enter your username or email and password.';
    } else {
        $statement = $conn->prepare(
            'SELECT user_id, full_name, username, password_hash, role
             FROM users WHERE (username = ? OR email = ?) AND role = \'ADMIN\' LIMIT 1'
        );
        $statement->bind_param('ss', $identity, $identity);
        $statement->execute();
        $admin = $statement->get_result()->fetch_assoc();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $admin['user_id'];
            $_SESSION['full_name'] = $admin['full_name'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = $admin['role'];
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Invalid administrator credentials.';
    }
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | RailEase</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="brand" href="../index.php"><img class="brand-logo" src="../assets/white_logo.png" alt="RailEase"></a>
        <p class="eyebrow">Administration</p>
        <h1>Welcome back</h1>
        <p class="muted">Sign in to manage the railway booking system.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="stack-form">
            <label for="identity">Username or email</label>
            <input id="identity" name="identity" type="text" autocomplete="username" required>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="button button-primary" type="submit">Sign in to admin panel</button>
        </form>
        <a class="back-link" href="../index.php">Back to RailEase</a>
    </main>
</body>
</html>