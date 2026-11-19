<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak! Anda tidak memiliki izin untuk menghapus akun.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header("Location: index.php");
exit();
