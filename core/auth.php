<?php
// api/core/auth.php
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';

// Polyfill في حال السيرفر لا يدعم str_starts_with (إصدارات PHP أقدم)
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

/**
 * قراءة التوكين من هيدر Authorization
 */
function get_bearer_token(): ?string
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    // محاولات مختلفة لقراءة Authorization
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;

    if (!$auth && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if (!$auth && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    if (!$auth || !str_starts_with($auth, 'Bearer ')) {
        return null;
    }

    return trim(substr($auth, 7));
}

/**
 * إرجاع بيانات السائق الحالي من الـ token
 */
function require_driver_auth(): array
{
    $token = get_bearer_token();
    if (!$token) {
        json_response(401, false, 'مطلوب تسجيل الدخول (لا يوجد توكين)');
    }

    $payload = jwt_decode($token);
    if (!$payload || !isset($payload['driver_id'])) {
        json_response(401, false, 'توكن غير صالح أو منتهي');
    }

    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE driver_id = :id AND status = 'active'");
    $stmt->execute([':id' => $payload['driver_id']]);
    $driver = $stmt->fetch();

    if (!$driver) {
        json_response(401, false, 'الحساب غير موجود أو غير نشط');
    }

    return $driver;
}
