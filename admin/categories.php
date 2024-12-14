<?php
require_once '../config/database.php';
session_start();

mb_internal_encoding('UTF-8');

function convertToSlug($str) {
    if (!mb_check_encoding($str, 'UTF-8')) {
        $str = mb_convert_encoding($str, 'UTF-8');
    }
    
    $str = mb_strtolower($str, 'UTF-8');
    
    $utf8 = array(
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
    );
    
    foreach ($utf8 as $ascii => $uni) {
        $str = preg_replace("/($uni)/i", $ascii, $str);
    }
    
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/[\s]+/', '-', trim($str));
    $str = preg_replace('/-+/', '-', $str);
    
    return $str;
}

// Kiểm tra người dùng đã đăng nhập và là admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Xử lý thêm danh mục mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $slug = convertToSlug($name); // Sử dụng hàm mới
    
    $stmt = $conn->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $slug, $description);
    
    if ($stmt->execute()) {
        echo "<div class='toast-container position-fixed top-0 end-0 p-3'>
            <div class='toast show' role='alert'>
                <div class='toast-header bg-success text-white'>
                    <strong class='me-auto'>Thành công!</strong>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='toast'></button>
                </div>
                <div class='toast-body'>
                    Thêm danh mục thành công!
                </div>
            </div>
        </div>";
    } else {
        echo "<div class='toast-container position-fixed top-0 end-0 p-3'>
            <div class='toast show' role='alert'>
                <div class='toast-header bg-danger text-white'>
                    <strong class='me-auto'>Lỗi!</strong>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='toast'></button>
                </div>
                <div class='toast-body'>
                    Có lỗi xảy ra khi thêm danh mục!
                </div>
            </div>
        </div>";
    }
}

// Xử lý sửa danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $category_id = $_POST['category_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $slug = convertToSlug($name); // Sử dụng hàm mới

    $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $slug, $description, $category_id);

    if ($stmt->execute()) {
        echo "<div class='toast-container position-fixed top-0 end-0 p-3'>
            <div class='toast show' role='alert'>
                <div class='toast-header bg-success text-white'>
                    <strong class='me-auto'>Thành công!</strong>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='toast'></button>
                </div>
                <div class='toast-body'>
                    Cập nhật danh mục thành công!
                </div>
            </div>
        </div>";
    } else {
        echo "<div class='toast-container position-fixed top-0 end-0 p-3'>
            <div class='toast show' role='alert'>
                <div class='toast-header bg-danger text-white'>
                    <strong class='me-auto'>Lỗi!</strong>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='toast'></button>
                </div>
                <div class='toast-body'>
                    Có lỗi xảy ra khi cập nhật danh m���c!
                </div>
            </div>
        </div>";
    }
}

// Xử lý xóa danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    
    // Kiểm tra xem danh mục có sản phẩm không
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $check_stmt->bind_param("i", $category_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        echo "<div class='toast-container position-fixed top-0 end-0 p-3'>
            <div class='toast show' role='alert'>
                <div class='toast-header bg-danger text-white'>
                    <strong class='me-auto'>Lỗi!</strong>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='toast'></button>
                </div>
                <div class='toast-body'>
                    Không thể xóa danh mục đang có sản phẩm!
                </div>
            </div>
        </div>";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $delete_stmt->bind_param("i", $category_id);
        
        if ($delete_stmt->execute()) {
            echo "<div class='toast-container position-fixed top-0 end-0 p-3'>
                <div class='toast show' role='alert'>
                    <div class='toast-header bg-success text-white'>
                        <strong class='me-auto'>Thành công!</strong>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='toast'></button>
                    </div>
                    <div class='toast-body'>
                        Xóa danh mục thành công!
                    </div>
                </div>
            </div>";
        } else {
            echo "<div class='toast-container position-fixed top-0 end-0 p-3'>
                <div class='toast show' role='alert'>
                    <div class='toast-header bg-danger text-white'>
                        <strong class='me-auto'>Lỗi!</strong>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='toast'></button>
                    </div>
                    <div class='toast-body'>
                        Có lỗi xảy ra khi xóa danh mục!
                    </div>
                </div>
            </div>";
        }
    }
}

// Lấy thông tin admin
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email, fullname FROM users WHERE id = ? AND role = 'admin'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Lấy tất cả danh mục và số lượng sản phẩm trong mỗi danh mục
$categories = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/logo.ico" type="image/x-icon">
    <title>Quản lý Danh mục</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            min-height: 100vh;
            z-index: 1000;
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
        .category-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 1rem;
        }
        .category-card:hover {
            transform: translateY(-5px);
        }
        .admin-header {
            background: white;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .toast-container {
            z-index: 9999;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Thanh bên -->
            <div class="col-md-3 col-lg-2 px-0 position-fixed sidebar">
                <div class="text-center py-4">
                    <i class="fas fa-user-shield fa-3x mb-3 text-white"></i>
                    <h5 class="text-white"><?php echo htmlspecialchars($admin['fullname']); ?></h5>
                    <p class="small text-white-50"><?php echo htmlspecialchars($admin['email']); ?></p>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Trang chủ</a>
                    <a class="nav-link" href="./products.php"><i class="fas fa-mobile-alt me-2"></i>Sản phẩm</a>
                    <a class="nav-link" href="./orders.php"><i class="fas fa-shopping-cart me-2"></i>Đơn hàng</a>
                    <a class="nav-link" href="./users.php"><i class="fas fa-users me-2"></i>Người dùng</a>
                    <a class="nav-link" href="./brands.php"><i class="fas fa-building me-2"></i>Thương hiệu</a>
                    <a class="nav-link active" href="./categories.php"><i class="fas fa-tags me-2"></i>Danh mục</a>
                    <a class="nav-link" href="./reviews.php"><i class="fas fa-star me-2"></i>Đánh giá</a>
                    <a class="nav-link text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a>
                </nav>
            </div>

            <!-- Nội dung chính -->
            <div class="col-md-9 col-lg-10 ms-auto">
                <div class="admin-container p-4">
                    <div class="admin-header d-flex justify-content-between align-items-center">
                        <h4>Quản lý Danh mục</h4>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Thêm danh mục mới
                        </button>
                    </div>

                    <div class="row">
                        <?php foreach ($categories as $category): ?>
                        <div class="col-md-4">
                            <div class="category-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($category['name']); ?></h5>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $category['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <input type="hidden" name="delete_category" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($category['description'] ?? 'Không có mô tả'); ?></p>
                                <small class="text-primary">Slug: <?php echo htmlspecialchars($category['slug']); ?></small><br>
                                <small class="text-muted">Số sản phẩm: <?php echo $category['product_count']; ?></small>
                            </div>

                            <!-- Modal sửa danh mục -->
            <div class="modal fade" id="editCategoryModal<?php echo $category['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Sửa danh mục</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="categories.php">
                                                <input type="hidden" name="edit_category" value="1">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Tên danh mục</label>
                                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Mô tả</label>
                                                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                                </div>
                                                <div class="modal-footer px-0 pb-0">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm danh mục -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm danh mục mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm" method="POST" action="categories.php">
                        <input type="hidden" name="add_category" value="1">
                        <div class="mb-3">
                            <label class="form-label">Tên danh mục</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">Thêm danh mục</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Khởi tạo tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Tự động ẩn toast sau 3 giây
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 150);
                }, 3000);
            });
        });
    </script>
</body>
</html>
