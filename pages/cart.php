<?php
session_start();
require_once '../config/database.php';

// Xử lý AJAX request để cập nhật giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'subtotal' => '0', 'total' => '0', 'cart_count' => 0];

    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Vui lòng đăng nhập để thực hiện chức năng này';
        echo json_encode($response);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['product_id'])) {
        $product_id = $data['product_id'];
        $user_id = $_SESSION['user_id'];

        // Kiểm tra sản phẩm có tồn tại và còn hàng không
        $check_product = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $check_product->bind_param("i", $product_id);
        $check_product->execute();
        $product = $check_product->get_result()->fetch_assoc();

        if ($product) {
            if ($data['action'] === 'update') {
                $new_quantity = (int)$data['quantity'];
                
                // Kiểm tra số lượng mới có hợp lệ không
                if ($new_quantity > $product['stock_quantity']) {
                    $response['message'] = 'Số lượng vượt quá hàng tồn kho!';
                } else if ($new_quantity < 1) {
                    // Nếu số lượng < 1, xóa sản phẩm khỏi giỏ hàng
                    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                    $stmt->bind_param("ii", $user_id, $product_id);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Đã xóa sản phẩm khỏi giỏ hàng';
                    }
                } else {
                    // Cập nhật số lượng mới
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                    $stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Đã cập nhật số lượng';
                    }
                }
            } else if ($data['action'] === 'remove') {
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $user_id, $product_id);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Đã xóa sản phẩm khỏi giỏ hàng';
                }
            }

            // Nếu thành công, cập nhật tổng giá trị giỏ hàng
            if ($response['success']) {
                // Lấy tổng số lượng và giá trị mới
                $total_query = $conn->prepare("
                    SELECT SUM(c.quantity) as total_items,
                           SUM(c.quantity * p.price) as total_amount
                    FROM cart c
                    JOIN products p ON c.product_id = p.id
                    WHERE c.user_id = ?
                ");
                $total_query->bind_param("i", $user_id);
                $total_query->execute();
                $totals = $total_query->get_result()->fetch_assoc();

                $response['cart_count'] = $totals['total_items'] ?? 0;
                $response['subtotal'] = number_format($totals['total_amount'] ?? 0, 0, ',', '.');
                $response['total'] = number_format($totals['total_amount'] ?? 0, 0, ',', '.');
            }
        } else {
            $response['message'] = 'Không tìm thấy sản phẩm!';
        }
    } else {
        $response['message'] = 'Dữ liệu không hợp lệ!';
    }

    echo json_encode($response);
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?return_url=' . urlencode('/pages/cart.php'));
    exit();
}

// Lấy thông tin giỏ hàng từ CSDL
$cart_items = [];
$total = 0;
$total_items = 0;

$stmt = $conn->prepare("
    SELECT c.id as cart_id, c.quantity, p.id, p.name, p.price, p.stock_quantity, p.image_url 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($item = $result->fetch_assoc()) {
    $cart_items[] = $item;
    $total += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

// Include header
require_once '../includes/header.php';
?>

<!-- Phần Giỏ Hàng -->
<section class="cart-section py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Giỏ hàng của bạn</h1>
            <?php if(!empty($cart_items)): ?>
                <span class="text-muted">
                    (<?php echo $total_items; ?> sản phẩm)
                </span>
            <?php endif; ?>
        </div>

        <?php if(empty($cart_items)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-shopping-cart fa-4x text-muted"></i>
                </div>
                <h3>Giỏ hàng trống</h3>
                <p class="text-muted mb-4">Hãy thêm sản phẩm vào giỏ hàng của bạn</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Tiếp tục mua sắm
                </a>
            </div>
        <?php else: ?>
            <!-- Toast Notification -->
            <div class="toast-container position-fixed top-50 start-50 translate-middle">
                <div class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true" id="cartToast">
                    <div class="d-flex">
                        <div class="toast-body"></div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Các Sản Phẩm Trong Giỏ Hàng -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <?php foreach($cart_items as $item): ?>
                                <div class="row align-items-center mb-3 pb-3 border-bottom cart-item" data-product-id="<?php echo $item['id']; ?>">
                                    <div class="col-md-2">
                                        <img src="../<?php echo $item['image_url']; ?>" 
                                             class="img-fluid rounded" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="text-muted mb-0">Đơn giá: <?php echo number_format($item['price'], 0, ',', '.'); ?>₫</p>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary decrease-btn" type="button" 
                                                    onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control text-center quantity-input" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   max="<?php echo $item['stock_quantity']; ?>"
                                                   onchange="updateQuantity(<?php echo $item['id']; ?>, 'set', this.value)"
                                                   data-product-id="<?php echo $item['id']; ?>">
                                            <button class="btn btn-outline-secondary increase-btn" type="button"
                                                    onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <span class="fw-bold item-total">
                                            <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>₫
                                        </span>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button class="btn btn-link text-danger p-0" onclick="removeItem(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Nút Tiếp Tục Mua Sắm -->
                    <div class="text-start mb-4">
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Tiếp tục mua sắm
                        </a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Tóm Tắt Đơn Hàng -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Tổng đơn hàng</h4>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Tạm tính (<?php echo $total_items; ?> sản phẩm)</span>
                                <span class="fw-bold cart-subtotal"><?php echo number_format($total, 0, ',', '.'); ?>₫</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Phí vận chuyển</span>
                                <span class="text-success">Miễn phí</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <span class="fw-bold">Tổng cộng</span>
                                <span class="fw-bold text-danger h4 mb-0 cart-total">
                                    <?php echo number_format($total, 0, ',', '.'); ?>₫
                                </span>
                            </div>
                            <a href="checkout.php" class="btn btn-danger w-100 mb-3">
                                Tiến hành thanh toán
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
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

.quantity-input {
    max-width: 60px;
}

.cart-item {
    transition: all 0.3s ease;
}

.cart-item:hover {
    background-color: #f8f9fa;
}
</style>

<script>
function updateQuantity(productId, action, value = null) {
    const quantityInput = event.target.closest('.input-group').querySelector('.quantity-input');
    const currentQuantity = parseInt(quantityInput.value);
    let newQuantity;

    // Xác định số lượng mới
    switch(action) {
        case 'increase':
            newQuantity = currentQuantity + 1;
            break;
        case 'decrease':
            newQuantity = currentQuantity - 1;
            break;
        case 'set':
            newQuantity = parseInt(value);
            break;
        default:
            return;
    }

    // Kiểm tra số lượng tồn kho
    const maxQuantity = parseInt(quantityInput.getAttribute('max'));
    if (newQuantity > maxQuantity) {
        showToast(`Chỉ còn ${maxQuantity} sản phẩm trong kho`, 'danger');
        return;
    }

    // Kiểm tra số lượng tối thiểu
    if (newQuantity < 1) {
        if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
            quantityInput.value = currentQuantity;
            return;
        }
    }

    // Cập nhật giao diện trước
    quantityInput.value = Math.max(0, newQuantity);

    // Gửi request cập nhật
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: newQuantity,
            action: 'update'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (newQuantity === 0) {
                // Xóa sản phẩm khỏi DOM với animation
                const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                cartItem.style.opacity = '0';
                cartItem.style.transform = 'translateX(100px)';
                setTimeout(() => {
                    cartItem.remove();
                    if (document.querySelectorAll('.cart-item').length === 0) {
                        location.reload();
                    }
                }, 300);
            } else {
                // Cập nhật giá tiền của item
                const itemPrice = parseFloat(quantityInput.closest('.cart-item').querySelector('.text-muted').textContent.replace(/[^\d]/g, ''));
                const itemTotal = itemPrice * newQuantity;
                quantityInput.closest('.cart-item').querySelector('.item-total').textContent = 
                    new Intl.NumberFormat('vi-VN').format(itemTotal) + '₫';
            }

            // Cập nhật tổng tiền
            document.querySelector('.cart-subtotal').textContent = data.subtotal + '₫';
            document.querySelector('.cart-total').textContent = data.total + '₫';

            // Cập nhật badge số lượng trong header
            const cartBadge = document.querySelector('header .fa-shopping-cart + .badge');
            if (cartBadge) {
                if (data.cart_count > 0) {
                    cartBadge.textContent = data.cart_count;
                } else {
                    cartBadge.remove();
                }
            }

            // Animation cho giá khi thay đổi
            const totalElement = document.querySelector('.cart-total');
            totalElement.style.animation = 'priceUpdate 0.5s ease';
            setTimeout(() => totalElement.style.animation = '', 500);
        } else {
            // Nếu có lỗi, khôi phục giá trị cũ
            quantityInput.value = currentQuantity;
            showToast(data.message, 'danger');
        }
    })
    .catch(error => {
        quantityInput.value = currentQuantity;
        showToast('Đã có lỗi xảy ra', 'danger');
    });
}

function removeItem(productId) {
    if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
        fetch('cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                product_id: productId,
                action: 'remove'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                cartItem.style.opacity = '0';
                cartItem.style.transform = 'translateX(100px)';
                setTimeout(() => {
                    cartItem.remove();
                    if (document.querySelectorAll('.cart-item').length === 0) {
                        location.reload();
                    }
                }, 300);

                // Cập nhật tổng tiền và số lượng
                document.querySelector('.cart-subtotal').textContent = data.subtotal + '₫';
                document.querySelector('.cart-total').textContent = data.total + '₫';
                
                const cartBadge = document.querySelector('header .fa-shopping-cart + .badge');
                if (cartBadge) {
                    if (data.cart_count > 0) {
                        cartBadge.textContent = data.cart_count;
                    } else {
                        cartBadge.remove();
                    }
                }

                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'danger');
            }
        })
        .catch(error => {
            showToast('Đã có lỗi xảy ra', 'danger');
        });
    }
}

// Thêm CSS cho animations
const style = document.createElement('style');
style.textContent = `
    .cart-item {
        transition: all 0.3s ease;
    }
    @keyframes priceUpdate {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(style);

function showToast(message, type) {
    const toast = document.getElementById('cartToast');
    const toastBody = toast.querySelector('.toast-body');
    
    toast.classList.remove('bg-success', 'bg-danger');
    toast.classList.add(`bg-${type}`);
    toastBody.textContent = message;
    
    const bsToast = new bootstrap.Toast(toast, {
        animation: true,
        autohide: true,
        delay: 3000
    });
    bsToast.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
