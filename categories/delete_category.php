<?php
include "../includes/auth.php";
include "../includes/db.php";

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $cat_id = intval($_GET['id']);

    // Verify ownership
    $check = $conn->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $cat_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $delete = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        $delete->bind_param("ii", $cat_id, $user_id);
        $delete->execute();
        $delete->close();
        header("Location: view_categories.php?deleted=1");
        exit;
    } else {
        header("Location: view_categories.php?error=1");
        exit;
    }

    $check->close();
} else {
    header("Location: view_categories.php");
    exit;
}
?>