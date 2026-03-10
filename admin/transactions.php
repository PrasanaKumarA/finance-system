<?php
include "../includes/auth.php";
include "../includes/db.php";
include "admin_auth.php";
$page_title = "All Transactions";
include "../includes/header.php";
include "../includes/navbar.php";

$selected_user = $_GET['user_id'] ?? '';

/* Get all users for dropdown */
$users = mysqli_query($conn, "SELECT id, name FROM users ORDER BY name ASC");

/* Base Query */
$query = "
    SELECT t.*, u.name as user_name, a.account_name, c.category_name
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN accounts a ON t.account_id = a.id
    LEFT JOIN categories c ON t.category_id = c.id
";

/* Filter by user if selected */
if (!empty($selected_user)) {
    $query .= " WHERE t.user_id = " . intval($selected_user);
}

$query .= " ORDER BY t.transaction_date DESC";

$result = mysqli_query($conn, $query);

/* Calculate totals */
$total_income = 0;
$total_expense = 0;

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
    if ($row['type'] == 'Income') {
        $total_income += $row['amount'];
    } else {
        $total_expense += $row['amount'];
    }
}

$net = $total_income - $total_expense;
?>

<div class="container">

    <h2>Admin — All Transactions</h2>

    <div class="filter-box mb-3">
        <form method="GET">
            <div class="form-row">
                <div class="form-group">
                    <label>Filter by User</label>
                    <select name="user_id">
                        <option value="">All Users</option>
                        <?php while ($u = mysqli_fetch_assoc($users)) { ?>
                            <option value="<?= $u['id'] ?>" <?= ($selected_user == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <button type="submit">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <div class="cards">
        <div class="card bank">
            <h3>Total Income</h3>
            <p>₹ <?= number_format($total_income, 2) ?></p>
        </div>

        <div class="card cash">
            <h3>Total Expense</h3>
            <p>₹ <?= number_format($total_expense, 2) ?></p>
        </div>

        <div class="card total">
            <h3>Net</h3>
            <p>₹ <?= number_format($net, 2) ?></p>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Account</th>
                <th>Category</th>
                <th>Type</th>
                <th>Amount</th>
            </tr>

            <?php foreach ($rows as $r) { ?>
                <tr>
                    <td><?= $r['transaction_date'] ?></td>
                    <td><?= htmlspecialchars($r['user_name']) ?></td>
                    <td><?= htmlspecialchars($r['account_name']) ?></td>
                    <td><?= htmlspecialchars($r['category_name'] ?? '-') ?></td>
                    <td>
                        <span class="badge <?= $r['type'] == 'Income' ? 'badge-income' : 'badge-expense' ?>">
                            <?= $r['type'] ?>
                        </span>
                    </td>
                    <td class="<?= $r['type'] == 'Income' ? 'text-success' : 'text-danger' ?>">
                        ₹ <?= number_format($r['amount'], 2) ?>
                    </td>
                </tr>
            <?php } ?>

        </table>
    </div>

</div>

<?php include "../includes/footer.php"; ?>