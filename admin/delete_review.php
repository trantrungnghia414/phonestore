<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['review_id'])) {
        $review_id = $data['review_id'];
        $delete_stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $delete_stmt->bind_param("i", $review_id);
        
        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $delete_stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID đánh giá không hợp lệ.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Phương thức không hợp lệ.']);
}
?>
