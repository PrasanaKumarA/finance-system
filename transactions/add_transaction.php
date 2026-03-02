<?php
include "../includes/auth.php";
include "../includes/db.php";

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $account_id = $_POST['account_id'];
    $category_id = $_POST['category_id'];
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $date = $_POST['transaction_date'];
    $desc = $_POST['description'];

    mysqli_query($conn, "
        INSERT INTO transactions 
        (user_id, account_id, category_id, type, amount, transaction_date, description)
        VALUES 
        ($user_id, $account_id, $category_id, '$type', $amount, '$date', '$desc')
    ");

    header("Location: ../index.php");
    exit();
}

$accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id=$user_id");
?>

<h2>Add Transaction</h2>

<form method="POST">

    <label>Account:</label><br>
    <select name="account_id" required>
        <?php while ($row = mysqli_fetch_assoc($accounts)) { ?>
            <option value="<?php echo $row['id']; ?>">
                <?php echo $row['account_name']; ?>
            </option>
        <?php } ?>
    </select><br><br>

    <label>Type:</label><br>
    <select name="type" id="type" required>
        <option value="Income">Income</option>
        <option value="Expense">Expense</option>
    </select><br><br>

    <label>Category:</label><br>
    <select name="category_id" id="category" required>
        <?php
        mysqli_data_seek($categories, 0);
        while ($cat = mysqli_fetch_assoc($categories)) { ?>
            <option value="<?php echo $cat['id']; ?>" data-type="<?php echo $cat['type']; ?>">
                <?php echo $cat['category_name']; ?>
            </option>
        <?php } ?>
    </select><br><br>

    <label>Amount:</label><br>
    <input type="number" name="amount" required><br><br>

    <label>Date:</label><br>
    <input type="date" name="transaction_date" required><br><br>

    <label>Description:</label><br>
    <input type="text" name="description"><br><br>

    <button type="submit">Add Transaction</button>

</form>

<script>
    const typeSelect = document.getElementById("type");
    const categorySelect = document.getElementById("category");

    function filterCategories() {
        const selectedType = typeSelect.value;
        const options = categorySelect.options;

        for (let i = 0; i < options.length; i++) {
            if (options[i].getAttribute("data-type") === selectedType) {
                options[i].style.display = "block";
            } else {
                options[i].style.display = "none";
            }
        }

        categorySelect.selectedIndex = 0;
    }

    typeSelect.addEventListener("change", filterCategories);
    window.onload = filterCategories;
</script>