<?php
// api/core/helpers.php
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';

/**
 * قراءة JSON من body
 */
function get_json_input(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * حساب المسافة بالكيلومتر بين إحداثيين (هفرسين)
 */
function haversine_distance_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadius = 6371; // km

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

/**
 * تسجيل حالة الطلب في order_logs + status_history
 */
function log_order_status(int $orderId, string $status, string $comment, string $changedBy = 'driver'): void
{
    $pdo = db();

    // order_logs
    $stmt = $pdo->prepare("
        INSERT INTO order_logs (order_id, status, comment)
        VALUES (:order_id, :status, :comment)
    ");
    $stmt->execute([
        ':order_id' => $orderId,
        ':status'   => $status,
        ':comment'  => $comment,
    ]);

    // status_history
    $stmt2 = $pdo->prepare("
        INSERT INTO status_history (order_id, status, changed_by)
        VALUES (:order_id, :status, :changed_by)
    ");
    $stmt2->execute([
        ':order_id'   => $orderId,
        ':status'     => $status,
        ':changed_by' => $changedBy,
    ]);
}
