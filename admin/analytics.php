<?php
include "../includes/auth.php";
include "../includes/db.php";
include "admin_auth.php";
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
    <h2>Admin System Analytics</h2>

    <div style="max-width:800px;margin:auto;">
        <canvas id="incomeExpenseChart"></canvas>
    </div>

    <br><br>

    <div style="max-width:600px;margin:auto;">
        <canvas id="expenseChart"></canvas>
    </div>

    <br><br>

    <div style="max-width:800px;margin:auto;">
        <canvas id="userChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    /* Income vs Expense */
    new Chart(document.getElementById('incomeExpenseChart'), {
        type: 'bar',
        data: {
            labels: ['Income', 'Expense'],
            datasets: [{
                label: 'System Summary',
                data: [<?= $total_income ?>, <?= $total_expense ?>],
                backgroundColor: ['#2ecc71', '#e74c3c']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            }
        }
    });

    /* Expense Distribution */
    new Chart(document.getElementById('expenseChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($cat_labels) ?>,
            datasets: [{
                data: <?= json_encode($cat_values) ?>,
                backgroundColor: [
                    '#e74c3c',
                    '#3498db',
                    '#2ecc71',
                    '#9b59b6',
                    '#f1c40f',
                    '#1abc9c'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
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
                backgroundColor: '#3498db'
            }]
        },
        options: {
            responsive: true
        }
    });

</script>

<?php include "../includes/footer.php"; ?>