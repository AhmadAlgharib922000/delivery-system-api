<?php
// api/branch/profile_update.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/../db.php';

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

$branch_id = $input['branch_id'] ?? $_POST['branch_id'] ?? null;
$name      = trim($input['name']      ?? $_POST['name']      ?? '');
$email     = trim($input['email']     ?? $_POST['email']     ?? '');
$phone     = trim($input['phone']     ?? $_POST['phone']     ?? '');
$address   = trim($input['address']   ?? $_POST['address']   ?? '');
$current   = trim($input['current_password'] ?? $_POST['current_password'] ?? '');
$newPass   = trim($input['new_password']     ?? $_POST['new_password']     ?? '');
$confirm   = trim($input['confirm_password'] ?? $_POST['confirm_password'] ?? '');

if (!$branch_id || $name === '' || $email === '' || $phone === '') {
    echo json_encode([
        "success" => false,
        "message" => "الحقول الأساسية مطلوبة"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM branches WHERE branch_id = ?");
    $stmt->execute([$branch_id]);
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$branch) {
        echo json_encode([
            "success" => false,
            "message" => "الحساب غير موجود"
        ]);
        exit;
    }

    // تحديث كلمة المرور
    if ($current !== '' || $newPass !== '' || $confirm !== '') {

        if ($current !== $branch['password']) {
            echo json_encode([
                "success" => false,
                "message" => "كلمة المرور الحالية خاطئة"
            ]);
            exit;
        }

        if ($newPass !== $confirm) {
            echo json_encode([
                "success" => false,
                "message" => "كلمة المرور غير متطابقة"
            ]);
            exit;
        }

        $updatePass = $pdo->prepare("UPDATE branches SET password = ? WHERE branch_id = ?");
        $updatePass->execute([$newPass, $branch_id]);
    }

    // تحديث البيانات الشخصية
    $update = $pdo->prepare("
        UPDATE branches SET name = ?, email = ?, phone = ?, address = ?
        WHERE branch_id = ?
    ");
    $update->execute([$name, $email, $phone, $address, $branch_id]);

    echo json_encode([
        "success" => true,
        "message" => "تم تحديث البيانات بنجاح"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "خطأ أثناء تحديث البيانات",
        "error" => $e->getMessage()
    ]);
}
