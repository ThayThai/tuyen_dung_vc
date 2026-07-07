<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=tuyen_dung", "root", "");
    $stmt = $pdo->query("SELECT id, ho_ten, cccd, truong, cap, vi_tri, trang_thai FROM `don_dang_ky`");
    print_r($stmt->fetchAll());
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
