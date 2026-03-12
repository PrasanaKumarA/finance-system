<?php
include "../includes/auth.php";
include "../includes/db.php";

$user_id = $_SESSION['user_id'];
$transaction_id = intval($_GET['id'] ?? 0);

if ($transaction_id <= 0) {
    header("Location: view_transactions.php");
    exit;
}

// Verify ownership
$check = mysqli_query($conn, "
    SELECT id FROM transactions 
    WHERE id = $transaction_id AND user_id = $user_id
    LIMIT 1
");

if (mysqli_num_rows($check) === 0) {
    $_SESSION['flash_error'] = "Transaction not found or access denied.";
    header("Location: view_transactions.php");
    exit;
}

// Delete the transaction
mysqli_query($conn, "DELETE FROM transactions WHERE id = $transaction_id AND user_id = $user_id");

$_SESSION['flash_success'] = "Transaction deleted successfully!";
header("Location: view_transactions.php");
exit;
?>
