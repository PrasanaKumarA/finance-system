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
    } elseif ($row['type'] == 'Expense') {
        $total_expense += $row['amount'];
    }
    // Transfers are excluded from income/expense totals
}

$net = $total_income - $total_expense;

/* ===== ALL TRANSACTIONS for mobile JS ===== */
$all_report_txns = mysqli_query($conn, "
    SELECT t.id, t.amount, t.type, t.description, t.transaction_date,
           a.account_name, c.category_name
    FROM transactions t
    JOIN accounts a ON t.account_id = a.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = $user_id
    ORDER BY t.transaction_date DESC
");
$report_txns_json = [];
while ($rt = mysqli_fetch_assoc($all_report_txns)) {
    $report_txns_json[] = $rt;
}

/* ===== GET ACCOUNTS & CATEGORIES ===== */
$accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
$categories = mysqli_query($conn, "SELECT * FROM categories");
?>

<div class="container">

    <!-- DESKTOP VIEW -->
    <div class="desktop-reports">
        <div class="action-bar">
            <h2>Reports & Analytics</h2>
            <a href="<?php echo BASE_PATH; ?>/index.php" class="btn btn-secondary">← Back</a>
        </div>

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
                            <option value="Transfer" <?= $type == 'Transfer' ? 'selected' : '' ?>>Transfer</option>
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

        <div class="table-wrapper">
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
                                <span class="badge <?= $r['type'] == 'Income' ? 'badge-income' : ($r['type'] == 'Transfer' ? 'badge-transfer' : 'badge-expense') ?>">
                                    <?= $r['type'] ?>
                                </span>
                            </td>
                            <td class="<?= $r['type'] == 'Income' ? 'text-success' : ($r['type'] == 'Transfer' ? 'text-warning' : 'text-danger') ?>">
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
    </div>

    <!-- MOBILE ANALYSIS VIEW -->
    <div class="mobile-analysis">
        <!-- Header -->
        <div class="ma-header">
            <h1>Analysis</h1>
            <a href="export.php?format=pdf" class="ma-export-btn" title="Export">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
            </a>
        </div>

        <!-- Period Tabs -->
        <div class="ma-tabs">
            <button class="ma-tab" data-period="week">Week</button>
            <button class="ma-tab active" data-period="month">Month</button>
            <button class="ma-tab" data-period="year">Year</button>
            <button class="ma-tab" data-period="custom">Custom</button>
        </div>

        <!-- Month Navigator -->
        <div class="ma-month-nav" id="maMonthNav">
            <button class="ma-nav-arrow" id="maPrev" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <div class="ma-period-label" id="maPeriodLabel"><?php echo date('F Y'); ?></div>
            <button class="ma-nav-arrow" id="maNext" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>

        <!-- Custom Date Range (hidden by default) -->
        <div class="ma-custom-range" id="maCustomRange" style="display:none;">
            <input type="date" id="maCustomFrom" class="ma-date-input">
            <span>to</span>
            <input type="date" id="maCustomTo" class="ma-date-input">
            <button type="button" id="maCustomApply" class="btn btn-sm">Apply</button>
        </div>

        <!-- Summary -->
        <div class="ma-summary" id="maSummary">
            <div class="ma-summary-item income">
                <span class="ma-summary-label">Income</span>
                <span class="ma-summary-value" id="maIncome">₹0</span>
            </div>
            <div class="ma-summary-item expense">
                <span class="ma-summary-label">Expense</span>
                <span class="ma-summary-value" id="maExpense">₹0</span>
            </div>
            <div class="ma-summary-item balance">
                <span class="ma-summary-label">Balance</span>
                <span class="ma-summary-value" id="maBalance">₹0</span>
            </div>
        </div>

        <!-- Transaction List -->
        <div class="ma-txn-header">
            <h3>Transactions</h3>
        </div>
        <div class="md-txn-list" id="maTxnList">
            <div class="md-search-empty">Select a period to view transactions</div>
        </div>
    </div>

</div>

<script>
    function setDateRange(range) {
        const today = new Date();
        let fromDate, toDate;

        if (range === 'week') {
            fromDate = new Date(today);
            const day = fromDate.getDay();
            const diff = day === 0 ? 6 : day - 1;
            fromDate.setDate(fromDate.getDate() - diff);
            toDate = new Date(fromDate);
            toDate.setDate(toDate.getDate() + 6);
        } else if (range === 'month') {
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
            toDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        } else if (range === 'year') {
            fromDate = new Date(today.getFullYear(), 0, 1);
            toDate = new Date(today.getFullYear(), 11, 31);
        } else if (range === 'clear') {
            document.getElementById('from_date').value = '';
            document.getElementById('to_date').value = '';
            document.getElementById('filter_form').submit();
            return;
        }

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

<!-- Mobile Analysis JS -->
<script>
    (function () {
        var allTxns = <?php echo json_encode($report_txns_json); ?>;
        var currentPeriod = 'month';
        var currentMonth = new Date().getMonth();
        var currentYear = new Date().getFullYear();
        var currentWeekStart = getWeekStart(new Date());

        function getWeekStart(d) {
            var day = d.getDay();
            var diff = day === 0 ? 6 : day - 1;
            var ws = new Date(d);
            ws.setDate(ws.getDate() - diff);
            return ws;
        }

        var tabs = document.querySelectorAll('.ma-tab');
        var periodLabel = document.getElementById('maPeriodLabel');
        var prevBtn = document.getElementById('maPrev');
        var nextBtn = document.getElementById('maNext');
        var monthNav = document.getElementById('maMonthNav');
        var customRange = document.getElementById('maCustomRange');

        if (!tabs.length) return;

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.remove('active'); });
                this.classList.add('active');
                currentPeriod = this.dataset.period;
                if (currentPeriod === 'custom') {
                    customRange.style.display = 'flex';
                    monthNav.style.display = 'none';
                } else {
                    customRange.style.display = 'none';
                    monthNav.style.display = 'flex';
                }
                updateView();
            });
        });

        if (prevBtn) prevBtn.addEventListener('click', function () { navigate(-1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { navigate(1); });

        var applyBtn = document.getElementById('maCustomApply');
        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                updateView();
            });
        }

        function navigate(dir) {
            if (currentPeriod === 'month') {
                currentMonth += dir;
                if (currentMonth > 11) { currentMonth = 0; currentYear++; }
                if (currentMonth < 0) { currentMonth = 11; currentYear--; }
            } else if (currentPeriod === 'year') {
                currentYear += dir;
            } else if (currentPeriod === 'week') {
                currentWeekStart.setDate(currentWeekStart.getDate() + (dir * 7));
            }
            updateView();
        }

        function updateView() {
            var filtered = filterTransactions();
            updateLabel();
            updateSummary(filtered);
            updateList(filtered);
        }

        function filterTransactions() {
            return allTxns.filter(function (t) {
                var d = new Date(t.transaction_date);
                if (currentPeriod === 'month') {
                    return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
                } else if (currentPeriod === 'year') {
                    return d.getFullYear() === currentYear;
                } else if (currentPeriod === 'week') {
                    var weekEnd = new Date(currentWeekStart);
                    weekEnd.setDate(weekEnd.getDate() + 6);
                    return d >= currentWeekStart && d <= weekEnd;
                } else if (currentPeriod === 'custom') {
                    var fromVal = document.getElementById('maCustomFrom').value;
                    var toVal = document.getElementById('maCustomTo').value;
                    if (!fromVal || !toVal) return true;
                    return d >= new Date(fromVal) && d <= new Date(toVal + 'T23:59:59');
                }
                return true;
            });
        }

        function updateLabel() {
            var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            if (currentPeriod === 'month') {
                periodLabel.textContent = months[currentMonth] + ' ' + currentYear;
            } else if (currentPeriod === 'year') {
                periodLabel.textContent = currentYear;
            } else if (currentPeriod === 'week') {
                var ws = currentWeekStart;
                var we = new Date(ws);
                we.setDate(we.getDate() + 6);
                periodLabel.textContent = ws.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }) + ' - ' + we.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            } else if (currentPeriod === 'custom') {
                periodLabel.textContent = 'Custom Range';
            }
        }

        function updateSummary(txns) {
            var income = 0, expense = 0;
            txns.forEach(function (t) {
                if (t.type === 'Income') income += parseFloat(t.amount);
                else if (t.type === 'Expense') expense += parseFloat(t.amount);
                // Transfers are excluded from income/expense totals
            });
            var incEl = document.getElementById('maIncome');
            var expEl = document.getElementById('maExpense');
            var balEl = document.getElementById('maBalance');
            if (incEl) incEl.textContent = '₹' + income.toLocaleString('en-IN');
            if (expEl) expEl.textContent = '₹' + expense.toLocaleString('en-IN');
            if (balEl) balEl.textContent = '₹' + (income - expense).toLocaleString('en-IN');
        }

        function updateList(txns) {
            var list = document.getElementById('maTxnList');
            if (!list) return;
            if (txns.length === 0) {
                list.innerHTML = '<div class="md-search-empty">No transactions for this period</div>';
                return;
            }
            var html = '';
            txns.forEach(function (t) {
                var color = t.type === 'Income' ? '#10b981' : (t.type === 'Transfer' ? '#3b82f6' : '#f59e0b');
                var badgeClass = t.type === 'Income' ? 'badge-income' : (t.type === 'Transfer' ? 'badge-transfer' : 'badge-expense');
                var dateStr = new Date(t.transaction_date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: '2-digit' });
                html += '<div class="md-txn-item">';
                html += '<div class="md-txn-icon" style="background:' + color + '15;"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg></div>';
                html += '<div class="md-txn-details"><h4>' + (t.description || t.category_name || 'Transaction') + '</h4>';
                html += '<p>₹' + parseFloat(t.amount).toLocaleString('en-IN') + '</p></div>';
                html += '<div class="md-txn-meta"><span class="md-txn-date">' + dateStr + '</span>';
                html += '<span class="badge ' + badgeClass + '" style="font-size:10px;padding:2px 6px;">' + t.type + '</span></div></div>';
            });
            list.innerHTML = html;
        }

        // Initial load
        updateView();
    })();
</script>

<?php include "../includes/footer.php"; ?>