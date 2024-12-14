<?php
session_start();
require_once '../config/database.php';

// Cập nhật last_activity thành NULL khi đăng xuất
if (isset($_SESSION['user_id'])) {
    $update_stmt = $conn->prepare("UPDATE users SET last_activity = NULL WHERE id = ?");
    $update_stmt->bind_param("i", $_SESSION['user_id']);
    $update_stmt->execute();
}

// Xóa tất cả các session
session_destroy();

// Chuyển hướng về trang chủ
header("Location: ../index.php");
exit();
