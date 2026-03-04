<?php
include "../includes/auth.php";
include "../includes/db.php";
include "admin_auth.php";
$page_title = "System Analytics";
include "../includes/header.php";
include "../includes/navbar.php";

/* ================= SYSTEM TOTALS ================= */
$income_result = mysqli_query($conn, "
    SELECT IFNULL(SUM(amount),0) as total 
    FROM transactions 
    WHERE type='Income'
");
$total_income = mysqli_fetch_assoc($income_result)['total'];

$expense_result = mysqli_query($conn, "
    SELECT IFNULL(SUM(amount),0) as total 
    FROM transactions 
    WHERE type='Expense'
");
$total_expense = mysqli_fetch_assoc($expense_result)['total'];

/* ================= EXPENSE DISTRIBUTION ================= */
$cat_labels = [];
$cat_values = [];

$category_query = mysqli_query($conn, "
    SELECT c.category_name, SUM(t.amount) as total
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.type='Expense'
    GROUP BY t.category_id
");

while ($row = mysqli_fetch_assoc($category_query)) {
    $cat_labels[] = $row['category_name'];
    $cat_values[] = (float) $row['total'];
}

/* If no expense categories exist */
if (empty($cat_labels)) {
    $cat_labels = ['No Expense Data'];
    $cat_values = [1];
}

/* ================= USER CONTRIBUTION ================= */
$user_labels = [];
$user_values = [];

$user_query = mysqli_query($conn, "
    SELECT u.name, SUM(t.amount) as total
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE t.type='Income'
    GROUP BY t.user_id
");

while ($row = mysqli_fetch_assoc($user_query)) {
    $user_labels[] = $row['name'];
    $user_values[] = (float) $row['total'];
}

if (empty($user_labels)) {
    $user_labels = ['No Data'];
    $user_values = [0];
}
?>

<div class="container">
    <h2>System Analytics</h2>

    <div class="chart-container" style="max-width:800px;">
        <h3>Income vs Expense</h3>
        <canvas id="incomeExpenseChart"></canvas>
    </div>

    <div class="chart-container" style="max-width:600px;">
        <h3>Expense Distribution</h3>
        <canvas id="expenseChart"></canvas>
    </div>

    <div class="chart-container" style="max-width:800px;">
        <h3>Income by User</h3>
        <canvas id="userChart"></canvas>
    </div>
</div>

<script>
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const chartTextColor = isDark ? '#94a3b8' : '#6b7280';
    const chartGridColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(0,0,0,0.06)';

    Chart.defaults.color = chartTextColor;
    Chart.defaults.borderColor = chartGridColor;

    /* Income vs Expense */
    new Chart(document.getElementById('incomeExpenseChart'), {
        type: 'bar',
        data: {
            labels: ['Income', 'Expense'],
            datasets: [{
                label: 'System Summary',
                data: [<?= $total_income ?>, <?= $total_expense ?>],
                backgroundColor: ['#10b981', '#ef4444'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: chartGridColor } },
                x: { grid: { display: false } }
            }
        }
    });

    /* Expense Distribution */
    new Chart(document.getElementById('expenseChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($cat_labels) ?>,
            datasets: [{
                data: <?= json_encode($cat_values) ?>,
                backgroundColor: ['#6366f1', '#06b6d4', '#10b981', '#8b5cf6', '#f59e0b', '#ec4899'],
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

    /* User Contribution */
    new Chart(document.getElementById('userChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($user_labels) ?>,
            datasets: [{
                label: 'Income by User',
                data: <?= json_encode($user_values) ?>,
                backgroundColor: '#6366f1',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, grid: { color: chartGridColor } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php include "../includes/footer.php"; ?>