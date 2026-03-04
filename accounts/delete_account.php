<?php
include "../includes/auth.php";
include "../includes/db.php";

$user_id = $_SESSION['user_id'];
$account_id = intval($_GET['id'] ?? 0);

/* Verify ownership */
$check = mysqli_query($conn, "SELECT id FROM accounts WHERE id = $account_id AND user_id = $user_id");
if (mysqli_num_rows($check) == 0) {
    header("Location: view_accounts.php");
    exit;
}

/* Delete all transactions for this account */
mysqli_query($conn, "DELETE FROM transactions WHERE account_id = $account_id AND user_id = $user_id");

/* Delete the account */
mysqli_query($conn, "DELETE FROM accounts WHERE id = $account_id AND user_id = $user_id");

header("Location: view_accounts.php?deleted=1");
exit;
?>