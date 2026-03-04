<?php
include "../includes/auth.php";
include "../includes/db.php";
$page_title = "Add Account";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $account_name = mysqli_real_escape_string($conn, trim($_POST['account_name']));
    $account_type = $_POST['account_type'];
    $opening_balance = $_POST['opening_balance'];

    if (!empty($account_name) && is_numeric($opening_balance)) {
        mysqli_query($conn, "INSERT INTO accounts (user_id, account_name, account_type, opening_balance)
        VALUES ($user_id, '$account_name', '$account_type', '$opening_balance')");
        $success_msg = "Account Added Successfully!";
    }
}
?>

<div class="container">
    <h2>Add New Account</h2>

    <?php if ($success_msg) { ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php } ?>

    <form method="POST">
        <label>Account Name</label>
        <input type="text" name="account_name" placeholder="E.g. Main Checking, Savings..." required>

        <label>Account Type</label>
        <select name="account_type">
            <option value="Bank">Bank</option>
            <option value="Cash">Cash</option>
            <option value="Mobile Wallet">Mobile Wallet</option>
            <option value="Credit Card">Credit Card</option>
        </select>

        <label>Opening Balance (₹)</label>
        <input type="number" step="0.01" name="opening_balance" placeholder="0.00" required>

        <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 8px;">+ Add Account</button>
    </form>
</div>

<?php include "../includes/footer.php"; ?>