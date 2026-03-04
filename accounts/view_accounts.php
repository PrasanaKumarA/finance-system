<?php
include "../includes/auth.php";
include "../includes/db.php";
include "../includes/functions.php";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role == "Admin") {
    $query = mysqli_query($conn, "SELECT * FROM accounts");
} else {
    $query = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
}

echo "<div class='container'>";
echo "<h2>Accounts</h2>";
echo "<a href='add_account.php' class='btn' style='padding: 10px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 4px;'>+ Add New Account</a><br><br>";

echo "<table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>";
echo "<tr style='background: #f4f4f4;'>
        <th style='padding: 10px; border: 1px solid #ddd;'>Account Name</th>
        <th style='padding: 10px; border: 1px solid #ddd;'>Type</th>
        <th style='padding: 10px; border: 1px solid #ddd;'>Available Balance</th>
      </tr>";

while ($row = mysqli_fetch_assoc($query)) {

    $balance = getAccountBalance($conn, $row['id']);

    echo "<tr>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'><a href='account_details.php?id=" . $row['id'] . "' style='color: #2c3e50; font-weight: bold; text-decoration: none; border-bottom: 1px dotted #3498db;'>" . htmlspecialchars($row['account_name']) . "</a></td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $row['account_type'] . "</td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'>₹" . number_format($balance, 2) . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

include "../includes/footer.php";
?>