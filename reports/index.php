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
    <h2>Reports & Analytics</h2>

    <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <p style="margin-top:0; font-weight:bold;">Quick Filters:</p>
        <button type="button" class="btn" style="padding: 5px 10px; margin-right: 5px;"
            onclick="setDateRange('week')">This Week</button>
        <button type="button" class="btn" style="padding: 5px 10px; margin-right: 5px;"
            onclick="setDateRange('month')">This Month</button>
        <button type="button" class="btn" style="padding: 5px 10px; margin-right: 5px;"
            onclick="setDateRange('year')">This Year</button>
        <button type="button" class="btn" style="padding: 5px 10px; background: #95a5a6;"
            onclick="setDateRange('clear')">Clear</button>
    </div>

    <form method="GET" id="filter_form"
        style="margin-bottom:20px; display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
        <div>
            <label>From:</label><br>
            <input type="date" name="from" id="from_date" value="<?= $from ?>">
        </div>
        <div>
            <label>To:</label><br>
            <input type="date" name="to" id="to_date" value="<?= $to ?>">
        </div>
        <div>
            <label>Type:</label><br>
            <select name="type">
                <option value="">All</option>
                <option value="Income" <?= $type == 'Income' ? 'selected' : '' ?>>Income</option>
                <option value="Expense" <?= $type == 'Expense' ? 'selected' : '' ?>>Expense</option>
            </select>
        </div>
        <div>
            <label>Account:</label><br>
            <select name="account_id">
                <option value="">All</option>
                <?php while ($a = mysqli_fetch_assoc($accounts)) { ?>
                    <option value="<?= $a['id'] ?>" <?= $account_id == $a['id'] ? 'selected' : '' ?>>
                        <?= $a['account_name'] ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div>
            <label>Category:</label><br>
            <select name="category_id">
                <option value="">All</option>
                <?php while ($c = mysqli_fetch_assoc($categories)) { ?>
                    <option value="<?= $c['id'] ?>" <?= $category_id == $c['id'] ? 'selected' : '' ?>>
                        <?= $c['category_name'] ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div style="margin-top: 10px;">
            <button type="submit" class="btn">Filter Database</button>
            <a href="export.php?format=pdf&from=<?= $from ?>&to=<?= $to ?>&type=<?= $type ?>" class="btn"
                style="background:#e74c3c;">Export PDF</a>
            <a href="export.php?format=excel&from=<?= $from ?>&to=<?= $to ?>&type=<?= $type ?>" class="btn"
                style="background:#27ae60;">Export Excel</a>
        </div>
    </form>

    <!-- SUMMARY CARDS -->
    <div class="cards" style="justify-content: flex-start; gap:20px;">
        <div class="card total">
            <h3>Total Income (Filtered)</h3>
            <p>₹
                <?= number_format($total_income, 2) ?>
            </p>
        </div>
        <div class="card cash" style="border-left: 5px solid #e74c3c;">
            <h3>Total Expense (Filtered)</h3>
            <p>₹
                <?= number_format($total_expense, 2) ?>
            </p>
        </div>
        <div class="card bank" style="border-left: 5px solid #f1c40f;">
            <h3>Net Balance</h3>
            <p>₹
                <?= number_format($net, 2) ?>
            </p>
        </div>
    </div>

    <h3>Transactions History</h3>

    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr style="background: #f4f4f4;">
            <th style="padding: 10px; border: 1px solid #ddd;">Date</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Account</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Category</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Description</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Type</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Amount</th>
        </tr>

        <?php if (count($rows) > 0) {
            foreach ($rows as $r) { ?>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <?= $r['transaction_date'] ?>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <?= htmlspecialchars($r['account_name']) ?>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <?= htmlspecialchars($r['category_name'] ?? '-') ?>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <?= htmlspecialchars($r['description']) ?>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <?= $r['type'] ?>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd; color:<?= $r['type'] == 'Income' ? 'green' : 'red' ?>">
                        ₹ <?= number_format($r['amount'], 2) ?>
                    </td>
                </tr>
            <?php }
        } else { ?>
            <tr>
                <td colspan="6" style="text-align:center; padding: 20px;">No transactions found for the selected criteria.
                </td>
            </tr>
        <?php } ?>
    </table>

</div>

<script>
    function setDateRange(range) {
        const today = new Date();
        let fromDate = new Date();
        let toDate = new Date();

        if (range === 'week') {
            const firstDay = today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1);
            fromDate.setDate(firstDay);
        } else if (range === 'month') {
            fromDate.setDate(1);
        } else if (range === 'year') {
            fromDate.setMonth(0);
            fromDate.setDate(1);
        } else if (range === 'clear') {
            document.getElementById('from_date').value = '';
            document.getElementById('to_date').value = '';
            document.getElementById('filter_form').submit();
            return;
        }

        document.getElementById('from_date').value = fromDate.toISOString().split('T')[0];
        document.getElementById('to_date').value = toDate.toISOString().split('T')[0];
        document.getElementById('filter_form').submit();
    }
</script>

<?php include "../includes/footer.php"; ?>