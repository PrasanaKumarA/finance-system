<?php
include "../includes/auth.php";
include "../includes/db.php";
$page_title = "Categories";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];

// Fetch categories grouped by type
$income_cats = mysqli_query($conn, "SELECT * FROM categories WHERE user_id=$user_id AND type='Income' ORDER BY category_name");
$expense_cats = mysqli_query($conn, "SELECT * FROM categories WHERE user_id=$user_id AND type='Expense' ORDER BY category_name");

$income_count = mysqli_num_rows($income_cats);
$expense_count = mysqli_num_rows($expense_cats);
?>
<div class="container">
    <div class="action-bar">
        <div>
            <h2>Manage Categories</h2>
            <p class="page-subtitle">Organize your income and expense categories</p>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo BASE_PATH; ?>/index.php" class="btn btn-secondary">← Back</a>
            <a href="add_category.php" class="btn">+ Add Category</a>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])) { ?>
        <div class="alert alert-success">✅ Category deleted successfully.</div>
    <?php } ?>
    <?php if (isset($_GET['error'])) { ?>
        <div class="alert alert-danger">⚠️ Category not found or you don't have permission to delete it.</div>
    <?php } ?>

    <!-- OVERVIEW CARDS -->
    <div class="cards" style="margin-bottom: 28px;">
        <div class="card" style="border-left: 4px solid var(--success);">
            <h3>💰 Income Categories</h3>
            <p><?php echo $income_count; ?></p>
        </div>
        <div class="card" style="border-left: 4px solid var(--danger);">
            <h3>💸 Expense Categories</h3>
            <p><?php echo $expense_count; ?></p>
        </div>
        <div class="card" style="border-left: 4px solid var(--primary);">
            <h3>📊 Total Categories</h3>
            <p><?php echo $income_count + $expense_count; ?></p>
        </div>
    </div>

    <!-- INCOME SECTION -->
    <div class="category-section">
        <div class="category-section-header">
            <div class="category-section-title">
                <span class="category-section-icon income">💰</span>
                <h3>Income Categories</h3>
                <span class="badge badge-income"><?php echo $income_count; ?></span>
            </div>
        </div>
        <div class="category-chips-grid">
            <?php if ($income_count === 0) { ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📂</div>
                    <p>No income categories yet</p>
                    <a href="add_category.php" class="btn btn-sm">+ Add Income Category</a>
                </div>
            <?php } else { ?>
                <?php while ($row = mysqli_fetch_assoc($income_cats)) { ?>
                    <div class="category-chip income">
                        <div class="category-chip-info">
                            <span class="category-chip-dot income"></span>
                            <span class="category-chip-name"><?php echo htmlspecialchars($row['category_name']); ?></span>
                        </div>
                        <a href="delete_category.php?id=<?php echo $row['id']; ?>" class="category-chip-delete"
                            title="Delete category"
                            onclick="return confirm('🗑️ Delete \'<?php echo htmlspecialchars(addslashes($row['category_name'])); ?>\'?\n\nThis action cannot be undone.')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                                <line x1="10" y1="11" x2="10" y2="17" />
                                <line x1="14" y1="11" x2="14" y2="17" />
                            </svg>
                        </a>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>

    <!-- EXPENSE SECTION -->
    <div class="category-section">
        <div class="category-section-header">
            <div class="category-section-title">
                <span class="category-section-icon expense">💸</span>
                <h3>Expense Categories</h3>
                <span class="badge badge-expense"><?php echo $expense_count; ?></span>
            </div>
        </div>
        <div class="category-chips-grid">
            <?php if ($expense_count === 0) { ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📂</div>
                    <p>No expense categories yet</p>
                    <a href="add_category.php" class="btn btn-sm">+ Add Expense Category</a>
                </div>
            <?php } else { ?>
                <?php while ($row = mysqli_fetch_assoc($expense_cats)) { ?>
                    <div class="category-chip expense">
                        <div class="category-chip-info">
                            <span class="category-chip-dot expense"></span>
                            <span class="category-chip-name"><?php echo htmlspecialchars($row['category_name']); ?></span>
                        </div>
                        <a href="delete_category.php?id=<?php echo $row['id']; ?>" class="category-chip-delete"
                            title="Delete category"
                            onclick="return confirm('🗑️ Delete \'<?php echo htmlspecialchars(addslashes($row['category_name'])); ?>\'?\n\nThis action cannot be undone.')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                                <line x1="10" y1="11" x2="10" y2="17" />
                                <line x1="14" y1="11" x2="14" y2="17" />
                            </svg>
                        </a>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
</div>
<?php include "../includes/footer.php"; ?>