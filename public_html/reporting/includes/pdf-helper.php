<?php
/**
 * Shared logic for building report HTML and generating PDF.
 * Used by export-pdf.php and by the "save as PDF" flow (table.php, saved-reports.php).
 */

function buildReportHtml(string $category, string $title, ?int $savedId, \PDO $pdo): string
{
    require_once __DIR__ . '/comments.php';
    $apiType = $category === 'behavioral' ? 'activity' : $category;
    $stmt = $pdo->prepare('SELECT id, received_at, session_id, payload FROM collector_log WHERE type = ? ORDER BY received_at DESC LIMIT 500');
    $stmt->execute([$apiType]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $comments = $savedId ? getReportComments($category, $savedId) : getReportComments($category);

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>';
    $html .= '<style>body{font-family:system-ui,sans-serif;margin:1rem;color:#333;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#eee;} .comment{margin:1rem 0;padding:0.5rem;background:#f5f5f5;} pre{font-size:11px;overflow-x:auto;}</style></head><body>';
    $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
    $html .= '<p>Exported on ' . date('Y-m-d H:i:s') . ' — Category: ' . htmlspecialchars($category) . '</p>';
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
    return $html;
}

/**
 * Build report HTML and optionally render to PDF.
 * Returns ['html' => string, 'pdf' => string|null] (pdf is raw bytes if Dompdf available and successful).
 */
function buildReportPdf(string $category, string $title, ?int $savedId, \PDO $pdo): array
{
    $html = buildReportHtml($category, $title, $savedId, $pdo);
    $pdfBytes = null;
    if (is_file(__DIR__ . '/../vendor/autoload.php')) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfBytes = $dompdf->output();
        } catch (Throwable $e) {
            // leave pdfBytes null
        }
    }
    return ['html' => $html, 'pdf' => $pdfBytes];
}
