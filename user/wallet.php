<?php

session_start();

require_once "../config/db.php";

$pageTitle = "My Wallet";


if (empty($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");

    exit;

}


$userId =
    (int) $_SESSION['user_id'];


// ==================================================
// CREATE WALLET IF USER DOES NOT HAVE ONE
// ==================================================

$stmt = $conn->prepare("
    INSERT IGNORE INTO wallets
    (user_id, balance)
    VALUES (?, 0)
");

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$stmt->close();


// ==================================================
// GET WALLET
// ==================================================

$stmt = $conn->prepare("
    SELECT wallet_id, balance
    FROM wallets
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$result =
    $stmt->get_result();

$wallet =
    $result->fetch_assoc();

$stmt->close();


$walletId =
    (int) $wallet['wallet_id'];

$balance =
    (float) $wallet['balance'];


// ==================================================
// GET TRANSACTIONS
// ==================================================

$stmt = $conn->prepare("
    SELECT
        transaction_type,
        amount,
        description,
        created_at
    FROM wallet_transactions
    WHERE wallet_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param(
    "i",
    $walletId
);

$stmt->execute();

$transactions =
    $stmt->get_result();

?>


<?php require_once "../includes/header.php"; ?>

<?php require_once "../includes/navbar.php"; ?>


<main class="wallet-page">

    <div class="wallet-container">


        <!-- PAGE HEADER -->

        <div class="wallet-page-heading">

            <p class="wallet-eyebrow">
                MY ACCOUNT
            </p>

            <h1>
                My Wallet
            </h1>

            <p>
                Add money and manage your RailEase wallet.
            </p>

        </div>


        <!-- BALANCE CARD -->

        <section class="wallet-balance-card">

            <div>

                <span>
                    Current Wallet Balance
                </span>

                <h2>

                    ₹<?php
                    echo number_format(
                        $balance,
                        2
                    );
                    ?>

                </h2>

            </div>


            <div class="wallet-balance-icon">
                ₹
            </div>

        </section>


        <!-- ADD MONEY -->

        <section class="wallet-card">

            <div class="wallet-card-heading">

                <div>

                    <h2>
                        Add Money
                    </h2>

                    <p>
                        Select an amount to add to your demo wallet.
                    </p>

                </div>

            </div>


            <form
                action="wallet_action.php"
                method="POST"
                class="wallet-amount-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="add_money"
                >


                <button
                    type="submit"
                    name="amount"
                    value="100"
                    class="wallet-amount-btn"
                >
                    + ₹100
                </button>


                <button
                    type="submit"
                    name="amount"
                    value="500"
                    class="wallet-amount-btn"
                >
                    + ₹500
                </button>


                <button
                    type="submit"
                    name="amount"
                    value="1000"
                    class="wallet-amount-btn"
                >
                    + ₹1,000
                </button>

            </form>


            <!-- CUSTOM AMOUNT -->

            <form
                action="wallet_action.php"
                method="POST"
                class="wallet-custom-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="add_money"
                >


                <input
                    type="number"
                    name="amount"
                    placeholder="Enter custom amount"
                    min="1"
                    step="1"
                    required
                >


                <button
                    type="submit"
                    class="wallet-add-btn"
                >
                    Add Money
                </button>

            </form>

        </section>


        <!-- TRANSACTION HISTORY -->

        <section class="wallet-card">

            <div class="wallet-card-heading">

                <div>

                    <h2>
                        Transaction History
                    </h2>

                    <p>
                        Your recent wallet transactions.
                    </p>

                </div>

            </div>


            <div class="wallet-table-wrapper">

                <table class="wallet-table">

                    <thead>

                        <tr>

                            <th>
                                Date
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Description
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    if (
                        $transactions->num_rows === 0
                    ):

                    ?>

                        <tr>

                            <td
                                colspan="4"
                                class="wallet-empty"
                            >

                                No transactions yet.

                            </td>

                        </tr>

                    <?php

                    else:

                        while (
                            $transaction =
                            $transactions->fetch_assoc()
                        ):

                            $isCredit =
                                $transaction['transaction_type']
                                === 'CREDIT';

                    ?>

                        <tr>

                            <td>

                                <?php

                                echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $transaction['created_at']
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <span
                                    class="<?php
                                    echo $isCredit
                                        ? 'wallet-credit'
                                        : 'wallet-debit';
                                    ?>"
                                >

                                    <?php
                                    echo $isCredit
                                        ? 'Credit'
                                        : 'Debit';
                                    ?>

                                </span>

                            </td>


                            <td>

                                <strong
                                    class="<?php
                                    echo $isCredit
                                        ? 'wallet-credit'
                                        : 'wallet-debit';
                                    ?>"
                                >

                                    <?php
                                    echo $isCredit
                                        ? '+'
                                        : '-';
                                    ?>

                                    ₹<?php
                                    echo number_format(
                                        $transaction['amount'],
                                        2
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $transaction['description']
                                );
                                ?>

                            </td>

                        </tr>

                    <?php

                        endwhile;

                    endif;

                    ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- WALLET INFORMATION -->

        <section class="wallet-info-box">

            <strong>
                Demo Wallet
            </strong>

            <p>
                This project uses a demo wallet instead of a real payment gateway.
                Wallet credits and debits are stored in the project database.
            </p>

        </section>


    </div>

</main>


<?php require_once "../includes/footer.php"; ?>