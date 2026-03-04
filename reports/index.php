<?php
include "../includes/auth.php";
include "../includes/db.php";
$page_title = "Reports & Analytics";
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

    <div class="filter-box">
        <div class="quick-filters">
            <button type="button" onclick="setDateRange('week')">This Week</button>
            <button type="button" onclick="setDateRange('month')">This Month</button>
            <button type="button" onclick="setDateRange('year')">This Year</button>
            <button type="button" onclick="setDateRange('clear')">Clear</button>
        </div>

        <form method="GET" id="filter_form">
            <div class="form-row mb-2">
                <div class="form-group">
                    <label>From</label>
                    <input type="date" name="from" id="from_date" value="<?= $from ?>">
                </div>
                <div class="form-group">
                    <label>To</label>
                    <input type="date" name="to" id="to_date" value="<?= $to ?>">
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type">
                        <option value="">All</option>
                        <option value="Income" <?= $type == 'Income' ? 'selected' : '' ?>>Income</option>
                        <option value="Expense" <?= $type == 'Expense' ? 'selected' : '' ?>>Expense</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Account</label>
                    <select name="account_id">
                        <option value="">All</option>
                        <?php while ($a = mysqli_fetch_assoc($accounts)) { ?>
                            <option value="<?= $a['id'] ?>" <?= $account_id == $a['id'] ? 'selected' : '' ?>>
                                <?= $a['account_name'] ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">All</option>
                        <?php while ($c = mysqli_fetch_assoc($categories)) { ?>
                            <option value="<?= $c['id'] ?>" <?= $category_id == $c['id'] ? 'selected' : '' ?>>
                                <?= $c['category_name'] ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn">Filter</button>
                <a href="export.php?format=pdf&from=<?= $from ?>&to=<?= $to ?>&type=<?= $type ?>"
                    class="btn btn-danger btn-sm">Export PDF</a>
                <a href="export.php?format=excel&from=<?= $from ?>&to=<?= $to ?>&type=<?= $type ?>"
                    class="btn btn-success btn-sm">Export Excel</a>
            </div>
        </form>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="cards">
        <div class="card total">
            <h3>Total Income (Filtered)</h3>
            <p>₹ <?= number_format($total_income, 2) ?></p>
        </div>
        <div class="card cash">
            <h3>Total Expense (Filtered)</h3>
            <p>₹ <?= number_format($total_expense, 2) ?></p>
        </div>
        <div class="card bank">
            <h3>Net Balance</h3>
            <p>₹ <?= number_format($net, 2) ?></p>
        </div>
    </div>

    <h3>Transactions History</h3>

    <table>
        <tr>
            <th>Date</th>
            <th>Account</th>
            <th>Category</th>
            <th>Description</th>
            <th>Type</th>
            <th>Amount</th>
        </tr>

        <?php if (count($rows) > 0) {
            foreach ($rows as $r) { ?>
                <tr>
                    <td><?= $r['transaction_date'] ?></td>
                    <td><?= htmlspecialchars($r['account_name']) ?></td>
                    <td><?= htmlspecialchars($r['category_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['description']) ?></td>
                    <td>
                        <span class="badge <?= $r['type'] == 'Income' ? 'badge-income' : 'badge-expense' ?>">
                            <?= $r['type'] ?>
                        </span>
                    </td>
                    <td class="<?= $r['type'] == 'Income' ? 'text-success' : 'text-danger' ?>">
                        ₹ <?= number_format($r['amount'], 2) ?>
                    </td>
                </tr>
            <?php }
        } else { ?>
            <tr>
                <td colspan="6" class="text-center text-muted">No transactions found for the selected criteria.</td>
            </tr>
        <?php } ?>
    </table>

</div>

<script>
    function setDateRange(range) {
        const today = new Date();
        let fromDate, toDate;

        if (range === 'week') {
            // Monday of current week
            fromDate = new Date(today);
            const day = fromDate.getDay();
            const diff = day === 0 ? 6 : day - 1; // Adjust for Monday start
            fromDate.setDate(fromDate.getDate() - diff);
            // Sunday of current week
            toDate = new Date(fromDate);
            toDate.setDate(toDate.getDate() + 6);
        } else if (range === 'month') {
            // 1st of current month
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
            // Last day of current month
            toDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        } else if (range === 'year') {
            // Jan 1st
            fromDate = new Date(today.getFullYear(), 0, 1);
            // Dec 31st
            toDate = new Date(today.getFullYear(), 11, 31);
        } else if (range === 'clear') {
            document.getElementById('from_date').value = '';
            document.getElementById('to_date').value = '';
            document.getElementById('filter_form').submit();
            return;
        }

        // Format to YYYY-MM-DD
        function fmt(d) {
            return d.getFullYear() + '-' +
                String(d.getMonth() + 1).padStart(2, '0') + '-' +
                String(d.getDate()).padStart(2, '0');
        }

        document.getElementById('from_date').value = fmt(fromDate);
        document.getElementById('to_date').value = fmt(toDate);
        document.getElementById('filter_form').submit();
    }
</script>

<?php include "../includes/footer.php"; ?>