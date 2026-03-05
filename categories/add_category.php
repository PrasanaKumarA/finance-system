<?php
include "../includes/auth.php";
include "../includes/db.php";
$page_title = "Add Category";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
    $type = $_POST['type'];

    if (!empty($category_name)) {
        mysqli_query($conn, "INSERT INTO categories (user_id, category_name, type) VALUES ($user_id, '$category_name', '$type')");
        $success_msg = "Category successfully created!";
    }
}
?>
<div class="container">
    <div class="action-bar">
        <div>
            <h2>Add New Category</h2>
            <p class="page-subtitle">Create a new income or expense category</p>
        </div>
        <a href="view_categories.php" class="btn btn-secondary">← Back to Categories</a>
    </div>

    <?php if ($success_msg) { ?>
        <div class="alert alert-success">✅ <?php echo $success_msg; ?></div>
    <?php } ?>

    <form method="POST" class="category-form">
        <div class="form-section">
            <label>Category Name</label>
            <div class="input-with-icon">
                <span class="input-icon">🏷️</span>
                <input type="text" name="category_name" placeholder="E.g. Groceries, Salary, Utilities..." required>
            </div>
        </div>

        <div class="form-section">
            <label>Category Type</label>
            <div class="category-type-selector">
                <input type="radio" name="type" value="Income" id="type-income" checked>
                <label for="type-income" class="type-card income">
                    <span class="type-card-icon">💰</span>
                    <span class="type-card-title">Income</span>
                    <span class="type-card-desc">Money received</span>
                </label>

                <input type="radio" name="type" value="Expense" id="type-expense">
                <label for="type-expense" class="type-card expense">
                    <span class="type-card-icon">💸</span>
                    <span class="type-card-title">Expense</span>
                    <span class="type-card-desc">Money spent</span>
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 8px; padding: 14px;">
            ✓ Save Category
        </button>
    </form>
</div>
<?php include "../includes/footer.php"; ?>