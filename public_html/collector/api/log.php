<?php
// 1. Headers First
$allowed_origins = ['https://test.shivanis.site'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$configPath = __DIR__ . '/config.php';
$pdo = null;

if (is_file($configPath)) {
    $config = require $configPath;
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', 
        $config['host'], $config['port'] ?? 3306, $config['dbname'], $config['charset'] ?? 'utf8mb4');
    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (PDOException $e) {
        error_log('DB Connection failed: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['jsEnabled'])) {
    if ($pdo) {
        $payload = [
            "type" => "static",
            "jsEnabled" => false,
            "userAgent" => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            "page" => $_GET['page'] ?? 'unknown'
        ];
        
        $stmt = $pdo->prepare('INSERT INTO collector_log (type, session_id, payload) VALUES (?, ?, ?)');
        $stmt->execute(['static', 'no-js-user', json_encode($payload)]);
    }
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$json = file_get_contents('php://input');

if ($json) {
    $payload = json_decode($json, true);
    $type = $payload['type'] ?? 'unknown';
    $sessionId = $payload['sessionId'] ?? '';

    if ($pdo) {
        $stmt = $pdo->prepare('INSERT INTO collector_log (type, session_id, payload) VALUES (:type, :session_id, :payload)');
        $stmt->execute([
            'type'       => $type,
            'session_id' => $sessionId,
            'payload'    => $json,
        ]);
    } else {
        file_put_contents(__DIR__ . '/data.txt', $json . PHP_EOL, FILE_APPEND);
    }
}

header("Content-Type: application/json");
echo json_encode(["status" => "success"]);