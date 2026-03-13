<?php
/**
 * Serve a saved report PDF by id. Checks auth; viewers can open saved reports.
 */
require_once __DIR__ . '/auth.php';
requireLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: 403.php');
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, title, category, pdf_file FROM reporting_saved_reports WHERE id = ?');
$stmt->execute([$id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$report || empty($report['pdf_file'])) {
    header('Location: 404.php');
    exit;
}

$path = __DIR__ . '/exports/' . basename($report['pdf_file']);
if (!is_file($path)) {
    header('Location: 404.php');
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($report['pdf_file']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
