<?php
// Bắt đầu phiên và include các file cần thiết
session_start();
require_once '../config/database.php';
require_once '../includes/header.php';

// Lấy các tham số từ URL
$category = isset($_GET['category']) ? $_GET['category'] : '';
$brand = isset($_GET['brand']) ? $_GET['brand'] : '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Xây dựng câu truy vấn SQL cơ bản
$sql = "SELECT DISTINCT p.*, c.name as category_name, c.slug as category_slug, 
               b.name as brand_name, b.slug as brand_slug 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE 1=1";

// Mảng chứa các tham số cho prepared statement
$params = array();
$types = "";

// Thêm điều kiện tìm kiếm
if (!empty($search_term)) {
    $search = "%$search_term%";
    $sql .= " AND (p.name LIKE ? OR b.name LIKE ? OR p.description LIKE ? OR CAST(p.price AS CHAR) LIKE ?)";
    $params = array_merge($params, [$search, $search, $search, $search]);
    $types .= "ssss";
}

// Thêm điều kiện lọc danh mục
if (!empty($category)) {
    $sql .= " AND c.slug = ?";
    $params[] = $category;
    $types .= "s";
}

// Thêm điều kiện lọc thương hiệu
if (!empty($brand)) {
    $sql .= " AND b.slug = ?";
    $params[] = $brand;
    $types .= "s";
}

// Thêm sắp xếp
$sql .= " ORDER BY p.created_at DESC";

// Thực thi truy vấn
try {
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Xử lý thông báo kết quả
    if (!empty($search_term)) {
        if ($result->num_rows === 0) {
            $search_message = "Không tìm thấy sản phẩm nào phù hợp với từ khóa: \"" . htmlspecialchars($search_term) . "\"";
        } else {
            $search_message = "Kết quả tìm kiếm cho: \"" . htmlspecialchars($search_term) . "\"";
        }
    }
} catch (Exception $e) {
    $error_message = "Đã xảy ra lỗi trong quá trình tìm kiếm. Vui lòng thử lại!";
    error_log($e->getMessage());
}

// Lấy danh sách danh mục và thương hiệu cho bộ lọc
$categories_sql = "SELECT * FROM categories ORDER BY name";
$brands_sql = "SELECT * FROM brands ORDER BY name";
$categories_result = $conn->query($categories_sql);
$brands_result = $conn->query($brands_sql);
?>

<!-- Hiển thị thông báo tìm kiếm nếu có -->
<?php if (isset($search_message)): ?>
    <div class="alert <?php echo isset($no_results) ? 'alert-warning' : 'alert-info'; ?> alert-dismissible fade show" role="alert">
        <i class="fas <?php echo isset($no_results) ? 'fa-exclamation-triangle' : 'fa-search'; ?> me-2"></i>
        <?php echo $search_message; ?>
        <a href="products.php" class="btn btn-sm btn-outline-secondary ms-3">
            <i class="fas fa-times"></i> Xóa tìm kiếm
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Phần Sản phẩm -->
<main>
    <section class="products-section my-5">
        <div class="container">
            <!-- Đường dẫn -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Sản phẩm</a></li>

                    <?php if (!empty($category)):
                        // Lấy tên danh mục từ slug
                        $cat_name_sql = "SELECT name FROM categories WHERE slug = ?";
                        $stmt = $conn->prepare($cat_name_sql);
                        $stmt->bind_param("s", $category);
                        $stmt->execute();
                        $cat_result = $stmt->get_result();
                        $cat_name = $cat_result->fetch_assoc();
                    ?>
                        <li class="breadcrumb-item">
                            <a href="products.php?category=<?php echo $category; ?>">
                                <?php echo htmlspecialchars($cat_name['name']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($brand)):
                        // Lấy tên thương hiệu từ slug
                        $brand_name_sql = "SELECT name FROM brands WHERE slug = ?";
                        $stmt = $conn->prepare($brand_name_sql);
                        $stmt->bind_param("s", $brand);
                        $stmt->execute();
                        $brand_result = $stmt->get_result();
                        $brand_name = $brand_result->fetch_assoc();
                    ?>
                        <li class="breadcrumb-item">
                            <a href="products.php?category=<?php echo $category; ?>&brand=<?php echo $brand; ?>">
                                <?php echo htmlspecialchars($brand_name['name']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($search_term)): ?>
                        <li class="breadcrumb-item active">Kết quả tìm kiếm: "<?php echo htmlspecialchars($search_term); ?>"</li>
                    <?php endif; ?>
                </ol>
            </nav>

            <!-- Thêm tiêu đề trang -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="text-center mb-4">
                        <?php
                        if (!empty($category)) {
                            echo htmlspecialchars($cat_name['name']);
                        } else {
                            echo "Tất cả sản phẩm";
                        }
                        ?>
                    </h1>
                </div>
            </div>

            <!-- Bộ lọc -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Lọc sản phẩm</h5>
                            <form action="" method="GET">
                                <!-- Giữ lại tham số tìm kiếm -->
                                <?php if (!empty($search_term)): ?>
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label">Danh mục</label>
                                    <select name="category" class="form-select">
                                        <option value="">Tất cả danh mục</option>
                                        <?php 
                                        $categories_result->data_seek(0);
                                        while ($cat = $categories_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $cat['slug']; ?>"
                                                <?php echo $category == $cat['slug'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Thương hiệu</label>
                                    <select name="brand" class="form-select">
                                        <option value="">Tất cả thương hiệu</option>
                                        <?php 
                                        $brands_result->data_seek(0);
                                        while ($b = $brands_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $b['slug']; ?>"
                                                <?php echo $brand == $b['slug'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($b['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-danger">Áp dụng</button>
                                    <a href="<?php echo 'products.php' . (!empty($search_term) ? '?search=' . urlencode($search_term) : ''); ?>" 
                                       class="btn btn-outline-secondary">Đặt lại</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lưới sản phẩm -->
                <div class="col-md-9">
                    <?php if (!empty($category)): ?>
                        <div class="alert alert-light mb-4">
                            <i class="fas fa-filter"></i>
                            Đang xem sản phẩm trong danh mục: <strong><?php echo htmlspecialchars($cat_name['name']); ?></strong>
                            <?php
                            // Tạo URL xóa bộ lọc có giữ lại tham số tìm kiếm
                            $clear_filter_url = 'products.php';
                            if (!empty($search_term)) {
                                $clear_filter_url .= '?search=' . urlencode($search_term);
                            }
                            ?>
                            <a href="<?php echo $clear_filter_url; ?>" class="float-end text-decoration-none">
                                <i class="fas fa-times"></i> Xóa bộ lọc
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                        ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <div style="height: 300px; overflow: hidden;">
                                            <img src="../<?php echo $row['image_url']; ?>"
                                                class="card-img-top h-100 w-100"
                                                style="object-fit: contain;"
                                                alt="<?php echo htmlspecialchars($row['name']); ?>">
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $row['name']; ?></h5>
                                            <p class="card-text"><?php echo $row['description']; ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-danger fw-bold"><?php echo number_format($row['price'], 0, ',', '.'); ?>đ</span>
                                                <a href="product-detail.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-danger">Chi tiết</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo '<div class="col-12"><p class="text-center">Không tìm thấy sản phẩm nào.</p></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>