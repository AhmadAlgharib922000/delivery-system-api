<?php
// api/core/db.php
require_once __DIR__ . '/response.php';

$httpHost = $_SERVER['HTTP_HOST'] ?? '';

// ====== بيئة محلية ======
if ($httpHost === 'localhost' || $httpHost === '127.0.0.1') {
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'delivery_system');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

// ====== بيئة الاستضافة ======
else {
    define('DB_HOST', 'sql308.infinityfree.com');
    define('DB_NAME', 'if0_40424646_delivery_system');
    define('DB_USER', 'if0_40424646');

    // كلمة المرور هي كلمة مرور حساب InfinityFree الخاصة بك
    // قم بتغييرها هنا إلى نفس كلمة سر حسابك
    define('DB_PASS', '0933847867');
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        json_response(500, false, 'Database connection error', [
            'error' => $e->getMessage(),
        ]);
    }

    return $pdo;
}
