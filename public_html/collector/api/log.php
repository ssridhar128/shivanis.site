<?php
$allowed_origins = ['https://test.shivanis.site'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$json = file_get_contents('php://input');

if ($json === false || $json === '') {
    header("Content-Type: application/json");
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

$payload = json_decode($json, true);
$type = isset($payload['type']) ? (string) $payload['type'] : 'unknown';
$sessionId = isset($payload['sessionId']) ? (string) $payload['sessionId'] : '';

$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) {
    $config = require $configPath;
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'] ?? 3306, $config['dbname'], $config['charset'] ?? 'utf8mb4');
    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $stmt = $pdo->prepare('INSERT INTO collector_log (type, session_id, payload) VALUES (:type, :session_id, :payload)');
        $stmt->execute([
            'type'       => $type,
            'session_id' => $sessionId,
            'payload'    => $json,
        ]);
    } catch (PDOException $e) {
        error_log('Collector log insert failed: ' . $e->getMessage());
        header("Content-Type: application/json");
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Ingestion failed"]);
        exit;
    }
} else {
    file_put_contents(__DIR__ . '/data.txt', $json . PHP_EOL, FILE_APPEND);
}

header("Content-Type: application/json");
echo json_encode(["status" => "success"]);