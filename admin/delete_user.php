<?php
include "../includes/auth.php";
include "../includes/db.php";
include "admin_auth.php";

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$user_id = intval($_GET['id']);

/* Prevent deleting yourself */
if ($user_id == $_SESSION['user_id']) {
    header("Location: users.php");
    exit;
}

/* Delete user */
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

header("Location: users.php");
exit;