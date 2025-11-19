<?php
// router for Render
// This file redirects all requests to their real API paths.

$path = $_SERVER['REQUEST_URI'];

if (str_starts_with($path, "/api")) {
    $file = __DIR__ . $path;

    if (file_exists($file) && !is_dir($file)) {
        require $file;
        exit;
    }
}

http_response_code(404);
echo "Not found: " . $path;
