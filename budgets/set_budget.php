<?php
include "../includes/auth.php";
include "../includes/db.php";
$page_title = "Budget Management";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$success_msg = "";

$current_month = intval(date('m'));
$current_year = intval(date('Y'));

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Overall monthly budget */
    $overall = floatval($_POST['overall_budget'] ?? 0);
    if ($overall > 0) {
        mysqli_query($conn, "
            INSERT INTO budgets (user_id, category_id, budget_amount, month, year)
            VALUES ($user_id, NULL, $overall, $current_month, $current_year)
            ON DUPLICATE KEY UPDATE budget_amount = $overall
        ");
    } else {
        mysqli_query($conn, "
            DELETE FROM budgets 
            WHERE user_id = $user_id AND category_id IS NULL 
            AND month = $current_month AND year = $current_year
        ");
    }

    /* Category budgets */
    if (isset($_POST['cat_budget']) && is_array($_POST['cat_budget'])) {
        foreach ($_POST['cat_budget'] as $cat_id => $amount) {
            $cat_id = intval($cat_id);
            $amount = floatval($amount);
            if ($amount > 0) {
                mysqli_query($conn, "
                    INSERT INTO budgets (user_id, category_id, budget_amount, month, year)
                    VALUES ($user_id, $cat_id, $amount, $current_month, $current_year)
                    ON DUPLICATE KEY UPDATE budget_amount = $amount
                ");
            } else {
                mysqli_query($conn, "
                    DELETE FROM budgets 
                    WHERE user_id = $user_id AND category_id = $cat_id
                    AND month = $current_month AND year = $current_year
                ");
            }
        }
    }

    $success_msg = "Budgets saved for " . date('F Y') . "!";
}

/* Load current budgets */
$overall_budget = 0;
$ob_query = mysqli_query($conn, "
    SELECT budget_amount FROM budgets
    WHERE user_id = $user_id AND category_id IS NULL
    AND month = $current_month AND year = $current_year
");
if ($ob_row = mysqli_fetch_assoc($ob_query)) {
    $overall_budget = $ob_row['budget_amount'];
}

/* Overall spent this month */
$spent_query = mysqli_query($conn, "
    SELECT IFNULL(SUM(amount), 0) as total_spent 
    FROM transactions
    WHERE user_id = $user_id AND type = 'Expense'
    AND MONTH(transaction_date) = $current_month
    AND YEAR(transaction_date) = $current_year
");
$total_spent = mysqli_fetch_assoc($spent_query)['total_spent'];

/* Load expense categories with budgets and spending */
$categories = mysqli_query($conn, "
    SELECT c.id, c.category_name,
           IFNULL(b.budget_amount, 0) as budget_amount,
           IFNULL((
               SELECT SUM(t.amount)
               FROM transactions t
               WHERE t.category_id = c.id
               AND t.user_id = $user_id
               AND t.type = 'Expense'
               AND MONTH(t.transaction_date) = $current_month
               AND YEAR(t.transaction_date) = $current_year
           ), 0) as category_spent
    FROM categories c
    LEFT JOIN budgets b ON b.category_id = c.id 
        AND b.user_id = $user_id 
        AND b.month = $current_month 
        AND b.year = $current_year
    WHERE c.user_id = $user_id AND c.type = 'Expense'
    ORDER BY c.category_name
");
?>

<div class="container">
    <h2>Budget Management —
        <?php echo date('F Y'); ?>
    </h2>

    <?php if ($success_msg) { ?>
        <div class="alert alert-success">
            <?php echo $success_msg; ?>
        </div>
    <?php } ?>

    <!-- Current Budget Overview -->
    <?php if ($overall_budget > 0) { ?>
        <div class="budget-card">
            <h3 style="margin-top:0;">Monthly Overview</h3>
            <?php
            $pct = $overall_budget > 0 ? round(($total_spent / $overall_budget) * 100) : 0;
            $bar_class = $pct > 100 ? 'danger' : ($pct > 75 ? 'warning' : '');
            ?>
            <div class="budget-item">
                <div class="budget-item-header">
                    <span class="budget-item-name">Overall Budget</span>
                    <span class="budget-item-amounts">
                        <strong>₹
                            <?php echo number_format($total_spent, 2); ?>
                        </strong>
                        / ₹
                        <?php echo number_format($overall_budget, 2); ?>
                        (
                        <?php echo min($pct, 999); ?>%)
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $bar_class; ?>" style="width: <?php echo min($pct, 100); ?>%">
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <form method="POST">
        <h3>Set Overall Monthly Budget</h3>
        <div class="form-row mb-2">
            <div class="form-group">
                <label>Monthly Budget (₹)</label>
                <input type="number" step="0.01" name="overall_budget"
                    value="<?php echo $overall_budget > 0 ? $overall_budget : ''; ?>" placeholder="E.g. 50000">
            </div>
        </div>

        <h3>Category Budgets</h3>

        <?php
        mysqli_data_seek($categories, 0);
        while ($cat = mysqli_fetch_assoc($categories)) {
            $cat_pct = $cat['budget_amount'] > 0 ? round(($cat['category_spent'] / $cat['budget_amount']) * 100) : 0;
            $cat_bar_class = $cat_pct > 100 ? 'danger' : ($cat_pct > 75 ? 'warning' : '');
            ?>
            <div class="budget-item" style="margin-bottom: 16px;">
                <div class="form-row" style="align-items: flex-end; margin-bottom: 6px;">
                    <div class="form-group">
                        <label>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </label>
                        <input type="number" step="0.01" name="cat_budget[<?php echo $cat['id']; ?>]"
                            value="<?php echo $cat['budget_amount'] > 0 ? $cat['budget_amount'] : ''; ?>"
                            placeholder="No budget set">
                    </div>
                    <div class="form-group">
                        <small style="color: var(--text-muted);">
                            Spent: ₹
                            <?php echo number_format($cat['category_spent'], 2); ?>
                            <?php if ($cat['budget_amount'] > 0)
                                echo " / ₹" . number_format($cat['budget_amount'], 2) . " ($cat_pct%)"; ?>
                        </small>
                    </div>
                </div>
                <?php if ($cat['budget_amount'] > 0) { ?>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $cat_bar_class; ?>"
                            style="width: <?php echo min($cat_pct, 100); ?>%"></div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <button type="submit" class="btn" style="width: 100%; margin-top: 12px;">💾 Save All Budgets</button>
    </form>
</div>

<?php include "../includes/footer.php"; ?>