<?php
// api/branch/login.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../db.php'; // يوفر $pdo من core/db.php

// دعم JSON + POST عادي
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

$email    = trim($input['email']    ?? $_POST['email']    ?? '');
$password = trim($input['password'] ?? $_POST['password'] ?? '');

if ($email === '' || $password === '') {
    echo json_encode([
        'success' => false,
        'message' => 'البريد الإلكتروني وكلمة المرور مطلوبة'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM branches WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$branch || $branch['password'] !== $password) {
        echo json_encode([
            'success' => false,
            'message' => 'بيانات الدخول غير صحيحة'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($branch['status'] !== 'approved') {
        echo json_encode([
            'success' => false,
            'message' => 'الحساب غير مفعل أو قيد المراجعة'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'data' => $branch
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في السيرفر',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
