<?php

require_once __DIR__ . '/admin_auth.php';
require_admin();

function admin_header(string $title, string $active = 'dashboard'): void
{
    $items = [
        'dashboard' => ['Dashboard', 'dashboard.php'],
        'trains' => ['Trains', 'trains.php'],
        'coaches' => ['Coaches', 'coaches.php'],
        'stations' => ['Stations', 'stations.php'],
        'meals' => ['Meals', 'meals.php'],
        'users' => ['Users', 'users.php'],
        'bookings' => ['Bookings', 'booking.php'],
        'support' => ['Support', 'support.php'],
    ];
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title) ?> | RailEase Admin</title>
        <link rel="stylesheet" href="../assets/css/style.css?v=2">
        <!-- Favicon -->
        <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
        <link rel="shortcut icon" href="../assets/favicon/favicon.ico">
    </head>
    <body class="admin-body">
        <div class="admin-layout">
            <aside class="admin-sidebar">
                <a class="brand brand-light" href="dashboard.php"><img class="brand-logo" src="../assets/blue_logo.png" alt="RailEase"></a>
                <p class="sidebar-label">Workspace</p>
                <nav class="admin-nav" aria-label="Admin navigation">
                    <?php foreach ($items as $key => [$label, $url]): ?>
                        <a class="<?= $active === $key ? 'active' : '' ?>" href="<?= $url ?>"><?= htmlspecialchars($label) ?></a>
                    <?php endforeach; ?>
                </nav>
                <div class="sidebar-footer">
                    <span class="admin-avatar"><?= strtoupper(substr(admin_user_name(), 0, 1)) ?></span>
                    <div><strong><?= htmlspecialchars(admin_user_name()) ?></strong><small>Administrator</small></div>
                    <a class="logout-link" href="../auth/logout.php" title="Log out">&#8594;</a>
                </div>
            </aside>
            <main class="admin-main">
                <header class="admin-topbar">
                    <div><p class="eyebrow">RailEase control room</p><h1><?= htmlspecialchars($title) ?></h1></div>
                    <span class="status-pill"><span></span> System online</span>
                </header>
    <?php
}

function admin_footer(): void
{
    ?>
            </main>
        </div>
    </body>
    </html>
    <?php
}