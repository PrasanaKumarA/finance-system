<?php
include "../includes/auth.php";
include "../includes/db.php";
include "../includes/header.php";
include "../includes/navbar.php";

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['ajax_action'])) {

    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $date = $_POST['transaction_date'];
    $desc = mysqli_real_escape_string($conn, $_POST['description']);

    if ($type === 'Transfer') {
        $from_account = $_POST['account_id'];
        $to_account = $_POST['to_account_id'];

        if ($from_account == $to_account) {
            $error_msg = "Cannot transfer to the same account.";
        } else {
            // Deduct from source
            mysqli_query($conn, "INSERT INTO transactions 
            (user_id, account_id, type, amount, transaction_date, description)
            VALUES ($user_id, $from_account, 'Expense', $amount, '$date', 'Transfer Out to Account #$to_account - $desc')");

            // Add to destination
            mysqli_query($conn, "INSERT INTO transactions 
            (user_id, account_id, type, amount, transaction_date, description)
            VALUES ($user_id, $to_account, 'Income', $amount, '$date', 'Transfer In from Account #$from_account - $desc')");

            $success_msg = "Transfer Successful!";
        }
    } else {
        $account_id = $_POST['account_id'];
        $category_id = $_POST['category_id'] ?? 'NULL';

        if ($category_id === '')
            $category_id = 'NULL';

        mysqli_query($conn, "
            INSERT INTO transactions 
            (user_id, account_id, category_id, type, amount, transaction_date, description)
            VALUES 
            ($user_id, $account_id, $category_id, '$type', $amount, '$date', '$desc')
        ");
        $success_msg = "Transaction Added Successfully!";
    }
}

$accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
$to_accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id=$user_id");
?>

<div class="container">
    <h2>Add Transaction / Transfer</h2>

    <?php if ($error_msg)
        echo "<p style='color:red;'>$error_msg</p>"; ?>
    <?php if ($success_msg)
        echo "<p style='color:green;'>$success_msg</p>"; ?>

    <form method="POST">

        <label>Type:</label><br>
        <select name="type" id="type" required>
            <option value="Income">Income</option>
            <option value="Expense">Expense</option>
            <option value="Transfer">Transfer</option>
        </select><br><br>

        <label id="account_label">Account:</label><br>
        <select name="account_id" id="account_id" required>
            <?php while ($row = mysqli_fetch_assoc($accounts)) { ?>
                <option value="<?php echo $row['id']; ?>">
                    <?php echo $row['account_name']; ?>
                </option>
            <?php } ?>
        </select><br><br>

        <div id="transfer_fields" style="display:none;">
            <label>To Account:</label><br>
            <select name="to_account_id" id="to_account_id">
                <?php while ($row = mysqli_fetch_assoc($to_accounts)) { ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo $row['account_name']; ?>
                    </option>
                <?php } ?>
            </select><br><br>
        </div>

        <div id="category_section">
            <label>Category:</label>
            <span style="font-size: 0.9em; margin-left:10px; cursor: pointer; color: blue; text-decoration: underline;"
                onclick="toggleAddCategory()">+ Add New Category</span><br>

            <div id="add_category_form"
                style="display:none; background:#f9f9f9; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:4px;">
                <input type="text" id="new_category_name" placeholder="Category Name" style="padding:5px;">
                <button type="button" onclick="addNewCategory()" style="padding:5px;">Save</button>
                <span id="cat_msg" style="margin-left: 10px;"></span>
            </div>

            <select name="category_id" id="category">
                <option value="">-- Select Category --</option>
                <?php
                mysqli_data_seek($categories, 0);
                while ($cat = mysqli_fetch_assoc($categories)) { ?>
                    <option value="<?php echo $cat['id']; ?>" data-type="<?php echo $cat['type']; ?>">
                        <?php echo $cat['category_name']; ?>
                    </option>
                <?php } ?>
            </select><br><br>
        </div>

        <label>Amount:</label><br>
        <input type="number" step="0.01" name="amount" required><br><br>

        <label>Date:</label><br>
        <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required><br><br>

        <label>Description:</label><br>
        <input type="text" name="description"><br><br>

        <button type="submit" class="btn">Save Transaction</button>

    </form>
</div>

<script>
    const typeSelect = document.getElementById("type");
    const categorySection = document.getElementById("category_section");
    const categorySelect = document.getElementById("category");
    const transferFields = document.getElementById("transfer_fields");
    const accountLabel = document.getElementById("account_label");
    const toAccountSelect = document.getElementById("to_account_id");

    function updateFormVisibility() {
        const selectedType = typeSelect.value;

        if (selectedType === 'Transfer') {
            categorySection.style.display = 'none';
            transferFields.style.display = 'block';
            accountLabel.innerText = 'From Account:';
            categorySelect.removeAttribute('required');
        } else {
            categorySection.style.display = 'block';
            transferFields.style.display = 'none';
            accountLabel.innerText = 'Account:';
            categorySelect.setAttribute('required', 'required');
            filterCategories();
        }
    }

    function filterCategories() {
        const selectedType = typeSelect.value;
        const options = categorySelect.options;

        let firstVisible = -1;
        for (let i = 0; i < options.length; i++) {
            if (options[i].value === "") {
                continue; // Always show placeholder
            }

            if (options[i].getAttribute("data-type") === selectedType) {
                options[i].style.display = "block";
                if (firstVisible === -1) firstVisible = i;
            } else {
                options[i].style.display = "none";
            }
        }

        categorySelect.value = "";
    }

    function toggleAddCategory() {
        const form = document.getElementById('add_category_form');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    function addNewCategory() {
        const nameInput = document.getElementById('new_category_name');
        const name = nameInput.value.trim();
        const type = typeSelect.value;
        const msgSpan = document.getElementById('cat_msg');

        if (!name) {
            msgSpan.innerText = "Name is required";
            msgSpan.style.color = "red";
            return;
        }

        if (type === 'Transfer') {
            msgSpan.innerText = "Cannot add category for transfer";
            msgSpan.style.color = "red";
            return;
        }

        msgSpan.innerText = "Saving...";
        msgSpan.style.color = "blue";

        const formData = new FormData();
        formData.append('category_name', name);
        formData.append('type', type);

        fetch('../categories/api_add_category.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add to dropdown
                    const newOption = document.createElement("option");
                    newOption.value = data.id;
                    newOption.text = data.category_name;
                    newOption.setAttribute("data-type", data.type);
                    categorySelect.add(newOption);

                    // Select and hide form
                    categorySelect.value = data.id;
                    nameInput.value = "";
                    toggleAddCategory();
                    msgSpan.innerText = "";
                } else {
                    msgSpan.innerText = data.error;
                    msgSpan.style.color = "red";
                }
            })
            .catch(err => {
                msgSpan.innerText = "Error saving category.";
                msgSpan.style.color = "red";
            });
    }

    typeSelect.addEventListener("change", updateFormVisibility);
    window.onload = updateFormVisibility;
</script>

<?php include "../includes/footer.php"; ?>