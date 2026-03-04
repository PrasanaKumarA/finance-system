<?php
include "../includes/auth.php";
include "../includes/db.php";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
    $type = $_POST['type'];

    mysqli_query($conn, "INSERT INTO categories (user_id, category_name, type) VALUES ($user_id, '$category_name', '$type')");

    header("Location: view_categories.php");
    exit();
}
?>
<div class="container">
    <h2>Add Category</h2>
    <form method="POST">
        <label>Category Name:</label><br>
        <input type="text" name="category_name" required><br><br>

        <label>Type:</label><br>
        <select name="type" required>
            <option value="Income">Income</option>
            <option value="Expense">Expense</option>
        </select><br><br>

        <button type="submit" class="btn"
            style="padding: 10px 15px; background: #2ecc71; color: white; text-decoration: none; border: none; border-radius: 4px; cursor: pointer;">Save
            Category</button>
    </form>
</div>
<?php include "../includes/footer.php"; ?>