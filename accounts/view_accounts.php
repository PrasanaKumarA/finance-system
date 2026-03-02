<?php
include "../includes/auth.php";
include "../includes/db.php";
include "../includes/functions.php";

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role == "Admin") {
    $query = mysqli_query($conn, "SELECT * FROM accounts");
} else {
    $query = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
}

echo "<h2>Accounts</h2>";

while ($row = mysqli_fetch_assoc($query)) {

    $balance = getAccountBalance($conn, $row['id']);

    echo "<p>";
    echo $row['account_name'] . " (" . $row['account_type'] . ") - ₹" . $balance;
    echo "</p>";
}
?>