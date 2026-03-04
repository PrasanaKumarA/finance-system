<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
    $type = mysqli_real_escape_string($conn, trim($_POST['type']));

    if (empty($category_name) || empty($type)) {
        echo json_encode(['success' => false, 'error' => 'Invalid input data']);
        exit();
    }

    $query = "INSERT INTO categories (user_id, category_name, type) VALUES ($user_id, '$category_name', '$type')";

    if (mysqli_query($conn, $query)) {
        $new_id = mysqli_insert_id($conn);
        echo json_encode([
            'success' => true,
            'id' => $new_id,
            'category_name' => $category_name,
            'type' => $type
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
}
?>