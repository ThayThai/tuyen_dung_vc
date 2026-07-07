<?php
// ============================================================
//  CẤU HÌNH HỆ THỐNG TUYỂN DỤNG VIÊN CHỨC
//  Tỉnh thành khác khi triển khai chỉ cần chỉnh sửa file này
// ============================================================

// 1. CẤU HÌNH DATABASE
define('DB_HOST', 'sql306.infinityfree.com');
define('DB_PORT', '3306');
define('DB_USER', 'if0_42353776');
define('DB_PASS', 'SY4Ldbr82oX');
define('DB_NAME', 'if0_42353776_tuyendung');


// 2. CẤU HÌNH BẢO MẬT & SECRET KEYS
define('ADMIN_SECRET_TOKEN', 'MY_SECRET_TOKEN');     // Token đăng nhập mặc định cho Admin
define('OTP_SECRET_KEY', 'OTP_SECRET_KEY_2026');    // Key bảo mật băm Token OTP tra cứu

// 3. CẤU HÌNH EMAIL / PHẢN HỒI THÔNG BÁO
define('EMAIL_SIGNATURE', 'Sở GDĐT tỉnh Đồng Tháp');
