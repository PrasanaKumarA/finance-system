<?php if ($_SESSION['role'] === 'Admin') { ?>
    <a href="/finance-system/admin/index.php">Admin Panel</a>
<?php } ?>
<div class="navbar">
    <a href="/finance-system/index.php">Dashboard</a>
    <a href="/finance-system/accounts/view_accounts.php">Accounts</a>
    <a href="/finance-system/categories/view_categories.php">Categories</a>
    <a href="/finance-system/transactions/add_transaction.php">Transaction</a>
    <a href="/finance-system/reports/index.php">Reports</a>
    <a href="/finance-system/logout.php">Logout</a>
</div>