<?php
require_once '../config/database.php';
session_start();

// Kiểm tra xem người dùng đã đăng nhập và là admin chưa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Lấy thông tin admin
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email, fullname FROM users WHERE id = ? AND role = 'admin'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Lấy thống kê
$stats = [];

// Đếm đơn hàng mới
$orderStmt = $conn->query("SELECT COUNT(*) as order_count FROM orders WHERE status = 'new'");
$stats['new_orders'] = $orderStmt->fetch_assoc()['order_count'];

// Đếm tổng số người dùng
$userStmt = $conn->query("SELECT COUNT(*) as user_count FROM users WHERE role = 'user'");
$stats['total_users'] = $userStmt->fetch_assoc()['user_count'];

// Đếm tổng số sản phẩm
$productStmt = $conn->query("SELECT COUNT(*) as product_count FROM products");
$stats['total_products'] = $productStmt->fetch_assoc()['product_count'];

// Lấy đánh giá trung bình
$ratingStmt = $conn->query("SELECT AVG(rating) as avg_rating FROM reviews");
$avgRating = $ratingStmt->fetch_assoc()['avg_rating'];
$stats['avg_rating'] = $avgRating ? number_format($avgRating, 1) : '0.0';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/logo.ico" type="image/x-icon">
    <title>Trang Quản Trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-container {
            padding: 2rem;
        }
        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            min-height: 100vh;
            padding: 1rem;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.8rem 1rem;
            margin: 0.2rem 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(5px);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            font-weight: bold;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .admin-header {
            background: white;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Thanh bên -->
            <div class="col-md-3 col-lg-2 px-0 position-fixed sidebar">
                <div class="text-center py-4">
                    <i class="fas fa-user-shield fa-3x mb-3"></i>
                    <h5><?php echo htmlspecialchars($admin['fullname']); ?></h5>
                    <p class="small"><?php echo htmlspecialchars($admin['email']); ?></p>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="index.php"><i class="fas fa-home me-2"></i>Trang chủ</a>
                    <a class="nav-link" href="./products.php"><i class="fas fa-mobile-alt me-2"></i>Sản phẩm</a>
                    <a class="nav-link" href="./orders.php"><i class="fas fa-shopping-cart me-2"></i>Đơn hàng</a>
                    <a class="nav-link" href="./users.php"><i class="fas fa-users me-2"></i>Người dùng</a>
                    <a class="nav-link" href="./brands.php"><i class="fas fa-building me-2"></i>Thương hiệu</a>
                    <a class="nav-link" href="./categories.php"><i class="fas fa-tags me-2"></i>Danh mục</a>
                    <a class="nav-link" href="./reviews.php"><i class="fas fa-star me-2"></i>Đánh giá</a>
                    <a class="nav-link text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a>
                </nav>
            </div>

            <!-- Nội dung chính -->
            <div class="col-md-9 col-lg-10 ms-auto">
                <div class="admin-container">
                    <div class="admin-header d-flex justify-content-between align-items-center">
                        <h4>Bảng điều khiển</h4>
                        <div>
                            <span class="text-muted me-2"><?php echo date('d/m/Y H:i:s', strtotime('+7 hours')); ?></span>
                        </div>
                    </div>

                    <!-- Thẻ thống kê -->
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <i class="fas fa-shopping-cart stats-icon text-primary"></i>
                                <h3><?php echo $stats['new_orders'] ?? '0'; ?></h3>
                                <p class="text-muted mb-0">Đơn hàng mới</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <i class="fas fa-users stats-icon text-success"></i>
                                <h3><?php echo $stats['total_users'] ?? '0'; ?></h3>
                                <p class="text-muted mb-0">Người dùng</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <i class="fas fa-mobile-alt stats-icon text-warning"></i>
                                <h3><?php echo $stats['total_products'] ?? '0'; ?></h3>
                                <p class="text-muted mb-0">Sản phẩm</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <i class="fas fa-star stats-icon text-info"></i>
                                <h3><?php echo $stats['avg_rating'] ?? '0.0'; ?></h3>
                                <p class="text-muted mb-0">Đánh giá trung bình</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chuyển đổi thanh bên
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                });
            }

            // Khởi tạo tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Tự động ẩn thông báo sau 5 giây
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('fade');
                    setTimeout(() => alert.remove(), 150);
                }, 5000);
            });

            // Cập nhật thời gian mỗi phút
            setInterval(() => {
                const now = new Date();
                const timeString = now.toLocaleString('vi-VN', { 
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit', 
                    minute: '2-digit'
                });
                document.querySelector('.text-muted').textContent = timeString;
            }, 60000);
        });
    </script>
</body>
</html>
