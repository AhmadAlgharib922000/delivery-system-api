<?php
require_once "../db.php";

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$phone = $_POST['phone'] ?? '';
$lat = $_POST['latitude'] ?? null;
$lng = $_POST['longitude'] ?? null;

if ($name == "" || $email == "" || $password == "" || $phone == "") {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit;
}

// Check email
$stmt = $pdo->prepare("SELECT driver_id FROM drivers WHERE email=?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo json_encode(["success" => false, "message" => "Email already exists"]);
    exit;
}

// Insert
$stmt = $pdo->prepare("
    INSERT INTO drivers (full_name, email, password, phone, current_latitude, current_longitude, status)
    VALUES (?, ?, ?, ?, ?, ?, 'active')
");

$done = $stmt->execute([$name, $email, $password, $phone, $lat, $lng]);

echo json_encode([
    "success" => $done ? true : false,
    "message" => $done ? "Driver registered successfully" : "Registration failed"
]);
