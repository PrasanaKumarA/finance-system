<?php
include "../includes/auth.php";
include "../includes/db.php";
include "admin_auth.php";
include "../categories/init_defaults.php";
$page_title = "Manage Users";
include "../includes/header.php";
include "../includes/navbar.php";

/* ================= ADD USER ================= */
if (isset($_POST['add_user'])) {

    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (!empty($name) && !empty($username) && !empty($password)) {

        /* Check if username exists */
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username already exists!";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (name, username, password, role, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("ssss", $name, $username, $hashed_password, $role);
            $stmt->execute();

            // Seed default categories for the new user
            $new_user_id = $conn->insert_id;
            seed_default_categories($conn, $new_user_id);

            $stmt->close();

            $success = "User added successfully!";
        }

        $check->close();
    } else {
        $error = "All fields are required!";
    }
}

/* Fetch all users */
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>

<div class="container">

    <h2>Manage Users</h2>

    <h3>Add New User</h3>

    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php } ?>

    <?php if (isset($success)) { ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php } ?>

    <form method="POST" class="mb-3">
        <label>Full Name</label>
        <input type="text" name="name" placeholder="Full Name" required>

        <label>Username</label>
        <input type="text" name="username" placeholder="Username" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="Password" required>

        <label>Role</label>
        <select name="role">
            <option value="user">User</option>
            <option value="Admin">Admin</option>
        </select>

        <button type="submit" name="add_user" style="width: 100%; margin-top: 8px;">Add User</button>
    </form>

    <hr>

    <h3>Existing Users</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Username</th>
            <th>Role</th>
            <th>Created</th>
            <th>Action</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td>
                    <span class="badge <?= $row['role'] == 'Admin' ? 'badge-admin' : 'badge-income' ?>">
                        <?= $row['role'] ?>
                    </span>
                </td>
                <td><?= $row['created_at'] ?></td>

                <td>
                    <?php if ($row['id'] != $_SESSION['user_id']) { ?>

                        <?php if ($row['role'] == 'Admin') { ?>
                            <a href="toggle_role.php?id=<?= $row['id'] ?>" class="text-warning"
                                onclick="return confirm('Change this Admin to User?')">
                                Make User
                            </a>
                        <?php } else { ?>
                            <a href="toggle_role.php?id=<?= $row['id'] ?>" class="text-success"
                                onclick="return confirm('Promote this User to Admin?')">
                                Make Admin
                            </a>
                        <?php } ?>

                        &nbsp;|&nbsp;

                        <a href="delete_user.php?id=<?= $row['id'] ?>" class="text-danger"
                            onclick="return confirm('Delete this user?')">
                            Delete
                        </a>

                    <?php } else { ?>
                        <span class="text-muted">Current User</span>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>

    </table>

</div>

<?php include "../includes/footer.php"; ?>