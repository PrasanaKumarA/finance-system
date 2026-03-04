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
            mysqli_query($conn, "INSERT INTO transactions 
            (user_id, account_id, type, amount, transaction_date, description)
            VALUES ($user_id, $from_account, 'Expense', $amount, '$date', 'Transfer Out to Account #$to_account - $desc')");

            mysqli_query($conn, "INSERT INTO transactions 
            (user_id, account_id, type, amount, transaction_date, description)
            VALUES ($user_id, $to_account, 'Income', $amount, '$date', 'Transfer In from Account #$from_account - $desc')");

            $success_msg = "Transfer successfully recorded!";
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
        $success_msg = "Transaction successfully recorded!";
    }
}

$accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
$to_accounts = mysqli_query($conn, "SELECT * FROM accounts WHERE user_id=$user_id");
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id=$user_id");
?>

<div class="container">
    <h2>Record Transaction</h2>

    <?php if ($error_msg)
        echo "<p style='color: var(--danger); font-weight: 600; background: #fef2f2; padding: 12px; border-radius: 6px; border: 1px solid #fecaca;'>$error_msg</p>"; ?>
    <?php if ($success_msg)
        echo "<p style='color: var(--success); font-weight: 600; background: #ecfdf5; padding: 12px; border-radius: 6px; border: 1px solid #a7f3d0;'>$success_msg</p>"; ?>

    <form method="POST" style="margin-top: 20px;">

        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <label>Transaction Type</label>
                <select name="type" id="type" required>
                    <option value="Income">Income</option>
                    <option value="Expense">Expense</option>
                    <option value="Transfer">Account Transfer</option>
                </select>
            </div>
            <div style="flex: 1;">
                <label>Date</label>
                <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <label id="account_label">Primary Account</label>
        <select name="account_id" id="account_id" required>
            <?php while ($row = mysqli_fetch_assoc($accounts)) { ?>
                <option value="<?php echo $row['id']; ?>"><?php echo $row['account_name']; ?></option>
            <?php } ?>
        </select>

        <div id="transfer_fields" style="display:none; padding-top: 5px;">
            <label>Destination Account</label>
            <select name="to_account_id" id="to_account_id">
                <?php while ($row = mysqli_fetch_assoc($to_accounts)) { ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['account_name']; ?></option>
                <?php } ?>
            </select>
        </div>

        <div id="category_section">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <label>Category</label>
                <span style="font-size: 13px; cursor: pointer; color: var(--primary); font-weight: 500;"
                    onclick="toggleAddCategory()">+ Quick Add Category</span>
            </div>

            <div id="add_category_form"
                style="display:none; background: #f8fafc; padding: 15px; margin-bottom: 20px; border: 1px dashed var(--primary); border-radius: var(--radius-sm);">
                <label style="font-size: 13px;">New Category Name</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="new_category_name" placeholder="Category Name" style="margin-bottom: 0;">
                    <button type="button" onclick="addNewCategory()" class="btn btn-primary"
                        style="padding: 10px 15px;">Add</button>
                </div>
                <span id="cat_msg" style="display:block; margin-top:5px; font-size:13px;"></span>
            </div>

            <select name="category_id" id="category">
                <option value="">-- Specify Category (Optional) --</option>
                <?php
                mysqli_data_seek($categories, 0);
                while ($cat = mysqli_fetch_assoc($categories)) { ?>
                    <option value="<?php echo $cat['id']; ?>" data-type="<?php echo $cat['type']; ?>">
                        <?php echo $cat['category_name']; ?></option>
                <?php } ?>
            </select>
        </div>

        <label>Amount (₹)</label>
        <input type="number" step="0.01" name="amount" placeholder="0.00" required>

        <label>Description / Notes</label>
        <input type="text" name="description" placeholder="Optional notes...">

        <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">Save Transaction</button>

    </form>
</div>

<script>
    const typeSelect = document.getElementById("type");
    const categorySection = document.getElementById("category_section");
    const categorySelect = document.getElementById("category");
    const transferFields = document.getElementById("transfer_fields");
    const accountLabel = document.getElementById("account_label");

    function updateFormVisibility() {
        const selectedType = typeSelect.value;
        if (selectedType === 'Transfer') {
            categorySection.style.display = 'none';
            transferFields.style.display = 'block';
            accountLabel.innerText = 'Source Account';
            categorySelect.removeAttribute('required');
        } else {
            categorySection.style.display = 'block';
            transferFields.style.display = 'none';
            accountLabel.innerText = 'Primary Account';
            // Category isn't strictly globally required unless logic dictates it
            filterCategories();
        }
    }

    function filterCategories() {
        const selectedType = typeSelect.value;
        const options = categorySelect.options;

        for (let i = 0; i < options.length; i++) {
            if (options[i].value === "") continue;
            if (options[i].getAttribute("data-type") === selectedType) {
                options[i].style.display = "block";
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
            msgSpan.style.color = "var(--danger)";
            return;
        }

        if (type === 'Transfer') return;

        msgSpan.innerText = "Saving...";
        msgSpan.style.color = "var(--primary)";

        const formData = new FormData();
        formData.append('category_name', name);
        formData.append('type', type);

        fetch('../categories/api_add_category.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const newOption = document.createElement("option");
                    newOption.value = data.id;
                    newOption.text = data.category_name;
                    newOption.setAttribute("data-type", data.type);
                    categorySelect.add(newOption);

                    categorySelect.value = data.id;
                    nameInput.value = "";
                    toggleAddCategory();
                    msgSpan.innerText = "";
                } else {
                    msgSpan.innerText = data.error;
                    msgSpan.style.color = "var(--danger)";
                }
            }).catch(err => {
                msgSpan.innerText = "Error saving category.";
                msgSpan.style.color = "var(--danger)";
            });
    }

    typeSelect.addEventListener("change", updateFormVisibility);
    window.onload = updateFormVisibility;
</script>

<?php include "../includes/footer.php"; ?>