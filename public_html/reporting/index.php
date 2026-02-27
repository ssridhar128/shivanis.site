<?php
/**
 * Part 5: REST API (test site only).
 * Routes: /api/static, /api/static/{id}, /api/performance, /api/performance/{id}, /api/activity, /api/activity/{id}
 * GET (no id)=list all for type, GET (id)=one, POST=create, PUT (id)=update, DELETE (id)=delete.
 * Uses same MySQL database as collector (collector_log).
 */

header('Content-Type: application/json; charset=utf-8');

// CORS – test site only
$allowed_origins = ['https://test.shivanis.site', 'http://localhost'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];
// Strip query string and normalize path
$path = parse_url($path, PHP_URL_PATH);
$path = rtrim($path, '/');
if (strpos($path, '/api/') !== 0) {
    jsonResponse(404, ['error' => 'Not found']);
    exit;
}
$path = substr($path, 4); // after /api
$segments = array_filter(explode('/', $path));
$resource = $segments[0] ?? '';
$id = isset($segments[1]) && $segments[1] !== '' ? (int) $segments[1] : null;

$allowedTypes = ['static', 'performance', 'activity'];
if (!in_array($resource, $allowedTypes, true)) {
    jsonResponse(404, ['error' => 'Unknown resource. Use /api/static, /api/performance, or /api/activity']);
    exit;
}

// Method rules: POST must not have id; PUT and DELETE must have id
if ($method === 'POST' && $id !== null) {
    jsonResponse(400, ['error' => 'POST must not include an ID']);
    exit;
}
if (in_array($method, ['PUT', 'DELETE'], true) && $id === null) {
    jsonResponse(400, ['error' => $method . ' requires an ID in the path']);
    exit;
}
if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
    jsonResponse(405, ['error' => 'Method not allowed']);
    exit;
}

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    jsonResponse(500, ['error' => 'Server config missing']);
    exit;
}
$config = require $configPath;
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'] ?? 3306, $config['dbname'], $config['charset'] ?? 'utf8mb4');
try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    jsonResponse(500, ['error' => 'Database connection failed']);
    exit;
}

$table = 'collector_log';
$type = $resource;

switch ($method) {
    case 'GET':
        if ($id === null) {
            $stmt = $pdo->prepare('SELECT id, received_at, type, session_id, payload FROM ' . $table . ' WHERE type = ? ORDER BY received_at DESC');
            $stmt->execute([$type]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['payload'] = json_decode($r['payload'], true);
            }
            jsonResponse(200, $rows);
        } else {
            $stmt = $pdo->prepare('SELECT id, received_at, type, session_id, payload FROM ' . $table . ' WHERE id = ? AND type = ?');
            $stmt->execute([$id, $type]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                jsonResponse(404, ['error' => 'Not found']);
                exit;
            }
            $row['payload'] = json_decode($row['payload'], true);
            jsonResponse(200, $row);
        }
        break;

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $sessionId = isset($body['sessionId']) ? (string) $body['sessionId'] : '';
        $payload = isset($body['payload']) ? (is_string($body['payload']) ? $body['payload'] : json_encode($body['payload'])) : json_encode($body);
        if ($sessionId === '' && $payload === '{}') {
            jsonResponse(400, ['error' => 'Provide sessionId and payload']);
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO ' . $table . ' (type, session_id, payload) VALUES (?, ?, ?)');
        $stmt->execute([$type, $sessionId, $payload]);
        $newId = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT id, received_at, type, session_id, payload FROM ' . $table . ' WHERE id = ?');
        $stmt->execute([$newId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $row['payload'] = json_decode($row['payload'], true);
        jsonResponse(201, $row);
        break;

    case 'PUT':
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $sessionId = isset($body['session_id']) ? (string) $body['session_id'] : null;
        $payload = isset($body['payload']) ? (is_string($body['payload']) ? $body['payload'] : json_encode($body['payload'])) : null;
        $updates = [];
        $params = [];
        if ($sessionId !== null) {
            $updates[] = 'session_id = ?';
            $params[] = $sessionId;
        }
        if ($payload !== null) {
            $updates[] = 'payload = ?';
            $params[] = $payload;
        }
        if (empty($updates)) {
            jsonResponse(400, ['error' => 'Provide session_id and/or payload to update']);
            exit;
        }
        $params[] = $id;
        $params[] = $type;
        $stmt = $pdo->prepare('UPDATE ' . $table . ' SET ' . implode(', ', $updates) . ' WHERE id = ? AND type = ?');
        $stmt->execute($params);
        if ($stmt->rowCount() === 0) {
            jsonResponse(404, ['error' => 'Not found']);
            exit;
        }
        $stmt = $pdo->prepare('SELECT id, received_at, type, session_id, payload FROM ' . $table . ' WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $row['payload'] = json_decode($row['payload'], true);
        jsonResponse(200, $row);
        break;

    case 'DELETE':
        $stmt = $pdo->prepare('DELETE FROM ' . $table . ' WHERE id = ? AND type = ?');
        $stmt->execute([$id, $type]);
        if ($stmt->rowCount() === 0) {
            jsonResponse(404, ['error' => 'Not found']);
            exit;
        }
        jsonResponse(200, ['message' => 'Deleted']);
        break;
}

function jsonResponse(int $code, $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
}