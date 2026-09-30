<?php

session_start();

require_once "../config/db.php";

$pageTitle = "My Profile";


// --------------------------------------------------
// CHECK LOGIN
// --------------------------------------------------

if (empty($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}


$userId = (int) $_SESSION['user_id'];


// --------------------------------------------------
// GET USER INFORMATION
// --------------------------------------------------

$stmt = $conn->prepare("
    SELECT
        user_id,
        full_name,
        username,
        email,
        phone,
        created_at
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();


if (!$user) {
    die("User not found.");
}


// --------------------------------------------------
// GET WALLET BALANCE
// --------------------------------------------------

$walletBalance = 0;

$stmt = $conn->prepare("
    SELECT balance
    FROM wallets
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);

$stmt->execute();

$result = $stmt->get_result();

$wallet = $result->fetch_assoc();

if ($wallet) {
    $walletBalance = $wallet['balance'];
}

$stmt->close();

?>

<?php require_once "../includes/header.php"; ?>

<?php require_once "../includes/navbar.php"; ?>


<main class="profile-page">

    <div class="profile-container">


        <!-- PAGE HEADER -->

        <div class="profile-page-heading">

            <p class="profile-eyebrow">
                ACCOUNT
            </p>

            <h1>
                My Profile
            </h1>

            <p>
                Manage your RailEase account information.
            </p>

        </div>


        <!-- PROFILE CARD -->

        <section class="profile-main-card">


            <!-- PROFILE TOP -->

            <div class="profile-user-header">

                <div class="profile-avatar">

                    <?php
                    echo strtoupper(
                        substr($user['full_name'], 0, 1)
                    );
                    ?>

                </div>


                <div>

                    <h2>
                        <?php
                        echo htmlspecialchars(
                            $user['full_name']
                        );
                        ?>
                    </h2>

                    <p>
                        @<?php
                        echo htmlspecialchars(
                            $user['username']
                        );
                        ?>
                    </p>

                </div>

            </div>


            <!-- USER INFORMATION -->

            <div class="profile-information">


                <div class="profile-info-item">

                    <span>
                        Full Name
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $user['full_name']
                        );
                        ?>
                    </strong>

                </div>


                <div class="profile-info-item">

                    <span>
                        Username
                    </span>

                    <strong>
                        @<?php
                        echo htmlspecialchars(
                            $user['username']
                        );
                        ?>
                    </strong>

                </div>


                <div class="profile-info-item">

                    <span>
                        Email
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $user['email']
                        );
                        ?>
                    </strong>

                </div>


                <div class="profile-info-item">

                    <span>
                        Phone Number
                    </span>

                    <strong>

                        <?php

                        echo !empty($user['phone'])
                            ? htmlspecialchars($user['phone'])
                            : "Not added";

                        ?>

                    </strong>

                </div>


                <div class="profile-info-item">

                    <span>
                        Account Created
                    </span>

                    <strong>

                        <?php

                        echo date(
                            "d M Y",
                            strtotime($user['created_at'])
                        );

                        ?>

                    </strong>

                </div>


                <div class="profile-info-item wallet-info">

                    <span>
                        Wallet Balance
                    </span>

                    <strong>

                        ₹<?php
                        echo number_format(
                            (float)$walletBalance,
                            2
                        );
                        ?>

                    </strong>

                </div>


            </div>


            <!-- PROFILE BUTTONS -->

            <div class="profile-buttons">

                <button
                    type="button"
                    class="profile-primary-btn"
                    onclick="openEditProfile()"
                >
                    Edit Profile
                </button>


                <button
                    type="button"
                    class="profile-secondary-btn"
                    onclick="openChangePassword()"
                >
                    Change Password
                </button>


                <a
                    href="manage_booking.php"
                    class="profile-secondary-btn"
                >
                    My Bookings
                </a>


                <a
                    href="../auth/logout.php"
                    class="profile-danger-btn"
                >
                    Logout
                </a>

            </div>


        </section>


        <!-- WALLET QUICK CARD -->

        <section class="profile-wallet-card">

            <div>

                <p>
                    Current Wallet Balance
                </p>

                <h2>

                    ₹<?php
                    echo number_format(
                        (float)$walletBalance,
                        2
                    );
                    ?>

                </h2>

            </div>


            <a href="wallet.php">
                Open Wallet →
            </a>

        </section>


    </div>

</main>


<!-- EDIT PROFILE MODAL -->

<div
    id="editProfileModal"
    class="profile-modal"
>

    <div class="profile-modal-box">

        <div class="modal-header">

            <h2>
                Edit Profile
            </h2>

            <button
                type="button"
                onclick="closeEditProfile()"
            >
                ×
            </button>

        </div>


        <form
            action="profile_action.php"
            method="POST"
        >

            <input
                type="hidden"
                name="action"
                value="update_profile"
            >


            <label>
                Full Name
            </label>

            <input
                type="text"
                name="full_name"
                value="<?php
                    echo htmlspecialchars(
                        $user['full_name']
                    );
                ?>"
                required
            >


            <label>
                Phone Number
            </label>

            <input
                type="text"
                name="phone"
                value="<?php
                    echo htmlspecialchars(
                        $user['phone'] ?? ''
                    );
                ?>"
            >


            <button
                type="submit"
                class="profile-primary-btn"
            >
                Save Changes
            </button>

        </form>

    </div>

</div>


<!-- CHANGE PASSWORD MODAL -->

<div
    id="changePasswordModal"
    class="profile-modal"
>

    <div class="profile-modal-box">

        <div class="modal-header">

            <h2>
                Change Password
            </h2>

            <button
                type="button"
                onclick="closeChangePassword()"
            >
                ×
            </button>

        </div>


        <form
            action="profile_action.php"
            method="POST"
        >

            <input
                type="hidden"
                name="action"
                value="change_password"
            >


            <label>
                Current Password
            </label>

            <input
                type="password"
                name="current_password"
                required
            >


            <label>
                New Password
            </label>

            <input
                type="password"
                name="new_password"
                required
            >


            <label>
                Confirm New Password
            </label>

            <input
                type="password"
                name="confirm_password"
                required
            >


            <button
                type="submit"
                class="profile-primary-btn"
            >
                Change Password
            </button>

        </form>

    </div>

</div>


<script>

function openEditProfile() {

    document.getElementById(
        "editProfileModal"
    ).style.display = "flex";

}


function closeEditProfile() {

    document.getElementById(
        "editProfileModal"
    ).style.display = "none";

}


function openChangePassword() {

    document.getElementById(
        "changePasswordModal"
    ).style.display = "flex";

}


function closeChangePassword() {

    document.getElementById(
        "changePasswordModal"
    ).style.display = "none";

}

</script>


<?php require_once "../includes/footer.php"; ?>