<?php
// cleanup.php - Dọn dẹp dữ liệu test cccd 999999888888

try {
    $pdo = new PDO("mysql:host=localhost;dbname=tuyen_dung;charset=utf8mb4", "root", "");
    
    // Tìm các ảnh thẻ của tài khoản test 999999888888
    $stmt = $pdo->prepare("SELECT file_anh FROM `don_dang_ky` WHERE cccd = ?");
    $stmt->execute(['999999888888']);
    $files = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($files as $file) {
        $fullPath = __DIR__ . '/../' . $file;
        if ($file && file_exists($fullPath)) {
            unlink($fullPath);
            echo "Da xoa file: $fullPath\n";
        }
    }
    
    $pdo->exec("DELETE FROM `don_dang_ky` WHERE cccd = '999999888888'");
    echo "Da xoa ban ghi test tu database.\n";
} catch (Exception $e) {
    echo "Loi: " . $e->getMessage() . "\n";
}
