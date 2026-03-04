<?php
include "includes/auth.php";
include "includes/db.php";
$page_title = "Profile Settings";
include "includes/header.php";
include "includes/navbar.php";

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new_name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $new_system_name = mysqli_real_escape_string($conn, trim($_POST['system_name']));

    /* Handle profile picture upload */
    $pic_sql = "";
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];

        if (!in_array($file_type, $allowed)) {
            $error_msg = "Invalid file type. Only JPG, PNG, GIF, WebP are allowed.";
        } elseif ($file_size > 2 * 1024 * 1024) {
            $error_msg = "File too large. Max 2MB.";
        } else {
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = "user_" . $user_id . "_" . time() . "." . $ext;
            $dest = __DIR__ . "/uploads/profiles/" . $filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest)) {
                $pic_sql = ", profile_picture = 'uploads/profiles/$filename'";
            } else {
                $error_msg = "Failed to upload file.";
            }
        }
    }

    if (empty($error_msg)) {
        $system_name_val = !empty($new_system_name) ? "'$new_system_name'" : "NULL";

        mysqli_query($conn, "
            UPDATE users 
            SET name = '$new_name',
                system_name = $system_name_val
                $pic_sql
            WHERE id = $user_id
        ");

        $_SESSION['name'] = $new_name;
        $success_msg = "Profile updated successfully!";
    }
}

/* Load current user data */
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);
?>

<div class="container">
    <div class="action-bar">
        <h2>Profile Settings</h2>
        <a href="/finance-system/index.php" class="btn btn-secondary">← Back</a>
    </div>

    <?php if ($success_msg) { ?>
        <div class="alert alert-success">
            <?php echo $success_msg; ?>
        </div>
    <?php } ?>
    <?php if ($error_msg) { ?>
        <div class="alert alert-danger">
            <?php echo $error_msg; ?>
        </div>
    <?php } ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="profile-avatar-upload">
            <?php if ($user['profile_picture'] && file_exists(__DIR__ . '/' . $user['profile_picture'])) { ?>
                <img src="/finance-system/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile">
            <?php } else { ?>
                <div class="profile-avatar-placeholder">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
            <?php } ?>
            <label for="profile_pic_input" title="Change photo">📷</label>
            <input type="file" id="profile_pic_input" name="profile_picture" accept="image/*">
        </div>

        <label>Display Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

        <label>Username</label>
        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>

        <label>Custom System Name</label>
        <input type="text" name="system_name" value="<?php echo htmlspecialchars($user['system_name'] ?? ''); ?>"
            placeholder="FinanceHub (default)">
        <small style="color: var(--text-muted); display: block; margin-top: -12px; margin-bottom: 16px;">
            This changes the brand name shown in the sidebar for your profile only.
        </small>

        <label>Role</label>
        <input type="text" value="<?php echo htmlspecialchars($user['role']); ?>" disabled>

        <button type="submit" class="btn" style="width: 100%; margin-top: 8px;">Save Changes</button>
    </form>
</div>

<script>
    /* Preview profile picture */
    document.getElementById('profile_pic_input')?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (ev) {
            const container = document.querySelector('.profile-avatar-upload');
            const existing = container.querySelector('img, .profile-avatar-placeholder');
            if (existing.tagName === 'IMG') {
                existing.src = ev.target.result;
            } else {
                const img = document.createElement('img');
                img.src = ev.target.result;
                img.alt = 'Profile';
                existing.replaceWith(img);
            }
        };
        reader.readAsDataURL(file);
    });
</script>

<?php include "includes/footer.php"; ?>