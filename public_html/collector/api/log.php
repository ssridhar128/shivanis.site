<?php
// 1. Set the CORS headers first
header("Access-Control-Allow-Origin: https://test.shivanis.site");
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
}
?>