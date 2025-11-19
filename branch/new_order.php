<?php
// api/branch/new_order.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/../db.php';

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

$branch_id = $input['branch_id'] ?? $_POST['branch_id'] ?? null;
$quantity  = $input['quantity']  ?? $_POST['quantity']  ?? null;
$notes     = trim($input['notes'] ?? $_POST['notes'] ?? '');

if (!$branch_id || !$quantity) {
    echo json_encode([
        "success" => false,
        "message" => "branch_id والكمية مطلوبة"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO orders (branch_id, quantity, notes, status, request_time)
        VALUES (?, ?, ?, 'waiting', NOW())
    ");

    $stmt->execute([$branch_id, $quantity, $notes]);

    echo json_encode([
        "success" => true,
        "message" => "تم إنشاء الطلب"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "خطأ أثناء إنشاء الطلب",
        "error" => $e->getMessage()
    ]);
}
