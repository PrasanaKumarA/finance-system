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

/* ================= ALL TRANSACTIONS (for mobile search & filter) ================= */
$all_txn_query = mysqli_query($conn, "
    SELECT t.id, t.amount, t.type, t.description, t.transaction_date,
           a.account_name, c.category_name
    FROM transactions t
    JOIN accounts a ON t.account_id = a.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = $user_id
    ORDER BY t.transaction_date DESC
");
$all_transactions_json = [];
while ($txn = mysqli_fetch_assoc($all_txn_query)) {
    $all_transactions_json[] = $txn;
}
?>

<div class="container">

    <!-- DESKTOP DASHBOARD -->
    <div class="d-none d-md-block">
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
                            <div class="progress-fill <?php echo $cbar; ?>" style="width: <?php echo min($cpct, 100); ?>%">
                            </div>
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
                <?php
                mysqli_data_seek($transactions, 0);
                while ($row = mysqli_fetch_assoc($transactions)) { 
                    $badge_class = 'badge-expense';
                    if ($row['type'] == 'Income') $badge_class = 'badge-income';
                    elseif ($row['type'] == 'Transfer') $badge_class = 'badge-transfer';
                ?>
                    <tr>
                        <td><?php echo $row['transaction_date']; ?></td>
                        <td><?php echo $row['account_name']; ?></td>
                        <td><?php echo $row['category_name']; ?></td>
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
                            <a href="transactions/edit_transaction.php?id=<?php echo $row['id']; ?>"
                                class="action-link">Edit</a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>

    <!-- MOBILE DASHBOARD APP LAYOUT -->
    <div class="d-block d-md-none mobile-dashboard">
        <!-- Header -->
        <div class="md-header">
            <div class="md-greeting">
                <p id="mobileGreeting">Good Evening,</p>
                <h1><?php echo htmlspecialchars($_SESSION['name']); ?></h1>
            </div>
            <div class="md-header-actions">
                <button class="md-icon-btn" id="mdSearchBtn" type="button" title="Search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
                <button class="md-icon-btn md-diamond-btn" id="mobileThemeBtn" type="button" title="Toggle theme">
                    <span class="icon-sun">☀️</span>
                    <span class="icon-moon">🌙</span>
                </button>
                <a href="profile.php" class="md-profile-icon">
                    <?php if (isset($profile_picture) && $profile_picture && file_exists(__DIR__ . '/' . $profile_picture)) { ?>
                        <img src="<?php echo BASE_PATH . '/' . htmlspecialchars($profile_picture); ?>" alt="Profile">
                    <?php } else { ?>
                        <div class="md-avatar-fallback"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                    <?php } ?>
                </a>
            </div>
        </div>

        <!-- Cash Flow Card -->
        <div class="md-cashflow-card">
            <div class="md-cashflow-header">
                <span class="md-cashflow-label">CASH FLOW</span>
                <div class="md-dropdown-wrapper">
                    <button class="md-cashflow-period" id="mdDropdownBtn" type="button">
                        This Month <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <div class="md-dropdown-menu" id="mdDropdownMenu">
                        <div class="md-dropdown-item active" data-period="month">
                            <strong>Month</strong>
                            <span><?php echo date('01 M') . ' - ' . date('t M Y'); ?></span>
                        </div>
                        <div class="md-dropdown-item" data-period="year">
                            <strong>Year</strong>
                            <span><?php echo date('01 Jan') . ' - 31 Dec ' . date('Y'); ?></span>
                        </div>
                        <div class="md-dropdown-item" data-period="all">
                            <strong>All Time</strong>
                            <span>∞</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="md-cashflow-amounts">
                <div class="md-cashflow-col">
                    <span class="md-cf-type spending">SPENDING</span>
                    <span class="md-cf-value" id="mdSpending">₹<?php echo number_format($monthly_expense); ?></span>
                </div>
                <div class="md-cashflow-col right">
                    <span class="md-cf-type income">INCOME</span>
                    <span class="md-cf-value" id="mdIncome">₹<?php echo number_format($monthly_income); ?></span>
                </div>
            </div>
            <div class="md-cashflow-balance">
                <span class="md-cf-balance-label">Net Balance</span>
                <span class="md-cf-balance-value" id="mdNetBalance">₹<?php echo number_format($net_profit); ?></span>
            </div>
        </div>

        <!-- Search Overlay -->
        <div class="md-search-overlay" id="mdSearchOverlay">
            <div class="md-search-bar">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="mdSearchInput" placeholder="Search transactions..." autocomplete="off">
                <button type="button" id="mdSearchClose" class="md-search-close">✕</button>
            </div>
            <div class="md-search-results" id="mdSearchResults"></div>
        </div>

        <!-- Recent Transactions List -->
        <div class="md-section-header">
            <h3>Recent Transactions</h3>
            <a href="transactions/view_transactions.php">See all</a>
        </div>

        <div class="md-txn-list">
            <?php
            mysqli_data_seek($transactions, 0);
            while ($row = mysqli_fetch_assoc($transactions)) {
                $isIncome = $row['type'] == 'Income';
                $isTransfer = $row['type'] == 'Transfer';
                
                if ($isTransfer) {
                    $color = '#3b82f6';
                } elseif ($isIncome) {
                    $color = '#10b981';
                } else {
                    $color = '#f59e0b';
                }
                
                // Category-based icons
                $icon_svg = '';
                if ($isTransfer) {
                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>';
                } elseif (strpos(strtolower($row['category_name'] ?? ''), 'food') !== false) {
                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>';
                } elseif (strpos(strtolower($row['category_name'] ?? ''), 'bill') !== false) {
                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>';
                } elseif (strpos(strtolower($row['category_name'] ?? ''), 'shop') !== false || strpos(strtolower($row['category_name'] ?? ''), 'grocer') !== false) {
                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>';
                } elseif ($isIncome) {
                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>';
                } else {
                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
                }

                $desc_text = htmlspecialchars($row['description']) ?: htmlspecialchars($row['category_name'] ?? 'Transaction');
                $account_icon = '';
                $account_type_lower = strtolower($row['account_name'] ?? '');
                if (strpos($account_type_lower, 'bank') !== false) {
                    $account_icon = '🏦';
                } elseif (strpos($account_type_lower, 'cash') !== false) {
                    $account_icon = '💵';
                } else {
                    $account_icon = '🏦';
                }

                $txn_date = strtotime($row['transaction_date']);
                $today = strtotime(date('Y-m-d'));
                $yesterday = strtotime('-1 day');
                if ($txn_date == $today) {
                    $date_label = 'Today';
                } elseif ($txn_date == $yesterday) {
                    $date_label = 'Yesterday';
                } else {
                    $date_label = date('d M', $txn_date);
                }
                
                $amount_color = $isIncome ? '#10b981' : ($isTransfer ? '#3b82f6' : 'var(--text-main)');
                ?>
                <div class="md-txn-item"
                    onclick="window.location='transactions/edit_transaction.php?id=<?php echo $row['id']; ?>'">
                    <div class="md-txn-icon" style="background: <?php echo $color; ?>15;">
                        <?php echo $icon_svg; ?>
                    </div>
                    <div class="md-txn-details">
                        <h4><?php echo $desc_text; ?></h4>
                        <p><?php echo $account_icon; ?> <?php echo htmlspecialchars($row['account_name']); ?><?php if ($isTransfer && strpos($row['description'], '→') !== false) { echo ' → ...'; } ?></p>
                    </div>
                    <div class="md-txn-meta">
                        <span class="md-txn-amount" style="color: <?php echo $amount_color; ?>">₹<?php echo number_format($row['amount'], 1); ?></span>
                        <span class="md-txn-date"><?php echo $date_label; ?></span>
                    </div>
                </div>
            <?php } ?>
        </div>

        <!-- Budgets Mobile Card -->
        <div class="md-section-header">
            <h3>Budgets</h3>
        </div>

        <div class="md-budget-card">
            <div class="md-budget-tabs">
                <button class="md-budget-tab active" data-tab="monthly">Monthly</button>
                <button class="md-budget-tab" data-tab="annual">Annual</button>
            </div>
            <div class="md-budget-content">
                <div class="md-budget-icon-wrapper">
                    <div class="md-budget-icon-circle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"></path>
                        </svg>
                    </div>
                </div>
                <?php if ($monthly_budget > 0) {
                    $pct = round(($monthly_expense / $monthly_budget) * 100);
                    ?>
                    <h4>Monthly Budget (<?php echo min($pct, 100); ?>%)</h4>
                    <p>₹<?php echo number_format($monthly_expense); ?> / ₹<?php echo number_format($monthly_budget); ?> spent</p>
                    <div class="progress-bar" style="margin-top: 12px;">
                        <div class="progress-fill <?php echo $pct > 100 ? 'danger' : ($pct > 75 ? 'warning' : ''); ?>" style="width: <?php echo min($pct, 100); ?>%"></div>
                    </div>
                <?php } else { ?>
                    <h4>No Budget Set</h4>
                    <p>Set a budget to track your spending goals.</p>
                <?php } ?>
                <a href="budgets/set_budget.php" class="md-budget-action-btn">
                    <?php echo $monthly_budget > 0 ? 'Edit Budget' : 'Set Up Budget'; ?>
                </a>
            </div>
        </div>

    </div>

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

<!-- Mobile Dashboard Scripts -->
<script>
    (function () {
        // === Dynamic Greeting ===
        var greetEl = document.getElementById('mobileGreeting');
        if (greetEl) {
            var h = new Date().getHours();
            greetEl.textContent = h < 12 ? 'Good Morning,' : h < 17 ? 'Good Afternoon,' : 'Good Evening,';
        }

        // === Mobile Theme Toggle ===
        var mobileThemeBtn = document.getElementById('mobileThemeBtn');
        if (mobileThemeBtn) {
            mobileThemeBtn.addEventListener('click', function () {
                var cur = document.documentElement.getAttribute('data-theme') || 'light';
                var next = cur === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('finance-theme', next);
            });
        }

        // === All Transactions Data ===
        var allTxns = <?php echo json_encode($all_transactions_json); ?>;

        // === Time Period Dropdown ===
        var dropdownBtn = document.getElementById('mdDropdownBtn');
        var dropdownMenu = document.getElementById('mdDropdownMenu');
        var spendingEl = document.getElementById('mdSpending');
        var incomeEl = document.getElementById('mdIncome');
        var netBalanceEl = document.getElementById('mdNetBalance');

        if (dropdownBtn && dropdownMenu) {
            dropdownBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('open');
            });

            document.addEventListener('click', function () {
                dropdownMenu.classList.remove('open');
            });

            dropdownMenu.querySelectorAll('.md-dropdown-item').forEach(function (item) {
                item.addEventListener('click', function (e) {
                    e.stopPropagation();
                    dropdownMenu.querySelectorAll('.md-dropdown-item').forEach(function (i) { i.classList.remove('active'); });
                    this.classList.add('active');
                    var period = this.dataset.period;
                    var label = this.querySelector('strong').textContent;
                    dropdownBtn.innerHTML = label + ' <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>';
                    dropdownMenu.classList.remove('open');
                    filterByPeriod(period);
                });
            });
        }

        function filterByPeriod(period) {
            var now = new Date();
            var spending = 0, income = 0;
            allTxns.forEach(function (t) {
                // Skip transfers — they should never affect spending/income
                if (t.type === 'Transfer') return;
                
                var d = new Date(t.transaction_date);
                var inRange = false;
                if (period === 'month') {
                    inRange = d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
                } else if (period === 'year') {
                    inRange = d.getFullYear() === now.getFullYear();
                } else {
                    inRange = true;
                }
                if (inRange) {
                    if (t.type === 'Expense') spending += parseFloat(t.amount);
                    else if (t.type === 'Income') income += parseFloat(t.amount);
                }
            });
            if (spendingEl) spendingEl.textContent = '₹' + spending.toLocaleString('en-IN');
            if (incomeEl) incomeEl.textContent = '₹' + income.toLocaleString('en-IN');
            if (netBalanceEl) netBalanceEl.textContent = '₹' + (income - spending).toLocaleString('en-IN');
        }

        // === Global Search ===
        var searchBtn = document.getElementById('mdSearchBtn');
        var searchOverlay = document.getElementById('mdSearchOverlay');
        var searchInput = document.getElementById('mdSearchInput');
        var searchClose = document.getElementById('mdSearchClose');
        var searchResults = document.getElementById('mdSearchResults');

        if (searchBtn && searchOverlay) {
            searchBtn.addEventListener('click', function () {
                searchOverlay.classList.add('open');
                setTimeout(function () { searchInput.focus(); }, 100);
            });
            searchClose.addEventListener('click', function () {
                searchOverlay.classList.remove('open');
                searchInput.value = '';
                searchResults.innerHTML = '';
            });
            searchInput.addEventListener('input', function () {
                var q = this.value.toLowerCase().trim();
                if (!q) { searchResults.innerHTML = ''; return; }
                var matches = allTxns.filter(function (t) {
                    return (t.description && t.description.toLowerCase().includes(q)) ||
                        (t.category_name && t.category_name.toLowerCase().includes(q)) ||
                        (t.amount && t.amount.toString().includes(q)) ||
                        (t.account_name && t.account_name.toLowerCase().includes(q));
                }).slice(0, 20);
                if (matches.length === 0) {
                    searchResults.innerHTML = '<div class="md-search-empty">No transactions found</div>';
                    return;
                }
                var html = '';
                matches.forEach(function (t) {
                    var color = t.type === 'Income' ? '#10b981' : (t.type === 'Transfer' ? '#3b82f6' : '#f59e0b');
                    var dateStr = new Date(t.transaction_date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: '2-digit' });
                    html += '<div class="md-txn-item" onclick="window.location=\'transactions/edit_transaction.php?id=' + t.id + '\'">';
                    html += '<div class="md-txn-icon" style="background: ' + color + '15;"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg></div>';
                    html += '<div class="md-txn-details"><h4>' + (t.description || t.category_name || 'Transaction') + '</h4>';
                    html += '<p>' + (t.account_name || '') + '</p></div>';
                    html += '<div class="md-txn-meta"><span class="md-txn-amount">₹' + parseFloat(t.amount).toLocaleString('en-IN') + '</span><span class="md-txn-date">' + dateStr + '</span></div></div>';
                });
                searchResults.innerHTML = html;
            });
        }

        // === Budget Tabs ===
        document.querySelectorAll('.md-budget-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.md-budget-tab').forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');
            });
        });
    })();
</script>

<?php include "includes/footer.php"; ?>