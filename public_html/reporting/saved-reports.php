<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$pageTitle = 'Saved Reports';
require __DIR__ . '/includes/header.php';

$pdo = getDb();
$reports = $pdo->query('SELECT r.id, r.title, r.slug, r.category, r.created_at, u.username AS created_by_name FROM reporting_saved_reports r JOIN reporting_users u ON r.created_by = u.id ORDER BY r.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

$createOk = !canOnlyViewSavedReports() && (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN);
if ($createOk && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['category'])) {
    $title = trim((string) $_POST['title']);
    $category = (string) $_POST['category'];
    if ($title !== '' && in_array($category, ['performance', 'behavioral', 'static'], true) && canAccessSection($category)) {
        $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($title)) ?: 'report-' . time();
        $stmt = $pdo->prepare('INSERT INTO reporting_saved_reports (title, slug, category, created_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$title, $slug . '-' . uniqid(), $category, getCurrentUserId()]);
        header('Location: view-report.php?id=' . (int) $pdo->lastInsertId());
        exit;
    }
}
?>
<main class="container py-4">
    <h1 class="h2 mb-4">Saved Reports</h1>
    <p class="text-secondary">Saved report views. Viewers can only open these; analysts can create and view.</p>

    <?php if ($createOk): ?>
    <div class="card bg-secondary border-dark mb-4">
        <div class="card-body">
            <h2 class="h5 card-title">Create saved report</h2>
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
                <div class="col-md-4"><button type="submit" class="btn btn-primary">Save report</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="list-group">
        <?php foreach ($reports as $r): ?>
        <a href="view-report.php?id=<?= (int) $r['id'] ?>" class="list-group-item list-group-item-action bg-secondary text-light border-dark d-flex justify-content-between align-items-center">
            <span><strong><?= htmlspecialchars($r['title']) ?></strong> — <?= htmlspecialchars($r['category']) ?></span>
            <small class="text-secondary"><?= htmlspecialchars($r['created_at']) ?> by <?= htmlspecialchars($r['created_by_name']) ?></small>
        </a>
        <?php endforeach; ?>
        <?php if (empty($reports)): ?>
        <p class="text-secondary">No saved reports yet.</p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
