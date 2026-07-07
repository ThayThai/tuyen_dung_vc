<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type,Authorization,X-Authorization');

// ===== IMPORT CẤU HÌNH HỆ THỐNG =====
require_once __DIR__ . '/config.php';

// ===== XỬ LÝ PREFLIGHT OPTIONS (CORS) =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$action = $_GET['action'] ?? '';
$is_custom_action = in_array($action, ['login', 'send_otp', 'verify_otp', 'get_counts', 'get_progress']);

// ===== WHITELIST TABLE (chống SQL Injection) =====
$allowed_tables = ['pl01_chitieu', 'pl02_chitieu', 'thong_bao', 'don_dang_ky', 'audit_logs'];
$table = $_GET['table'] ?? ($is_custom_action ? '' : 'pl01_chitieu');

if (!$is_custom_action && !in_array($table, $allowed_tables)) {
    http_response_code(400);
    die(json_encode(['error' => 'Tên bảng không hợp lệ']));
}

// ===== KẾT NỐI DATABASE =====
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Kết nối database thất bại: ' . $e->getMessage()]));
}

// ===== CÁC HÀM TRỢ GIÚP (HELPERS) =====

function verifyAdminToken($pdo) {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $tokenHeader = $headers['Authorization'] ?? $headers['authorization'] ?? 
                   $headers['X-Authorization'] ?? $headers['x-authorization'] ?? 
                   $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 
                   $_SERVER['HTTP_X_AUTHORIZATION'] ?? 
                   $_GET['token'] ?? '';
    
    if (empty($tokenHeader)) {
        return null;
    }
    
    $token = $tokenHeader;
    if (stripos($tokenHeader, 'Bearer ') === 0) {
        $token = substr($tokenHeader, 7);
    }
    
    if ($token === ADMIN_SECRET_TOKEN) {
        return [
            'id' => 0,
            'username' => 'legacy_admin',
            'role' => 'super_admin',
            'full_name' => 'Legacy Admin'
        ];
    }
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.role, u.full_name, s.expired_at 
        FROM admin_sessions s
        JOIN admin_users u ON s.admin_id = u.id
        WHERE s.token = ?
    ");
    $stmt->execute([$token]);
    $session = $stmt->fetch();
    
    if ($session && strtotime($session['expired_at']) > time()) {
        return [
            'id' => $session['id'],
            'username' => $session['username'],
            'role' => $session['role'],
            'full_name' => $session['full_name']
        ];
    }
    
    return null;
}

function write_audit_log($pdo, $adminUsername, $actionType, $description) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO `audit_logs` (admin_username, action_type, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminUsername, $actionType, $description, $ip]);
    } catch (Exception $e) {
        // Bỏ qua lỗi ghi log để không ảnh hưởng tới luồng chính
    }
}

function sendEmailSimulated($to, $subject, $body) {
    $logFile = __DIR__ . '/sent_emails.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "=========================================\n";
    $logEntry .= "TIMESTAMP: $timestamp\n";
    $logEntry .= "TO: $to\n";
    $logEntry .= "SUBJECT: $subject\n";
    $logEntry .= "BODY:\n$body\n";
    $logEntry .= "=========================================\n\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// ===== PHÂN TÍCH REQUEST =====
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

$admin = verifyAdminToken($pdo);
$is_public_post = ($method === 'POST' && $table === 'don_dang_ky');

// ===== CHẶN CÁC THAO TÁC CẦN TOKEN ADMIN =====
if ($method !== 'GET' && !$is_public_post && !$is_custom_action && !$admin) {
    http_response_code(401);
    die(json_encode(['error' => 'Bạn chưa đăng nhập hoặc phiên làm việc đã hết hạn']));
}

// ===== XỬ LÝ UPLOAD FILE TRƯỚC KHI THỰC HIỆN CÁC METHOD =====
if ($table === 'don_dang_ky') {
    // 1. PDF
    if (!empty($body['file_pdf_base64'])) {
        $base64_data = $body['file_pdf_base64'];
        if (strpos($base64_data, 'data:application/pdf;base64,') === 0) {
            $base64_str = substr($base64_data, strlen('data:application/pdf;base64,'));
            $pdf_content = base64_decode($base64_str);
            if (strlen($pdf_content) > 5 * 1024 * 1024) {
                http_response_code(400);
                die(json_encode(['error' => 'Tệp PDF đính kèm không được vượt quá 5MB.']));
            }
            if (substr($pdf_content, 0, 4) !== '%PDF') {
                http_response_code(400);
                die(json_encode(['error' => 'Tệp tải lên không phải là định dạng PDF hợp lệ.']));
            }
            $upload_dir = __DIR__ . '/uploads';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $cccd = preg_replace('/[^0-9]/', '', $body['cccd'] ?? uniqid());
            $filename = 'phieu_dang_ky_' . $cccd . '_' . time() . '.pdf';
            $filepath = $upload_dir . '/' . $filename;
            if (file_put_contents($filepath, $pdf_content)) {
                $body['file_pdf'] = 'uploads/' . $filename;
            }
        }
    }
    unset($body['file_pdf_base64']);
    
    // 2. Photo
    if (!empty($body['file_anh_base64'])) {
        $base64_data = $body['file_anh_base64'];
        if (strpos($base64_data, 'data:image/') === 0) {
            $comma_pos = strpos($base64_data, ',');
            if ($comma_pos !== false) {
                $header = substr($base64_data, 0, $comma_pos);
                $base64_str = substr($base64_data, $comma_pos + 1);
                $ext = 'jpg';
                if (strpos($header, 'image/png') !== false) {
                    $ext = 'png';
                } else if (strpos($header, 'image/jpeg') !== false) {
                    $ext = 'jpeg';
                } else if (strpos($header, 'image/jpg') !== false) {
                    $ext = 'jpg';
                }
                $image_content = base64_decode($base64_str);
                if (strlen($image_content) > 5 * 1024 * 1024) {
                    http_response_code(400);
                    die(json_encode(['error' => 'Ảnh thẻ không được vượt quá 5MB.']));
                }
                $is_jpeg = (substr($image_content, 0, 3) === "\xFF\xD8\xFF");
                $is_png = (substr($image_content, 0, 8) === "\x89PNG\r\n\x1a\n");
                if (!$is_jpeg && !$is_png) {
                    http_response_code(400);
                    die(json_encode(['error' => 'Chỉ chấp nhận ảnh thẻ dạng JPG hoặc PNG.']));
                }
                $upload_dir = __DIR__ . '/uploads';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $cccd = preg_replace('/[^0-9]/', '', $body['cccd'] ?? uniqid());
                $filename = 'anh_the_' . $cccd . '_' . time() . '.' . $ext;
                $filepath = $upload_dir . '/' . $filename;
                if (file_put_contents($filepath, $image_content)) {
                    $body['file_anh'] = 'uploads/' . $filename;
                }
            }
        }
    }
    unset($body['file_anh_base64']);
}

// ===== XỬ LÝ CÁC METHOD =====
try {
    switch ($method) {
        case 'GET':
            if ($table === 'audit_logs') {
                if (!$admin || $admin['role'] !== 'super_admin') {
                    http_response_code(403);
                    die(json_encode(['error' => 'Chỉ Super Admin mới được xem nhật ký hệ thống.']));
                }
                $stmt = $pdo->query("SELECT * FROM `audit_logs` ORDER BY id DESC LIMIT 500");
                echo json_encode($stmt->fetchAll());
                exit();
            }

            if ($table === 'don_dang_ky') {
                if ($admin) {
                    // Admin lấy toàn bộ danh sách đơn đăng ký
                    $stmt = $pdo->query("SELECT * FROM `don_dang_ky` ORDER BY id DESC");
                    echo json_encode($stmt->fetchAll());
                } else {
                    if ($action === 'get_counts') {
                        // Đếm số lượng hồ sơ nộp vào từng trường
                        $stmt = $pdo->query("SELECT truong, COUNT(*) as qty FROM `don_dang_ky` GROUP BY truong");
                        echo json_encode($stmt->fetchAll());
                        exit();
                    }

                    if ($action === 'get_progress') {
                        $cccd = trim($_GET['cccd'] ?? '');
                        if (empty($cccd)) {
                            http_response_code(400);
                            die(json_encode(['error' => 'Thiếu số CCCD hoặc Số điện thoại']));
                        }
                        $cleanInput = str_replace(' ', '', $cccd);
                        $stmt = $pdo->prepare("SELECT ho_ten, truong, vi_tri, trang_thai, diem, ket_qua FROM `don_dang_ky` WHERE REPLACE(cccd, ' ', '') = ? OR REPLACE(sdt, ' ', '') = ? LIMIT 1");
                        $stmt->execute([$cleanInput, $cleanInput]);
                        $row = $stmt->fetch();
                        if (!$row) {
                            http_response_code(404);
                            die(json_encode(['error' => 'Không tìm thấy hồ sơ với CCCD hoặc Số điện thoại này.']));
                        }
                        echo json_encode([
                            'ok' => true,
                            'ho_ten' => $row['ho_ten'],
                            'truong' => $row['truong'],
                            'vi_tri' => $row['vi_tri'],
                            'trang_thai' => $row['trang_thai'],
                            'diem' => $row['diem'],
                            'ket_qua' => $row['ket_qua']
                        ]);
                        exit();
                    }
                    
                    if (isset($_GET['truong'])) {
                        $reqTruong = trim($_GET['truong']);
                        // 1. Kiểm tra xem đã có kết quả Vòng 1 chưa (có bất kỳ hồ sơ nào đã duyệt hoặc từ chối)
                        $stmtCheck = $pdo->query("SELECT COUNT(*) FROM `don_dang_ky` WHERE `trang_thai` IN ('Đã duyệt', 'Từ chối')");
                        $hasResults = $stmtCheck->fetchColumn() > 0;
                        
                        if (!$hasResults) {
                            http_response_code(403);
                            die(json_encode(['error' => 'Chưa công bố kết quả Vòng 1. Danh sách thí sinh nộp vào trường chưa được công khai.']));
                        }
                        
                        // 2. Trả về danh sách thí sinh nộp vào trường này (chỉ trả về ho_ten, vi_tri, cap, trang_thai)
                        $stmt = $pdo->prepare("SELECT ho_ten, vi_tri, cap, trang_thai FROM `don_dang_ky` WHERE truong = ?");
                        $stmt->execute([$reqTruong]);
                        echo json_encode($stmt->fetchAll());
                        exit();
                    }

                    // Thí sinh tra cứu kết quả thi của mình bằng CCCD hoặc Số điện thoại
                    $cccd = trim($_GET['cccd'] ?? '');
                    if (empty($cccd)) {
                        http_response_code(400);
                        die(json_encode(['error' => 'Thiếu thông tin tra cứu']));
                    }

                    // Phân giải Số điện thoại thành CCCD tương ứng nếu cần
                    $cleanInput = str_replace(' ', '', $cccd);
                    $stmtResolve = $pdo->prepare("SELECT cccd FROM `don_dang_ky` WHERE REPLACE(cccd, ' ', '') = ? OR REPLACE(sdt, ' ', '') = ? LIMIT 1");
                    $stmtResolve->execute([$cleanInput, $cleanInput]);
                    $resolvedCccd = $stmtResolve->fetchColumn();
                    if ($resolvedCccd) {
                        $cccd = $resolvedCccd;
                    }

                    // Bắt buộc xác thực mã OTP trước khi cho phép đọc kết quả chi tiết
                    $otpToken = $_GET['otp_token'] ?? '';
                    $expectedToken = hash_hmac('sha256', $cccd, OTP_SECRET_KEY);
                    if (empty($otpToken) || $otpToken !== $expectedToken) {
                        http_response_code(403);
                        die(json_encode(['error' => 'Vui lòng xác thực mã OTP trước khi xem kết quả chi tiết.']));
                    }
                    
                    $sql = "SELECT ho_ten, cccd, vi_tri, truong, cap, trang_thai, diem, ket_qua, created_at, dia_chi_thong_bao, sdt, email, que_quan, ho_khau, trinh_do_chuyen_mon, thong_tin_gia_dinh, qua_trinh_dao_tao, qua_trinh_cong_tac FROM `don_dang_ky` WHERE REPLACE(cccd, ' ', '') = :cccd";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['cccd' => str_replace(' ', '', $cccd)]);
                    echo json_encode($stmt->fetchAll());
                }
            } else {
                $orderBy = ($table === 'thong_bao') ? 'id DESC' : 'stt ASC';
                $stmt = $pdo->query("SELECT * FROM `$table` ORDER BY $orderBy");
                echo json_encode($stmt->fetchAll());
            }
            break;

        case 'POST':
            // 1. Endpoint Đăng nhập Admin
            if ($action === 'login') {
                $username = trim($body['username'] ?? '');
                $password = trim($body['password'] ?? '');
                if (empty($username) || empty($password)) {
                    http_response_code(400);
                    die(json_encode(['error' => 'Thiếu tên đăng nhập hoặc mật khẩu']));
                }
                
                $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($password, $user['password_hash'])) {
                    http_response_code(400);
                    die(json_encode(['error' => 'Tên đăng nhập hoặc mật khẩu không đúng']));
                }
                
                // Sinh token ngẫu nhiên
                $token = bin2hex(random_bytes(32));
                $expired_at = date('Y-m-d H:i:s', time() + 86400); // Hạn 24h
                
                $stmtSession = $pdo->prepare("INSERT INTO `admin_sessions` (admin_id, token, expired_at) VALUES (?, ?, ?)");
                $stmtSession->execute([$user['id'], $token, $expired_at]);
                
                write_audit_log($pdo, $user['username'], 'DANG_NHAP', "Admin đăng nhập thành công vào hệ thống.");
                
                echo json_encode([
                    'ok' => true,
                    'token' => $token,
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'full_name' => $user['full_name']
                ]);
                exit();
            }

            // 2. Endpoint Gửi OTP cho thí sinh tra cứu
            if ($action === 'send_otp') {
                $cccd = trim($body['cccd'] ?? '');
                if (empty($cccd)) {
                    http_response_code(400);
                    die(json_encode(['error' => 'Thiếu số CCCD hoặc Số điện thoại']));
                }
                
                $cleanInput = str_replace(' ', '', $cccd);
                $stmt = $pdo->prepare("SELECT id, ho_ten, email, cccd FROM `don_dang_ky` WHERE REPLACE(cccd, ' ', '') = ? OR REPLACE(sdt, ' ', '') = ? LIMIT 1");
                $stmt->execute([$cleanInput, $cleanInput]);
                $candidate = $stmt->fetch();
                
                if (!$candidate) {
                    http_response_code(400);
                    die(json_encode(['error' => 'Không tìm thấy hồ sơ đăng ký nào gắn liền với số CCCD hoặc Số điện thoại này']));
                }
                
                $resolvedCccd = $candidate['cccd'];
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                $expired_at = date('Y-m-d H:i:s', time() + 300); // 5 phút
                
                $stmtDelete = $pdo->prepare("DELETE FROM `otp_codes` WHERE cccd = ?");
                $stmtDelete->execute([$resolvedCccd]);
                
                $stmtInsert = $pdo->prepare("INSERT INTO `otp_codes` (cccd, otp_code, expired_at) VALUES (?, ?, ?)");
                $stmtInsert->execute([$resolvedCccd, $otp, $expired_at]);
                
                // Gửi email giả lập
                $emailSubject = "Mã xác thực OTP tra cứu kết quả tuyển dụng";
                $emailBody = "Chào " . $candidate['ho_ten'] . ",\n\n";
                $emailBody .= "Mã xác thực OTP của bạn là: " . $otp . "\n";
                $emailBody .= "Mã OTP này có thời hạn sử dụng là 5 phút.\n";
                $emailBody .= "Vui lòng nhập mã này vào trang tra cứu để tiếp tục.\n\n";
                $emailBody .= "Trân trọng,\n" . EMAIL_SIGNATURE;
                
                sendEmailSimulated($candidate['email'], $emailSubject, $emailBody);
                
                echo json_encode([
                    'ok' => true,
                    'cccd' => $resolvedCccd,
                    'message' => 'Mã OTP đã được gửi thành công. Vui lòng mở file sent_emails.log trong thư mục dự án để lấy mã OTP (Giả lập email).'
                ]);
                exit();
            }

            // 3. Endpoint Xác thực OTP
            if ($action === 'verify_otp') {
                $cccd = trim($body['cccd'] ?? '');
                $otp = trim($body['otp'] ?? '');
                if (empty($cccd) || empty($otp)) {
                    http_response_code(400);
                    die(json_encode(['error' => 'Thiếu số CCCD/Số điện thoại hoặc mã OTP']));
                }
                
                $cleanInput = str_replace(' ', '', $cccd);
                $stmtResolve = $pdo->prepare("SELECT cccd FROM `don_dang_ky` WHERE REPLACE(cccd, ' ', '') = ? OR REPLACE(sdt, ' ', '') = ? LIMIT 1");
                $stmtResolve->execute([$cleanInput, $cleanInput]);
                $resolvedCccd = $stmtResolve->fetchColumn();
                if ($resolvedCccd) {
                    $cccd = $resolvedCccd;
                }
                
                $stmt = $pdo->prepare("SELECT * FROM `otp_codes` WHERE cccd = ? AND otp_code = ?");
                $stmt->execute([$cccd, $otp]);
                $otpRecord = $stmt->fetch();
                
                if (!$otpRecord || strtotime($otpRecord['expired_at']) < time()) {
                    http_response_code(400);
                    die(json_encode(['error' => 'Mã OTP không đúng hoặc đã hết hạn']));
                }
                
                $stmtDelete = $pdo->prepare("DELETE FROM `otp_codes` WHERE cccd = ?");
                $stmtDelete->execute([$cccd]);
                
                // Trả về mã token mã hóa một chiều dùng làm vé thông hành lấy dữ liệu
                $hash = hash_hmac('sha256', $cccd, OTP_SECRET_KEY);
                
                echo json_encode([
                    'ok' => true,
                    'token' => $hash,
                    'message' => 'Xác thực OTP thành công!'
                ]);
                exit();
            }

            // 4. Các POST dữ liệu vào bảng
            if (empty($body)) {
                http_response_code(400);
                die(json_encode(['error' => 'Dữ liệu rỗng']));
            }

            // Phân quyền tạo bản ghi (Chỉ admin được thêm các bảng trừ don_dang_ky)
            if ($table !== 'don_dang_ky') {
                if (!$admin || $admin['role'] !== 'super_admin') {
                    http_response_code(403);
                    die(json_encode(['error' => 'Chỉ Super Admin mới được thêm dữ liệu vào bảng này.']));
                }
            } else {
                // Nếu admin thêm hồ sơ thí sinh
                if ($admin && $admin['role'] !== 'super_admin') {
                    http_response_code(403);
                    die(json_encode(['error' => 'Tài khoản của bạn không được phép thêm hồ sơ thí sinh.']));
                }
            }

            // Tự động cập nhật (UPDATE) thay vì chèn mới (INSERT) nếu trùng CCCD và trạng thái là "Chờ duyệt"
            if ($table === 'don_dang_ky' && !empty($body['cccd'])) {
                $checkStmt = $pdo->prepare("SELECT id, trang_thai FROM `don_dang_ky` WHERE cccd = ?");
                $checkStmt->execute([$body['cccd']]);
                $existing = $checkStmt->fetch();
                if ($existing) {
                    if ($existing['trang_thai'] !== 'Chờ duyệt') {
                        http_response_code(400);
                        die(json_encode(['error' => 'Hồ sơ của bạn đã được duyệt hoặc xử lý, không thể thay đổi thông tin nữa.']));
                    }
                    
                    $idToUpdate = $existing['id'];
                    $set = implode(', ', array_map(fn($k) => "`$k`=:$k", array_keys($body)));
                    $stmt = $pdo->prepare("UPDATE `don_dang_ky` SET $set WHERE id=:_id");
                    $stmt->execute([...$body, '_id' => $idToUpdate]);
                    
                    if ($admin) {
                        write_audit_log($pdo, $admin['username'], 'SUA_HO_SO', "Cập nhật hồ sơ đăng ký trùng CCCD: " . $body['cccd']);
                    }
                    
                    echo json_encode(['id' => $idToUpdate, 'updated' => true, 'ok' => true]);
                    exit();
                }
            }
            
            $is_batch = isset($body[0]) && is_array($body[0]);
            if ($is_batch) {
                $pdo->beginTransaction();
                try {
                    $inserted_ids = [];
                    foreach ($body as $row) {
                        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($row)));
                        $vals = ':' . implode(', :', array_keys($row));
                        $stmt = $pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($vals)");
                        $stmt->execute($row);
                        $inserted_ids[] = $pdo->lastInsertId();
                    }
                    $pdo->commit();
                    
                    if ($admin) {
                        write_audit_log($pdo, $admin['username'], 'THEM_HANG_LOAT', "Thêm hàng loạt bản ghi mới vào $table.");
                    }
                    
                    echo json_encode(['ids' => $inserted_ids, 'ok' => true]);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    http_response_code(500);
                    die(json_encode(['error' => 'Lỗi chèn hàng loạt: ' . $e->getMessage()]));
                }
            } else {
                $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($body)));
                $vals = ':' . implode(', :', array_keys($body));
                $stmt = $pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($vals)");
                $stmt->execute($body);
                $insertedId = $pdo->lastInsertId();
                
                if ($admin) {
                    write_audit_log($pdo, $admin['username'], 'THEM_MOI', "Thêm mới bản ghi vào bảng $table. ID: " . $insertedId);
                }
                
                echo json_encode(['id' => $insertedId, 'ok' => true]);
            }
            break;

        case 'PUT':
            if (!$id || empty($body)) {
                http_response_code(400);
                die(json_encode(['error' => 'Thiếu id hoặc dữ liệu']));
            }
            
            // Phân quyền cập nhật chi tiết theo vai trò Admin
            if ($table === 'pl01_chitieu' || $table === 'pl02_chitieu' || $table === 'thong_bao') {
                if ($admin['role'] !== 'super_admin') {
                    http_response_code(403);
                    die(json_encode(['error' => 'Chỉ Super Admin mới có quyền sửa đổi chỉ tiêu tuyển dụng hoặc thông báo hệ thống.']));
                }
            }
            
            if ($table === 'don_dang_ky') {
                $keys = array_keys($body);
                if ($admin['role'] === 'approver') {
                    // Cán bộ duyệt hồ sơ chỉ được chỉnh sửa trang_thai
                    $diff = array_diff($keys, ['trang_thai']);
                    if (!empty($diff)) {
                        http_response_code(403);
                        die(json_encode(['error' => 'Cán bộ duyệt hồ sơ chỉ được phép duyệt hoặc từ chối hồ sơ Vòng 1.']));
                    }
                } else if ($admin['role'] === 'score_entry') {
                    // Cán bộ nhập điểm chỉ được chỉnh sửa diem, ket_qua
                    $diff = array_diff($keys, ['diem', 'ket_qua']);
                    if (!empty($diff)) {
                        http_response_code(403);
                        die(json_encode(['error' => 'Cán bộ nhập điểm chỉ được phép cập nhật điểm số và kết quả Vòng 2.']));
                    }
                }
            }
            
            $set = implode(', ', array_map(fn($k) => "`$k`=:$k", array_keys($body)));
            $stmt = $pdo->prepare("UPDATE `$table` SET $set WHERE id=:_id");
            $stmt->execute([...$body, '_id' => $id]);
            
            // Tự động gửi Email mô phỏng thông báo kết quả cho thí sinh khi duyệt hoặc nhập điểm
            if ($table === 'don_dang_ky') {
                $stmtCand = $pdo->prepare("SELECT ho_ten, email, trang_thai, diem, ket_qua FROM `don_dang_ky` WHERE id = ?");
                $stmtCand->execute([$id]);
                $cand = $stmtCand->fetch();
                if ($cand) {
                    if (in_array('trang_thai', array_keys($body))) {
                        $emailSubject = "Thông báo kết quả duyệt hồ sơ tuyển dụng - Vòng 1";
                        $emailBody = "Chào " . $cand['ho_ten'] . ",\n\n";
                        $emailBody .= "Hồ sơ tuyển dụng của bạn đã được cập nhật trạng thái: " . $cand['trang_thai'] . ".\n";
                        if ($cand['trang_thai'] === 'Đã duyệt') {
                            $emailBody .= "Chúc mừng bạn đã đủ điều kiện bước tiếp vào Vòng 2. Vui lòng theo dõi lịch thông báo tiếp theo.\n";
                        } else {
                            $emailBody .= "Rất tiếc hồ sơ của bạn chưa đáp ứng yêu cầu tuyển dụng.\n";
                        }
                        $emailBody .= "\nTrân trọng,\n" . EMAIL_SIGNATURE;
                        sendEmailSimulated($cand['email'], $emailSubject, $emailBody);
                    }
                    if (in_array('diem', array_keys($body)) || in_array('ket_qua', array_keys($body))) {
                        $emailSubject = "Thông báo điểm thi & kết quả tuyển dụng - Vòng 2";
                        $emailBody = "Chào " . $cand['ho_ten'] . ",\n\n";
                        $emailBody .= "Kết quả thi tuyển Vòng 2 của bạn đã được cập nhật:\n";
                        $emailBody .= "- Điểm số: " . ($cand['diem'] !== null ? $cand['diem'] : 'Chưa xét') . "\n";
                        $emailBody .= "- Kết quả xét tuyển: " . ($cand['ket_qua'] ? $cand['ket_qua'] : 'Chưa xét') . "\n\n";
                        $emailBody .= "Trân trọng,\n" . EMAIL_SIGNATURE;
                        sendEmailSimulated($cand['email'], $emailSubject, $emailBody);
                    }
                }
            }
            
            write_audit_log($pdo, $admin['username'], 'SUA_BANG', "Sửa đổi bản ghi bảng $table. ID: $id. Nội dung thay đổi: " . json_encode($body, JSON_UNESCAPED_UNICODE));
            
            echo json_encode(['ok' => true]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                die(json_encode(['error' => 'Thiếu id']));
            }
            
            // Chỉ Super Admin được phép xóa bất kỳ thứ gì
            if ($admin['role'] !== 'super_admin') {
                http_response_code(403);
                die(json_encode(['error' => 'Chỉ Super Admin mới được quyền xóa bản ghi.']));
            }
            
            $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id=?");
            $stmt->execute([$id]);
            
            write_audit_log($pdo, $admin['username'], 'XOA_BANG', "Xóa bản ghi khỏi bảng $table. ID: $id");
            
            echo json_encode(['ok' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method không được hỗ trợ']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
