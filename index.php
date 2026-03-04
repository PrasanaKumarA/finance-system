<?php
include "includes/auth.php";
include "includes/db.php";
include "includes/functions.php";
$page_title = "Dashboard";
include "includes/header.php";
include "includes/navbar.php";

$user_id = $_SESSION['user_id'];

/* ================= MAIN BALANCES ================= */
$total_balance = getTotalBalance($conn, $user_id);
$bank_balance = getBankBalance($conn, $user_id);
$cash_balance = getCashBalance($conn, $user_id);

/* ================= MONTHLY SUMMARY ================= */
$current_month = date('m');
$current_year = date('Y');

$income_query = mysqli_query($conn, "
    SELECT SUM(amount) as total_income 
    FROM transactions 
    WHERE user_id = $user_id 
    AND type = 'Income'
    AND MONTH(transaction_date) = $current_month
    AND YEAR(transaction_date) = $current_year
");
$income_data = mysqli_fetch_assoc($income_query);
$monthly_income = $income_data['total_income'] ?? 0;

$expense_query = mysqli_query($conn, "
    SELECT SUM(amount) as total_expense 
    FROM transactions 
    WHERE user_id = $user_id 
    AND type = 'Expense'
    AND MONTH(transaction_date) = $current_month
    AND YEAR(transaction_date) = $current_year
");
$expense_data = mysqli_fetch_assoc($expense_query);
$monthly_expense = $expense_data['total_expense'] ?? 0;

$net_profit = $monthly_income - $monthly_expense;

/* ================= BUDGET OVERVIEW ================= */
$budget_query = mysqli_query($conn, "
    SELECT budget_amount FROM budgets
    WHERE user_id = $user_id AND category_id IS NULL
    AND month = $current_month AND year = $current_year
");
$monthly_budget = 0;
if ($b_row = mysqli_fetch_assoc($budget_query)) {
    $monthly_budget = $b_row['budget_amount'];
}

/* Category budgets for dashboard */
$cat_budgets = mysqli_query($conn, "
    SELECT c.category_name, b.budget_amount,
           IFNULL((
               SELECT SUM(t.amount)
               FROM transactions t
               WHERE t.category_id = c.id
               AND t.user_id = $user_id
               AND t.type = 'Expense'
               AND MONTH(t.transaction_date) = $current_month
               AND YEAR(t.transaction_date) = $current_year
           ), 0) as spent
    FROM budgets b
    JOIN categories c ON b.category_id = c.id
    WHERE b.user_id = $user_id
    AND b.month = $current_month
    AND b.year = $current_year
    AND b.category_id IS NOT NULL
    ORDER BY c.category_name
");

/* ================= CATEGORY PIE CHART ================= */
$category_chart = mysqli_query($conn, "
    SELECT c.category_name, SUM(t.amount) as total
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = $user_id
    AND t.type = 'Expense'
    GROUP BY t.category_id
");

$category_labels = [];
$category_values = [];

while ($row = mysqli_fetch_assoc($category_chart)) {
    $category_labels[] = $row['category_name'];
    $category_values[] = $row['total'];
}

/* ================= BAR CHART (Last 6 Months) ================= */
$bar_labels = [];
$bar_income = [];
$bar_expense = [];

for ($i = 5; $i >= 0; $i--) {
    $month = date('m', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));

    $bar_labels[] = $label;

    $income = mysqli_query($conn, "
        SELECT SUM(amount) as total 
        FROM transactions
        WHERE user_id = $user_id
        AND type='Income'
        AND MONTH(transaction_date)=$month
        AND YEAR(transaction_date)=$year
    ");
    $income_row = mysqli_fetch_assoc($income);
    $bar_income[] = $income_row['total'] ?? 0;

    $expense = mysqli_query($conn, "
        SELECT SUM(amount) as total 
        FROM transactions
        WHERE user_id = $user_id
        AND type='Expense'
        AND MONTH(transaction_date)=$month
        AND YEAR(transaction_date)=$year
    ");
    $expense_row = mysqli_fetch_assoc($expense);
    $bar_expense[] = $expense_row['total'] ?? 0;
}

/* ================= RECENT TRANSACTIONS ================= */
$transactions = mysqli_query($conn, "
    SELECT t.*, a.account_name, c.category_name
    FROM transactions t
    JOIN accounts a ON t.account_id = a.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = $user_id
    ORDER BY t.created_at DESC
    LIMIT 5
");
?>

<div class="container">

    <div class="action-bar">
        <h2>Dashboard</h2>
        <a href="budgets/set_budget.php" class="btn">📊 Set Budget</a>
    </div>

    <!-- MAIN CARDS -->
    <div class="cards">
        <div class="card total">
            <h3>Total Balance</h3>
            <p>₹ <?php echo number_format($total_balance, 2); ?></p>
        </div>
        <div class="card bank">
            <h3>Bank Balance</h3>
            <p>₹ <?php echo number_format($bank_balance, 2); ?></p>
        </div>
        <div class="card cash">
            <h3>Cash Balance</h3>
            <p>₹ <?php echo number_format($cash_balance, 2); ?></p>
        </div>
    </div>

    <!-- MONTHLY SUMMARY -->
    <h3>This Month Summary</h3>
    <div class="cards">
        <div class="card total">
            <h3>Monthly Income</h3>
            <p>₹ <?php echo number_format($monthly_income, 2); ?></p>
        </div>
        <div class="card cash">
            <h3>Monthly Expense</h3>
            <p>₹ <?php echo number_format($monthly_expense, 2); ?></p>
        </div>
        <div class="card bank">
            <h3>Net Profit</h3>
            <p>₹ <?php echo number_format($net_profit, 2); ?></p>
        </div>
    </div>

    <!-- BUDGET PROGRESS -->
    <?php if ($monthly_budget > 0 || mysqli_num_rows($cat_budgets) > 0) { ?>
        <div class="budget-card">
            <div class="action-bar" style="margin-bottom: 12px;">
                <h3 style="margin:0;">Budget Tracker</h3>
                <a href="budgets/set_budget.php" class="action-link">Edit →</a>
            </div>

            <?php if ($monthly_budget > 0) {
                $pct = round(($monthly_expense / $monthly_budget) * 100);
                $bar_class = $pct > 100 ? 'danger' : ($pct > 75 ? 'warning' : '');
                ?>
                <div class="budget-item">
                    <div class="budget-item-header">
                        <span class="budget-item-name">Overall Monthly</span>
                        <span class="budget-item-amounts">
                            <strong>₹<?php echo number_format($monthly_expense, 2); ?></strong>
                            / ₹<?php echo number_format($monthly_budget, 2); ?>
                            (<?php echo min($pct, 999); ?>%)
                        </span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $bar_class; ?>" style="width: <?php echo min($pct, 100); ?>%">
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php
            mysqli_data_seek($cat_budgets, 0);
            while ($cb = mysqli_fetch_assoc($cat_budgets)) {
                $cpct = round(($cb['spent'] / $cb['budget_amount']) * 100);
                $cbar = $cpct > 100 ? 'danger' : ($cpct > 75 ? 'warning' : '');
                ?>
                <div class="budget-item">
                    <div class="budget-item-header">
                        <span class="budget-item-name"><?php echo htmlspecialchars($cb['category_name']); ?></span>
                        <span class="budget-item-amounts">
                            <strong>₹<?php echo number_format($cb['spent'], 2); ?></strong>
                            / ₹<?php echo number_format($cb['budget_amount'], 2); ?>
                        </span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $cbar; ?>" style="width: <?php echo min($cpct, 100); ?>%"></div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <!-- PIE CHART -->
    <h3>Expense Distribution</h3>

    <?php if (!empty($category_labels)) { ?>
        <div class="chart-container" style="max-width: 500px; margin: 0 auto 30px auto;">
            <canvas id="expenseChart"></canvas>
        </div>
    <?php } else { ?>
        <p class="text-muted">No expense data available.</p>
    <?php } ?>

    <!-- BAR CHART -->
    <h3>Income vs Expense (Last 6 Months)</h3>
    <div class="chart-container">
        <canvas id="barChart" style="max-height:350px;"></canvas>
    </div>

    <!-- RECENT TRANSACTIONS -->
    <div class="action-bar mt-3">
        <h3 style="margin:0;">Recent Transactions</h3>
        <a href="transactions/view_transactions.php" class="action-link">See All →</a>
    </div>

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
        <?php while ($row = mysqli_fetch_assoc($transactions)) { ?>
            <tr>
                <td><?php echo $row['transaction_date']; ?></td>
                <td><?php echo $row['account_name']; ?></td>
                <td><?php echo $row['category_name']; ?></td>
                <td><?php echo htmlspecialchars($row['description']); ?></td>
                <td>
                    <span class="badge <?php echo $row['type'] == 'Income' ? 'badge-income' : 'badge-expense'; ?>">
                        <?php echo $row['type']; ?>
                    </span>
                </td>
                <td class="<?php echo $row['type'] == 'Income' ? 'text-success' : 'text-danger'; ?>">
                    ₹ <?php echo number_format($row['amount'], 2); ?>
                </td>
                <td>
                    <a href="transactions/edit_transaction.php?id=<?php echo $row['id']; ?>" class="action-link">Edit</a>
                </td>
            </tr>
        <?php } ?>
    </table>

</div>

<!-- FAB: Quick Add Transaction -->
<a href="transactions/add_transaction.php" class="fab" title="Quick Add Transaction">+</a>

<script>
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const chartTextColor = isDark ? '#94a3b8' : '#6b7280';
    const chartGridColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(0,0,0,0.06)';

    Chart.defaults.color = chartTextColor;
    Chart.defaults.borderColor = chartGridColor;

    <?php if (!empty($category_labels)) { ?>
        new Chart(document.getElementById('expenseChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($category_values); ?>,
                    backgroundColor: ['#6366f1', '#06b6d4', '#f59e0b', '#10b981', '#ec4899', '#8b5cf6'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' } }
                }
            }
        });
    <?php } ?>

    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($bar_labels); ?>,
            datasets: [
                {
                    label: 'Income',
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                    data: <?php echo json_encode($bar_income); ?>
                },
                {
                    label: 'Expense',
                    backgroundColor: '#ef4444',
                    borderRadius: 6,
                    data: <?php echo json_encode($bar_expense); ?>
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { usePointStyle: true, pointStyle: 'circle', padding: 16 } }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: chartGridColor } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php include "includes/footer.php"; ?>