<?php
require_once "../db.php";

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT driver_id, full_name, email, phone, status FROM drivers WHERE email=? AND password=?");
$stmt->execute([$email, $password]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    exit;
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['status'] !== "active") {
    echo json_encode(["success" => false, "message" => "Driver account is banned or inactive"]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "user" => $user
]);
