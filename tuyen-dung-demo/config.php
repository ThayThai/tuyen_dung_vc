<?php
// ============================================================
//  CẤU HÌNH HỆ THỐNG TUYỂN DỤNG VIÊN CHỨC
//  Tỉnh thành khác khi triển khai chỉ cần chỉnh sửa file này
// ============================================================

// 1. CẤU HÌNH DATABASE
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tuyen_dung');

// 2. CẤU HÌNH BẢO MẬT & SECRET KEYS
define('ADMIN_SECRET_TOKEN', 'MY_SECRET_TOKEN');     // Token đăng nhập mặc định cho Admin
define('OTP_SECRET_KEY', 'OTP_SECRET_KEY_2026');    // Key bảo mật băm Token OTP tra cứu

// 3. CẤU HÌNH EMAIL / PHẢN HỒI THÔNG BÁO
define('EMAIL_SIGNATURE', 'Sở GDĐT tỉnh Đồng Tháp');
