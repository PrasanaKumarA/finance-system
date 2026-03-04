<?php
include "../includes/auth.php";
include "../includes/db.php";
include "../includes/functions.php";
$page_title = "Account Details";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$account_id = $_GET['id'] ?? 0;
$role = $_SESSION['role'];

// Verify access
if ($role == "Admin") {
    $account_query = mysqli_query($conn, "SELECT * FROM accounts WHERE id=$account_id");
} else {
    $account_query = mysqli_query($conn, "SELECT * FROM accounts WHERE id=$account_id AND user_id=$user_id");
}

if (mysqli_num_rows($account_query) == 0) {
    echo "<div class='container'><p class='text-danger'>Account not found or access denied.</p></div>";
    include "../includes/footer.php";
    exit();
}

$account = mysqli_fetch_assoc($account_query);
$balance = getAccountBalance($conn, $account_id);

$transactions = mysqli_query($conn, "
    SELECT t.*, c.category_name 
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.account_id = $account_id
    ORDER BY t.transaction_date DESC
");
?>
<div class="container">
    <h2>Account: <?php echo htmlspecialchars($account['account_name']); ?></h2>

    <div class="cards">
        <div class="card cash">
            <h3>Available Balance</h3>
            <p>₹ <?php echo number_format($balance, 2); ?></p>
        </div>
        <div class="card bank">
            <h3>Opening Balance</h3>
            <p>₹ <?php echo number_format($account['opening_balance'] ?? 0, 2); ?></p>
        </div>
        <div class="card total">
            <h3>Account Type</h3>
            <p><?php echo htmlspecialchars($account['account_type']); ?></p>
        </div>
    </div>

    <h3>Transaction History</h3>
    <table>
        <tr>
            <th>Date</th>
            <th>Category</th>
            <th>Description</th>
            <th>Type</th>
            <th>Amount</th>
        </tr>
        <?php if (mysqli_num_rows($transactions) > 0) {
            while ($t = mysqli_fetch_assoc($transactions)) { ?>
                <tr>
                    <td><?php echo $t['transaction_date']; ?></td>
                    <td><?php echo $t['category_name'] ? htmlspecialchars($t['category_name']) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($t['description']); ?></td>
                    <td>
                        <span class="badge <?php echo $t['type'] == 'Income' ? 'badge-income' : 'badge-expense'; ?>">
                            <?php echo $t['type']; ?>
                        </span>
                    </td>
                    <td class="<?php echo $t['type'] == 'Income' ? 'text-success' : 'text-danger'; ?>">
                        ₹ <?php echo number_format($t['amount'], 2); ?>
                    </td>
                </tr>
            <?php }
        } else { ?>
            <tr>
                <td colspan="5" class="text-center text-muted">No transactions found for this account.</td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php include "../includes/footer.php"; ?>