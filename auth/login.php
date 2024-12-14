<?php
require_once '../config/database.php';

session_start();

// Kiểm tra xem người dùng đã đăng nhập chưa
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}

// Lấy return_url từ query string nếu có
$return_url = isset($_GET['return_url']) ? $_GET['return_url'] : '';

// Xử lý form đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $return_url = isset($_POST['return_url']) ? $_POST['return_url'] : '';

    // Xác thực thông tin đăng nhập
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Thiết lập các biến session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // Chuyển hướng về trang trước đó nếu có
            if (!empty($return_url)) {
                header("Location: .." . $return_url);
            } else {
                // Nếu là admin thì về trang admin, ngược lại về trang chủ
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/index.php");
                } else {
                    header("Location: ../index.php");
                }
            }
            exit();
        } else {
            $error = "Mật khẩu không chính xác";
        }
    } else {
        $error = "Email không tồn tại trong hệ thống";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            max-width: 400px;
            width: 90%;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .login-title {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            box-shadow: 0 0 10px rgba(142, 197, 252, 0.3);
            border-color: #8ec5fc;
        }

        .btn-login {
            background: linear-gradient(to right, #e0c3fc, #8ec5fc);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 500;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .form-label {
            font-weight: 500;
            color: #555;
        }

        .social-login {
            margin-top: 30px;
            text-align: center;
        }

        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: white;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            transform: translateY(-3px);
        }

        .fb-btn {
            background: #3b5998;
        }

        .google-btn {
            background: #db4437;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
        }

        .register-link a {
            color: #8ec5fc;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .toast {
            min-width: 350px;
            font-size: 1.1rem;
            backdrop-filter: blur(10px);
            background: rgba(13, 202, 240, 0.9) !important;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border-radius: 10px;
        }

        .toast-body {
            padding: 1rem 1.5rem;
            text-align: center;
            flex-grow: 1;
        }

        .toast .d-flex {
            align-items: center;
        }
    </style>
</head>

<body>
    <div class="toast-container position-fixed top-50 start-50 translate-middle">
        <div class="toast align-items-center text-white bg-info border-0" role="alert" aria-live="assertive" aria-atomic="true" id="forgotPasswordToast">
            <div class="d-flex">
                <div class="toast-body fs-5 p-3">
                    <i class="fas fa-grin-tongue-wink me-2"></i>
                    <i class="fas fa-grin-tongue-wink me-2"></i>
                    <i class="fas fa-grin-tongue-wink me-2"></i>
                    Liêuu liêuu
                    <i class="fas fa-grin-tongue-wink ms-2"></i>
                    <i class="fas fa-grin-tongue-wink ms-2"></i>
                    <i class="fas fa-grin-tongue-wink ms-2"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="login-container">
        <h2 class="login-title">Đăng nhập</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] . (!empty($return_url) ? '?return_url=' . urlencode($return_url) : ''); ?>">
            <!-- Thêm input hidden để lưu return_url -->
            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($return_url); ?>">

            <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Mật khẩu</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
            </div>
            <button type="submit" class="btn btn-primary btn-login">Đăng nhập</button>
        </form>

        <div class="social-login">
            <p class="text-muted">Hoặc đăng nhập với</p>
            <a href="#" class="social-btn fb-btn"><i class="fab fa-facebook-f"></i></a>
            <a href="./google-oauth.php" class="social-btn google-btn"><i class="fab fa-google"></i></a>
        </div>

        <div class="register-link">
            <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
            <a href="#" class="text-muted" onclick="showForgotPasswordToast(event)">Quên mật khẩu?</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showForgotPasswordToast(event) {
            event.preventDefault();
            const toast = new bootstrap.Toast(document.getElementById('forgotPasswordToast'), {
                autohide: true,
                delay: 4000 // Tăng thời gian hiển thị lên 4 giây
            });
            toast.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const toastElList = document.querySelectorAll('.toast');
            toastElList.forEach(toastEl => {
                toastEl.addEventListener('shown.bs.toast', function() {
                    setTimeout(function() {
                        const toast = bootstrap.Toast.getInstance(toastEl);
                        toast.hide();
                    }, 5000);
                });
            });
        });
    </script>
</body>

</html>