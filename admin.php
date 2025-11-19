<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/response.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/jwt.php';

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$action) {
    json_response(400, false, "يرجى تحديد action");
}

/* ---------------------------------------------------------
   دالة: توثيق الأدمن JWT
--------------------------------------------------------- */
function require_admin()
{
    $token = get_bearer_token();
    if (!$token) json_response(401, false, 'توكن غير موجود');

    $payload = jwt_decode($token);
    if (!$payload || !isset($payload['admin_id'])) {
        json_response(401, false, 'توكن غير صالح أو منتهي');
    }

    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id=? LIMIT 1");
    $stmt->execute([$payload['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin) json_response(401, false, 'حساب الأدمن غير موجود');

    return $admin;
}

/* ============================================================
   1) تسجيل الدخول
============================================================ */
// POST /admin.php?action=login
// body: { email, password }
if ($action === 'login' && $method === 'POST') {

    $data = get_json_input();
    $email = trim($data['email'] ?? '');
    $pass  = trim($data['password'] ?? '');

    if ($email === '' || $pass === '') {
        json_response(422, false, "جميع الحقول مطلوبة");
    }

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if (!$admin || $admin['password'] !== $pass) {
        json_response(401, false, "بيانات الدخول غير صحيحة");
    }

    $payload = [
        "admin_id" => (int)$admin['admin_id'],
        "email"    => $admin['email'],
        "full_name"=> $admin['full_name'],
        "iat"      => time(),
        "exp"      => time() + 86400 * 7 // أسبوع
    ];

    $token = jwt_encode($payload);

    unset($admin['password']);

    json_response(200, true, "تم تسجيل الدخول", [
        "token" => $token,
        "admin" => $admin
    ]);
}

/* ============================================================
   2) نسيان كلمة المرور (reset password)
============================================================ */
// POST /admin.php?action=forgot
// body: { email, new_password }
if ($action === 'forgot' && $method === 'POST') {

    $data = get_json_input();
    $email = trim($data['email'] ?? '');
    $new_pass = trim($data['new_password'] ?? '');

    if ($email === "" || $new_pass === "") {
        json_response(422, false, "جميع الحقول مطلوبة");
    }

    $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE email=?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() === 0) {
        json_response(404, false, "البريد الإلكتروني غير موجود");
    }

    $pdo->prepare("UPDATE admins SET password=? WHERE email=?")
        ->execute([$new_pass, $email]);

    json_response(200, true, "تم تحديث كلمة المرور");
}

/* ============================================================
   3) بيانات الأدمن الحالي
============================================================ */
if ($action === 'me' && $method === 'GET') {
    $admin = require_admin();
    unset($admin['password']);
    json_response(200, true, "تم", $admin);
}

/* ============================================================
   4) قائمة الفروع
============================================================ */
// GET /admin.php?action=branches
if ($action === 'branches' && $method === 'GET') {

    $stmt = $pdo->query("
        SELECT *
        FROM branches
        ORDER BY branch_id DESC
    ");

    json_response(200, true, "قائمة الفروع", [
        "branches" => $stmt->fetchAll()
    ]);
}

/* ============================================================
   5) تعديل بيانات فرع
============================================================ */
// POST /admin.php?action=branch_update
if ($action === 'branch_update' && $method === 'POST') {
    require_admin();

    $data = get_json_input();
    $id   = intval($data['branch_id'] ?? 0);

    if ($id <= 0) json_response(422, false, "branch_id مطلوب");

    $fields = [
        "name","email","password","phone","address",
        "latitude","longitude","complex_id","status","collection_id"
    ];

    $updates = [];
    $params = [];

    foreach ($fields as $f) {
        if (isset($data[$f])) {
            $updates[] = "$f = ?";
            $params[]  = $data[$f];
        }
    }

    if (empty($updates)) {
        json_response(400, false, "لا يوجد شيء لتحديثه");
    }

    $params[] = $id;

    $sql = "UPDATE branches SET " . implode(",", $updates) . " WHERE branch_id=?";
    $pdo->prepare($sql)->execute($params);

    json_response(200, true, "تم تحديث بيانات الفرع");
}

/* ============================================================
   6) تغيير حالة فرع (approved / pending / rejected)
============================================================ */
// POST /admin.php?action=branch_status
if ($action === 'branch_status' && $method === 'POST') {
    require_admin();

    $data = get_json_input();
    $id = intval($data['branch_id'] ?? 0);
    $status = $data['status'] ?? '';

    if (!in_array($status, ['approved','pending','rejected'])) {
        json_response(422, false, "حالة غير صحيحة");
    }

    $pdo->prepare("UPDATE branches SET status=? WHERE branch_id=?")
        ->execute([$status,$id]);

    json_response(200, true, "تم تعديل الحالة");
}

/* ============================================================
   7) قائمة السائقين
============================================================ */
// GET /admin.php?action=drivers
if ($action === 'drivers' && $method === 'GET') {

    $stmt = $pdo->query("
        SELECT *
        FROM drivers
        ORDER BY driver_id DESC
    ");

    json_response(200, true, "قائمة السائقين", [
        "drivers" => $stmt->fetchAll()
    ]);
}

/* ============================================================
   8) تعديل بيانات سائق
============================================================ */
// POST /admin.php?action=driver_update
if ($action === 'driver_update' && $method === 'POST') {
    require_admin();

    $data = get_json_input();
    $id   = intval($data['driver_id'] ?? 0);

    if ($id <= 0) json_response(422, false, "driver_id مطلوب");

    $fields = [
        "full_name","email","password","phone",
        "current_latitude","current_longitude",
        "is_available","status"
    ];

    $updates = [];
    $params = [];

    foreach ($fields as $f) {
        if (isset($data[$f])) {
            $updates[] = "$f = ?";
            $params[]  = $data[$f];
        }
    }

    if (empty($updates)) json_response(400, false, "لا يوجد شيء لتحديثه");

    $params[] = $id;

    $sql = "UPDATE drivers SET " . implode(",", $updates) . " WHERE driver_id=?";
    $pdo->prepare($sql)->execute($params);

    json_response(200, true, "تم تحديث بيانات السائق");
}

/* ============================================================
   9) تغيير حالة السائق (active / banned)
============================================================ */
// POST /admin.php?action=driver_status
if ($action === 'driver_status' && $method === 'POST') {
    require_admin();

    $data = get_json_input();
    $id = intval($data['driver_id'] ?? 0);
    $status = $data['status'] ?? '';

    if (!in_array($status, ['active','banned'])) {
        json_response(422, false, "حالة غير صحيحة");
    }

    $pdo->prepare("UPDATE drivers SET status=? WHERE driver_id=?")
        ->execute([$status,$id]);

    json_response(200, true, "تم تغيير حالة السائق");
}

/* ============================================================
   10) قائمة الطلبات
============================================================ */
// GET /admin.php?action=orders
if ($action === 'orders' && $method === 'GET') {

    $stmt = $pdo->query("
        SELECT 
            o.*,
            b.name AS branch_name,
            d.full_name AS driver_name
        FROM orders o
        LEFT JOIN branches b ON b.branch_id=o.branch_id
        LEFT JOIN drivers  d ON d.driver_id=o.driver_id
        ORDER BY o.order_id DESC
    ");

    json_response(200, true, "قائمة الطلبات", [
        "orders" => $stmt->fetchAll()
    ]);
}

/* ============================================================
   11) تفاصيل طلب كامل (order + history + logs)
============================================================ */
// GET /admin.php?action=order_full&order_id=33
if ($action === 'order_full' && $method === 'GET') {

    $id = intval($_GET['order_id'] ?? 0);
    if ($id <= 0) json_response(422, false, "order_id مطلوب");

    // تفاصيل الطلب
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            b.name AS branch_name,
            b.phone AS branch_phone,
            b.address AS branch_address,
            d.full_name AS driver_name,
            d.phone AS driver_phone
        FROM orders o
        LEFT JOIN branches b ON b.branch_id=o.branch_id
        LEFT JOIN drivers d ON d.driver_id=o.driver_id
        WHERE o.order_id=?
    ");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) json_response(404, false, "الطلب غير موجود");

    // history
    $h = $pdo->prepare("SELECT * FROM status_history WHERE order_id=? ORDER BY created_at ASC");
    $h->execute([$id]);

    // logs
    $l = $pdo->prepare("SELECT * FROM order_logs WHERE order_id=? ORDER BY timestamp ASC");
    $l->execute([$id]);

    json_response(200, true, "تفاصيل الطلب", [
        "order"   => $order,
        "history" => $h->fetchAll(),
        "logs"    => $l->fetchAll()
    ]);
}

/* ============================================================
   12) تغيير حالة الطلب
============================================================ */
// POST /admin.php?action=order_update
if ($action === 'order_update' && $method === 'POST') {
    require_admin();

    $data = get_json_input();
    $id     = intval($data['order_id'] ?? 0);
    $status = $data['status'] ?? '';
    $comment= $data['comment'] ?? '';

    if (!in_array($status,['waiting','assigned','picked_up','completed','cancelled'])) {
        json_response(422, false, "حالة غير صالحة");
    }

    $pdo->prepare("UPDATE orders SET status=? WHERE order_id=?")
        ->execute([$status,$id]);

    // history
    $pdo->prepare("
        INSERT INTO status_history (order_id, status, changed_by)
        VALUES (?, ?, ?)
    ")->execute([$id, $status, "admin"]);

    // log
    $pdo->prepare("
        INSERT INTO order_logs (order_id,status,comment)
        VALUES (?,?,?)
    ")->execute([$id,$status,$comment]);

    json_response(200, true, "تم تحديث حالة الطلب");
}

/* ============================================================
   13) إحصائيات لوحة التحكم
============================================================ */
// GET /admin.php?action=stats
if ($action === 'stats' && $method === 'GET') {

    $stats = [];

    $stats['orders'] = $pdo->query("
        SELECT 
            COUNT(*) total,
            SUM(status='waiting') waiting,
            SUM(status='assigned') assigned,
            SUM(status='picked_up') picked_up,
            SUM(status='completed') completed,
            SUM(status='cancelled') cancelled
        FROM orders
    ")->fetch();

    $stats['drivers'] = $pdo->query("
        SELECT 
            COUNT(*) total,
            SUM(status='active') active,
            SUM(status='banned') banned
        FROM drivers
    ")->fetch();

    $stats['branches'] = $pdo->query("
        SELECT 
            COUNT(*) total,
            SUM(status='approved') approved,
            SUM(status='pending') pending,
            SUM(status='rejected') rejected
        FROM branches
    ")->fetch();

    json_response(200, true, "إحصائيات لوحة التحكم", $stats);
}

json_response(400, false, "action غير معروف");
