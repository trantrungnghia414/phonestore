<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thêm vào giỏ hàng'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Kiểm tra sản phẩm tồn tại và còn hàng
    $stmt = $conn->prepare("SELECT id, stock_quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
        if ($product['stock_quantity'] < $quantity) {
            $response['message'] = "Số lượng sản phẩm trong kho không đủ!";
        } else {
            // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
            $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Cập nhật số lượng
                $current_cart = $result->fetch_assoc();
                $new_quantity = $current_cart['quantity'] + $quantity;
                
                if ($product['stock_quantity'] < $new_quantity) {
                    $response['message'] = "Số lượng sản phẩm trong kho không đủ!";
                } else {
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                    $stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = "Đã cập nhật số lượng trong giỏ hàng!";
                    }
                }
            } else {
                // Thêm mới vào giỏ hàng
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $user_id, $product_id, $quantity);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Đã thêm sản phẩm vào giỏ hàng!";
                }
            }
            
            // Lấy tổng số lượng sản phẩm trong giỏ hàng
            if ($response['success']) {
                $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $total = $result->fetch_assoc();
                $response['cart_count'] = $total['total'];
            }
        }
    } else {
        $response['message'] = "Không tìm thấy sản phẩm!";
    }
} else {
    $response['message'] = "Yêu cầu không hợp lệ!";
}

echo json_encode($response);
