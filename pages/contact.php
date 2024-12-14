<?php
require_once '../includes/header.php';
?>

<main>
    <!-- Phần Hero -->
    <section class="contact-hero bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 mb-4">Liên hệ với chúng tôi</h1>
                    <p class="lead">Chúng tôi luôn sẵn sàng hỗ trợ và lắng nghe ý kiến của bạn. Hãy liên hệ với chúng tôi qua các kênh dưới đây.</p>
                </div>
                <div class="col-md-6">
                    <img src="../assets/images/contact-hero.png" alt="Contact Us" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Thông tin liên hệ -->
    <section class="contact-info py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-map-marker-alt fa-3x text-danger mb-3"></i>
                            <h4>Địa chỉ</h4>
                            <p>21 Điện Biên Phủ, Khóm 4, Trà Vinh, Việt Nam</p>
                            <p>126 Nguyễn Thiện Thành, Phường 5, Trà Vinh, Việt Nam</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-phone fa-3x text-danger mb-3"></i>
                            <h4>Điện thoại</h4>
                            <p>Hotline: 1900 1234<br>Hỗ trợ: 0123 456 789</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="fas fa-envelope fa-3x text-danger mb-3"></i>
                            <h4>Email</h4>
                            <p>contact@phonestore.com<br>support@phonestore.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Biểu mẫu liên hệ -->
    <section class="contact-form py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Gửi tin nhắn cho chúng tôi</h2>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-body p-4">
                            <form>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Số điện thoại</label>
                                    <input type="tel" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Chủ đề</label>
                                    <select class="form-select">
                                        <option>Hỗ trợ sản phẩm</option>
                                        <option>Khiếu nại dịch vụ</option>
                                        <option>Hợp tác kinh doanh</option>
                                        <option>Khác</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nội dung tin nhắn</label>
                                    <textarea class="form-control" rows="5" required></textarea>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-danger px-5">Gửi tin nhắn</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Phần bản đồ -->
    <section class="map-section">
        <div class="container-fluid p-0">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3929.9510232667654!2d106.34686867486755!3d9.934211674241226!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31a0175ea296facb%3A0x55ded92e29068221!2zMjEgxJBp4buHbiBCacOqbiBQaOG7pywgUGjGsOG7nW5nIDMsIFRwLiBUcsOgIFZpbmgsIFRyw6AgVmluaCwgVmnhu4d0IE5hbQ!5e0!3m2!1svi!2s!4v1710400428599!5m2!1svi!2s"
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </section>
</main>

<?php
require_once '../includes/footer.php';
?>
