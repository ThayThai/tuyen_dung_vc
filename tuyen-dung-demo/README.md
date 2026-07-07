# Hệ Thống Đăng Ký & Quản Lý Tuyển Dụng Viên Chức

Đây là ứng dụng web hỗ trợ thí sinh nộp phiếu đăng ký dự tuyển viên chức giáo dục trực tuyến, tự động trích xuất thông tin từ file PDF, đồng bộ ảnh thẻ và hỗ trợ Admin duyệt hồ sơ, nhập điểm, xuất báo cáo danh sách theo định dạng Excel (.xlsx).

---

## 📂 Cấu trúc thư mục bàn giao

```text
tuyen-dung/
├── database/            # Chứa file kịch bản tạo CSDL MySQL
│   └── database.sql     # Kịch bản khởi tạo database và dữ liệu ban đầu
├── documents/           # Các tài liệu mẫu, biểu mẫu hướng dẫn tuyển dụng
│   ├── Hồ sơ viên chức.pdf
│   ├── Mau so 1-ND 85-2023.doc
│   └── Phieu_Dang_Ky_TRINH_QUOC_THAI_UPDATED_087204002727.pdf  # PDF mẫu test
├── js/                  # Thư mục chứa mã nguồn Javascript
│   ├── api.js           # Các hàm kết nối và gọi API cho trang Admin
│   └── lookup.js        # Logic tra cứu thông tin kết quả thi & xác thực OTP
├── tests/               # Các kịch bản kiểm thử tự động (dùng cho lập trình viên)
│   ├── test_db.php                   # Test kết nối database MySQL
│   ├── test_upload_curl.php          # Test POST đăng ký kèm ảnh thẻ base64
│   ├── test_put_upload.php           # Test PUT cập nhật ảnh thẻ
│   └── test_coordinate_clustering.js # Test thuật toán trích xuất Y-coordinate
├── uploads/             # Thư mục lưu trữ ảnh thẻ và tệp PDF ứng viên tải lên
│   └── .gitkeep         # File giữ thư mục trống trên Git
├── admin.html           # Giao diện Quản trị viên (Duyệt hồ sơ, nhập điểm, xuất Excel)
├── api.php              # RESTful API Backend xử lý toàn bộ logic nghiệp vụ & DB
├── chi-tieu.html        # Giao diện xem chỉ tiêu tuyển dụng các trường
├── dang-ky.html         # Giao diện đăng ký dự tuyển dành cho thí sinh
├── index.html           # Trang chủ giới thiệu hệ thống
├── ket-qua.html         # Giao diện tra cứu kết quả thi tuyển (Vòng 1 & Vòng 2)
├── quy-trinh.html       # Trang thông tin quy trình tuyển dụng
├── thong-bao.html       # Trang hiển thị các thông báo từ hội đồng tuyển dụng
└── .gitignore           # File cấu hình bỏ qua tệp tin rác khi đẩy lên Git/Internet
```

---

## 💻 Hướng dẫn thiết lập trên môi trường cục bộ (Localhost - XAMPP)

### Bước 1: Khởi động Apache và MySQL
* Mở **XAMPP Control Panel**, nhấn nút **Start** ở cả hai dịch vụ **Apache** và **MySQL**.

### Bước 2: Import Cơ sở dữ liệu
1. Truy cập [http://localhost/phpmyadmin](http://localhost/phpmyadmin) trên trình duyệt.
2. Tạo một database mới với tên: `tuyen_dung` (chọn mã hóa `utf8mb4_unicode_ci` hoặc `utf8mb4_general_ci`).
3. Chọn database `tuyen_dung` vừa tạo, nhấn vào tab **Import** (Nhập).
4. Nhấn **Choose File** và chọn tệp tin [database.sql](file:///c:/xampp/htdocs/tuyen-dung-fixed/tuyen-dung/database/database.sql) nằm trong thư mục `tuyen-dung/database/`.
5. Nhấn **Import** ở cuối trang để hoàn tất.

### Bước 3: Cấu hình kết nối Cơ sở dữ liệu
* Mở file [api.php](file:///c:/xampp/htdocs/tuyen-dung-fixed/tuyen-dung/api.php).
* Tìm khối mã **KẾT NỐI DATABASE** ở khoảng dòng 28:
  ```php
  $pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", 'root', '');
  ```
* Nếu MySQL cục bộ của bạn dùng tài khoản khác hoặc có mật khẩu, hãy điều chỉnh `'root'` (username) và `''` (mật khẩu) tương ứng.

### Bước 4: Chạy thử nghiệm hệ thống
Truy cập vào các đường dẫn sau trên trình duyệt:
* Trang chủ giới thiệu: [http://localhost/tuyen-dung-fixed/tuyen-dung/index.html](http://localhost/tuyen-dung-fixed/tuyen-dung/index.html)
* Trang Đăng ký (Thí sinh): [http://localhost/tuyen-dung-fixed/tuyen-dung/dang-ky.html](http://localhost/tuyen-dung-fixed/tuyen-dung/dang-ky.html)
* Trang Quản trị Admin: [http://localhost/tuyen-dung-fixed/tuyen-dung/admin.html](http://localhost/tuyen-dung-fixed/tuyen-dung/admin.html)
  * *Tài khoản đăng nhập mặc định*: `admin`
  * *Mật khẩu mặc định*: `admin`

---

## 🌐 Hướng dẫn triển khai lên mạng Internet (Production Server)

Khi đưa ứng dụng lên máy chủ hosting hoặc VPS thật để sử dụng chính thức, bạn cần tuân thủ các quy tắc bảo mật sau:

### 1. Thay đổi mật khẩu kết nối CSDL trong API
* Cập nhật thông tin máy chủ DB, tên đăng nhập và mật khẩu chính thức trong tệp `api.php`.

### 2. Thay đổi Token bảo mật Admin
* Tìm dòng mã sau ở dòng 52 trong `api.php`:
  ```php
  if ($token === 'MY_SECRET_TOKEN') {
  ```
* Hãy thay đổi chuỗi `'MY_SECRET_TOKEN'` thành một mã token ngẫu nhiên phức tạp của riêng bạn để bảo vệ API khỏi các truy cập trái phép. Đồng thời cập nhật lại hằng số `TOKEN` này ở đầu file [api.js](file:///c:/xampp/htdocs/tuyen-dung-fixed/tuyen-dung/js/api.js) phía Client.

### 3. Phân quyền ghi cho thư mục `uploads/`
* Thư mục `uploads/` là nơi lưu trữ ảnh chân dung và PDF đăng ký của thí sinh. Trên các hệ điều hành Linux (Ubuntu/CentOS), bạn cần cấp quyền ghi cho Web Server (thường là người dùng `www-data` hoặc `apache`).
* Sử dụng lệnh terminal sau trên VPS:
  ```bash
  chmod -R 775 uploads/
  chown -R www-data:www-data uploads/
  ```
* Hoặc phân quyền ghi `chmod 777 uploads` thông qua các phần mềm FTP (FileZilla, v.v.).

### 4. Cấu hình gửi Mail thật cho tính năng OTP
* Hệ thống đang sử dụng cơ chế giả lập ghi log email ra file `sent_emails.log` tại local để dễ dàng theo dõi.
* Khi chạy thật trên Internet, hãy thay thế hàm `sendEmailSimulated` trong `api.php` bằng thư viện gửi thư SMTP thật (như **PHPMailer** kết nối qua SMTP Gmail/SendGrid) hoặc hàm `mail()` của PHP để gửi mã OTP trực tiếp vào hòm thư điện tử của thí sinh.

---

## 🔄 Hướng dẫn Thiết lập Đồng bộ & Tự động Deploy qua Git + Webhook (Chỉnh sửa song song)

Khi bạn muốn tiếp tục lập trình, chỉnh sửa code ở môi trường local (XAMPP) và tự động đồng bộ lập tức lên server internet mỗi khi có cập nhật, hãy làm theo các bước dưới đây:

### Bước 1: Khởi tạo Git tại Local
1. Mở Terminal tại thư mục `tuyen-dung/` trên máy tính local của bạn.
2. Chạy lệnh để khởi tạo Git:
   ```bash
   git init
   ```
3. Đẩy các file vào danh sách theo dõi (Git sẽ tự động bỏ qua thư mục `uploads/` và file log theo cấu hình `.gitignore` có sẵn):
   ```bash
   git add .
   git commit -m "Initial commit"
   ```

### Bước 2: Đưa mã nguồn lên GitHub (Private Repository)
1. Truy cập [GitHub](https://github.com/) và tạo một repository mới. **Lưu ý: Hãy chọn chế độ Private (Riêng tư)** để bảo vệ thông tin cấu hình cơ sở dữ liệu của bạn.
2. Liên kết thư mục local với repo GitHub vừa tạo:
   ```bash
   git remote add origin <ĐƯỜNG_DẪN_REPO_GITHUB_CỦA_BẠN>
   git branch -M main
   git push -u origin main
   ```

### Bước 3: Cấu hình Git trên Web Server (Hosting/VPS)
1. SSH vào VPS (hoặc mở Terminal trên Hosting), di chuyển tới thư mục chứa web (ví dụ `/var/www/html/` hoặc `public_html/`) và clone code về:
   ```bash
   git clone <ĐƯỜNG_DẪN_REPO_GITHUB_CỦA_BẠN> tuyen-dung
   ```
2. Phân quyền lại cho thư mục `uploads/` trên server như hướng dẫn ở phần trên.

### Bước 4: Viết kịch bản tự động Pull code (Webhook Handler)
Tạo một file PHP có tên là `deploy.php` nằm cùng cấp với `api.php` trên Web Server với nội dung sau:
```php
<?php
// deploy.php - GitHub Webhook Auto Deploy
$secret = 'MÃ_BẢO_MẬT_WEBHOOK_TỰ_CHỌN'; // Đặt một mật khẩu khó đoán ở đây

$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
if (empty($signature)) {
    http_response_code(403);
    die('Yêu cầu không hợp lệ');
}

$post_data = file_get_contents('php://input');
$expected_signature = 'sha1=' . hash_hmac('sha1', $post_data, $secret);

if (!hash_equals($expected_signature, $signature)) {
    http_response_code(403);
    die('Xác thực Webhook thất bại');
}

// Chạy lệnh kéo code mới nhất từ GitHub về server
$output = shell_exec('git pull origin main 2>&1');
file_put_contents('deploy.log', date('Y-m-d H:i:s') . "\n" . $output . "\n-------------------\n", FILE_APPEND);
echo "Deploy thành công!";
```

### Bước 5: Cấu hình Webhook trên GitHub
1. Trên repository GitHub của bạn, truy cập vào **Settings** -> **Webhooks** -> nhấn **Add webhook**.
2. Điền thông tin:
   * **Payload URL**: `https://your-domain.vn/tuyen-dung/deploy.php` (Thay bằng tên miền thật của bạn).
   * **Content type**: `application/json`.
   * **Secret**: Nhập đúng mã bảo mật bạn đã đặt trong file `deploy.php` (`MÃ_BẢO_MẬT_WEBHOOK_TỰ_CHỌN`).
3. Nhấn **Add webhook** để kích hoạt.

Từ nay về sau, mỗi khi bạn chỉnh sửa bất kỳ file nào ở Localhost, bạn chỉ cần lưu lại và chạy 3 lệnh sau:
```bash
git add .
git commit -m "Cập nhật tính năng X"
git push
```
Mã nguồn trên Web Server sẽ tự động được kéo về và cập nhật ngay lập tức mà bạn không cần mở hosting hay FTP lên nữa.

---

## 🛠️ Hướng dẫn chạy kiểm thử (dành cho Lập trình viên phát triển)


Nếu bạn cần sửa đổi và nâng cấp hệ thống, bạn có thể chạy các kịch bản kiểm thử tự động trong thư mục `tests/` thông qua giao diện CLI:
1. **Kiểm tra kết nối CSDL**:
   ```bash
   php -f tuyen-dung/tests/test_db.php
   ```
2. **Kiểm tra luồng đăng ký POST kèm tệp ảnh**:
   ```bash
   php -f tuyen-dung/tests/test_upload_curl.php
   ```
3. **Kiểm tra luồng cập nhật PUT ảnh thẻ**:
   ```bash
   php -f tuyen-dung/tests/test_put_upload.php
   ```
