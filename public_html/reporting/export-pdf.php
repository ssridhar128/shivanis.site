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

$pdo = getDb();
require_once __DIR__ . '/includes/pdf-helper.php';
$result = buildReportPdf($categoryForAccess, $title, $savedId > 0 ? $savedId : null, $pdo);

if ($result['pdf'] !== null) {
    header('Content-Type: application/pdf');
    $filename = 'report-' . ($savedId ?: $categoryForAccess) . '-' . date('Ymd-His') . '.pdf';
    header('Content-Disposition: inline; filename="' . $filename . '"');
    echo $result['pdf'];
    exit;
}

$html = $result['html'];
header('Content-Type: text/html; charset=utf-8');
$html = str_replace('</style></head>', '</style><style media="print">body{max-width:100%;} .no-print{display:none;}</style></head>', $html);
$html .= '<p class="no-print" style="margin-top:2rem;padding:1rem;background:#f0f0f0;"><strong>Print to PDF:</strong> Use your browser\'s Print (Ctrl+P / Cmd+P) and choose "Save as PDF" or "Print to PDF" to save this report as a PDF file.</p>';
echo $html;
exit;
