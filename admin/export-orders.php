<?php
require_once '../config/database.php';
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Thiết lập header cho file Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="don-hang.xls"');
header('Cache-Control: max-age=0');

// Lấy danh sách đơn hàng
$query = "SELECT o.id, o.created_at, o.total_amount, o.status, o.shipping_address, o.phone,
                 u.fullname, u.email,
                 p.payment_method, p.payment_status
          FROM orders o
          JOIN users u ON o.user_id = u.id
          LEFT JOIN payments p ON o.id = p.order_id
          ORDER BY o.created_at DESC";
$orders = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Mapping trạng thái đơn hàng
$status_text = [
    'pending' => 'Đang vận chuyển',
    'delivered' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];

// Mapping phương thức thanh toán
$payment_method_text = [
    'cod' => 'Thanh toán khi nhận hàng',
    'banking' => 'Chuyển khoản ngân hàng',
    'momo' => 'Ví MoMo'
];

// Tạo header cho file Excel
echo '<table border="1">';
echo '<tr>
        <th>Mã đơn hàng</th>
        <th>Ngày đặt</th>
        <th>Khách hàng</th>
        <th>Email</th>
        <th>Số điện thoại</th>
        <th>Địa chỉ</th>
        <th>Tổng tiền</th>
        <th>Phương thức thanh toán</th>
        <th>Trạng thái thanh toán</th>
        <th>Trạng thái đơn hàng</th>
      </tr>';

// Xuất dữ liệu
foreach ($orders as $order) {
    echo '<tr>';
    echo '<td>#' . $order['id'] . '</td>';
    echo '<td>' . date('d/m/Y H:i', strtotime($order['created_at'])) . '</td>';
    echo '<td>' . htmlspecialchars($order['fullname']) . '</td>';
    echo '<td>' . htmlspecialchars($order['email']) . '</td>';
    echo '<td>' . htmlspecialchars($order['phone']) . '</td>';
    echo '<td>' . htmlspecialchars($order['shipping_address']) . '</td>';
    echo '<td>' . number_format($order['total_amount'], 0, ',', '.') . 'đ</td>';
    echo '<td>' . ($payment_method_text[$order['payment_method']] ?? 'Không xác định') . '</td>';
    echo '<td>' . ($order['payment_status'] == 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán') . '</td>';
    echo '<td>' . ($status_text[$order['status']] ?? 'Không xác định') . '</td>';
    echo '</tr>';
}

echo '</table>'; 