<?php
include "../includes/auth.php";
include "../includes/db.php";
include "admin_auth.php";

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$user_id = intval($_GET['id']);

/* Prevent self role change */
if ($user_id == $_SESSION['user_id']) {
    header("Location: users.php");
    exit;
}

/* Get current role */
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: users.php");
    exit;
}

$current_role = $user['role'];

/* Count how many admins exist */
$admin_count_result = mysqli_query($conn, "SELECT COUNT(*) as total_admins FROM users WHERE role='Admin'");
$admin_count = mysqli_fetch_assoc($admin_count_result)['total_admins'];

/* Prevent removing last admin */
if ($current_role == 'Admin' && $admin_count <= 1) {
    header("Location: users.php");
    exit;
}

/* Toggle role */
$new_role = ($current_role == 'Admin') ? 'user' : 'Admin';

$update = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
$update->bind_param("si", $new_role, $user_id);
$update->execute();
$update->close();

header("Location: users.php");
exit;