<?php
// Bắt đầu phiên và include các file cần thiết trước KHI có bất kỳ output nào
ob_start(); // Bật output buffering
session_start();
require_once './config/database.php';

// Chuyển hướng
header("Location: ./pages/home.php");
exit();
?>