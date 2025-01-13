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

// Lấy tất cả người dùng với trạng thái online
$users_query = "SELECT id, fullname, email, phone, created_at, 
                CASE 
                    WHEN last_activity >= NOW() - INTERVAL 15 MINUTE THEN 1 
                    ELSE 0 
                END as is_online 
                FROM users 
                WHERE role = 'user' 
                ORDER BY created_at DESC";
$users_result = $conn->query($users_query);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Kiểm tra và xử lý thêm người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']); 
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    
    // Kiểm tra các trường không được để trống
    if (empty($fullname) || empty($email) || empty($password)) {
        echo "<div class='alert alert-danger'>Vui lòng điền đầy đủ thông tin!</div>";
    } else {
        // Kiểm tra email đã tồn tại chưa
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            echo "<div class='alert alert-danger'>Email đã tồn tại trong hệ thống!</div>";
        } else {
            // Mã hóa mật khẩu
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Thêm người dùng mới
            $insert_stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
            $insert_stmt->bind_param("ssss", $fullname, $email, $phone, $hashed_password);
            
            if ($insert_stmt->execute()) {
                echo "<div class='alert alert-success'>Thêm người dùng thành công!</div>";
                // Refresh trang sau 2 giây
                echo "<script>setTimeout(function(){ window.location.href = 'users.php'; }, 2000);</script>";
            } else {
                echo "<div class='alert alert-danger'>Có lỗi xảy ra: " . $conn->error . "</div>";
            }
        }
    }
}

// Xử lý xóa người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
    $delete_stmt->bind_param("i", $user_id);
    
    if ($delete_stmt->execute()) {
        echo "<div class='alert alert-success'>Xóa người dùng thành công!</div>";
        echo "<script>setTimeout(function(){ window.location.href = 'users.php'; }, 2000);</script>";
    } else {
        echo "<div class='alert alert-danger'>Có lỗi xảy ra khi xóa người dùng!</div>";
    }
}

// Hoàn thiện xử lý cập nhật thông tin người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    if (empty($fullname) || empty($email)) {
        echo "<div class='alert alert-danger'>Vui lòng điền đầy đủ thông tin!</div>";
    } else {
        // Kiểm tra email có bị trùng không (trừ email hiện tại của user)
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $user_id);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            echo "<div class='alert alert-danger'>Email đã tồn tại trong hệ thống!</div>";
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ? WHERE id = ? AND role = 'user'");
            $update_stmt->bind_param("sssi", $fullname, $email, $phone, $user_id);
            
            if ($update_stmt->execute()) {
                echo "<div class='alert alert-success'>Cập nhật thông tin thành công!</div>";
                echo "<script>setTimeout(function(){ window.location.href = 'users.php'; }, 2000);</script>";
            } else {
                echo "<div class='alert alert-danger'>Có lỗi xảy ra khi cập nhật thông tin!</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/logo.ico" type="image/x-icon">
    <title>Quản Lý Người Dùng</title>
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
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .user-card:hover {
            transform: translateY(-5px);
        }
        .admin-header {
            background: white;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
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
                    <a class="nav-link active" href="./users.php"><i class="fas fa-users me-2"></i>Người dùng</a>
                    <a class="nav-link" href="./brands.php"><i class="fas fa-building me-2"></i>Thương hiệu</a>
                    <a class="nav-link" href="./categories.php"><i class="fas fa-tags me-2"></i>Danh mục</a>
                    <a class="nav-link" href="./reviews.php"><i class="fas fa-star me-2"></i>Đánh giá</a>
                    <a class="nav-link text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a>
                </nav>
            </div>

            <!-- Nội dung chính -->
            <div class="col-md-9 col-lg-10 ms-auto">
                <div class="container py-4">
                    <div class="admin-header d-flex justify-content-between align-items-center">
                        <h4>Quản Lý Người Dùng</h4>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-2"></i>Thêm người dùng
                        </button>
                    </div>

                    <!-- Danh sách người dùng -->
                    <div class="row">
                        <?php foreach ($users as $user): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="user-card">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($user['fullname']); ?></h5>
                                        <div class="dropdown">
                                            <button class="btn btn-link text-dark" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-edit me-2"></i>Chỉnh sửa
                                                </a></li>
                                                <li>
                                                    <form method="POST" class="delete-user-form" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="delete_user" value="1">
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash me-2"></i>Xóa
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></p>
                                        <p class="mb-1"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user['phone'] ?? 'Chưa cập nhật'); ?></p>
                                        <p class="mb-0"><i class="fas fa-calendar me-2"></i>Tham gia: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                                    </div>
                                    <div class="text-end">
                                        <span class="status-badge status-<?php echo $user['is_online'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['is_online'] ? 'Đang online' : 'Offline'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm người dùng -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm người dùng mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm" method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="fullname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="tel" name="phone" class="form-control" pattern="[0-9]{10}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <input type="hidden" name="add_user" value="1">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" form="addUserForm" class="btn btn-primary">Thêm người dùng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal chỉnh sửa người dùng -->
    <?php foreach ($users as $user): ?>
    <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh sửa thông tin người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="fullname" class="form-control" required 
                                   value="<?php echo htmlspecialchars($user['fullname']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required 
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="tel" name="phone" class="form-control" pattern="[0-9]{10}"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <input type="hidden" name="edit_user" value="1">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Làm mới trang sau mỗi 60 giây (1 phút)
            setInterval(function() {
                window.location.reload();
            }, 60000);

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
