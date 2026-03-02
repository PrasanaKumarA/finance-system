<?php
include "../includes/auth.php";
include "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION['user_id'];
    $account_name = $_POST['account_name'];
    $account_type = $_POST['account_type'];
    $opening_balance = $_POST['opening_balance'];

    mysqli_query($conn, "INSERT INTO accounts (user_id, account_name, account_type, opening_balance)
    VALUES ($user_id, '$account_name', '$account_type', '$opening_balance')");

    echo "Account Added Successfully";
}
?>

<h2>Add Account</h2>
<form method="POST">
    <input type="text" name="account_name" placeholder="Account Name" required><br><br>

    <select name="account_type">
        <option value="Bank">Bank</option>
        <option value="Cash">Cash</option>
    </select><br><br>

    <input type="number" name="opening_balance" placeholder="Opening Balance" required><br><br>

    <button type="submit">Add Account</button>
</form>