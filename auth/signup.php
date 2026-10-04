<!-- saikat -->

<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../user/home.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullName = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (
        empty($fullName) ||
        empty($username) ||
        empty($email) ||
        empty($phone) ||
        empty($password) ||
        empty($confirmPassword)
    ) {
        $message = "All fields are required.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Enter a valid email address.";
    }
    elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $message = "Phone number must contain 10 digits.";
    }
    elseif (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
    }
    elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    }
    else {

        // Check username and email
        $check = $conn->prepare(
            "SELECT user_id FROM users
             WHERE username = ? OR email = ?
             LIMIT 1"
        );

        $check->bind_param("ss", $username, $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Username or email already exists.";

        } else {

            // Hash password
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users
                (full_name, username, email, phone, password_hash)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssss",
                $fullName,
                $username,
                $email,
                $phone,
                $passwordHash
            );

            if ($stmt->execute()) {

                $userId = $conn->insert_id;

                // Create wallet for user
                $wallet = $conn->prepare(
                    "INSERT INTO wallets (user_id, balance)
                     VALUES (?, 0)"
                );

                $wallet->bind_param("i", $userId);
                $wallet->execute();
                $wallet->close();

                header("Location: login.php?registered=1");
                exit;

            } else {
                $message = "Registration failed.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Signup - RailEase</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

</head>

<body class="auth-page">

<main class="auth-card">

        <a class="brand" href="../index.php">
            <img class="brand-logo" src="../assets/logo.png" alt="RailEase">
        </a>

        <p class="eyebrow">Passenger account</p>
        <h1>Create account</h1>
        <p class="muted">Register your RailEase account.</p>

        <?php if (!empty($message)): ?>

            <div class="alert alert-error">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST" class="stack-form">

            <label for="full_name">Full name</label>

            <input
                id="full_name"
                type="text"
                name="full_name"
                required
            >

            <label for="username">Username</label>

            <input
                id="username"
                type="text"
                name="username"
                autocomplete="username"
                required
            >

            <label for="email">Email</label>

            <input
                id="email"
                type="email"
                name="email"
                autocomplete="email"
                required
            >

            <label for="phone">Phone number</label>

            <input
                id="phone"
                type="tel"
                name="phone"
                pattern="[0-9]{10}"
                maxlength="10"
                autocomplete="tel"
                required
            >

            <label for="password">Password</label>

            <input
                id="password"
                type="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <label for="confirm_password">Confirm password</label>

            <input
                id="confirm_password"
                type="password"
                name="confirm_password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <button class="button button-primary" type="submit">
                Create Account
            </button>

        </form>

        <p>
            Already have an account?
            <a class="back-link" href="login.php">Login</a>
        </p>

</main>

</body>
</html>