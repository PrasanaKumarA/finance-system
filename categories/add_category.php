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
    <h2>Add New Category</h2>

    <?php if ($success_msg) { ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php } ?>

    <form method="POST">
        <label>Category Name</label>
        <input type="text" name="category_name" placeholder="E.g. Groceries, Salary, Utilities..." required>

        <label>Category Type</label>
        <select name="type" required>
            <option value="Income">Income</option>
            <option value="Expense">Expense</option>
        </select>

        <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 8px;">+ Save Category</button>
    </form>
</div>
<?php include "../includes/footer.php"; ?>