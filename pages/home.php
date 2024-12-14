<?php
ob_start(); // Bật output buffering
include_once '../includes/header.php';

// Function để lấy danh sách danh mục và sản phẩm nổi bật
function getFeaturedCategories()
{
    global $conn;

    $sql = "SELECT c.*, COUNT(p.id) as product_count,
            (SELECT CONCAT('../', p2.image_url)
             FROM products p2 
             LEFT JOIN reviews r ON p2.id = r.product_id 
             WHERE p2.category_id = c.id 
             GROUP BY p2.id 
             ORDER BY COUNT(r.id) DESC, p2.created_at DESC 
             LIMIT 1) as featured_image,
            (SELECT p2.name 
             FROM products p2 
             LEFT JOIN reviews r ON p2.id = r.product_id 
             WHERE p2.category_id = c.id 
             GROUP BY p2.id 
             ORDER BY COUNT(r.id) DESC, p2.created_at DESC 
             LIMIT 1) as featured_product
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            GROUP BY c.id
            ORDER BY c.name";

    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Lấy danh sách danh mục
$categories = getFeaturedCategories();

// Debug để xem giá trị
// echo "<pre>"; print_r($categories); echo "</pre>";
?>

<!-- Phần Hero -->
<section class="hero bg-light py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold mb-4">Chào mừng đến với Phone Store</h1>
                <p class="lead mb-4">Khám phá bộ sưu tập điện thoại thông minh mới nhất với giá cả hợp lý và dịch vụ chất lượng.</p>
                <a href="../pages/products.php" class="btn btn-danger btn-lg">Xem sản phẩm</a>
            </div>
            <div class="col-md-6">
                <img src="../assets/images/hero-phone.png" alt="Latest Smartphones" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<!-- Danh mục nổi bật -->
<section class="featured-categories mb-5">
    <div class="container">
        <h2 class="text-center mb-4">Danh mục nổi bật</h2>
        <div class="position-relative">
            <div id="categoryCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php foreach ($categories as $index => $category): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="row">
                                <?php
                                $totalCategories = count($categories);
                                for ($i = 0; $i < 4; $i++):
                                    $currentIndex = ($index + $i) % $totalCategories;
                                    $currentCategory = $categories[$currentIndex];
                                ?>
                                    <div class="col-md-3">
                                        <div class="card h-100">
                                            <div class="card-img-wrapper">
                                                <?php if (!empty($currentCategory['featured_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($currentCategory['featured_image']); ?>"
                                                        class="card-img-top" alt="<?php echo htmlspecialchars($currentCategory['name']); ?>">
                                                <?php else: ?>
                                                    <img src="../assets/images/default.png"
                                                        class="card-img-top" alt="<?php echo htmlspecialchars($currentCategory['name']); ?>">
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body text-center">
                                                <h5 class="card-title"><?php echo htmlspecialchars($currentCategory['name']); ?></h5>
                                                <p class="card-text">
                                                    <?php echo $currentCategory['featured_product'] ? htmlspecialchars($currentCategory['featured_product']) : 'Chưa có sản phẩm'; ?>
                                                </p>
                                                <a href="products.php?category=<?php echo $currentCategory['slug']; ?>"
                                                    class="btn btn-outline-danger">Xem thêm</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="carousel-control-prev" type="button" data-bs-target="#categoryCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#categoryCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </div>
</section>

<style>
    .featured-categories {
        overflow: hidden;
        padding: 60px 0;
        background: linear-gradient(to right, #f8f9fa, #ffffff, #f8f9fa);
    }

    #categoryCarousel {
        padding: 30px 60px;
    }

    .carousel-item {
        transition: transform 1s ease-in-out;
    }

    .featured-categories h2 {
        font-size: 2.5rem;
        margin-bottom: 2rem;
        font-weight: 600;
    }

    .card {
        background: #fff;
        border: none;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        height: 100%;
        display: flex;
        flex-direction: column;
        margin: 10px 0;
    }

    .card-img-wrapper {
        position: relative;
        width: 100%;
        height: auto;
        overflow: hidden;
    }

    .card-img-wrapper img {
        width: 100%;
        height: auto;
        object-fit: contain;
        max-height: 250px;
        padding: 15px;
    }

    .card-body {
        padding: 2rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .card-title {
        font-size: 1.4rem;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .card-text {
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }

    .btn-outline-danger {
        padding: 10px 25px;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .carousel-control-prev,
    .carousel-control-next {
        width: 50px;
        height: 50px;
        top: 50%;
        transform: translateY(-50%);
        background: #fff;
        border-radius: 50%;
        opacity: 0.8;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .carousel-control-prev {
        left: 20px;
    }

    .carousel-control-next {
        right: 20px;
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        width: 25px;
        height: 25px;
        filter: invert(1) grayscale(100);
    }

    .row {
        display: flex;
        flex-wrap: wrap;
    }

    .col-md-3 {
        display: flex;
        padding: 0 15px;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    @media (min-width: 1200px) {
        .featured-categories .container {
            max-width: 1300px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var carousel = new bootstrap.Carousel(document.getElementById('categoryCarousel'), {
            interval: 10000,
            wrap: true,
            touch: true,
            pause: 'hover'
        });
    });
</script>

<!-- Ưu đãi đặc biệt -->
<section class="special-offers mb-5 bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">Ưu đãi đặc biệt</h2>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-4">
                                <img src="../assets/images/offer1.png" alt="Special Offer 1" class="img-fluid">
                            </div>
                            <div class="col-8">
                                <h5 class="card-title">Giảm đến 20%</h5>
                                <p class="card-text">Cho tất cả các dòng iPhone 13 series</p>
                                <a href="products.php?category=dien-thoai-di-dong&brand=apple" class="btn btn-danger">Mua ngay</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-4">
                                <img src="../assets/images/offer2.png" alt="Special Offer 2" class="img-fluid">
                            </div>
                            <div class="col-8">
                                <h5 class="card-title">Trả góp 0%</h5>
                                <p class="card-text">Áp dụng cho Samsung Galaxy S series</p>
                                <a href="products.php?category=dien-thoai-di-dong&brand=samsung" class="btn btn-danger">Tìm hiểu thêm</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tại sao chọn chúng tôi -->
<section class="why-choose-us mb-5">
    <div class="container">
        <h2 class="text-center mb-4">Tại sao chọn Phone Store?</h2>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-shield-alt fa-3x text-danger mb-3"></i>
                    <h5>Bảo hành chính hãng</h5>
                    <p>100% sản phẩm được bảo hành chính hãng</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-truck fa-3x text-danger mb-3"></i>
                    <h5>Giao hàng miễn phí</h5>
                    <p>Miễn phí giao hàng toàn quốc</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-sync fa-3x text-danger mb-3"></i>
                    <h5>Đổi trả dễ dàng</h5>
                    <p>30 ngày đổi trả miễn phí</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <i class="fas fa-headset fa-3x text-danger mb-3"></i>
                    <h5>Hỗ trợ 24/7</h5>
                    <p>Tư vấn chuyên nghiệp mọi lúc</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../includes/footer.php';
?>