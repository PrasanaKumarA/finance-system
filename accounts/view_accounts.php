<?php
include "../includes/auth.php";
include "../includes/db.php";
include "../includes/functions.php";
$page_title = "Accounts";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Admins should only see their own accounts here. 
// Global views are isolated in the Admin Panel.
$query = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");

$type_icons = [
    'Bank' => '🏦',
    'Cash' => '💵',
    'Mobile Wallet' => '📱',
    'Credit Card' => '💳'
];
?>

<div class="container">
    <div class="action-bar">
        <h2>Accounts</h2>
        <a href="add_account.php" class="btn">+ Add New Account</a>
    </div>

    <?php if (isset($_GET['deleted'])) { ?>
        <div class="alert alert-success">Account and all related transactions have been deleted.</div>
    <?php } ?>
    <?php if (isset($_GET['error'])) { ?>
        <div class="alert alert-danger">Account not found or you don't have permission to delete it.</div>
    <?php } ?>

    <table>
        <tr>
            <th>Account Name</th>
            <th>Type</th>
            <th>Available Balance</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($query)) {
            $balance = getAccountBalance($conn, $row['id']);
            $icon = $type_icons[$row['account_type']] ?? '';
            ?>
            <tr>
                <td><a
                        href="account_details.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['account_name']); ?></a>
                </td>
                <td><?php echo $icon . ' ' . htmlspecialchars($row['account_type'] ?? '—'); ?></td>
                <td>₹ <?php echo number_format($balance, 2); ?></td>
                <td>
                    <a href="account_details.php?id=<?php echo $row['id']; ?>" class="action-link">View</a>
                    <a href="delete_account.php?id=<?php echo $row['id']; ?>" class="action-link danger"
                        onclick="return confirm('⚠️ Delete this account?\n\nAll transactions linked to this account will also be permanently deleted. This cannot be undone.')">
                        Delete
                    </a>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>

<?php include "../includes/footer.php"; ?>