<?php
include "../includes/auth.php";
include "../includes/db.php";
include "../includes/functions.php";
$page_title = "Accounts";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role == "Admin") {
    $query = mysqli_query($conn, "SELECT * FROM accounts");
} else {
    $query = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
}
?>

<div class="container">
    <div class="action-bar">
        <h2>Accounts</h2>
        <a href="add_account.php" class="btn">+ Add New Account</a>
    </div>

    <table>
        <tr>
            <th>Account Name</th>
            <th>Type</th>
            <th>Available Balance</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($query)) {
            $balance = getAccountBalance($conn, $row['id']);
            ?>
            <tr>
                <td><a
                        href="account_details.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['account_name']); ?></a>
                </td>
                <td><?php echo $row['account_type']; ?></td>
                <td>₹ <?php echo number_format($balance, 2); ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<?php include "../includes/footer.php"; ?>