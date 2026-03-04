<?php
include "../includes/auth.php";
include "../includes/db.php";
$page_title = "Categories";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id=$user_id ORDER BY type, category_name");
?>
<div class="container">
    <div class="action-bar">
        <h2>Manage Categories</h2>
        <div class="flex gap-2">
            <a href="/finance-system/index.php" class="btn btn-secondary">← Back</a>
            <a href="add_category.php" class="btn">+ Add New Category</a>
        </div>
    </div>

    <table>
        <tr>
            <th>Name</th>
            <th>Type</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($categories)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                <td>
                    <span class="badge <?php echo $row['type'] == 'Income' ? 'badge-income' : 'badge-expense'; ?>">
                        <?php echo $row['type']; ?>
                    </span>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php include "../includes/footer.php"; ?>