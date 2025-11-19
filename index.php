<?php
// Router لتوجيه الطلبات إلى الملفات المناسبة

$requested = $_SERVER['REQUEST_URI'];

// إذا كان الملف موجود فعلياً → PHP يشغّله مباشرة
$file = __DIR__ . $requested;

if ($requested !== '/' && file_exists($file)) {
    return false; 
}

// إذا لم يوجد الملف → نعيد رسالة JSON
header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    "success" => false,
    "message" => "API is running!",
    "route"   => $requested
]);
