<?php
include "../includes/auth.php";
include "../includes/db.php";

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$account_id = intval($_GET['id'] ?? 0);

/* Verify ownership — admins can delete any account */
if ($role === 'Admin') {
    $check = mysqli_query($conn, "SELECT id, user_id FROM accounts WHERE id = $account_id");
} else {
    $check = mysqli_query($conn, "SELECT id, user_id FROM accounts WHERE id = $account_id AND user_id = $user_id");
}

if (mysqli_num_rows($check) == 0) {
    header("Location: view_accounts.php?error=notfound");
    exit;
}

$account = mysqli_fetch_assoc($check);
$owner_id = $account['user_id'];

/* Delete all transactions for this account */
mysqli_query($conn, "DELETE FROM transactions WHERE account_id = $account_id AND user_id = $owner_id");

/* Delete the account */
mysqli_query($conn, "DELETE FROM accounts WHERE id = $account_id");

header("Location: view_accounts.php?deleted=1");
exit;
?>