<?php
session_start();
require_once '../config/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Xử lý xóa review nếu có request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'])) {
    $review_id = $_POST['review_id'];
    $delete_stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $delete_stmt->bind_param("i", $review_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true]); // Trả về JSON cho AJAX
        exit();
    } else {
        // Thêm thông tin lỗi vào phản hồi
        echo json_encode(['success' => false, 'error' => $delete_stmt->error, 'review_id' => $review_id]); // Trả về JSON cho AJAX
        exit();
    }
}

// Lấy thông tin admin
$admin_id = $_SESSION['user_id'];
$admin_stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin = $admin_stmt->get_result()->fetch_assoc();

// Lấy danh sách đánh giá
$reviews_stmt = $conn->prepare("
    SELECT r.*, p.name as product_name, u.fullname as user_name 
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/logo.ico" type="image/x-icon">
    <title>Quản lý đánh giá - Admin Panel</title>
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
        .admin-container {
            padding: 2rem;
        }
        .review-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .review-card:hover {
            transform: translateY(-5px);
        }
        .star-rating {
            color: #ffc107;
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
                    <a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Trang chủ</a>
                    <a class="nav-link" href="./products.php"><i class="fas fa-mobile-alt me-2"></i>Sản phẩm</a>
                    <a class="nav-link" href="./orders.php"><i class="fas fa-shopping-cart me-2"></i>Đơn hàng</a>
                    <a class="nav-link" href="./users.php"><i class="fas fa-users me-2"></i>Người dùng</a>
                    <a class="nav-link" href="./brands.php"><i class="fas fa-building me-2"></i>Thương hiệu</a>
                    <a class="nav-link" href="./categories.php"><i class="fas fa-tags me-2"></i>Danh mục</a>
                    <a class="nav-link active" href="./reviews.php"><i class="fas fa-star me-2"></i>Đánh giá</a>
                    <a class="nav-link text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a>
                </nav>
            </div>

            <!-- Nội dung chính -->
            <div class="col-md-9 col-lg-10 ms-auto">
                <div class="admin-container">
                    <div class="admin-header d-flex justify-content-between align-items-center">
                        <h4>Quản lý đánh giá</h4>
                        <div>
                            <span class="text-muted me-2"><?php echo date('d/m/Y H:i:s', strtotime('+7 hours')); ?></span>
                        </div>
                    </div>

                    <!-- Danh sách đánh giá -->
                    <div class="reviews-container">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                        <p class="text-muted mb-0 small">
                                            <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-3">
                                        <p class="mb-0"><?php echo htmlspecialchars($review['product_name']); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="star-rating mb-2">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button class="btn btn-sm btn-danger" onclick="deleteReview(<?php echo $review['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteReview(reviewId) {
            if (confirm('Bạn có chắc chắn muốn xóa đánh giá này?')) {
                // Gửi yêu cầu AJAX để xóa đánh giá
                fetch('./delete_review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        review_id: reviewId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Nếu xóa thành công, tải lại trang
                        window.location.reload();
                    } else {
                        alert('Có lỗi xảy ra khi xóa đánh giá!');
                    }
                })
                .catch(error => {
                    console.error('Lỗi:', error);
                    alert('Có lỗi xảy ra khi xóa đánh giá!');
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
</body>
</html>
