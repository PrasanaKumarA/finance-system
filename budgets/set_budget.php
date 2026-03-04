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

/* Handle Quick Add Category */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_add_category'])) {
    $cat_name = mysqli_real_escape_string($conn, trim($_POST['new_category_name']));
    $cat_type = mysqli_real_escape_string($conn, $_POST['new_category_type']);
    if (!empty($cat_name)) {
        mysqli_query($conn, "INSERT INTO categories (user_id, category_name, type) VALUES ($user_id, '$cat_name', '$cat_type')");
        $success_msg = "Category '$cat_name' added! You can now set a budget for it below.";
    }
}

/* Handle budget form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_budgets'])) {

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

/* Load current overall budget */
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

/* Load ALL user categories with budgets and spending */
$categories = mysqli_query($conn, "
    SELECT c.id, c.category_name, c.type as category_type,
           IFNULL(b.budget_amount, 0) as budget_amount,
           IFNULL((
               SELECT SUM(t.amount)
               FROM transactions t
               WHERE t.category_id = c.id
               AND t.user_id = $user_id
               AND MONTH(t.transaction_date) = $current_month
               AND YEAR(t.transaction_date) = $current_year
           ), 0) as category_spent
    FROM categories c
    LEFT JOIN budgets b ON b.category_id = c.id 
        AND b.user_id = $user_id 
        AND b.month = $current_month 
        AND b.year = $current_year
    WHERE c.user_id = $user_id
    ORDER BY c.type ASC, c.category_name ASC
");

$cat_count = mysqli_num_rows($categories);
?>

<div class="container">
    <div class="action-bar">
        <h2>Budget Management — <?php echo date('F Y'); ?></h2>
        <a href="/finance-system/index.php" class="btn btn-secondary">← Back</a>
    </div>

    <?php if ($success_msg) { ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php } ?>

    <!-- Budget Overview -->
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
                        <strong>₹<?php echo number_format($total_spent, 2); ?></strong>
                        / ₹<?php echo number_format($overall_budget, 2); ?>
                        (<?php echo min($pct, 999); ?>%)
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $bar_class; ?>" style="width: <?php echo min($pct, 100); ?>%">
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- Budget Form -->
    <form method="POST">
        <input type="hidden" name="save_budgets" value="1">

        <h3>Set Overall Monthly Budget</h3>
        <div class="form-row mb-2">
            <div class="form-group">
                <label>Monthly Budget (₹)</label>
                <input type="number" step="0.01" name="overall_budget"
                    value="<?php echo $overall_budget > 0 ? $overall_budget : ''; ?>" placeholder="E.g. 50000">
            </div>
        </div>

        <div class="action-bar" style="margin-bottom: 16px;">
            <h3 style="margin:0;">Category Budgets</h3>
        </div>

        <?php if ($cat_count > 0) {
            mysqli_data_seek($categories, 0);
            $current_type = '';
            while ($cat = mysqli_fetch_assoc($categories)) {
                if ($cat['category_type'] !== $current_type) {
                    $current_type = $cat['category_type'];
                    echo '<div style="margin: 12px 0 8px;"><span class="badge ' . ($current_type === 'Income' ? 'badge-income' : 'badge-expense') . '">' . $current_type . ' Categories</span></div>';
                }
                $cat_pct = $cat['budget_amount'] > 0 ? round(($cat['category_spent'] / $cat['budget_amount']) * 100) : 0;
                $cat_bar_class = $cat_pct > 100 ? 'danger' : ($cat_pct > 75 ? 'warning' : '');
                ?>
                <div class="budget-item" style="margin-bottom: 16px;">
                    <div class="form-row" style="align-items: flex-end; margin-bottom: 6px;">
                        <div class="form-group">
                            <label><?php echo htmlspecialchars($cat['category_name']); ?></label>
                            <input type="number" step="0.01" name="cat_budget[<?php echo $cat['id']; ?>]"
                                value="<?php echo $cat['budget_amount'] > 0 ? $cat['budget_amount'] : ''; ?>"
                                placeholder="No budget set">
                        </div>
                        <div class="form-group">
                            <small style="color: var(--text-muted);">
                                Spent: ₹<?php echo number_format($cat['category_spent'], 2); ?>
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
            <?php }
        } else { ?>
            <p class="text-muted">No categories found. Add a category below to start budgeting.</p>
        <?php } ?>

        <button type="submit" class="btn" style="width: 100%; margin-top: 12px;">💾 Save All Budgets</button>
    </form>

    <!-- Quick Add Category -->
    <div class="budget-card" style="margin-top: 28px;">
        <h3 style="margin-top:0;">⚡ Quick Add Category</h3>
        <form method="POST">
            <input type="hidden" name="quick_add_category" value="1">
            <div class="form-row mb-2">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="new_category_name" placeholder="E.g. Groceries, Utilities..." required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="new_category_type" required>
                        <option value="Expense">Expense</option>
                        <option value="Income">Income</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-success" style="width: 100%;">+ Add Category</button>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>