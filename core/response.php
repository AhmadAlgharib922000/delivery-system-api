<?php
// api/core/response.php

function json_response(int $statusCode, bool $success, string $message, $data = null): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    // للسماح للـ Flutter أو أي فرونت إند
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);

    exit;
}
