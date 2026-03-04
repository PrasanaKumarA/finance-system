<?php
include "../includes/auth.php";
include "../includes/db.php";
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
    <h2>All Transactions</h2>
    <a href="add_transaction.php" class="btn"
        style="padding: 10px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 4px;">+ Add
        Transaction</a>
    <br><br>
    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr style="background: #f4f4f4;">
            <th style="padding: 10px; border: 1px solid #ddd;">Date</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Account</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Category</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Description</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Type</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Amount</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($transactions)) { ?>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <?php echo $row['transaction_date']; ?>
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <?php echo htmlspecialchars($row['account_name']); ?>
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <?php echo htmlspecialchars($row['category_name'] ?? '-'); ?>
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <?php echo htmlspecialchars($row['description']); ?>
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <?php echo $row['type']; ?>
                </td>
                <td
                    style="padding: 10px; border: 1px solid #ddd; color:<?php echo $row['type'] == 'Income' ? 'green' : 'red'; ?>">
                    ₹
                    <?php echo number_format($row['amount'], 2); ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php include "../includes/footer.php"; ?>