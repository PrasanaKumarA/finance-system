<?php
include "../includes/auth.php";
include "../includes/db.php";
include "admin_auth.php";
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
        <p style="color:red;"><?= $error ?></p>
    <?php } ?>

    <?php if (isset($success)) { ?>
        <p style="color:green;"><?= $success ?></p>
    <?php } ?>

    <form method="POST" style="margin-bottom:20px;">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>

        <select name="role">
            <option value="user">User</option>
            <option value="Admin">Admin</option>
        </select>

        <button type="submit" name="add_user">Add User</button>
    </form>

    <hr>

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
                <td><?= $row['name'] ?></td>
                <td><?= $row['username'] ?></td>
                <td><?= $row['role'] ?></td>
                <td><?= $row['created_at'] ?></td>

                <td>
                    <?php if ($row['id'] != $_SESSION['user_id']) { ?>

                        <!-- Role Toggle -->
                        <?php if ($row['role'] == 'Admin') { ?>
                            <a href="toggle_role.php?id=<?= $row['id'] ?>" style="color:orange;"
                                onclick="return confirm('Change this Admin to User?')">
                                Make User
                            </a>
                        <?php } else { ?>
                            <a href="toggle_role.php?id=<?= $row['id'] ?>" style="color:green;"
                                onclick="return confirm('Promote this User to Admin?')">
                                Make Admin
                            </a>
                        <?php } ?>

                        |

                        <!-- Delete -->
                        <a href="delete_user.php?id=<?= $row['id'] ?>" style="color:red;"
                            onclick="return confirm('Delete this user?')">
                            Delete
                        </a>

                    <?php } else { ?>
                        <span style="color:gray;">Current User</span>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>

    </table>

</div>

<?php include "../includes/footer.php"; ?>