<?php
header("Content-Type: application/json; charset=UTF-8");
echo json_encode([
    "success" => true,
    "message" => "Delivery System API is running successfully 🚀",
    "time" => date("Y-m-d H:i:s")
]);
