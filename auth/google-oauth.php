<?php
require_once '../config/database.php';
require_once '../vendor/autoload.php';
session_start();

// Đọc biến môi trường từ file .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Cập nhật các biến sau
$google_oauth_client_id = $_ENV['GOOGLE_OAUTH_CLIENT_ID'];
$google_oauth_client_secret = $_ENV['GOOGLE_OAUTH_CLIENT_SECRET'];  
$google_oauth_redirect_uri = 'http://localhost/phonestore/auth/google-oauth.php';
$google_oauth_version = 'v3';

// Nếu tham số code tồn tại và hợp lệ
if (isset($_GET['code']) && !empty($_GET['code'])) {
    // Thực hiện yêu cầu cURL để lấy mã truy cập
    $params = [
        'code' => $_GET['code'],
        'client_id' => $google_oauth_client_id,
        'client_secret' => $google_oauth_client_secret,
        'redirect_uri' => $google_oauth_redirect_uri,
        'grant_type' => 'authorization_code'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response, true);

    // Đảm bảo mã truy cập hợp lệ
    if (isset($response['access_token']) && !empty($response['access_token'])) {
        // Thực hiện yêu cầu cURL để lấy thông tin người dùng
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/' . $google_oauth_version . '/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $response['access_token']]);
        $response = curl_exec($ch);
        curl_close($ch);
        $profile = json_decode($response, true);

        // Đảm bảo dữ liệu hồ sơ tồn tại
        if (isset($profile['email'])) {
            $email = $profile['email'];
            $fullname = isset($profile['family_name']) ? $profile['family_name'] : '';
            if(isset($profile['given_name'])) {
                $fullname .= ' ' . $profile['given_name'];
            }

            // Kiểm tra xem email đã tồn tại trong CSDL chưa
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Nếu email chưa tồn tại, tạo tài khoản mới
                $password = password_hash('123', PASSWORD_DEFAULT); // Mật khẩu mặc định là 123
                $role = 'user';

                $stmt = $conn->prepare("INSERT INTO users (email, password, fullname, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $email, $password, $fullname, $role);
                $stmt->execute();

                $_SESSION['user_id'] = $conn->insert_id;
            } else {
                // Nếu email đã tồn tại, lấy thông tin user
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
            }

            $_SESSION['role'] = 'user';
            header('Location: ../index.php');
            exit();
        } else {
            exit('Không thể lấy thông tin hồ sơ! Vui lòng thử lại sau!');
        }
    } else {
        exit('Mã truy cập không hợp lệ! Vui lòng thử lại sau!');
    }
} else {
    // Chuyển hướng đến trang xác thực Google
    $params = [
        'response_type' => 'code',
        'client_id' => $google_oauth_client_id,
        'redirect_uri' => $google_oauth_redirect_uri,
        'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ];
    header('Location: https://accounts.google.com/o/oauth2/auth?' . http_build_query($params));
    exit;
}
