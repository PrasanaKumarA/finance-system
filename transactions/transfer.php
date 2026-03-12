<?php
include "../includes/auth.php";
include "../includes/db.php";
$page_title = "Transfer Money";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $from_account = $_POST['from_account'];
    $to_account = $_POST['to_account'];
    $amount = $_POST['amount'];
    $date = $_POST['transaction_date'];

    if ($from_account == $to_account) {
        $error_msg = "Cannot transfer to same account";
    } else {

        // Deduct from source
        mysqli_query($conn, "INSERT INTO transactions 
        (user_id, account_id, type, amount, transaction_date, description)
        VALUES ($user_id, $from_account, 'Transfer', '$amount', '$date', 'Transfer Out')");

        // Add to destination
        mysqli_query($conn, "INSERT INTO transactions 
        (user_id, account_id, type, amount, transaction_date, description)
        VALUES ($user_id, $to_account, 'Transfer', '$amount', '$date', 'Transfer In')");

        $success_msg = "Transfer Successful!";
    }
}

$accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
?>

<div class="container">
    <h2>Transfer Money</h2>

    <?php if ($error_msg) { ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php } ?>
    <?php if ($success_msg) { ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php } ?>

    <form method="POST">

        <label>From Account</label>
        <select name="from_account" required>
            <?php while ($row = mysqli_fetch_assoc($accounts)) { ?>
                <option value="<?php echo $row['id']; ?>">
                    <?php echo $row['account_name']; ?>
                </option>
            <?php } ?>
        </select>

        <?php
        // Reload accounts again because previous loop consumed result
        $accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
        ?>

        <label>To Account</label>
        <select name="to_account" required>
            <?php while ($row = mysqli_fetch_assoc($accounts)) { ?>
                <option value="<?php echo $row['id']; ?>">
                    <?php echo $row['account_name']; ?>
                </option>
            <?php } ?>
        </select>

        <label>Amount (₹)</label>
        <input type="number" step="0.01" name="amount" placeholder="0.00" required>

        <label>Date</label>
        <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>

        <button type="submit" class="btn" style="width: 100%; margin-top: 8px;">Transfer</button>

    </form>
</div>

<?php include "../includes/footer.php"; ?>