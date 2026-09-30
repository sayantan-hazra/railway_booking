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

<body>

<div class="auth-container">

    <div class="auth-card">

        <h1>Welcome Back</h1>

        <p>Login to your RailEase account</p>

        <?php if (isset($_GET["registered"])): ?>

            <div class="success-message">
                Registration successful. Please login.
            </div>

        <?php endif; ?>

        <?php if (!empty($message)): ?>

            <div class="error-message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <label>Username</label>

            <input
                type="text"
                name="username"
                required
            >

            <label>Password</label>

            <input
                type="password"
                name="password"
                required
            >

            <button type="submit">
                Login
            </button>

        </form>

        <p>
            Don't have an account?
            <a href="signup.php">Create Account</a>
        </p>

    </div>

</div>

</body>
</html>