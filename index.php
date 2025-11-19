<?php
// Router بسيط لتوجيه كل الطلبات للملفات الصحيحة

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// إذا تمت زيارة ملف موجود مباشرة (مثلاً admin.php أو driver/login.php)
if ($path !== "/" && file_exists(__DIR__ . $path)) {
    return false; // let PHP handle it directly
}

// رد افتراضي
header("Content-Type: application/json; charset=UTF-8");
echo json_encode([
    "success" => true,
    "message" => "Delivery API is running!",
    "path" => $path
]);
