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

// Lấy danh sách đơn hàng kèm thông tin người dùng
$query = "SELECT o.*, u.fullname, u.email 
          FROM orders o
          JOIN users u ON o.user_id = u.id
          ORDER BY o.created_at DESC";
$orders = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Kiểm tra dữ liệu
// if (!$orders) {
//     echo "Lỗi truy vấn: " . $conn->error;
//     exit;
// }
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/logo.ico" type="image/x-icon">
    <title>Quản Lý Đơn Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            min-height: 100vh;
            padding: 1rem;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1rem;
            margin: 0.2rem 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: bold;
        }

        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s;
        }

        .order-card:hover {
            transform: translateY(-5px);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .status-new {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-processing {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-completed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .order-header {
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
                    <a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Trang chủ</a>
                    <a class="nav-link" href="./products.php"><i class="fas fa-mobile-alt me-2"></i>Sản phẩm</a>
                    <a class="nav-link active" href="./orders.php"><i class="fas fa-shopping-cart me-2"></i>Đơn hàng</a>
                    <a class="nav-link" href="./users.php"><i class="fas fa-users me-2"></i>Người dùng</a>
                    <a class="nav-link" href="./brands.php"><i class="fas fa-building me-2"></i>Thương hiệu</a>
                    <a class="nav-link" href="./categories.php"><i class="fas fa-tags me-2"></i>Danh mục</a>
                    <a class="nav-link" href="./reviews.php"><i class="fas fa-star me-2"></i>Đánh giá</a>
                    <a class="nav-link text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a>
                </nav>
            </div>

            <!-- Nội dung chính -->
            <div class="col-md-9 col-lg-10 ms-auto">
                <div class="p-4">
                    <div class="order-header d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">Quản Lý Đơn Hàng</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary">
                                <i class="fas fa-filter me-2"></i>Lọc
                            </button>
                            <button class="btn btn-outline-success">
                                <i class="fas fa-file-excel me-2"></i>Xuất Excel
                            </button>
                        </div>
                    </div>

                    <!-- Danh sách đơn hàng -->
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card p-3">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <h5 class="mb-1">#<?php echo $order['id']; ?></h5>
                                    <p class="text-muted mb-0">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($order['fullname'] ?? ''); ?></h6>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($order['phone'] ?? 'Chưa có số điện thoại'); ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="mb-0"><?php echo number_format($order['total_amount'] ?? 0, 0, ',', '.'); ?>đ</h6>
                                </div>
                                <div class="col-md-2 text-end">
                                    <span class="status-badge status-<?php echo strtolower($order['status'] ?? ''); ?>">
                                        <?php
                                        $status_text = [
                                            'pending' => 'Đang vận chuyển',
                                            // 'processing' => 'Đang xử lý',
                                            // 'shipped' => 'Đã giao',
                                            'delivered' => 'Hoàn thành',
                                            'cancelled' => 'Đã hủy'
                                        ];
                                        echo htmlspecialchars($status_text[$order['status']] ?? 'Không xác định');
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Khởi tạo tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
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
        });
    </script>
</body>

</html>