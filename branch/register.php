<?php
// api/branch/register.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/../db.php';

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

$name       = trim($input['name']       ?? $_POST['name']       ?? '');
$email      = trim($input['email']      ?? $_POST['email']      ?? '');
$password   = trim($input['password']   ?? $_POST['password']   ?? '');
$phone      = trim($input['phone']      ?? $_POST['phone']      ?? '');
$address    = trim($input['address']    ?? $_POST['address']    ?? '');
$latitude   = trim($input['latitude']   ?? $_POST['latitude']   ?? '');
$longitude  = trim($input['longitude']  ?? $_POST['longitude']  ?? '');
$complex_id =        $input['complex_id'] ?? $_POST['complex_id'] ?? null;

if ($name === '' || $email === '' || $password === '' || $phone === '') {
    echo json_encode([
        "success" => false,
        "message" => "جميع الحقول الأساسية مطلوبة"
    ]);
    exit;
}

try {
    $check = $pdo->prepare("SELECT branch_id FROM branches WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        echo json_encode([
            "success" => false,
            "message" => "البريد الإلكتروني مستخدم"
        ]);
        exit;
    }

    $insert = $pdo->prepare("
        INSERT INTO branches (name, email, password, phone, address, latitude, longitude, complex_id, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $insert->execute([$name, $email, $password, $phone, $address, $latitude, $longitude, $complex_id]);

    echo json_encode([
        "success" => true,
        "message" => "تم إنشاء الحساب وبانتظار الموافقة"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "خطأ أثناء التسجيل",
        "error" => $e->getMessage()
    ]);
}
