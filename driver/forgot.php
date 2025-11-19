<?php
require_once "../db.php";

$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$new_pass = $_POST['new_password'] ?? '';

$stmt = $pdo->prepare("SELECT driver_id FROM drivers WHERE full_name=? AND phone=?");
$stmt->execute([$name, $phone]);

if ($stmt->rowCount() == 0) {
    echo json_encode(["success" => false, "message" => "No matching driver"]);
    exit;
}

$pdo->prepare("UPDATE drivers SET password=? WHERE full_name=? AND phone=?")
    ->execute([$new_pass, $name, $phone]);

echo json_encode(["success" => true, "message" => "Password updated"]);
