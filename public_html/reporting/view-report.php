<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/comments.php';
requireLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: saved-reports.php');
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, title, category, created_by FROM reporting_saved_reports WHERE id = ?');
$stmt->execute([$id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$report) {
    header('Location: 404.php');
    exit;
}

// Viewers can only view saved reports. Analysts/super_admin can view if they have section access.
if (canOnlyViewSavedReports()) {
    // Viewer: allow
} elseif (!canAccessSection($report['category'])) {
    header('Location: 403.php');
    exit;
}

$comments = getReportComments($report['category'], (int) $report['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN) && canAccessSection($report['category'])) {
    addReportComment($report['category'], (string) $_POST['comment_text'], (int) $report['id']);
    header('Location: view-report.php?id=' . $id);
    exit;
}

$pageTitle = $report['title'];
$category = $report['category'];
$apiType = $category === 'behavioral' ? 'activity' : $category; // API uses "activity", we use "behavioral"
require __DIR__ . '/includes/header.php';
?>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0"><?= htmlspecialchars($report['title']) ?></h1>
        <a href="export-pdf.php?category=<?= urlencode($category) ?>&saved=<?= (int) $id ?>" class="btn btn-outline-light" target="_blank">Export PDF</a>
    </div>
    <p class="text-secondary">Saved report — <?= htmlspecialchars($category) ?></p>

    <section class="card bg-secondary border-dark mb-4">
        <div class="card-body">
            <h2 class="h5 card-title">Data overview</h2>
            <div class="chart-container" style="height: 280px;"><canvas id="chartView"></canvas></div>
        </div>
    </section>

    <section class="card bg-secondary border-dark mb-4">
        <div class="card-body">
            <h2 class="h5 card-title">Data table</h2>
            <div id="tableStatus" class="text-secondary">Loading...</div>
            <div class="table-responsive d-none" id="tableWrap">
                <table class="table table-dark table-striped">
                    <thead><tr><th>ID</th><th>Received</th><th>Session</th><th>Payload</th></tr></thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/includes/section-observations.php'; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    const api = 'api/<?= htmlspecialchars($apiType, ENT_QUOTES, 'UTF-8') ?>';
    fetch(api).then(r => r.json()).then(data => {
        const arr = Array.isArray(data) ? data : [];
        new Chart(document.getElementById('chartView'), {
            type: 'bar',
            data: { labels: ['<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>'], datasets: [{ label: 'Events', data: [arr.length], backgroundColor: '#6366f1' }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#9ca3af' } } }, scales: { x: { ticks: { color: '#9ca3af' } }, y: { ticks: { color: '#9ca3af' } } }
        });
        const status = document.getElementById('tableStatus');
        const wrap = document.getElementById('tableWrap');
        const body = document.getElementById('tableBody');
        if (arr.length === 0) { status.textContent = 'No records.'; return; }
        status.classList.add('d-none');
        wrap.classList.remove('d-none');
        arr.forEach(r => {
            const tr = document.createElement('tr');
            const pl = typeof r.payload === 'object' ? JSON.stringify(r.payload) : (r.payload || '');
            tr.innerHTML = '<td>' + r.id + '</td><td>' + (r.received_at || '') + '</td><td>' + (r.session_id || '') + '</td><td><pre class="mb-0 small">' + pl.replace(/</g, '&lt;') + '</pre></td>';
            body.appendChild(tr);
        });
    }).catch(() => { document.getElementById('tableStatus').textContent = 'Error loading data.'; });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
