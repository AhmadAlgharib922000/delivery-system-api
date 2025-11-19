<?php
// api/branch/order_details.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../db.php';

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    echo json_encode([
        "success" => false,
        "message" => "order_id مفقود"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT o.*, b.name AS branch_name, d.full_name AS driver_name, d.phone AS driver_phone
        FROM orders o
        LEFT JOIN branches b ON b.branch_id = o.branch_id
        LEFT JOIN drivers d  ON d.driver_id = o.driver_id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "order" => $order
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "خطأ أثناء جلب التفاصيل",
        "error" => $e->getMessage()
    ]);
}
