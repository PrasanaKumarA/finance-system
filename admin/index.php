<?php
include "../includes/auth.php";
include "../includes/db.php";
include "admin_auth.php";
include "../includes/header.php";
include "../includes/navbar.php";

/* TOTAL USERS */
$users_query = mysqli_query($conn, "SELECT COUNT(*) as total_users FROM users");
$total_users = mysqli_fetch_assoc($users_query)['total_users'];

/* TOTAL SYSTEM INCOME */
$income_query = mysqli_query($conn, "
    SELECT SUM(amount) as total_income 
    FROM transactions 
    WHERE type = 'Income'
");
$total_income = mysqli_fetch_assoc($income_query)['total_income'] ?? 0;

/* TOTAL SYSTEM EXPENSE */
$expense_query = mysqli_query($conn, "
    SELECT SUM(amount) as total_expense 
    FROM transactions 
    WHERE type = 'Expense'
");
$total_expense = mysqli_fetch_assoc($expense_query)['total_expense'] ?? 0;

$net = $total_income - $total_expense;
?>

<div class="container">

    <h2>👑 Admin Dashboard</h2>

    <div class="cards">

        <div class="card total">
            <h3>Total Users</h3>
            <p>
                <?= $total_users ?>
            </p>
        </div>

        <div class="card bank">
            <h3>Total System Income</h3>
            <p>₹
                <?= number_format($total_income, 2) ?>
            </p>
        </div>

        <div class="card cash">
            <h3>Total System Expense</h3>
            <p>₹
                <?= number_format($total_expense, 2) ?>
            </p>
        </div>

        <div class="card total">
            <h3>Net Profit</h3>
            <p>₹
                <?= number_format($net, 2) ?>
            </p>
        </div>

    </div>

    <br>

    <a href="users.php" class="btn">Manage Users</a>

</div>

<?php include "../includes/footer.php"; ?>