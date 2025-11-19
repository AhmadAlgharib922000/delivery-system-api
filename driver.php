<?php
/**
 *  FINAL DRIVER API — 2025
 *  Author: Ahmad + ChatGPT
 *  Single-file endpoint for all driver operations
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/response.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/jwt.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$action) {
    json_response(400, false, "يرجى تحديد action");
}

/* ------------------------------------------------------------------
   1) تسجيل الدخول — login
   ------------------------------------------------------------------*/
if ($action === "login" && $method === "POST") {
    $input = get_json_input();

    $email = trim($input["email"] ?? "");
    $password = trim($input["password"] ?? "");

    if ($email === "" || $password === "") {
        json_response(422, false, "البريد الإلكتروني وكلمة المرور مطلوبة");
    }

    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $driver = $stmt->fetch();

    if (!$driver || $driver["password"] !== $password) {
        json_response(401, false, "بيانات الدخول غير صحيحة");
    }

    if ($driver["status"] !== "active") {
        json_response(403, false, "الحساب غير نشط أو محظور");
    }

    // إنشاء JWT
    $payload = [
        "driver_id" => (int)$driver["driver_id"],
        "full_name" => $driver["full_name"],
        "email" => $driver["email"],
        "iat" => time(),
        "exp" => time() + (86400 * 7)
    ];

    $token = jwt_encode($payload);

    json_response(200, true, "تم تسجيل الدخول", [
        "token" => $token,
        "driver" => $driver
    ]);
}

/* ------------------------------------------------------------------
   2) تسجيل سائق جديد — register
   ------------------------------------------------------------------*/
if ($action === "register" && $method === "POST") {
    $input = get_json_input();

    $full_name = trim($input["full_name"] ?? "");
    $email     = trim($input["email"] ?? "");
    $phone     = trim($input["phone"] ?? "");
    $password  = trim($input["password"] ?? "");

    if ($full_name === "" || $email === "" || $phone === "" || $password === "") {
        json_response(422, false, "جميع الحقول مطلوبة");
    }

    $check = $pdo->prepare("SELECT driver_id FROM drivers WHERE email=? LIMIT 1");
    $check->execute([$email]);

    if ($check->fetch()) {
        json_response(409, false, "البريد الإلكتروني مستخدم مسبقاً");
    }

    $stmt = $pdo->prepare("
        INSERT INTO drivers(full_name, email, phone, password, status)
        VALUES (?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$full_name, $email, $phone, $password]);

    json_response(200, true, "تم إنشاء الحساب بنجاح");
}

/* ------------------------------------------------------------------
   3) استرجاع كلمة المرور — forgot_password
   ------------------------------------------------------------------*/
if ($action === "forgot_password" && $method === "POST") {
    $input = get_json_input();

    $full_name = trim($input["full_name"] ?? "");
    $phone = trim($input["phone"] ?? "");
    $new_pass = trim($input["new_password"] ?? "");

    if ($full_name === "" || $phone === "" || $new_pass === "") {
        json_response(422, false, "جميع الحقول مطلوبة");
    }

    $stmt = $pdo->prepare("SELECT driver_id FROM drivers WHERE full_name=? AND phone=?");
    $stmt->execute([$full_name, $phone]);
    $driver = $stmt->fetch();

    if (!$driver) {
        json_response(404, false, "لا يوجد سائق بهذه المعلومات");
    }

    $pdo->prepare("UPDATE drivers SET password=? WHERE driver_id=?")
        ->execute([$new_pass, $driver["driver_id"]]);

    json_response(200, true, "تم تغيير كلمة المرور بنجاح");
}

/* ------------------------------------------------------------------
   كل ما بعد هذا يحتاج توكين
   ------------------------------------------------------------------*/
$driver = require_driver_auth();

/* ------------------------------------------------------------------
   4) me — بيانات السائق
   ------------------------------------------------------------------*/
if ($action === "me") {
    json_response(200, true, "بيانات السائق", $driver);
}

/* ------------------------------------------------------------------
   5) update_profile
   ------------------------------------------------------------------*/
if ($action === "update_profile" && $method === "POST") {
    $input = get_json_input();

    $full_name = trim($input["full_name"] ?? $driver["full_name"]);
    $email     = trim($input["email"] ?? $driver["email"]);
    $phone     = trim($input["phone"] ?? $driver["phone"]);

    $new_pass = trim($input["new_password"] ?? "");
    $current_pass = trim($input["current_password"] ?? "");

    if ($new_pass !== "") {
        if ($current_pass !== $driver["password"]) {
            json_response(422, false, "كلمة المرور الحالية غير صحيحة");
        }
        $pass_sql = ", password='$new_pass'";
    } else {
        $pass_sql = "";
    }

    $stmt = $pdo->prepare("
        UPDATE drivers SET 
            full_name=?, email=?, phone=? $pass_sql
        WHERE driver_id=?
    ");
    $stmt->execute([$full_name, $email, $phone, $driver["driver_id"]]);

    json_response(200, true, "تم تحديث الملف الشخصي");
}

/* ------------------------------------------------------------------
   6) update_location
   ------------------------------------------------------------------*/
if ($action === "update_location" && $method === "POST") {
    $input = get_json_input();

    if (!isset($input["latitude"]) || !isset($input["longitude"])) {
        json_response(422, false, "الإحداثيات مطلوبة");
    }

    $lat = floatval($input["latitude"]);
    $lng = floatval($input["longitude"]);
    $is_available = isset($input["is_available"]) ? intval($input["is_available"]) : $driver["is_available"];

    $stmt = $pdo->prepare("
        UPDATE drivers 
        SET current_latitude=?, current_longitude=?, is_available=? 
        WHERE driver_id=?
    ");
    $stmt->execute([$lat, $lng, $is_available, $driver["driver_id"]]);

    json_response(200, true, "تم تحديث الموقع");
}

/* ------------------------------------------------------------------
   7) active_orders
   ------------------------------------------------------------------*/
if ($action === "active_orders") {

    $stmt = $pdo->prepare("
        SELECT o.*, b.name AS branch_name, b.latitude AS branch_lat, b.longitude AS branch_lng
        FROM orders o
        JOIN branches b ON o.branch_id=b.branch_id
        WHERE o.driver_id=? AND o.status IN ('assigned','picked_up')
        ORDER BY o.order_id DESC
    ");
    $stmt->execute([$driver["driver_id"]]);

    json_response(200, true, "Active orders", $stmt->fetchAll());
}

/* ------------------------------------------------------------------
   8) completed_orders
   ------------------------------------------------------------------*/
if ($action === "completed_orders") {

    $stmt = $pdo->prepare("
        SELECT o.*, b.name AS branch_name
        FROM orders o
        JOIN branches b ON o.branch_id=b.branch_id
        WHERE o.driver_id=? AND o.status='completed'
        ORDER BY o.order_id DESC
    ");
    $stmt->execute([$driver["driver_id"]]);

    json_response(200, true, "Completed orders", $stmt->fetchAll());
}

/* ------------------------------------------------------------------
   9) orders_nearby
   ------------------------------------------------------------------*/
if ($action === "orders_nearby") {

    $lat = isset($_GET["lat"]) ? floatval($_GET["lat"]) : $driver["current_latitude"];
    $lng = isset($_GET["lng"]) ? floatval($_GET["lng"]) : $driver["current_longitude"];

    if (!$lat || !$lng) {
        // لا GPS → أرسل بدون ترتيب
        $q = $pdo->query("
            SELECT o.*, b.name AS branch_name, b.latitude AS branch_lat, b.longitude AS branch_lng
            FROM orders o
            JOIN branches b ON o.branch_id=b.branch_id
            WHERE o.status='waiting' AND o.driver_id IS NULL
            LIMIT 100
        ");
        json_response(200, true, "بدون GPS", $q->fetchAll());
    }

    // GPS موجود → حساب المسافة
    $stmt = $pdo->prepare("
        SELECT 
            o.*, b.name AS branch_name,
            (6371 * ACOS(
                COS(RADIANS(:lat))*COS(RADIANS(b.latitude))*
                COS(RADIANS(b.longitude)-RADIANS(:lng)) +
                SIN(RADIANS(:lat))*SIN(RADIANS(b.latitude))
            )) AS distance_km
        FROM orders o
        JOIN branches b ON o.branch_id=b.branch_id
        WHERE o.status='waiting' AND o.driver_id IS NULL
        ORDER BY distance_km ASC
        LIMIT 100
    ");

    $stmt->execute(["lat" => $lat, "lng" => $lng]);

    json_response(200, true, "تم جلب الأقرب", [
        "driver_location" => ["lat" => $lat, "lng" => $lng],
        "orders" => $stmt->fetchAll()
    ]);
}

/* ------------------------------------------------------------------
   10) order_details
   ------------------------------------------------------------------*/
if ($action === "order_details") {
    $order_id = intval($_GET["order_id"] ?? 0);
    if ($order_id <= 0) json_response(422, false, "order_id مطلوب");

    $stmt = $pdo->prepare("
        SELECT o.*, b.name AS branch_name, b.address AS branch_address
        FROM orders o
        JOIN branches b ON o.branch_id=b.branch_id
        WHERE o.order_id=?
    ");
    $stmt->execute([$order_id]);

    json_response(200, true, "تفاصيل الطلب", $stmt->fetch());
}

/* ------------------------------------------------------------------
   11) claim_order
   ------------------------------------------------------------------*/
if ($action === "claim_order" && $method === "POST") {
    $input = get_json_input();
    $order_id = intval($input["order_id"] ?? 0);

    if ($order_id <= 0) json_response(422, false, "order_id مطلوب");

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id=? FOR UPDATE");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $pdo->rollBack();
        json_response(404, false, "الطلب غير موجود");
    }

    if ($order["status"] !== "waiting") {
        $pdo->rollBack();
        json_response(409, false, "الطلب لم يعد متاحاً");
    }

    $pdo->prepare("
        UPDATE orders SET status='assigned', driver_id=?, assigned_time=NOW()
        WHERE order_id=?
    ")->execute([$driver["driver_id"], $order_id]);

    log_order_status($order_id, "assigned", "تعيين السائق");

    $pdo->commit();
    json_response(200, true, "تم استلام الطلب");
}

/* ------------------------------------------------------------------
   12) arrive_branch
   ------------------------------------------------------------------*/
if ($action === "arrive_branch" && $method === "POST") {
    $input = get_json_input();
    $order_id = intval($input["order_id"] ?? 0);

    $stmt = $pdo->prepare("
        SELECT o.*, b.latitude AS b_lat, b.longitude AS b_lng
        FROM orders o 
        JOIN branches b ON o.branch_id=b.branch_id
        WHERE o.order_id=? AND o.driver_id=?
    ");
    $stmt->execute([$order_id, $driver["driver_id"]]);
    $order = $stmt->fetch();

    if (!$order) json_response(404, false, "لا يوجد طلب مرتبط بك");

    $dist = haversine_distance_km(
        $driver["current_latitude"],
        $driver["current_longitude"],
        $order["b_lat"],
        $order["b_lng"]
    );

    if ($dist > 0.1) {
        json_response(422, false, "اقترب من الفرع أكثر", ["distance_km" => $dist]);
    }

    $pdo->prepare("UPDATE orders SET received_time=NOW() WHERE order_id=?")
        ->execute([$order_id]);

    log_order_status($order_id, "received", "وصول للفرع");

    json_response(200, true, "تم تسجيل الوصول", ["distance_km" => $dist]);
}

/* ------------------------------------------------------------------
   13) confirm_pickup
   ------------------------------------------------------------------*/
if ($action === "confirm_pickup" && $method === "POST") {
    $input = get_json_input();
    $order_id = intval($input["order_id"] ?? 0);
    $received_qty = intval($input["received_quantity"] ?? 0);

    $pdo->prepare("
        UPDATE orders SET 
            status='picked_up',
            received_quantity=?,
            pickup_time=NOW(),
            time_in_branch=TIMESTAMPDIFF(MINUTE, received_time, NOW())
        WHERE order_id=? AND driver_id=?
    ")->execute([$received_qty, $order_id, $driver["driver_id"]]);

    log_order_status($order_id, "picked_up", "تم الاستلام");

    json_response(200, true, "تم تأكيد الاستلام");
}

/* ------------------------------------------------------------------
   14) complete_delivery
   ------------------------------------------------------------------*/
if ($action === "complete_delivery" && $method === "POST") {
    $input = get_json_input();

    $order_id = intval($input["order_id"] ?? 0);
    $rating = intval($input["rating"] ?? 0);

    $pdo->prepare("
        UPDATE orders SET 
            status='completed',
            dropoff_time=NOW(),
            duration_minutes=TIMESTAMPDIFF(MINUTE, request_time, NOW()),
            rating=?
        WHERE order_id=? AND driver_id=?
    ")->execute([$rating, $order_id, $driver["driver_id"]]);

    log_order_status($order_id, "completed", "إكمال التوصيل");

    json_response(200, true, "تم إكمال التوصيل");
}

/* ------------------------------------------------------------------
   15) cancel_order
   ------------------------------------------------------------------*/
if ($action === "cancel_order" && $method === "POST") {
    $input = get_json_input();
    $order_id = intval($input["order_id"] ?? 0);
    $reason = trim($input["reason"] ?? "");

    $pdo->prepare("
        UPDATE orders SET 
            status='waiting',
            driver_id=NULL,
            assigned_time=NULL,
            pickup_time=NULL,
            received_time=NULL,
            time_in_branch=NULL,
            cancellation_reason=?
        WHERE order_id=? AND driver_id=?
    ")->execute([$reason, $order_id, $driver["driver_id"]]);

    log_order_status($order_id, "waiting", "إلغاء من السائق: $reason");

    json_response(200, true, "تم إلغاء الطلب وإعادته للانتظار");
}

/* ------------------------------------------------------------------*/
json_response(400, false, "Action غير معروفة: $action");
