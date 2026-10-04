<!-- saikat -->
 <?php
session_start();

require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION["user_id"])) {
    header("Location: ../user/home.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {

        $message = "Username and password are required.";

    } else {

        $stmt = $conn->prepare(
            "SELECT
                user_id,
                full_name,
                username,
                email,
                phone,
                password_hash,
                role
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);

        $stmt->execute();

        $result = $stmt->get_result();

        $user = $result->fetch_assoc();

        $stmt->close();

        if ($user && password_verify(
            $password,
            $user["password_hash"]
        )) {

            // Prevent session fixation
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["phone"] = $user["phone"];
            $_SESSION["role"] = $user["role"];

            header("Location: ../user/home.php");
            exit;

        } else {

            $message = "Invalid username or password.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login - RailEase</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

</head>

<body class="auth-page">

<main class="auth-card">

        <a class="brand" href="../index.php">
            <img class="brand-logo" src="../assets/logo.png" alt="RailEase">
        </a>

        <p class="eyebrow">Passenger account</p>
        <h1>Welcome back</h1>
        <p class="muted">Login to your RailEase account.</p>

        <?php if (isset($_GET["registered"])): ?>

            <div class="alert alert-success">
                Registration successful. Please login.
            </div>

        <?php endif; ?>

        <?php if (!empty($message)): ?>

            <div class="alert alert-error">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST" class="stack-form">

            <label for="username">Username</label>

            <input
                id="username"
                type="text"
                name="username"
                autocomplete="username"
                required
            >

            <label for="password">Password</label>

            <input
                id="password"
                type="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button class="button button-primary" type="submit">
                Login
            </button>

        </form>

        <p>
            Don't have an account?
            <a class="back-link" href="signup.php">Create account</a>
        </p>

</main>

</body>
</html>