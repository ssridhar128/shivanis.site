<?php
// Start output buffering to trap ANY stray warnings or spaces so they don't break redirects!
ob_start(); 

require_once __DIR__ . '/auth.php';
requireLogin();

$pdo = getDb();
$createOk = !canOnlyViewSavedReports() && (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN);

// 1. Process all POST requests and redirects BEFORE drawing any HTML

// --- Handle Deletes ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['report_id'])) {
    $reportId = (int) $_POST['report_id'];
    if ($reportId > 0 && (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN)) {
        $stmt = $pdo->prepare('SELECT id, pdf_file FROM reporting_saved_reports WHERE id = ?');
        $stmt->execute([$reportId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Wrap in a try/catch so a mismatched table name doesn't crash the page
            try {
                $pdo->prepare('DELETE FROM reporting_comments WHERE report_id = ?')->execute([$reportId]);
            } catch (Throwable $e) {
                // Silently continue if the table name is different
            }
            
            // Delete the report itself
            $pdo->prepare('DELETE FROM reporting_saved_reports WHERE id = ?')->execute([$reportId]);
            
            if (!empty($row['pdf_file'])) {
                $path = __DIR__ . '/exports/' . basename($row['pdf_file']);
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
        
        // Clear the buffer trap and force a clean GET redirect
        ob_end_clean();
        header('Location: saved-reports.php?deleted=1', true, 303);
        exit;
    }
}

// --- Handle PDF Saves ---
if ($createOk && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['category']) && (!isset($_POST['action']) || $_POST['action'] !== 'delete')) {
    $title = trim((string) $_POST['title']);
    $category = (string) $_POST['category'];
    
    if ($title !== '' && in_array($category, ['performance', 'behavioral', 'static'], true) && canAccessSection($category)) {
        require_once __DIR__ . '/includes/pdf-helper.php';
        $result = buildReportPdf($category, $title, null, $pdo);
        $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($title)) ?: 'report-' . time();
        $slug .= '-' . uniqid();
        $pdfFile = null;
        
        if ($result['pdf'] !== null) {
            $dir = __DIR__ . '/exports';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $filename = $slug . '.pdf';
            $path = $dir . '/' . $filename;
            if (file_put_contents($path, $result['pdf']) !== false) {
                $pdfFile = $filename;
            }
        }
        
        $stmt = $pdo->prepare('INSERT INTO reporting_saved_reports (title, slug, category, pdf_file, created_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$title, $slug, $category, $pdfFile, getCurrentUserId()]);
        $newId = (int) $pdo->lastInsertId();
        
        $pdfPath = $pdfFile ? ('exports/' . $pdfFile) : '';
        
        // Clear the buffer trap and force a clean GET redirect
        ob_end_clean();
        header('Location: saved-reports.php?saved=1&name=' . rawurlencode($title) . '&id=' . $newId . ($pdfPath ? '&path=' . rawurlencode($pdfPath) : ''), true, 303);
        exit;
    }
}

// 2. NOW it is safe to load the HTML header and draw the page
$pageTitle = 'Saved Reports';
require __DIR__ . '/includes/header.php';

$reports = $pdo->query('SELECT r.id, r.title, r.slug, r.category, r.pdf_file, r.created_at, r.created_by, u.username AS created_by_name FROM reporting_saved_reports r JOIN reporting_users u ON r.created_by = u.id ORDER BY r.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<main class="container py-4">
    <h1 class="h2 mb-4">Saved Reports</h1>
    <?php if (!empty($_GET['saved']) && !empty($_GET['name'])): ?>
    <div class="alert alert-success">
        <strong>Success!</strong> Report created: <?= htmlspecialchars($_GET['name']) ?>.
        <?php if (!empty($_GET['path'])): ?>PDF saved at <code><?= htmlspecialchars($_GET['path']) ?></code>. <?php endif; ?>
        Link to the PDF is listed below — click the report name to open it.
    </div>
    <?php endif; ?>
    <?php if (!empty($_GET['deleted'])): ?><div class="alert alert-info">Report deleted.</div><?php endif; ?>
    <p class="text-secondary">Saved PDF reports. Each link opens the generated PDF (charts, data table, comments). Viewers can only open these; analysts can create and delete.</p>

    <?php if ($createOk): ?>
    <div class="card bg-secondary border-dark mb-4">
        <div class="card-body">
            <h2 class="h5 card-title">Save report as PDF</h2>
            <form method="post" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. Q1 Performance Summary">
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-select">
                        <?php foreach (['performance' => 'Performance', 'behavioral' => 'Behavioral', 'static' => 'Static / Overview'] as $val => $label): ?>
                            <?php if (canAccessSection($val)): ?><option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option><?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4"><button type="submit" class="btn btn-primary">Save PDF to list</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="list-group">
        <?php foreach ($reports as $r):
            $pdfLink = !empty($r['pdf_file']) ? ('view-pdf.php?id=' . (int) $r['id']) : ('view-report.php?id=' . (int) $r['id']);
            $canDelete = $createOk && ((int) $r['created_by'] === getCurrentUserId() || getCurrentRole() === ROLE_SUPER_ADMIN);
        ?>
        <div class="list-group-item bg-secondary text-light border-dark d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="<?= $pdfLink ?>" class="text-decoration-none text-light flex-grow-1">
                <strong><?= htmlspecialchars($r['title']) ?></strong> — <?= htmlspecialchars($r['category']) ?>
                <span class="text-primary ms-1 small">View PDF</span>
            </a>
            <small class="text-secondary"><?= htmlspecialchars($r['created_at']) ?> by <?= htmlspecialchars($r['created_by_name']) ?></small>
            <?php if ($canDelete): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete this report?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="report_id" value="<?= (int) $r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (empty($reports)): ?>
        <p class="text-secondary">No saved reports yet.</p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>