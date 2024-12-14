</main>
<!-- Phần Footer -->
<footer class="footer bg-dark text-light pt-5 pb-3">
    <style>
        /* Kiểu dáng Footer */
        .footer {
            margin-top: auto;
        }

        .footer h5 {
            font-weight: 600;
            position: relative;
            padding-bottom: 12px;
        }

        .footer h5::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: var(--primary-color);
        }

        .footer .social-links a {
            display: inline-block;
            width: 35px;
            height: 35px;
            background-color: rgba(255,255,255,0.1);
            text-align: center;
            line-height: 35px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .footer .social-links a:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .footer ul li a {
            transition: all 0.3s ease;
        }

        .footer ul li a:hover {
            color: var(--primary-color) !important;
            padding-left: 8px;
        }

        .newsletter-form .form-control {
            background-color: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
        }

        .newsletter-form .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }

        .newsletter-form .form-control:focus {
            background-color: rgba(255,255,255,0.2);
            box-shadow: none;
        }

        .payment-methods img {
            max-height: 30px;
        }
    </style>

    <div class="container">
        <div class="row">
            <!-- Thông tin công ty -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4"><i class="fas fa-mobile-alt"></i> Phone Store</h5>
                <p class="mb-3">Cửa hàng điện thoại uy tín hàng đầu Việt Nam. Chuyên cung cấp các sản phẩm điện thoại chính hãng.</p>
                <div class="social-links">
                    <a href="#" class="me-3 text-light"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="me-3 text-light"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="me-3 text-light"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Liên kết nhanh -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Liên kết nhanh</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/pages/home.php" class="text-light text-decoration-none">Trang chủ</a></li>
                    <li class="mb-2"><a href="/pages/products.php" class="text-light text-decoration-none">Sản phẩm</a></li>
                    <li class="mb-2"><a href="/pages/contact.php" class="text-light text-decoration-none">Liên hệ</a></li>
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none">Chính sách bảo hành</a></li>
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none">Điều khoản dịch vụ</a></li>
                </ul>
            </div>

            <!-- Thông tin liên hệ -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Liên hệ</h5>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <i class="fas fa-home me-2"></i> 126 Nguyễn Thiện Thành, Phường 5, Trà Vinh, Việt Nam
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-envelope me-2"></i> contact@phonestore.com
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-phone me-2"></i> 1900 1234
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-clock me-2"></i> 8:00 AM - 22:00 PM
                    </li>
                </ul>
            </div>

            <!-- Đăng ký nhận tin -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Đăng ký nhận tin</h5>
                <p class="mb-3">Đăng ký để nhận thông tin về sản phẩm mới và khuyến mãi</p>
                <form class="newsletter-form">
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="Email của bạn" required>
                        <button class="btn btn-danger" type="submit">Đăng ký</button>
                    </div>
                </form>
                <div class="payment-methods mt-4">
                    <h6 class="text-uppercase mb-3">Thanh toán</h6>
                    <img src="../assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid">
                    <img src="../assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid">
                    <img src="../assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid">
                    <img src="../assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid">
                    <img src="../assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid">
                    <img src="../assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid">
                    <img src="../assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid">
                    <img src="../assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid">
                </div>
            </div>
        </div>

        <!-- Bản quyền -->
        <div class="row mt-4">
            <div class="col-12">
                <hr class="bg-light">
                <p class="text-center mb-0">
                    &copy; <?php echo date('Y'); ?> Phone Store. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS và Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<script>
    // Xử lý form đăng ký nhận tin
    document.querySelector('.newsletter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = this.querySelector('input[type="email"]').value;
        // Thêm logic xử lý đăng ký nhận tin tại đây
        alert('Cảm ơn bạn đã đăng ký nhận tin!');
        this.reset();
    });

    // Hiệu ứng hover cho các liên kết mạng xã hội
    document.querySelectorAll('.social-links a').forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
</script>

</body>
</html>