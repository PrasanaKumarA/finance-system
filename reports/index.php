<?php
include "../includes/auth.php";
include "../includes/db.php";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];

/* ===== FILTER VALUES ===== */
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$type = $_GET['type'] ?? '';
$account_id = $_GET['account_id'] ?? '';
$category_id = $_GET['category_id'] ?? '';

/* ===== BUILD QUERY ===== */
$query = "
    SELECT t.*, a.account_name, c.category_name
    FROM transactions t
    JOIN accounts a ON t.account_id = a.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = $user_id
";

if ($from && $to) {
    $query .= " AND t.transaction_date BETWEEN '$from' AND '$to'";
}

if ($type) {
    $query .= " AND t.type = '$type'";
}

if ($account_id) {
    $query .= " AND t.account_id = $account_id";
}

if ($category_id) {
    $query .= " AND t.category_id = $category_id";
}

$query .= " ORDER BY t.transaction_date DESC";

$results = mysqli_query($conn, $query);

/* ===== SUMMARY CALCULATION ===== */
$total_income = 0;
$total_expense = 0;

$rows = [];
while ($row = mysqli_fetch_assoc($results)) {
    $rows[] = $row;
    if ($row['type'] == 'Income') {
        $total_income += $row['amount'];
    } else {
        $total_expense += $row['amount'];
    }
}

$net = $total_income - $total_expense;

/* ===== GET ACCOUNTS & CATEGORIES ===== */
$accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
$categories = mysqli_query($conn, "SELECT * FROM categories");
?>

<div class="container">
    <h2>Reports</h2>

    <form method="GET" style="margin-bottom:20px;">
        From: <input type="date" name="from" value="<?= $from ?>">
        To: <input type="date" name="to" value="<?= $to ?>">

        Type:
        <select name="type">
            <option value="">All</option>
            <option value="Income" <?= $type == 'Income' ? 'selected' : '' ?>>Income</option>
            <option value="Expense" <?= $type == 'Expense' ? 'selected' : '' ?>>Expense</option>
        </select>

        Account:
        <select name="account_id">
            <option value="">All</option>
            <?php while ($a = mysqli_fetch_assoc($accounts)) { ?>
                <option value="<?= $a['id'] ?>" <?= $account_id == $a['id'] ? 'selected' : '' ?>>
                    <?= $a['account_name'] ?>
                </option>
            <?php } ?>
        </select>

        Category:
        <select name="category_id">
            <option value="">All</option>
            <?php while ($c = mysqli_fetch_assoc($categories)) { ?>
                <option value="<?= $c['id'] ?>" <?= $category_id == $c['id'] ? 'selected' : '' ?>>
                    <?= $c['category_name'] ?>
                </option>
            <?php } ?>
        </select>

        <button type="submit">Filter</button>
        <a href="export.php?format=pdf&from=<?= $from ?>&to=<?= $to ?>&type=<?= $type ?>" class="btn">Export PDF</a>

        <a href="export.php?format=excel&from=<?= $from ?>&to=<?= $to ?>&type=<?= $type ?>" class="btn">Export Excel</a>
    </form>

    <!-- SUMMARY CARDS -->
    <div class="cards">
        <div class="card total">
            <h3>Total Income</h3>
            <p>₹
                <?= number_format($total_income, 2) ?>
            </p>
        </div>
        <div class="card cash">
            <h3>Total Expense</h3>
            <p>₹
                <?= number_format($total_expense, 2) ?>
            </p>
        </div>
        <div class="card bank">
            <h3>Net</h3>
            <p>₹
                <?= number_format($net, 2) ?>
            </p>
        </div>
    </div>

    <h3>Transactions</h3>

    <table>
        <tr>
            <th>Date</th>
            <th>Account</th>
            <th>Category</th>
            <th>Type</th>
            <th>Amount</th>
        </tr>

        <?php foreach ($rows as $r) { ?>
            <tr>
                <td>
                    <?= $r['transaction_date'] ?>
                </td>
                <td>
                    <?= $r['account_name'] ?>
                </td>
                <td>
                    <?= $r['category_name'] ?>
                </td>
                <td>
                    <?= $r['type'] ?>
                </td>
                <td style="color:<?= $r['type'] == 'Income' ? 'green' : 'red' ?>">
                    ₹
                    <?= number_format($r['amount'], 2) ?>
                </td>
            </tr>
        <?php } ?>
    </table>

</div>

<?php include "../includes/footer.php"; ?>