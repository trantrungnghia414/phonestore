<?php
require_once '../config/database.php';
session_start();

// Kiểm tra người dùng đã đăng nhập và là admin
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

// Thêm vào đầu file, sau <?php
mb_internal_encoding('UTF-8');

// Thêm hàm chuyển đổi tiếng Việt sang không dấu
function convertToSlug($str) {
    // Chuyển sang UTF-8 nếu chưa
    if (!mb_check_encoding($str, 'UTF-8')) {
        $str = mb_convert_encoding($str, 'UTF-8');
    }
    
    // Chuyển thành chữ thường
    $str = mb_strtolower($str, 'UTF-8');
    
    // Mảng ký tự có dấu và không dấu
    $utf8 = array(
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
    );
    
    // Thay thế từng ký tự
    foreach ($utf8 as $ascii => $uni) {
        $str = preg_replace("/($uni)/i", $ascii, $str);
    }
    
    // Chuyển đổi các ký tự không hợp lệ thành dấu gạch ngang
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    
    // Thay thế khoảng trắng thành dấu gạch ngang
    $str = preg_replace('/[\s]+/', '-', trim($str));
    
    // Thay thế nhiều dấu gạch ngang liên tiếp thành một dấu
    $str = preg_replace('/-+/', '-', $str);
    
    return $str;
}

// Xử lý thêm sản phẩm mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $brand_id = $_POST['brand_id'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $description = $_POST['description'];
    $slug = convertToSlug($name);

    // Xử lý upload ảnh
    $target_dir = "../assets/uploads/products/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_url = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];

        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Chỉ chấp nhận file ảnh định dạng JPG, PNG hoặc GIF!";
            header("Location: products.php");
            exit();
        }

        $max_size = 5 * 1024 * 1024; // 5MB
        if ($_FILES['image']['size'] > $max_size) {
            $_SESSION['error_message'] = "Kích thước file không được vượt quá 5MB!";
            header("Location: products.php");
            exit();
        }

        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = $slug . '-' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_url = 'assets/uploads/products/' . $file_name;
        } else {
            $_SESSION['error_message'] = "Có lỗi xảy ra khi tải ảnh lên!";
            header("Location: products.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Vui lòng chọn ảnh cho sản phẩm!";
        header("Location: products.php");
        exit();
    }

    // Kiểm tra giá và số lượng
    if ($price <= 0) {
        $_SESSION['error_message'] = "Giá sản phẩm phải lớn hơn 0!";
        header("Location: products.php");
        exit();
    }

    if ($stock_quantity < 0) {
        $_SESSION['error_message'] = "Số lượng trong kho không được âm!";
        header("Location: products.php");
        exit();
    }

    // Thêm sản phẩm vào CSDL
    $stmt = $conn->prepare("INSERT INTO products (category_id, brand_id, name, slug, description, price, stock_quantity, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisssdis", $category_id, $brand_id, $name, $slug, $description, $price, $stock_quantity, $image_url);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Thêm sản phẩm thành công!";
        header("Location: products.php");
        exit();
    } else {
        // Xóa file ảnh nếu thêm sản phẩm thất bại
        if (file_exists($target_file)) {
            unlink($target_file);
        }
        $_SESSION['error_message'] = "Có lỗi xảy ra khi thêm sản phẩm!";
        header("Location: products.php");
        exit();
    }
}

// Xử lý sửa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $brand_id = $_POST['brand_id'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $description = $_POST['description'];
    $slug = convertToSlug($name);

    // Lấy thông tin ảnh cũ
    $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $old_image = $stmt->get_result()->fetch_assoc()['image_url'];

    $image_url = $old_image;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/uploads/products/";
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];

        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Chỉ chấp nhận file ảnh định dạng JPG, PNG hoặc GIF!";
            header("Location: products.php");
            exit();
        }

        $max_size = 5 * 1024 * 1024;
        if ($_FILES['image']['size'] > $max_size) {
            $_SESSION['error_message'] = "Kích thước file không được vượt quá 5MB!";
            header("Location: products.php");
            exit();
        }

        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = $slug . '-' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_url = 'assets/uploads/products/' . $file_name;
            // Xóa ảnh cũ
            if (file_exists("../" . $old_image)) {
                unlink("../" . $old_image);
            }
        }
    }

    $stmt = $conn->prepare("UPDATE products SET category_id=?, brand_id=?, name=?, slug=?, description=?, price=?, stock_quantity=?, image_url=? WHERE id=?");
    $stmt->bind_param("iisssdisi", $category_id, $brand_id, $name, $slug, $description, $price, $stock_quantity, $image_url, $product_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Cập nhật sản phẩm thành công!";
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra khi cập nhật sản phẩm!";
    }
    header("Location: products.php");
    exit();
}

// Xử lý xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['delete_product'];

    // Lấy đường dẫn ảnh trước khi xóa
    $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        // Xóa file ảnh
        if (!empty($product['image_url']) && file_exists("../" . $product['image_url'])) {
            unlink("../" . $product['image_url']);
        }
        $_SESSION['success_message'] = "Xóa sản phẩm thành công!";
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra khi xóa sản phẩm!";
    }
    header("Location: products.php");
    exit();
}

// Hiển thị thông báo nếu có
if (isset($_SESSION['success_message'])) {
    echo "<div class='toast-container position-fixed top-0 end-0 p-3'>
        <div class='toast show' role='alert'>
            <div class='toast-header bg-success text-white'>
                <strong class='me-auto'>Thành công!</strong>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='toast'></button>
            </div>
            <div class='toast-body'>
                {$_SESSION['success_message']}
            </div>
        </div>
    </div>";
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo "<div class='toast-container position-fixed top-0 end-0 p-3'>
        <div class='toast show' role='alert'>
            <div class='toast-header bg-danger text-white'>
                <strong class='me-auto'>Lỗi!</strong>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='toast'></button>
            </div>
            <div class='toast-body'>
                {$_SESSION['error_message']}
            </div>
        </div>
    </div>";
    unset($_SESSION['error_message']);
}

// Lấy tham số lọc từ URL
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$brand_filter = isset($_GET['brand']) ? $_GET['brand'] : '';

// Lấy danh sách danh mục cho dropdown lọc
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách thương hiệu cho dropdown lọc
$brands_query = "SELECT * FROM brands ORDER BY name";
$brands_result = $conn->query($brands_query);
$brands = $brands_result->fetch_all(MYSQLI_ASSOC);

// Xây dựng câu query với điều kiện lọc
$query = "SELECT p.*, c.name as category_name, b.name as brand_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN brands b ON p.brand_id = b.id
          WHERE 1=1";

if (!empty($category_filter)) {
    $query .= " AND c.id = " . intval($category_filter);
}

if (!empty($brand_filter)) {
    $query .= " AND b.id = " . intval($brand_filter);
}

$query .= " ORDER BY p.created_at DESC";

$result = $conn->query($query);
$products = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/logo.ico" type="image/x-icon">
    <title>Quản Lý Sản Phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .admin-header {
            background: white;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-group .btn {
            padding: 0.25rem 0.5rem;
        }

        .btn-group .btn:hover {
            transform: translateY(-1px);
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
                    <i class="fas fa-user-shield fa-3x mb-3"></i>
                    <h5><?php echo htmlspecialchars($admin['fullname']); ?></h5>
                    <p class="small"><?php echo htmlspecialchars($admin['email']); ?></p>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Trang chủ</a>
                    <a class="nav-link active" href="./products.php"><i class="fas fa-mobile-alt me-2"></i>Sản phẩm</a>
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
                <div class="p-4">
                    <div class="admin-header d-flex justify-content-between align-items-center">
                        <h4></i>Quản Lý Sản Phẩm</h4>
                        <button class="btn btn-primary btn-custom" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus me-2"></i>Thêm sản phẩm mới
                        </button>
                    </div>

                    <!-- Thêm vào trước phần bảng sản phẩm -->
                    <div class="mb-4">
                        <form class="row g-3" method="GET">
                            <div class="col-md-4">
                                <label class="form-label">Lọc theo danh mục</label>
                                <select name="category" class="form-select" onchange="this.form.submit()">
                                    <option value="">Tất cả danh mục</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Lọc theo thương hiệu</label>
                                <select name="brand" class="form-select" onchange="this.form.submit()">
                                    <option value="">Tất cả thương hiệu</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>"
                                                <?php echo ($brand_filter == $brand['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <a href="products.php" class="btn btn-secondary">Đặt lại</a>
                            </div>
                        </form>
                    </div>

                    <!-- Hiển thị sản phẩm dạng bảng -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Hình ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th>Thương hiệu</th>
                                    <th>Giá</th>
                                    <th>Tồn kho</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td style="width: 100px">
                                            <img src="../<?php echo htmlspecialchars($product['image_url']); ?>"
                                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                            <small class="text-muted"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['brand_name']); ?></td>
                                        <td><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</td>
                                        <td>
                                            <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                                <?php echo $product['stock_quantity'] > 0 ? $product['stock_quantity'] : 'Hết hàng'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group" style="gap: 10px;">
                                                <button class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editProductModal<?php echo $product['id']; ?>"
                                                    style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline"
                                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                                    <input type="hidden" name="delete_product" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm sản phẩm -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm sản phẩm mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="add_product" value="1">
                        <div class="mb-3">
                            <label class="form-label">Tên sản phẩm</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Danh mục</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Chọn danh mục</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thương hiệu</label>
                            <select name="brand_id" class="form-select" required>
                                <option value="">Chọn thương hiệu</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>">
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giá (VNĐ)</label>
                            <input type="number" name="price" class="form-control" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số lượng trong kho</label>
                            <input type="number" name="stock_quantity" class="form-control" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hình ảnh</label>
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif" required>
                            <small class="text-muted">Chấp nhận file JPG, PNG, GIF. Tối đa 5MB.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal sửa sản phẩm -->
    <?php foreach ($products as $product): ?>
        <div class="modal fade" id="editProductModal<?php echo $product['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Sửa sản phẩm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="edit_product" value="1">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Tên sản phẩm</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Danh mục</label>
                                <select name="category_id" class="form-select" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Thương hiệu</label>
                                <select name="brand_id" class="form-select" required>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>" 
                                                <?php echo ($brand['id'] == $product['brand_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Giá (VNĐ)</label>
                                <input type="number" name="price" class="form-control" value="<?php echo $product['price']; ?>" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Số lượng trong kho</label>
                                <input type="number" name="stock_quantity" class="form-control" value="<?php echo $product['stock_quantity']; ?>" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hình ảnh hiện tại</label>
                                <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" class="img-thumbnail mb-2" style="max-height: 100px;">
                                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif">
                                <small class="text-muted">Chỉ chọn ảnh mới nếu muốn thay đổi. Chấp nhận file JPG, PNG, GIF. Tối đa 5MB.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
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
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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