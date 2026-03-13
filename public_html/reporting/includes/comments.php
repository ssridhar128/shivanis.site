<?php
function getReportComments(string $category, ?int $savedReportId = null): array
{
    $pdo = getDb();
    if ($savedReportId !== null) {
        $stmt = $pdo->prepare('SELECT c.id, c.comment_text, c.created_at, u.username FROM reporting_comments c JOIN reporting_users u ON c.created_by = u.id WHERE c.saved_report_id = ? ORDER BY c.created_at DESC');
        $stmt->execute([$savedReportId]);
    } else {
        $stmt = $pdo->prepare('SELECT c.id, c.comment_text, c.created_at, u.username FROM reporting_comments c JOIN reporting_users u ON c.created_by = u.id WHERE c.category = ? AND c.saved_report_id IS NULL ORDER BY c.created_at DESC');
        $stmt->execute([$category]);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addReportComment(string $category, string $commentText, ?int $savedReportId = null): bool
{
    $userId = getCurrentUserId();
    if ($userId === null) {
        return false;
    }
    $pdo = getDb();
    $stmt = $pdo->prepare('INSERT INTO reporting_comments (category, saved_report_id, comment_text, created_by) VALUES (?, ?, ?, ?)');
    $stmt->execute([$category, $savedReportId, trim($commentText), $userId]);
    return true;
}
