<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = filter_var($_POST['fullname'], FILTER_SANITIZE_STRING);

    // Kiểm tra định dạng email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ";
    } else {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email đã tồn tại trong hệ thống";
        } else {
            // Thêm người dùng mới
            $stmt = $conn->prepare("INSERT INTO users (email, password, fullname) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $password, $fullname);

            if ($stmt->execute()) {
                header("Location: login.php?registered=true");
                exit();
            } else {
                $error = "Đã xảy ra lỗi, vui lòng thử lại";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #a1c4fd 0%, #c2e9fb 100%);
            height: 100vh;
        }

        .register-container {
            max-width: 450px;
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin: 50px auto;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #a1c4fd;
        }

        .btn-register {
            background: linear-gradient(to right, #a1c4fd, #c2e9fb);
            border: none;
            padding: 10px 20px;
        }

        .btn-register:hover {
            background: linear-gradient(to right, #c2e9fb, #a1c4fd);
        }

        .form-label {
            font-weight: 500;
        }

        .register-title {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
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

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link p {
            color: #666;
            margin-bottom: 10px;
        }

        .login-link a {
            color: #8ec5fc;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            color: #e0c3fc;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="register-container">
            <h2 class="register-title">Đăng ký tài khoản</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="mb-3">
                    <label for="fullname" class="form-label">Họ và tên</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-register">Đăng ký</button>
                </div>
            </form>

            <div class="social-login">
                <p class="text-muted">Hoặc đăng ký với</p>
                <a href="#" class="social-btn fb-btn"><i class="fab fa-facebook-f"></i></a>
                <a href="./google-oauth.php" class="social-btn google-btn"><i class="fab fa-google"></i></a>
            </div>

            <div class="login-link">
                <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Kiểm tra mật khẩu trùng khớp
        document.getElementById('confirm_password').addEventListener('input', function() {
            if (this.value !== document.getElementById('password').value) {
                this.setCustomValidity('Mật khẩu không khớp');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>

</html>