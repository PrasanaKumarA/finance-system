<?php
include "includes/auth.php";
include "includes/db.php";
include "includes/functions.php";
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

    <h2>Dashboard</h2>

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

    <!-- PIE CHART -->
    <h3>Expense Distribution</h3>

    <?php if (!empty($category_labels)) { ?>
        <div class="card" style="margin-bottom:30px; width:100%; max-width: 500px; margin: 0 auto 30px auto;">
            <canvas id="expenseChart"></canvas>
        </div>
    <?php } else { ?>
        <p style="color:gray;">No expense data available.</p>
    <?php } ?>

    <!-- BAR CHART -->
    <h3>Income vs Expense (Last 6 Months)</h3>
    <div class="card" style="margin-bottom:40px;">
        <canvas id="barChart" style="max-height:350px;"></canvas>
    </div>

    <!-- RECENT TRANSACTIONS -->
    <h3>Recent Transactions</h3>
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
                <td><?php echo $row['account_name']; ?></td>
                <td><?php echo $row['category_name']; ?></td>
                <td><?php echo htmlspecialchars($row['description']); ?></td>
                <td><?php echo $row['type']; ?></td>
                <td style="color:<?php echo $row['type'] == 'Income' ? 'green' : 'red'; ?>">
                    ₹ <?php echo number_format($row['amount'], 2); ?>
                </td>
            </tr>
        <?php } ?>
    </table>

</div>

<script>
    <?php if (!empty($category_labels)) { ?>
        new Chart(document.getElementById('expenseChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($category_values); ?>,
                    backgroundColor: ['#e74c3c', '#3498db', '#f1c40f', '#2ecc71', '#9b59b6', '#1abc9c']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    <?php } ?>

    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($bar_labels); ?>,
            datasets: [
                {
                    label: 'Income',
                    backgroundColor: '#2ecc71',
                    data: <?php echo json_encode($bar_income); ?>
                },
                {
                    label: 'Expense',
                    backgroundColor: '#e74c3c',
                    data: <?php echo json_encode($bar_expense); ?>
                }
            ]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

<?php include "includes/footer.php"; ?>