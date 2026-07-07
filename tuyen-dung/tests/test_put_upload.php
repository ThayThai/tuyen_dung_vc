<?php
// test_put_upload.php - Kiểm thử cập nhật ảnh chân dung qua API PUT dùng curl

$cccd = '999999888888';
$url = 'http://localhost/tuyen-dung-fixed/tuyen-dung/api.php?table=don_dang_ky';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=tuyen_dung;charset=utf8mb4", "root", "");
    $stmt = $pdo->prepare("SELECT id FROM `don_dang_ky` WHERE cccd = ?");
    $stmt->execute([$cccd]);
    $id = $stmt->fetchColumn();
    if (!$id) {
        die("Loi: Khong tim thay ung vien de test PUT!\n");
    }
    echo "Tim thay ung vien ID: $id de kiem thu PUT.\n";
} catch (Exception $e) {
    die("Khong ket noi duoc DB: " . $e->getMessage() . "\n");
}

// Tạo chuỗi base64 ảnh PNG mới (khác ảnh cũ)
$new_png_base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUtEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

// Thực hiện request PUT với token admin giả lập
$put_url = $url . '&id=' . $id . '&token=MY_SECRET_TOKEN';

$payload = [
    'file_anh_base64' => $new_png_base64,
    'ho_ten' => 'NGUYEN VAN TEST PHOTO UPDATED'
];

$ch = curl_init($put_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: MY_SECRET_TOKEN'
]);

echo "Dang gui request PUT de cap nhat ho so va anh...\n";
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n";

if ($http_code == 200) {
    $resObj = json_decode($response, true);
    if (isset($resObj['ok']) && $resObj['ok']) {
        echo "--> PUT ANH THANH CONG!\n";
        
        // Kiem tra duong dan anh moi trong DB
        try {
            $stmt = $pdo->prepare("SELECT ho_ten, file_anh FROM `don_dang_ky` WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            echo "Ho ten moi: " . $row['ho_ten'] . "\n";
            echo "Duong dan anh moi luu trong DB: " . $row['file_anh'] . "\n";
            if ($row['file_anh'] && file_exists('c:/xampp/htdocs/tuyen-dung-fixed/tuyen-dung/' . $row['file_anh'])) {
                echo "--> FILE ANH MOI THUC TE TON TAI TREN O DIA!\n";
            } else {
                echo "--> LOI: FILE ANH MOI KHONG TON TAI TREN O DIA!\n";
            }
        } catch (Exception $e) {
            echo "Loi truy van DB: " . $e->getMessage() . "\n";
        }
    } else {
        echo "--> PUT THAT BAI: API tra ve loi\n";
    }
} else {
    echo "--> PUT THAT BAI: API tra ve loi HTTP\n";
}
