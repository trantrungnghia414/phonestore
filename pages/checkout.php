<?php
session_start();
require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?return_url=' . urlencode('/pages/checkout.php'));
    exit();
}

// Kiểm tra giỏ hàng có sản phẩm không
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$cart_count = $result->fetch_assoc()['count'];

if ($cart_count == 0) {
    header('Location: cart.php');
    exit();
}

// Sau khi kiểm tra xong mới include header
require_once '../includes/header.php';

// Lấy thông tin giỏ hàng từ CSDL
$cart_items = [];
$total_amount = 0;

$stmt = $conn->prepare("
    SELECT c.quantity, p.id, p.name, p.price 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($item = $result->fetch_assoc()) {
    $cart_items[] = $item;
    $total_amount += $item['price'] * $item['quantity'];
}

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Bắt đầu transaction
        $conn->begin_transaction();

        // 1. Tạo đơn hàng mới
        $order_sql = "INSERT INTO orders (user_id, total_amount, shipping_address, phone, status, payment_status) 
                     VALUES (?, ?, ?, ?, 'pending', 'unpaid')";
        $stmt = $conn->prepare($order_sql);
        $stmt->bind_param(
            "idss",
            $_SESSION['user_id'],
            $total_amount,
            $_POST['shipping_address'],
            $_POST['phone']
        );
        $stmt->execute();
        $order_id = $conn->insert_id;

        // 2. Thêm chi tiết đơn hàng
        $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($item_sql);

        foreach ($cart_items as $item) {
            $stmt->bind_param(
                "iiid",
                $order_id,
                $item['id'],
                $item['quantity'],
                $item['price']
            );
            $stmt->execute();

            // Cập nhật số lượng sản phẩm
            $update_stock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $stmt2 = $conn->prepare($update_stock);
            $stmt2->bind_param("ii", $item['quantity'], $item['id']);
            $stmt2->execute();
        }

        // 3. Tạo thanh toán
        $payment_sql = "INSERT INTO payments (order_id, amount, payment_method, payment_status) 
                       VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($payment_sql);
        $stmt->bind_param(
            "ids",
            $order_id,
            $total_amount,
            $_POST['payment_method']
        );
        $stmt->execute();

        // 4. Xóa giỏ hàng
        $delete_cart = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($delete_cart);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Hiển thị thông báo thành công
        $success = true;
        $order_success_id = $order_id;
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
        $error = "Đã có lỗi xảy ra, vui lòng thử lại!";
    }
}
?>

<div class="container py-5">
  <?php if (isset($success) && $success): ?>
    <div class="text-center">
      <div class="mb-4">
        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
      </div>
      <h2 class="mb-4">Đặt hàng thành công!</h2>
      <p class="lead">Cảm ơn bạn đã đặt hàng. Mã đơn hàng của bạn là: <strong>#<?php echo $order_success_id; ?></strong></p>
      <p>Chúng tôi sẽ sớm liên hệ với bạn để xác nhận đơn hàng.</p>
      <div class="mt-4">
        <a href="products.php" class="btn btn-primary me-2">Tiếp tục mua sắm</a>
        <a href="profile.php?user_id=<?php echo $_SESSION['user_id']; ?>" class="btn btn-outline-primary">Xem đơn hàng</a>
      </div>
    </div>
  <?php else: ?>
    <div class="row">
      <div class="col-md-8">
        <div class="card shadow-sm">
          <div class="card-body">
            <h3 class="card-title mb-4">Thông tin đặt hàng</h3>
            <?php if (isset($error)): ?>
              <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="checkout.php">
              <div class="mb-4">
                <h5>Thông tin giao hàng</h5>
                <div class="mb-3">
                  <label class="form-label">Địa chỉ giao hàng</label>
                  <input type="text" name="shipping_address" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Số điện thoại</label>
                  <input type="tel" name="phone" class="form-control" required>
                </div>
              </div>

              <div class="mb-4">
                <h5>Phương thức thanh toán</h5>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="payment_method" value="cod" id="cod" checked>
                  <label class="form-check-label" for="cod">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Thanh toán khi nhận hàng
                  </label>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="payment_method" value="banking" id="banking">
                  <label class="form-check-label" for="banking">
                    <i class="fas fa-university me-2"></i>
                    Chuyển khoản ngân hàng
                  </label>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="payment_method" value="momo" id="momo">
                  <label class="form-check-label" for="momo">
                    <i class="fas fa-wallet me-2"></i>
                    Ví MoMo
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="payment_method" value="vnpay" id="vnpay">
                  <label class="form-check-label" for="vnpay">
                    <i class="fas fa-credit-card me-2"></i>
                    VNPAY
                  </label>
                </div>
              </div>

              <button type="submit" class="btn btn-primary btn-lg w-100">Đặt hàng</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title mb-4">Đơn hàng của bạn</h5>
            <?php foreach ($cart_items as $item): ?>
              <div class="d-flex justify-content-between mb-3">
                <div>
                  <h6 class="mb-0"><?php echo $item['name']; ?></h6>
                  <small class="text-muted">Số lượng: <?php echo $item['quantity']; ?></small>
                </div>
                <div class="text-end">
                  <span class="fw-bold"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                </div>
              </div>
            <?php endforeach; ?>
            <hr>
            <div class="d-flex justify-content-between mb-2">
              <span>Tạm tính</span>
              <span class="fw-bold"><?php echo number_format($total_amount, 0, ',', '.'); ?>đ</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Phí vận chuyển</span>
              <span class="text-success">Miễn phí</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-0">
              <span class="h5">Tổng cộng</span>
              <span class="h5 text-primary"><?php echo number_format($total_amount, 0, ',', '.'); ?>đ</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>