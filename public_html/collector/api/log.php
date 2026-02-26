<?php
// 1. CORS: allow our origins and allow credentials (cookies) when requested
$allowed_origins = ['https://test.shivanis.site'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 2. Handle the Preflight (OPTIONS) check directly in PHP
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 3. Process the actual POST data
$json = file_get_contents('php://input');

if ($json) {
    // Save to a text file to verify ingestion for Part 4
    file_put_contents('data.txt', $json . PHP_EOL, FILE_APPEND);
    
    header("Content-Type: application/json");
    echo json_encode(["status" => "success"]);
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No data received"]);
}?>