<?php
// test_upload_curl.php - Kiểm thử tải ảnh chân dung qua API dùng curl

$cccd = '999999888888';
$url = 'http://localhost/tuyen-dung-fixed/tuyen-dung/api.php?table=don_dang_ky';

// Tạo chuỗi base64 ảnh PNG hợp lệ (1x1 pixel)
$png_base64_header = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

// Nhai thêm một lượng byte lớn ở cuối để giả lập ảnh dung lượng lớn (1.2MB) mà vẫn hợp lệ
$base64_body = base64_encode(str_repeat("A", 900000));
$large_png_base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==' . $base64_body;

$payload = [
    'ho_ten' => 'NGUYEN VAN TEST PHOTO',
    'ngay_sinh' => '1998-05-15',
    'gioi_tinh' => 'Nam',
    'dan_toc' => 'Kinh',
    'ton_giao' => 'Khong',
    'cccd' => $cccd,
    'ngay_cap' => '2015-05-15',
    'noi_cap' => 'Cuc Canh Sat',
    'sdt' => '0987654321',
    'email' => 'testphoto@example.com',
    'que_quan' => 'Dong Thap',
    'ho_khau' => 'Dong Thap',
    'dia_chi_thong_bao' => 'Dong Thap',
    'suc_khoe' => 'Tot',
    'chieu_cao' => 170,
    'can_nang' => 60,
    'trinh_do_van_hoa' => '12/12',
    'trinh_do_chuyen_mon' => 'Dai hoc',
    'cap' => 'THPT',
    'truong' => 'THPT Thien Ho Duong',
    'vi_tri' => 'Giao vien Toan',
    'phu_luc' => 'PL01',
    'ngoai_ngu' => 'Tiếng Anh',
    'thong_tin_gia_dinh' => '[]',
    'qua_trinh_dao_tao' => '[]',
    'qua_trinh_cong_tac' => '[]',
    'trang_thai' => 'Chờ duyệt',
    'file_anh_base64' => $large_png_base64
];

// Xoá hồ sơ cũ nếu trùng để test insert
try {
    $pdo = new PDO("mysql:host=localhost;dbname=tuyen_dung;charset=utf8mb4", "root", "");
    $pdo->exec("DELETE FROM `don_dang_ky` WHERE cccd = '$cccd'");
    echo "Da xoa ho so cu neu co.\n";
} catch (Exception $e) {
    echo "Khong ket noi duoc DB: " . $e->getMessage() . "\n";
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

echo "Dang gui request dang ky ho so kem anh base64 lon (~1.2MB)...\n";
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n";

if ($http_code == 200) {
    $resObj = json_decode($response, true);
    if (isset($resObj['ok']) && $resObj['ok']) {
        echo "--> GUI ANH THANH CONG!\n";
        
        // Kiem tra duong dan anh trong DB
        try {
            $stmt = $pdo->prepare("SELECT file_anh FROM `don_dang_ky` WHERE cccd = ?");
            $stmt->execute([$cccd]);
            $file_anh = $stmt->fetchColumn();
            echo "Duong dan anh luu trong DB: " . $file_anh . "\n";
            if ($file_anh && file_exists('c:/xampp/htdocs/tuyen-dung-fixed/tuyen-dung/' . $file_anh)) {
                echo "--> FILE ANH THUC TE TON TAI TREN O DIA!\n";
            } else {
                echo "--> LOI: FILE ANH KHONG TON TAI TREN O DIA!\n";
            }
        } catch (Exception $e) {
            echo "Loi truy van DB: " . $e->getMessage() . "\n";
        }
    } else {
        echo "--> GUI ANH THAT BAI: API tra ve loi\n";
    }
} else {
    echo "--> GUI ANH THAT BAI: API tra ve loi HTTP\n";
}
