<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/comments.php';
requireLogin();

$category = $_GET['category'] ?? '';
$savedId = isset($_GET['saved']) ? (int) $_GET['saved'] : null;

$title = 'Report';
$categoryForAccess = $category;
if ($savedId > 0) {
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT title, category FROM reporting_saved_reports WHERE id = ?');
    $stmt->execute([$savedId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        header('Location: 403.php');
        exit;
    }
    $title = $row['title'];
    $categoryForAccess = $row['category'];
}

$allowed = ['performance', 'behavioral', 'static'];
if (!in_array($categoryForAccess, $allowed, true)) {
    header('Location: 403.php');
    exit;
}
if (canOnlyViewSavedReports() && $savedId <= 0) {
    header('Location: 403.php');
    exit;
}
if (!canOnlyViewSavedReports() && !canAccessSection($categoryForAccess)) {
    header('Location: 403.php');
    exit;
}

$apiType = $categoryForAccess === 'behavioral' ? 'activity' : $categoryForAccess;
try {
    $pdo = getDb();
} catch (Throwable $e) {
    header('Location: 403.php');
    exit;
}
$stmt = $pdo->prepare('SELECT id, received_at, session_id, payload FROM collector_log WHERE type = ? ORDER BY received_at DESC LIMIT 500');
$stmt->execute([$apiType]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$comments = $savedId ? getReportComments($categoryForAccess, $savedId) : getReportComments($categoryForAccess);

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>';
$html .= '<style>body{font-family:system-ui,sans-serif;margin:1rem;color:#333;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#eee;} .comment{margin:1rem 0;padding:0.5rem;background:#f5f5f5;} pre{font-size:11px;overflow-x:auto;}</style></head><body>';
$html .= '<h1>' . htmlspecialchars($title) . '</h1>';
$html .= '<p>Exported on ' . date('Y-m-d H:i:s') . ' — Category: ' . htmlspecialchars($categoryForAccess) . '</p>';
$html .= '<h2>Data table</h2><table><thead><tr><th>ID</th><th>Received</th><th>Session</th><th>Payload</th></tr></thead><tbody>';
foreach ($rows as $r) {
    $pl = is_string($r['payload']) ? $r['payload'] : json_encode(json_decode($r['payload'], true) ?: []);
    $html .= '<tr><td>' . (int) $r['id'] . '</td><td>' . htmlspecialchars($r['received_at'] ?? '') . '</td><td>' . htmlspecialchars($r['session_id'] ?? '') . '</td><td><pre>' . htmlspecialchars($pl) . '</pre></td></tr>';
}
$html .= '</tbody></table>';
$html .= '<h2>Analyst comments</h2>';
foreach ($comments as $c) {
    $html .= '<div class="comment"><small>' . htmlspecialchars($c['username'] . ' · ' . $c['created_at']) . '</small><p>' . nl2br(htmlspecialchars($c['comment_text'])) . '</p></div>';
}
if (empty($comments)) {
    $html .= '<p>No comments.</p>';
}
$html .= '</body></html>';

$useDompdf = is_file(__DIR__ . '/vendor/autoload.php');
if ($useDompdf) {
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dir = __DIR__ . '/exports';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $filename = 'report-' . ($savedId ?: $categoryForAccess) . '-' . date('Ymd-His') . '.pdf';
        $path = $dir . '/' . $filename;
        file_put_contents($path, $dompdf->output());
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $dompdf->output();
        exit;
    } catch (Throwable $e) {
        $useDompdf = false;
    }
}

if (!$useDompdf) {
    header('Content-Type: text/html; charset=utf-8');
    $html = str_replace('</style></head>', '</style><style media="print">body{max-width:100%;} .no-print{display:none;}</style></head>', $html);
    $html .= '<p class="no-print" style="margin-top:2rem;padding:1rem;background:#f0f0f0;"><strong>Print to PDF:</strong> Use your browser\'s Print (Ctrl+P / Cmd+P) and choose "Save as PDF" or "Print to PDF" to save this report as a PDF file.</p>';
    echo $html;
    exit;
}
