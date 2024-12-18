# Phone Store

![Phone Store](assets/images/logo.png)

Phone Store là một trang web bán đồ công nghệ được xây dựng bằng HTML, CSS, JavaScript, Bootstrap, PHP và MySQL.

## Mục lục

- [Giới thiệu](#giới-thiệu)
- [Tính năng](#tính-năng)
- [Cài đặt](#cài-đặt)
- [Cấu trúc thư mục](#cấu-trúc-thư-mục)
- [Màu sắc chủ đạo](#màu-sắc-chủ-đạo)
- [Đóng góp](#đóng-góp)
- [Liên hệ](#liên-hệ)

## Giới thiệu

Phone Store là một nền tảng thương mại điện tử cho phép người dùng mua sắm các sản phẩm công nghệ như điện thoại di động, máy tính bảng, và phụ kiện. Trang web cung cấp giao diện thân thiện, dễ sử dụng và tích hợp nhiều tính năng hữu ích.

## Tính năng

- Quản lý sản phẩm, danh mục, thương hiệu
- Giỏ hàng và thanh toán trực tuyến
- Đăng nhập và đăng ký người dùng
- Đánh giá và nhận xét sản phẩm
- Quản lý đơn hàng
- Đăng nhập bằng Google

## Cài đặt

1. Clone repository về máy:
    ```sh
    git clone https://github.com/your-username/phonestore.git
    ```

2. Cài đặt các dependencies bằng Composer:
    ```sh
    composer install
    ```

3. Tạo file [.env](http://_vscodecontentref_/1) từ file mẫu `.env.example` và cập nhật các thông tin cấu hình cần thiết:
    ```sh
    cp .env.example .env
    ```

4. Import cơ sở dữ liệu từ file `dbdt.sql` vào MySQL bằng phpMyAdmin:
    - Mở phpMyAdmin và chọn cơ sở dữ liệu bạn muốn import.
    - Chọn tab "Import" và chọn file `dbdt.sql`.
    - Nhấn "Go" để bắt đầu quá trình import.

5. Khởi động server PHP:
    ```sh
    php -S localhost:8000
    ```

6. Truy cập trang web tại [http://localhost:8000](http://localhost:8000).

## Cấu trúc thư mục

```plaintext
phone_store/
├── admin/                    # Thư mục quản trị
│   ├── brands.php            # Quản lý thương hiệu
│   ├── categories.php        # Quản lý danh mục
│   ├── delete_review.php     # Xóa đánh giá
│   ├── index.php             # Trang dashboard admin
│   ├── orders.php            # Quản lý đơn hàng
│   ├── products.php          # Quản lý sản phẩm
│   ├── reviews.php           # Quản lý đánh giá
│   └── users.php             # Quản lý người dùng
│
├── assets/                   # Chứa tài nguyên static
│   ├── images/               # Chứa hình ảnh
│   └── uploads/              # Chứa ảnh upload
│
├── auth/                     # Xác thực người dùng
│   ├── google-oauth.php      # Đăng nhập bằng Google
│   ├── login.php             # Đăng nhập
│   ├── logout.php            # Đăng xuất
│   ├── profile.php           # Hồ sơ người dùng
│   └── register.php          # Đăng ký
│
├── config/                   # Cấu hình
│   └── database.php          # Cấu hình database
│
├── db/                       # Thư mục cơ sở dữ liệu
│   └── dbdt.sql              # Tệp SQL
│
├── includes/                 # Chứa các file include
│   ├── footer.php            # Footer
│   ├── functions.php         # Các hàm tiện ích
│   ├── header.php            # Header
│   └── navbar.php            # Navbar
│
├── pages/                    # Các trang chính của website
│   ├── add-to-cart.php       # Thêm vào giỏ hàng
│   ├── cart.php              # Giỏ hàng
│   ├── checkout.php          # Thanh toán
│   ├── contact.php           # Liên hệ
│   ├── home.php              # Trang chủ
│   ├── product-detail.php    # Chi tiết sản phẩm
│   └── products.php          # Trang danh sách sản phẩm
│
├── .env                      # Cấu hình .env
├── .gitignore                # Tệp .gitignore
├── composer.json             # Tệp cấu hình Composer
├── composer.lock             # Tệp khóa Composer
├── index.php                 # File index chính
├── README.md                 # Tệp README
└── vendor/                   # Thư mục vendor của Composer
```

## Màu sắc chủ đạo

```plaintext
:root {
  /* Màu chủ đạo */
  --primary-color: #e31837;     /* Đỏ chính */
  --primary-dark: #b71c1c;      /* Đỏ đậm */
  --primary-light: #ff5252;     /* Đỏ nhạt */
  
  /* Màu phụ */
  --secondary-color: #f5f5f5;   /* Xám nhạt */
  --text-color: #333333;        /* Màu chữ chính */
  --light-text: #ffffff;        /* Màu chữ sáng */
  --dark-text: #000000;         /* Màu chữ tối */
  
  /* Màu nền */
  --bg-color: #ffffff;          /* Nền trắng */
  --bg-light: #f8f9fa;          /* Nền xám nhạt */
  
  /* Màu accent */
  --success-color: #28a745;     /* Màu thành công */
  --error-color: #dc3545;       /* Màu lỗi */
}
```

Đóng góp
Nếu bạn muốn đóng góp cho dự án, vui lòng tạo pull request hoặc mở issue mới trên GitHub.

Liên hệ
Email: contact@phonestore.com
Hotline: 1900 1234
Địa chỉ: 126 Nguyễn Thiện Thành, Phường 5, Trà Vinh, Việt Nam
Cảm ơn bạn đã sử dụng Phone Store!