<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../db.php';

$name        = trim($_POST['name'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');

if ($name === '' || $phone === '' || $newPassword === '') {
    echo json_encode([
        "success" => false,
        "message" => "جميع الحقول مطلوبة"
    ]);
    exit;
}

try {
    // التحقق من الفرع فقط
    $stmt = $pdo->prepare("SELECT branch_id FROM branches WHERE name = ? AND phone = ?");
    $stmt->execute([$name, $phone]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "success" => false,
            "message" => "لم يتم العثور على حساب مطابق للبيانات المدخلة"
        ]);
        exit;
    }

    $pdo->prepare("UPDATE branches SET password = ? WHERE name = ? AND phone = ?")
        ->execute([$newPassword, $name, $phone]);

    echo json_encode([
        "success" => true,
        "message" => "تم تحديث كلمة المرور بنجاح"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "حدث خطأ في النظام",
        "error"   => $e->getMessage()
    ]);
}
