<?php
include "../includes/auth.php";
include "../includes/db.php";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id=$user_id ORDER BY type, category_name");
?>
<div class="container">
    <h2>Manage Categories</h2>
    <a href="add_category.php" class="btn"
        style="padding: 10px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 4px;">+ Add
        New Category</a>
    <br><br>
    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr style="background: #f4f4f4;">
            <th style="padding: 10px; border: 1px solid #ddd;">Name</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Type</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($categories)) { ?>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <?php echo htmlspecialchars($row['category_name']); ?>
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <?php echo $row['type']; ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php include "../includes/footer.php"; ?>