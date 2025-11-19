<?php
// api/branch/forgot.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/../db.php';

// JSON + POST
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

$name        = trim($input['name']        ?? $_POST['name']        ?? '');
$phone       = trim($input['phone']       ?? $_POST['phone']       ?? '');
$newPassword = trim($input['new_password'] ?? $_POST['new_password'] ?? '');

if ($name === '' || $phone === '' || $newPassword === '') {
    echo json_encode([
        "success" => false,
        "message" => "جميع الحقول مطلوبة"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT branch_id FROM branches WHERE name = ? AND phone = ?");
    $stmt->execute([$name, $phone]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "success" => false,
            "message" => "لا يوجد حساب مطابق"
        ]);
        exit;
    }

    $update = $pdo->prepare("UPDATE branches SET password = ? WHERE name = ? AND phone = ?");
    $update->execute([$newPassword, $name, $phone]);

    echo json_encode([
        "success" => true,
        "message" => "تم تحديث كلمة المرور بنجاح"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "خطأ في النظام",
        "error" => $e->getMessage()
    ]);
}
