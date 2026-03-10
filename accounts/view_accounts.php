<?php
include "../includes/auth.php";
include "../includes/db.php";
include "../includes/functions.php";
$page_title = "Accounts";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Admins should only see their own accounts here. 
// Global views are isolated in the Admin Panel.
$query = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");

$type_icons = [
    'Bank' => '🏦',
    'Cash' => '💵',
    'Mobile Wallet' => '📱',
    'Credit Card' => '💳'
];

// Pre-calculate all balances and group by type for mobile view
$accounts_data = [];
$total_balance = 0;
$credit_balance = 0;
$grouped = [];

mysqli_data_seek($query, 0);
while ($row = mysqli_fetch_assoc($query)) {
    $balance = getAccountBalance($conn, $row['id']);
    $row['calculated_balance'] = $balance;
    $accounts_data[] = $row;
    $total_balance += $balance;
    if ($row['account_type'] === 'Credit Card') {
        $credit_balance += $balance;
    }
    $type = $row['account_type'] ?? 'Other';
    if (!isset($grouped[$type]))
        $grouped[$type] = [];
    $grouped[$type][] = $row;
}
?>

<div class="container">

    <!-- DESKTOP VIEW -->
    <div class="desktop-accounts">
        <div class="action-bar">
            <h2>Accounts</h2>
            <a href="add_account.php" class="btn">+ Add New Account</a>
        </div>

        <?php if (isset($_GET['deleted'])) { ?>
            <div class="alert alert-success">Account and all related transactions have been deleted.</div>
        <?php } ?>
        <?php if (isset($_GET['error'])) { ?>
            <div class="alert alert-danger">Account not found or you don't have permission to delete it.</div>
        <?php } ?>

        <div class="table-wrapper">
            <table>
                <tr>
                    <th>Account Name</th>
                    <th>Type</th>
                    <th>Available Balance</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($accounts_data as $row) {
                    $balance = $row['calculated_balance'];
                    $icon = $type_icons[$row['account_type']] ?? '';
                    ?>
                    <tr>
                        <td><a
                                href="account_details.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['account_name']); ?></a>
                        </td>
                        <td><?php echo $icon . ' ' . htmlspecialchars($row['account_type'] ?? '—'); ?></td>
                        <td>₹ <?php echo number_format($balance, 2); ?></td>
                        <td>
                            <a href="account_details.php?id=<?php echo $row['id']; ?>" class="action-link">View</a>
                            <a href="delete_account.php?id=<?php echo $row['id']; ?>" class="action-link danger"
                                onclick="return confirm('⚠️ Delete this account?\n\nAll transactions linked to this account will also be permanently deleted. This cannot be undone.')">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>

    <!-- MOBILE VIEW (Screenshot-5 Design) -->
    <div class="mobile-accounts">
        <?php if (isset($_GET['deleted'])) { ?>
            <div class="alert alert-success">Account deleted successfully.</div>
        <?php } ?>

        <div class="mac-header">
            <h2>All Accounts
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            </h2>
            <a href="add_account.php" class="mac-add-btn">+ Add account</a>
        </div>

        <p class="mac-subtext">Transactions based balance, actual may vary.</p>

        <div class="mac-balance-toggle">
            <span>Show balance</span>
            <div class="mac-toggle-switch active" id="macBalanceToggle"></div>
        </div>

        <!-- Summary Cards -->
        <div class="mac-summary-row">
            <div class="mac-summary-card">
                <div class="mac-summary-card-label">
                    Available Balance
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                </div>
                <div class="mac-summary-card-value mac-bal-text">₹<?php echo number_format($total_balance, 1); ?></div>
            </div>
            <div class="mac-summary-card">
                <div class="mac-summary-card-label">
                    Available Credit
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                </div>
                <div class="mac-summary-card-value mac-bal-text">₹<?php echo number_format($credit_balance, 1); ?></div>
            </div>
        </div>

        <!-- Grouped Account Items -->
        <?php foreach ($grouped as $type => $accs) {
            $groupIcon = $type_icons[$type] ?? '📁';
            ?>
            <div class="mac-group">
                <div class="mac-group-label">
                    <?php echo $groupIcon; ?>     <?php echo htmlspecialchars($type); ?>
                </div>
                <?php foreach ($accs as $acc) { ?>
                    <a href="account_details.php?id=<?php echo $acc['id']; ?>" class="mac-item">
                        <span class="mac-item-name"><?php echo htmlspecialchars($acc['account_name']); ?></span>
                        <span class="mac-item-right">
                            <span
                                class="mac-item-amount mac-bal-text">₹<?php echo number_format($acc['calculated_balance'], 1); ?></span>
                            <svg class="mac-item-chevron" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </span>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

</div>

<script>
    // Show/hide balance toggle
    (function () {
        var toggle = document.getElementById('macBalanceToggle');
        if (!toggle) return;
        toggle.addEventListener('click', function () {
            this.classList.toggle('active');
            var balTexts = document.querySelectorAll('.mac-bal-text');
            var show = this.classList.contains('active');
            balTexts.forEach(function (el) {
                if (show) {
                    el.classList.remove('hidden-bal');
                } else {
                    el.classList.add('hidden-bal');
                }
            });
        });
    })();
</script>

<?php include "../includes/footer.php"; ?>