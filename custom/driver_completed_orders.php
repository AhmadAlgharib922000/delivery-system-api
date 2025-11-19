<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/response.php';

$pdo = db();

$driver_id = isset($_GET['driver_id']) ? intval($_GET['driver_id']) : 0;

if ($driver_id <= 0) {
    json_response(400, false, "معرّف السائق غير صالح");
}

$stmt = $pdo->prepare("
    SELECT 
        o.*,
        b.name AS branch_name,
        b.phone AS branch_phone,
        b.address AS branch_address,
        b.latitude AS branch_lat,
        b.longitude AS branch_lng
    FROM orders o
    LEFT JOIN branches b ON b.branch_id = o.branch_id
    WHERE o.driver_id = ?
      AND o.status = 'completed'
    ORDER BY o.dropoff_time DESC
");

$stmt->execute([$driver_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response(200, true, "Success", ["orders" => $orders]);
