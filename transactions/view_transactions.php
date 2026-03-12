<?php
include "../includes/auth.php";
include "../includes/db.php";
$page_title = "All Transactions";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$transactions = mysqli_query($conn, "
    SELECT t.*, a.account_name, c.category_name
    FROM transactions t
    JOIN accounts a ON t.account_id = a.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = $user_id
    ORDER BY t.transaction_date DESC
");

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<div class="container">
    <div class="action-bar">
        <h2>All Transactions</h2>
        <a href="<?php echo BASE_PATH; ?>/index.php" class="btn btn-secondary">← Back</a>
        <a href="add_transaction.php" class="btn">+ Add Transaction</a>
    </div>

    <?php if ($flash_success) { ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div>
    <?php } ?>
    <?php if ($flash_error) { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($flash_error); ?></div>
    <?php } ?>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>Date</th>
                <th>Account</th>
                <th>Category</th>
                <th>Description</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Action</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($transactions)) { 
                $badge_class = 'badge-expense';
                if ($row['type'] == 'Income') $badge_class = 'badge-income';
                elseif ($row['type'] == 'Transfer') $badge_class = 'badge-transfer';
            ?>
                <tr>
                    <td><?php echo $row['transaction_date']; ?></td>
                    <td><?php echo htmlspecialchars($row['account_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td>
                        <span class="badge <?php echo $badge_class; ?>">
                            <?php echo $row['type']; ?>
                        </span>
                    </td>
                    <td class="<?php echo $row['type'] == 'Income' ? 'text-success' : ($row['type'] == 'Transfer' ? 'text-warning' : 'text-danger'); ?>">
                        ₹ <?php echo number_format($row['amount'], 2); ?>
                    </td>
                    <td>
                        <a href="edit_transaction.php?id=<?php echo $row['id']; ?>" class="action-link">Edit</a>
                        <a href="delete_transaction.php?id=<?php echo $row['id']; ?>" 
                           class="action-link danger"
                           onclick="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.')">Delete</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>
<?php include "../includes/footer.php"; ?>