<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config.php";

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_PATH . "/login.php");
    exit;
}
?>