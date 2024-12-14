<?php
require_once '../config/database.php';
session_start();

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email, fullname, phone, address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Lấy URL trả về từ session nếu có
$return_url = isset($_SESSION['return_url']) ? $_SESSION['return_url'] : '../index.php';
unset($_SESSION['return_url']); // Xóa sau khi lấy giá trị

// Xử lý cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = isset($_POST['fullname']) ? htmlspecialchars(trim($_POST['fullname'])) : '';
    $phone = isset($_POST['phone']) && !empty($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : null;
    $address = isset($_POST['address']) && !empty($_POST['address']) ? htmlspecialchars(trim($_POST['address'])) : null;
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';

    // Xác thực số điện thoại
    if (!empty($phone) && (!preg_match('/^[0-9]{10}$/', $phone))) {
        $error = "Số điện thoại phải có đúng 10 chữ số";
    } else {
        if (!empty($new_password)) {
            // Xác minh mật khẩu hiện tại
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();

            if (password_verify($current_password, $user_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, address = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $fullname, $phone, $address, $hashed_password, $user_id);
            } else {
                $error = "Mật khẩu hiện tại không chính xác";
            }
        } else {
            // Chỉ cập nhật fullname, phone, address nếu không có mật khẩu mới
            $updateFields = [];
            $params = [];
            
            if (!empty($fullname)) {
                $updateFields[] = "fullname = ?";
                $params[] = $fullname;
            }
            if (!empty($phone)) {
                $updateFields[] = "phone = ?";
                $params[] = $phone;
            }
            if (!empty($address)) {
                $updateFields[] = "address = ?";
                $params[] = $address;
            }
            
            // Chỉ thực hiện cập nhật nếu có trường nào đó không rỗng
            if (!empty($updateFields)) {
                $updateQuery = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
                $params[] = $user_id; // Thêm user_id vào params
                $stmt = $conn->prepare($updateQuery);
                
                // Bind params
                $types = str_repeat("s", count($params) - 1) . "i"; // Tạo chuỗi kiểu dữ liệu cho bind_param
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    $success = "Cập nhật thông tin thành công";
                    // Lấy lại thông tin người dùng sau khi cập nhật
                    $stmt = $conn->prepare("SELECT email, fullname, phone, address FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc(); // Cập nhật lại thông tin người dùng
                } else {
                    $error = "Có lỗi xảy ra, vui lòng thử lại";
                }
            }
        }
    }
}

// Lấy số lượng đơn hàng
$stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$order_count = $stmt->get_result()->fetch_assoc()['order_count'];

// Lấy số lượng đánh giá
$stmt = $conn->prepare("SELECT COUNT(*) as review_count FROM reviews WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$review_count = $stmt->get_result()->fetch_assoc()['review_count'];

// Lấy danh sách đơn hàng của người dùng
$stmt = $conn->prepare("SELECT id, created_at, total_amount, status FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Khởi tạo biến $orders
$orders = []; // Đảm bảo biến $orders được khởi tạo

// Kiểm tra xem có đơn hàng nào không
if ($result->num_rows > 0) {
    while ($order = $result->fetch_assoc()) {
        $orders[] = $order; // Thêm đơn hàng vào mảng $orders
    }
}

// Xử lý nhận hoặc hủy đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $action = $_POST['action'];

    if ($action === 'accept') {
        // Cập nhật trạng thái đơn hàng từ pending thành delivered
        $stmt = $conn->prepare("UPDATE orders SET status = 'delivered' WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        
        // Cập nhật trạng thái thanh toán nếu cần
        $stmt_payment = $conn->prepare("UPDATE payments SET payment_status = 'completed' WHERE order_id = ?");
        $stmt_payment->bind_param("i", $order_id);
    } elseif ($action === 'cancel') {
        // Cập nhật trạng thái đơn hàng từ pending thành cancelled
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        
        // Cập nhật trạng thái thanh toán nếu cần
        $stmt_payment = $conn->prepare("UPDATE payments SET payment_status = 'refunded' WHERE order_id = ?");
        $stmt_payment->bind_param("i", $order_id);
    }

    if (isset($stmt) && $stmt->execute()) {
        if (isset($stmt_payment)) {
            $stmt_payment->execute(); // Cập nhật trạng thái thanh toán
        }
        $success = "Cập nhật trạng thái đơn hàng thành công.";
        
        // Lấy lại thông tin người dùng sau khi cập nhật
        $stmt = $conn->prepare("SELECT email, fullname, phone, address FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc(); // Cập nhật lại thông tin người dùng
    } else {
        $error = "Có lỗi xảy ra, vui lòng thử lại.";
    }
}

// Lấy danh sách đánh giá của người dùng
$stmt = $conn->prepare("SELECT r.id, r.rating, r.comment, r.created_at, p.name FROM reviews r JOIN products p ON r.product_id = p.id WHERE r.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviews_result = $stmt->get_result();

// Khởi tạo biến $reviews
$reviews = []; // Đảm bảo biến $reviews được khởi tạo

// Kiểm tra xem có đánh giá nào không
if ($reviews_result->num_rows > 0) {
    while ($review = $reviews_result->fetch_assoc()) {
        $reviews[] = $review; // Thêm đánh giá vào mảng $reviews
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin tài khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            min-height: 100vh;
            padding: 20px 0;
        }

        .profile-container {
            max-width: 1000px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            padding: 30px;
        }

        .profile-sidebar {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #6c757d;
        }

        .nav-pills .nav-link {
            color: #495057;
            margin: 5px 0;
        }

        .nav-pills .nav-link.active {
            background-color: #0d6efd;
        }

        .form-control {
            border-radius: 5px;
            padding: 10px;
        }

        .btn-update {
            background: #0d6efd;
            color: white;
            padding: 10px 25px;
            border-radius: 5px;
        }

        .stats-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stats-number {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
        }

        .stats-label {
            color: #6c757d;
            font-size: 14px;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .toast {
            background-color: white;
            min-width: 300px;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 8px 15px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: #333;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #f8f9fa;
        }

        .user-email {
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;
            max-width: 200px;
            margin: 0 auto;
        }

        .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>

<body>
    <!-- Nút quay lại -->
    <a href="<?php echo $return_url; ?>" class="back-btn">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>

    <!-- Vùng chứa thông báo -->
    <div class="toast-container">
        <?php if (isset($error)): ?>
            <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $error; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $success; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <div class="profile-container">
            <div class="row">
                <!-- Thanh bên -->
                <div class="col-md-3">
                    <div class="profile-sidebar">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h5 class="mb-3"><?php echo htmlspecialchars($user['fullname']); ?></h5>
                        <p class="text-muted mb-3 user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div class="nav flex-column nav-pills">
                            <a class="nav-link active" href="#profile" onclick="showSection('profile', this)">Thông tin cá nhân</a>
                            <a class="nav-link" href="#orders" onclick="showSection('orders', this)">Đơn hàng của tôi</a>
                            <a class="nav-link" href="#reviews" onclick="showSection('reviews', this)">Đánh giá của tôi</a>
                            <a class="nav-link text-danger" href="logout.php">Đăng xuất</a>
                        </div>
                    </div>
                </div>

                <!-- Nội dung chính -->
                <div class="col-md-9">
                    <!-- Thống kê hồ sơ -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="stats-card text-center">
                                <div class="stats-number"><?php echo $order_count; ?></div>
                                <div class="stats-label">Tổng đơn hàng</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stats-card text-center">
                                <div class="stats-number"><?php echo $review_count; ?></div>
                                <div class="stats-label">Sản phẩm đã đánh giá</div>
                            </div>
                        </div>
                    </div>

                    <!-- Form hồ sơ -->
                    <div class="card" id="profile-section">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Cập nhật thông tin</h4>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="mb-3">
                                    <label for="fullname" class="form-label">Họ và tên</label>
                                    <input type="text" class="form-control" id="fullname" name="fullname"
                                        value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Số điện thoại</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        pattern="[0-9]{10}" title="Vui lòng nhập đúng 10 số"
                                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Địa chỉ</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Mật khẩu mới</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>
                                <button type="submit" class="btn btn-update">Cập nhật thông tin</button>
                            </form>
                        </div>
                    </div>

                    <div class="card d-none" id="orders-section">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Đơn hàng của tôi</h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Mã đơn hàng</th>
                                        <th>Ngày đặt</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Ki���m tra xem mảng $orders có dữ liệu không
                                    if (!empty($orders)) {
                                        foreach ($orders as $order) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($order['id']) . "</td>";
                                            echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
                                            echo "<td>" . number_format($order['total_amount'], 0, ',', '.') . "đ</td>";
                                            echo "<td>" . htmlspecialchars($order['status']) . "</td>";
                                            echo "<td>";
                                            if ($order['status'] === 'pending') {
                                                echo '<form method="POST" style="display:inline;">
                                                        <input type="hidden" name="order_id" value="' . htmlspecialchars($order['id']) . '">
                                                        <input type="hidden" name="action" value="accept">
                                                        <button type="submit" class="btn btn-success btn-sm">Nhận</button>
                                                      </form>';
                                                echo '<form method="POST" style="display:inline;">
                                                        <input type="hidden" name="order_id" value="' . htmlspecialchars($order['id']) . '">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <button type="submit" class="btn btn-danger btn-sm">Hủy</button>
                                                      </form>';
                                            }
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center'>Không có đơn hàng nào.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card d-none" id="reviews-section">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Đánh giá của tôi</h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Đánh giá</th>
                                        <th>Nhận xét</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Kiểm tra xem mảng $reviews có dữ liệu không
                                    if (!empty($reviews)) {
                                        foreach ($reviews as $review) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($review['name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
                                            echo "<td>" . htmlspecialchars($review['comment']) . "</td>";
                                            echo "<td>" . htmlspecialchars($review['created_at']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center'>Không có đánh giá nào.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Khởi tạo tất cả các thông báo
        document.addEventListener('DOMContentLoaded', function() {
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            var toastList = toastElList.map(function(toastEl) {
                var toast = new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 3000
                });
                toast.show();
                return toast;
            });
        });

        function showSection(section, element) {
            // Ẩn tất cả các phần
            document.getElementById('profile-section').classList.add('d-none');
            document.getElementById('orders-section').classList.add('d-none');
            document.getElementById('reviews-section').classList.add('d-none');

            // Hiện phần tương ứng
            if (section === 'profile') {
                document.getElementById('profile-section').classList.remove('d-none');
            } else if (section === 'orders') {
                document.getElementById('orders-section').classList.remove('d-none');
            } else if (section === 'reviews') {
                document.getElementById('reviews-section').classList.remove('d-none');
            }

            // Cập nhật lớp cho các liên kết
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
            });
            element.classList.add('active');
        }

        // Khởi tạo hiển thị phần thông tin cá nhân khi tải trang
        document.addEventListener('DOMContentLoaded', function() {
            showSection('profile', document.querySelector('.nav-link.active'));
        });
    </script>
</body>

</html>