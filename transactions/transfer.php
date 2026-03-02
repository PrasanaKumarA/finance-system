<?php
include "../includes/auth.php";
include "../includes/db.php";

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $from_account = $_POST['from_account'];
    $to_account = $_POST['to_account'];
    $amount = $_POST['amount'];
    $date = $_POST['transaction_date'];

    if ($from_account == $to_account) {
        echo "Cannot transfer to same account";
    } else {

        // Deduct from source
        mysqli_query($conn, "INSERT INTO transactions 
        (user_id, account_id, type, amount, transaction_date, description)
        VALUES ($user_id, $from_account, 'Expense', '$amount', '$date', 'Transfer Out')");

        // Add to destination
        mysqli_query($conn, "INSERT INTO transactions 
        (user_id, account_id, type, amount, transaction_date, description)
        VALUES ($user_id, $to_account, 'Income', '$amount', '$date', 'Transfer In')");

        echo "Transfer Successful";
    }
}

$accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
?>

<h2>Transfer Money</h2>

<form method="POST">

    <label>From Account:</label><br>
    <select name="from_account">
        <?php while ($row = mysqli_fetch_assoc($accounts)) { ?>
            <option value="<?php echo $row['id']; ?>">
                <?php echo $row['account_name']; ?>
            </option>
        <?php } ?>
    </select><br><br>

    <?php
    // Reload accounts again because previous loop consumed result
    $accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
    ?>

    <label>To Account:</label><br>
    <select name="to_account">
        <?php while ($row = mysqli_fetch_assoc($accounts)) { ?>
            <option value="<?php echo $row['id']; ?>">
                <?php echo $row['account_name']; ?>
            </option>
        <?php } ?>
    </select><br><br>

    <label>Amount:</label><br>
    <input type="number" name="amount" required><br><br>

    <label>Date:</label><br>
    <input type="date" name="transaction_date" required><br><br>

    <button type="submit">Transfer</button>

</form>