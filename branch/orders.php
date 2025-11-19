<?php
// api/branch/orders.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../db.php';

$branch_id = $_GET['branch_id'] ?? null;
$status    = $_GET['status']    ?? 'all';

if (!$branch_id) {
    echo json_encode([
        "success" => false,
        "message" => "branch_id مفقود"
    ]);
    exit;
}

try {
    $sql = "SELECT o.*, d.full_name AS driver_name
            FROM orders o
            LEFT JOIN drivers d ON o.driver_id = d.driver_id
            WHERE o.branch_id = ?";

    if ($status !== 'all') {
        $sql .= " AND o.status = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$branch_id, $status]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$branch_id]);
    }

    echo json_encode([
        "success" => true,
        "orders" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "خطأ",
        "error" => $e->getMessage()
    ]);
}
