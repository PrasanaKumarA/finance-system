<?php
include "../includes/auth.php";

if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}
?>