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
?>
<div class="container">
    <div class="action-bar">
        <h2>All Transactions</h2>
        <a href="add_transaction.php" class="btn">+ Add Transaction</a>
    </div>

    <table>
        <tr>
            <th>Date</th>
            <th>Account</th>
            <th>Category</th>
            <th>Description</th>
            <th>Type</th>
            <th>Amount</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($transactions)) { ?>
            <tr>
                <td><?php echo $row['transaction_date']; ?></td>
                <td><?php echo htmlspecialchars($row['account_name']); ?></td>
                <td><?php echo htmlspecialchars($row['category_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['description']); ?></td>
                <td>
                    <span class="badge <?php echo $row['type'] == 'Income' ? 'badge-income' : 'badge-expense'; ?>">
                        <?php echo $row['type']; ?>
                    </span>
                </td>
                <td class="<?php echo $row['type'] == 'Income' ? 'text-success' : 'text-danger'; ?>">
                    ₹ <?php echo number_format($row['amount'], 2); ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php include "../includes/footer.php"; ?>