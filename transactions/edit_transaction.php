<?php
include "../includes/auth.php";
include "../includes/db.php";
$page_title = "Edit Transaction";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$transaction_id = intval($_GET['id'] ?? 0);
$success_msg = "";
$error_msg = "";

/* Load the transaction */
$t_query = mysqli_query($conn, "
    SELECT * FROM transactions 
    WHERE id = $transaction_id AND user_id = $user_id
    LIMIT 1
");

if (mysqli_num_rows($t_query) == 0) {
    echo "<div class='container'><div class='alert alert-danger'>Transaction not found or access denied.</div><a href='view_transactions.php' class='btn'>Back</a></div>";
    include "../includes/footer.php";
    exit;
}

$txn = mysqli_fetch_assoc($t_query);

/* Handle UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $account_id = intval($_POST['account_id']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : 'NULL';
    $amount = floatval($_POST['amount']);
    $date = mysqli_real_escape_string($conn, $_POST['transaction_date']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);

    $cat_sql = ($category_id === 'NULL') ? "category_id = NULL" : "category_id = $category_id";

    $update = mysqli_query($conn, "
        UPDATE transactions 
        SET type = '$type',
            account_id = $account_id,
            $cat_sql,
            amount = $amount,
            transaction_date = '$date',
            description = '$desc'
        WHERE id = $transaction_id AND user_id = $user_id
    ");

    if ($update) {
        $success_msg = "Transaction updated successfully!";
        // Reload data
        $t_query = mysqli_query($conn, "SELECT * FROM transactions WHERE id = $transaction_id");
        $txn = mysqli_fetch_assoc($t_query);
    } else {
        $error_msg = "Failed to update transaction.";
    }
}

/* Get accounts and categories */
$accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id = $user_id");
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id = $user_id");
?>

<div class="container">
    <div class="action-bar">
        <h2>Edit Transaction</h2>
        <a href="view_transactions.php" class="btn btn-secondary">← Back</a>
    </div>

    <?php if ($success_msg) { ?>
        <div class="alert alert-success">
            <?php echo $success_msg; ?>
        </div>
    <?php } ?>
    <?php if ($error_msg) { ?>
        <div class="alert alert-danger">
            <?php echo $error_msg; ?>
        </div>
    <?php } ?>

    <form method="POST">

        <div class="form-row mb-2">
            <div class="form-group">
                <label>Transaction Type</label>
                <select name="type" required>
                    <option value="Income" <?php echo $txn['type'] == 'Income' ? 'selected' : ''; ?>>Income</option>
                    <option value="Expense" <?php echo $txn['type'] == 'Expense' ? 'selected' : ''; ?>>Expense</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="transaction_date" value="<?php echo $txn['transaction_date']; ?>" required>
            </div>
        </div>

        <label>Account</label>
        <select name="account_id" required>
            <?php while ($a = mysqli_fetch_assoc($accounts)) { ?>
                <option value="<?php echo $a['id']; ?>" <?php echo $txn['account_id'] == $a['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($a['account_name']); ?>
                </option>
            <?php } ?>
        </select>

        <label>Category</label>
        <select name="category_id">
            <option value="">-- None --</option>
            <?php while ($c = mysqli_fetch_assoc($categories)) { ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $txn['category_id'] == $c['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['category_name']); ?>
                </option>
            <?php } ?>
        </select>

        <label>Amount (₹)</label>
        <input type="number" step="0.01" name="amount" value="<?php echo $txn['amount']; ?>" required>

        <label>Description</label>
        <input type="text" name="description" value="<?php echo htmlspecialchars($txn['description']); ?>">

        <button type="submit" class="btn" style="width: 100%; margin-top: 8px;">Update Transaction</button>
    </form>
</div>

<?php include "../includes/footer.php"; ?>