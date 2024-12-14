<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $update_activity = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $update_activity->bind_param("i", $_SESSION['user_id']);
    $update_activity->execute();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/logo.ico" type="image/x-icon">
    <title>Phone Store - Cửa hàng điện thoại uy tín</title>

    <!-- CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- CSS Tùy chỉnh -->
    <style>
        :root {
            /* Màu chủ đạo */
            --primary-color: #e31837;
            /* Đỏ chính */
            --primary-dark: #b71c1c;
            /* Đỏ đậm */
            --primary-light: #ff5252;
            /* Đỏ nhạt */

            /* Màu phụ */
            --secondary-color: #f5f5f5;
            /* Xám nhạt */
            --text-color: #333333;
            /* Màu chữ chính */
            --light-text: #ffffff;
            /* Màu chữ sáng */
            --dark-text: #000000;
            /* Màu chữ tối */

            /* Màu nền */
            --bg-color: #ffffff;
            /* Nền trắng */
            --bg-light: #f8f9fa;
            /* Nền xám nhạt */

            /* Màu accent */
            --success-color: #28a745;
            /* Màu thành công */
            --error-color: #dc3545;
            /* Màu lỗi */
        }

        /* Kiểu dáng Header */
        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }

        .nav-link {
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-light) !important;
        }

        /* Form tìm kiếm */
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.2rem rgba(227, 24, 55, 0.25);
        }

        /* Nút giỏ hàng */
        .btn-outline-light:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
        }

        /* Huy hiệu */
        .badge {
            font-size: 0.7rem;
        }

        /* Giữ footer ở dưới cùng */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }
    </style>

    <!-- JS Bootstrap và JS Tùy chỉnh -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
        // Mã JS tùy chỉnh có thể đặt ở đây
        document.addEventListener('DOMContentLoaded', function() {
            // Khởi tạo các chức năng tùy chỉnh
            console.log('DOM đã được tải và phân tích hoàn tất');
        });
    </script>
</head>

<body>
    <!-- Phần đầu trang -->
    <header>
        <!-- Thanh trên cùng -->
        <div class="bg-dark text-light py-2">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <span class="me-3"><i class="fas fa-phone"></i> Hotline: 1900 1234</span>
                        <span><i class="fas fa-envelope"></i> Email: contact@phonestore.com</span>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="../auth/profile.php" class="text-light me-3">
                                <i class="fas fa-user"></i> Tài khoản
                            </a>
                            <a href="../auth/logout.php" class="text-light">
                                <i class="fas fa-sign-out-alt"></i> Đăng xuất
                            </a>
                        <?php else: ?>
                            <a href="../auth/login.php" class="text-light me-3">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập
                            </a>
                            <a href="../auth/register.php" class="text-light">
                                <i class="fas fa-user-plus"></i> Đăng ký
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Điều hướng chính -->
        <nav class="navbar navbar-expand-lg" style="background-color: var(--primary-color);">
            <div class="container">
                <a class="navbar-brand text-light" href="../index.php">
                    <i class="fas fa-mobile-alt"></i> PHONE STORE
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link text-light" href="../pages/home.php">Trang chủ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="../pages/products.php">Sản phẩm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="../pages/contact.php">Liên hệ</a>
                        </li>
                    </ul>

                    <!-- Form tìm kiếm -->
                    <form class="d-flex me-3" action="../pages/products.php" method="GET">
                        <div class="input-group">
                            <input class="form-control" type="search" name="search"
                                placeholder="Tìm theo tên, hãng, giá..."
                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                                aria-label="Search">
                            <button class="btn btn-outline-light" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Nút giỏ hàng -->
                    <a href="../pages/cart.php" class="btn btn-outline-light position-relative">
                        <i class="fas fa-shopping-cart"></i>
                        <?php
                        if (isset($_SESSION['user_id'])) {
                            // Lấy tổng số lượng sản phẩm trong giỏ hàng
                            $cart_count = $conn->prepare("
                                SELECT SUM(quantity) as total_items 
                                FROM cart 
                                WHERE user_id = ?
                            ");
                            $cart_count->bind_param("i", $_SESSION['user_id']);
                            $cart_count->execute();
                            $total_items = $cart_count->get_result()->fetch_assoc()['total_items'];

                            if ($total_items > 0) {
                                echo '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">'
                                    . $total_items .
                                    '</span>';
                            }
                        }
                        ?>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Phần nội dung chính -->
    <main class="container py-4">