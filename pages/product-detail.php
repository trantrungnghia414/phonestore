<?php
require_once '../config/database.php';
session_start();

// Xử lý thêm đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    // Kiểm tra xem người dùng đã đánh giá sản phẩm này chưa
    $check_stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $existing_review = $check_stmt->get_result()->fetch_assoc();
    
    if ($existing_review) {
        // Cập nhật đánh giá cũ
        $update_stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE user_id = ? AND product_id = ?");
        $update_stmt->bind_param("isii", $rating, $comment, $user_id, $product_id);
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Cập nhật đánh giá thành công!";
        } else {
            $_SESSION['error_message'] = "Có lỗi xảy ra khi cập nhật đánh giá!";
        }
    } else {
        // Thêm đánh giá mới
        $insert_stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);
        if ($insert_stmt->execute()) {
            $_SESSION['success_message'] = "Thêm đánh giá thành công!";
        } else {
            $_SESSION['error_message'] = "Có lỗi xảy ra khi thêm đánh giá!";
        }
    }
    
    // Chuyển hướng để tránh gửi lại form khi refresh
    header('Location: product-detail.php?id=' . $product_id);
    exit();
}

// Lấy product_id từ URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
  header('Location: index.php');
  exit();
}

// Lấy thông tin sản phẩm
$product_query = "SELECT p.*, c.name as category_name, b.name as brand_name 
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN brands b ON p.brand_id = b.id 
                 WHERE p.id = ?";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
  header('Location: index.php');
  exit();
}

// Lấy đánh giá của sản phẩm
$reviews_query = "SELECT r.*, u.fullname 
                 FROM reviews r
                 JOIN users u ON r.user_id = u.id
                 WHERE r.product_id = ?
                 ORDER BY r.created_at DESC";
$stmt = $conn->prepare($reviews_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Tính rating trung bình
$avg_rating = 0;
if (count($reviews) > 0) {
  $total_rating = array_sum(array_column($reviews, 'rating'));
  $avg_rating = round($total_rating / count($reviews), 1);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="../assets/images/logo.ico" type="image/x-icon">
  <title><?php echo htmlspecialchars($product['name']); ?> - Chi tiết sản phẩm</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .product-image {
      max-width: 100%;
      height: auto;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .product-info {
      background: white;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .price {
      font-size: 1.8rem;
      color: #e74c3c;
      font-weight: bold;
    }

    .stock-badge {
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-weight: 500;
    }

    .stock-available {
      background: #e8f5e9;
      color: #2e7d32;
    }

    .stock-low {
      background: #fff3e0;
      color: #ef6c00;
    }

    .stock-out {
      background: #ffebee;
      color: #c62828;
    }

    .review-card {
      background: white;
      padding: 1.5rem;
      border-radius: 10px;
      margin-bottom: 1rem;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .star-rating {
      color: #ffc107;
    }

    .btn-add-cart {
      background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
      color: white;
      padding: 1rem 2rem;
      border: none;
      border-radius: 25px;
      font-weight: 600;
      transition: transform 0.3s;
    }

    .btn-add-cart:hover {
      transform: translateY(-2px);
      color: white;
    }

    .specs-table td {
      padding: 0.75rem;
    }

    .specs-table tr:nth-child(even) {
      background: #f8f9fa;
    }

    .star-rating-input .btn-outline-warning {
      color: #ffc107;
      border-color: #ffc107;
    }

    .star-rating-input .btn-check:checked + .btn-outline-warning {
      background-color: #ffc107;
      color: white;
    }

    .toast {
        background: rgba(13, 202, 240, 0.9) !important;
        color: white;
        min-width: 300px;
        text-align: center;
        padding: 15px;
        font-size: 1.1rem;
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        border-radius: 10px;
    }

    .flying-item {
        position: absolute;
        z-index: 9999;
        pointer-events: none;
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 50%;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    }

    .cart-animation {
        animation: cartShake 0.5s ease-in-out;
    }

    @keyframes cartShake {
        0%, 100% { transform: rotate(0deg); }
        20% { transform: rotate(-10deg); }
        40% { transform: rotate(10deg); }
        60% { transform: rotate(-5deg); }
        80% { transform: rotate(5deg); }
    }

    .badge-pop {
        animation: badgePop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes badgePop {
        0% { transform: scale(0); }
        80% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
  </style>
</head>

<body>
  <?php include '../includes/header.php'; ?>
  <?php include '../includes/navbar.php'; ?>

  <div class="container py-5">
    <div class="row">
      <!-- Hình ảnh sản phẩm -->
      <div class="col-md-6 mb-4">
        <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
      </div>

      <!-- Thông tin sản phẩm -->
      <div class="col-md-6">
        <div class="product-info">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
              <?php if (isset($product['category_name']) && isset($product['category_id'])): ?>
                <li class="breadcrumb-item">
                  <a href="products.php?category=<?php echo htmlspecialchars($product['category_slug'] ?? ''); ?>">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                  </a>
                </li>
              <?php endif; ?>

              <?php if (isset($product['brand_name']) && isset($product['brand_id'])): ?>
                <li class="breadcrumb-item">
                  <a href="products.php?category=<?php echo htmlspecialchars($product['category_slug'] ?? ''); ?>&brand=<?php echo htmlspecialchars($product['brand_slug'] ?? ''); ?>">
                    <?php echo htmlspecialchars($product['brand_name']); ?>
                  </a>
                </li>
              <?php endif; ?>

              <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars($product['name'] ?? ''); ?>
              </li>
            </ol>
          </nav>

          <h1 class="h2 mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>

          <div class="d-flex align-items-center mb-3">
            <div class="star-rating me-2">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star<?php echo $i <= $avg_rating ? '' : '-o'; ?>"></i>
              <?php endfor; ?>
            </div>
            <span class="text-muted">(<?php echo count($reviews); ?> đánh giá)</span>
          </div>

          <p class="price mb-3"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</p>

          <?php
          $stock_class = 'stock-available';
          $stock_text = 'Còn hàng';
          if ($product['stock_quantity'] <= 0) {
            $stock_class = 'stock-out';
            $stock_text = 'Hết hàng';
          } elseif ($product['stock_quantity'] <= 5) {
            $stock_class = 'stock-low';
            $stock_text = 'Sắp hết hàng';
          }
          ?>
          <span class="stock-badge <?php echo $stock_class; ?> mb-3 d-inline-block">
            <i class="fas fa-box me-1"></i><?php echo $stock_text; ?>
          </span>

          <div class="mb-4">
            <h5>Mô tả sản phẩm:</h5>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
          </div>

          <table class="table specs-table mb-4">
            <tr>
              <td><i class="fas fa-tag me-2"></i>Thương hiệu:</td>
              <td><?php echo htmlspecialchars($product['brand_name']); ?></td>
            </tr>
            <tr>
              <td><i class="fas fa-boxes me-2"></i>Danh mục:</td>
              <td><?php echo htmlspecialchars($product['category_name']); ?></td>
            </tr>
          </table>

          <!-- Form thêm vào giỏ -->
          <?php if ($product['stock_quantity'] > 0): ?>
              <form id="addToCartForm" class="mb-3">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <div class="input-group mb-3">
                      <span class="input-group-text">Số lượng</span>
                      <input type="number" class="form-control" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                  </div>
                  <button type="submit" class="btn btn-add-cart w-100">
                      <i class="fas fa-shopping-cart me-2"></i>Thêm vào giỏ hàng
                  </button>
              </form>

              <!-- Toast Notification -->
              <div class="toast-container position-fixed top-50 start-50 translate-middle">
                  <div class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true" id="cartToast">
                      <div class="d-flex">
                          <div class="toast-body"></div>
                          <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                      </div>
                  </div>
              </div>

              <script>
              document.getElementById('addToCartForm').addEventListener('submit', function(e) {
                  e.preventDefault();
                  
                  <?php if (!isset($_SESSION['user_id'])): ?>
                      showToast('Vui lòng đăng nhập để thêm vào giỏ hàng', 'danger');
                      return;
                  <?php endif; ?>

                  const productImage = document.querySelector('.product-image');
                  const headerCart = document.querySelector('header .fa-shopping-cart').closest('a');
                  const cartRect = headerCart.getBoundingClientRect();
                  const imgRect = productImage.getBoundingClientRect();

                  // Tạo phần tử bay
                  const flyingImg = document.createElement('img');
                  flyingImg.src = productImage.src;
                  flyingImg.className = 'flying-item';
                  flyingImg.style.left = `${imgRect.left + window.scrollX}px`;
                  flyingImg.style.top = `${imgRect.top + window.scrollY}px`;
                  document.body.appendChild(flyingImg);

                  // Tính toán điểm đến
                  const endLeft = cartRect.left + window.scrollX + (cartRect.width / 2) - 50;
                  const endTop = cartRect.top + window.scrollY + (cartRect.height / 2) - 50;

                  // Tạo keyframes động cho animation
                  const keyframes = `
                      @keyframes flyToCart {
                          0% {
                              transform: translate(0, 0) scale(1) rotate(0deg);
                              opacity: 1;
                          }
                          50% {
                              transform: translate(${(endLeft - (imgRect.left + window.scrollX))/2}px, 
                                         ${(endTop - (imgRect.top + window.scrollY))/2 - 100}px) 
                                          scale(0.5) rotate(180deg);
                              opacity: 0.8;
                          }
                          100% {
                              transform: translate(${endLeft - (imgRect.left + window.scrollX)}px, 
                                         ${endTop - (imgRect.top + window.scrollY)}px) 
                                          scale(0.1) rotate(360deg);
                              opacity: 0;
                          }
                      }
                  `;

                  // Thêm style animation vào head
                  const styleSheet = document.createElement('style');
                  styleSheet.textContent = keyframes;
                  document.head.appendChild(styleSheet);

                  // Áp dụng animation
                  flyingImg.style.animation = 'flyToCart 1s cubic-bezier(0.47, 0, 0.745, 0.715) forwards';

                  flyingImg.addEventListener('animationend', () => {
                      flyingImg.remove();
                      styleSheet.remove();
                      
                      // Hiệu ứng rung cho icon giỏ hàng
                      headerCart.classList.add('cart-animation');
                      
                      const formData = new FormData(this);
                      fetch('add-to-cart.php', {
                          method: 'POST',
                          body: formData
                      })
                      .then(response => response.json())
                      .then(data => {
                          if (data.success) {
                              updateCartBadge(data.cart_count);
                              const badge = headerCart.querySelector('.badge');
                              if (badge) {
                                  badge.classList.add('badge-pop');
                                  setTimeout(() => badge.classList.remove('badge-pop'), 300);
                              }
                          } else {
                              showToast(data.message, 'danger');
                          }
                      })
                      .catch(error => {
                          showToast('Đã có lỗi xảy ra', 'danger');
                      });

                      setTimeout(() => {
                          headerCart.classList.remove('cart-animation');
                      }, 500);
                  });
              });

              function updateCartBadge(count) {
                  const cartIcon = document.querySelector('header .fa-shopping-cart');
                  let badge = cartIcon.parentElement.querySelector('.badge');
                  
                  if (count > 0) {
                      if (!badge) {
                          badge = document.createElement('span');
                          badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                          cartIcon.parentNode.appendChild(badge);
                      }
                      badge.textContent = count;
                  } else if (badge) {
                      badge.remove();
                  }
              }

              function showToast(message, type) {
                  const toast = document.getElementById('cartToast');
                  const toastBody = toast.querySelector('.toast-body');
                  
                  toast.classList.remove('bg-success', 'bg-danger');
                  toast.classList.add(`bg-${type}`);
                  toastBody.textContent = message;
                  
                  const bsToast = new bootstrap.Toast(toast, {
                      animation: true,
                      autohide: true,
                      delay: 4000
                  });
                  bsToast.show();
              }
              </script>
          <?php else: ?>
              <div class="alert alert-warning">
                  <i class="fas fa-exclamation-triangle me-2"></i>Hết hàng
              </div>
          <?php endif; ?>

          <?php if (isset($_SESSION['success'])): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <?php 
                  echo $_SESSION['success'];
                  unset($_SESSION['success']);
                  ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
          <?php endif; ?>

          <?php if (isset($_SESSION['error'])): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <?php 
                  echo $_SESSION['error'];
                  unset($_SESSION['error']);
                  ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Phần đánh giá -->
    <div class="row mt-5">
      <div class="col-12">
        <h3 class="mb-4">Đánh giá sản phẩm</h3>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Vui lòng <a href="../auth/login.php?return_url=<?php echo urlencode('/pages/product-detail.php?id=' . $product_id); ?>" class="alert-link">đăng nhập</a> để đánh giá sản phẩm
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Form đánh giá -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Viết đánh giá của bạn</h5>
                    <form action="product-detail.php?id=<?php echo $product_id; ?>" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Đánh giá sao</label>
                            <div class="star-rating-input mb-2">
                                <div class="btn-group" role="group">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" class="btn-check" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                        <label class="btn btn-outline-warning" for="star<?php echo $i; ?>">
                                            <i class="fas fa-star"></i> <?php echo $i; ?>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="comment" class="form-label">Nhận xét của bạn</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" required 
                                      placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Gửi đánh giá
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Hiển thị đánh giá -->
        <?php if (empty($reviews)): ?>
            <p class="text-muted">Chưa có đánh giá nào cho sản phẩm này.</p>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><?php echo htmlspecialchars($review['fullname']); ?></h6>
                        <small class="text-muted">
                            <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                        </small>
                    </div>
                    <div class="star-rating mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php include '../includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Tự động ẩn toast sau 3 giây
    document.addEventListener('DOMContentLoaded', function() {
      let toasts = document.querySelectorAll('.toast');
      toasts.forEach(function(toast) {
        setTimeout(function() {
          bootstrap.Toast.getInstance(toast).hide();
        }, 3000);
      });
    });
  </script>
</body>

</html>
</html>