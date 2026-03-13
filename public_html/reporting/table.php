<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/comments.php';
requireLogin();
if (canOnlyViewSavedReports()) {
    header('Location: 403.php');
    exit;
}

// Map dropdown type to section for access check. Activity = behavioral section.
$typeToSection = ['static' => 'static', 'performance' => 'performance', 'activity' => 'behavioral'];
$allTypes = ['static', 'performance', 'activity'];

// Only show types the user is allowed to see (analyst section restriction).
$allowedTypesForUser = [];
foreach ($allTypes as $t) {
    if (canAccessSection($typeToSection[$t])) {
        $allowedTypesForUser[] = $t;
    }
}
if (empty($allowedTypesForUser)) {
    header('Location: 403.php');
    exit;
}

$requestedType = isset($_GET['type']) && in_array($_GET['type'], $allTypes, true) ? $_GET['type'] : $allowedTypesForUser[0];
if (!in_array($requestedType, $allowedTypesForUser, true)) {
    header('Location: 403.php');
    exit;
}
$currentType = $requestedType;
$commentCategory = $currentType === 'activity' ? 'behavioral' : $currentType;
$exportCategory = $commentCategory; // for Export PDF link

// Save as PDF from Data Table: prompt name → generate PDF, save file, add to Saved Reports list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_report' && isset($_POST['report_title'], $_POST['type']) && (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN)) {
    $title = trim((string) $_POST['report_title']);
    $saveType = (string) $_POST['type'];
    if ($title !== '' && in_array($saveType, $allowedTypesForUser, true) && canAccessSection($typeToSection[$saveType])) {
        $cat = $saveType === 'activity' ? 'behavioral' : $saveType;
        $pdo = getDb();
        require_once __DIR__ . '/includes/pdf-helper.php';
        $result = buildReportPdf($cat, $title, null, $pdo);
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
        $stmt->execute([$title, $slug, $cat, $pdfFile, getCurrentUserId()]);
        $newId = (int) $pdo->lastInsertId();
        $base = dirname($_SERVER['SCRIPT_NAME']);
        $base = ($base === '/' || $base === '\\' || $base === '.') ? '' : rtrim($base, '/');
        $pdfPath = $pdfFile ? ('exports/' . $pdfFile) : '';
        header('Location: ' . $base . '/saved-reports.php?saved=1&name=' . rawurlencode($title) . '&id=' . $newId . ($pdfPath ? '&path=' . rawurlencode($pdfPath) : ''));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'], $_POST['type']) && (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN)) {
    $postType = $_POST['type'];
    if (in_array($postType, $allowedTypesForUser, true)) {
        $cat = $postType === 'activity' ? 'behavioral' : $postType;
        addReportComment($cat, (string) $_POST['comment_text'], null);
        header('Location: table.php?type=' . $postType);
        exit;
    }
}

$comments = getReportComments($commentCategory);
$pageTitle = 'Data Table';
require __DIR__ . '/includes/header.php';
?>
<main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h2 mb-0">Data Table</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saveReportModal">Export PDF</button>
    </div>
    <!-- Modal: name this PDF and save to Saved Reports (same content as Export PDF) -->
    <div class="modal fade" id="saveReportModal" tabindex="-1" aria-labelledby="saveReportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-secondary border-dark">
                <div class="modal-header border-dark">
                    <h5 class="modal-title text-light" id="saveReportModalLabel">Export PDF</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="table.php?type=<?= rawurlencode($currentType) ?>" id="saveReportForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_report">
                        <input type="hidden" name="type" id="saveReportType" value="<?= htmlspecialchars($currentType) ?>">
                        <label for="report_title" class="form-label text-light">Name for this report (PDF)</label>
                        <input type="text" name="report_title" id="report_title" class="form-control" required placeholder="e.g. Q1 Performance Summary" maxlength="255">
                        <p class="small text-secondary mt-2 mb-0">Saves the same PDF you see when you click Preview PDF. It will appear on the Saved Reports page with a link to open it.</p>
                    </div>
                    <div class="modal-footer border-dark">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save PDF to Saved Reports</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="mb-4">
        <label for="resourceSelect" class="form-label">Report:</label>
        <select id="resourceSelect" class="form-select" style="max-width: 220px;">
            <?php foreach ($allowedTypesForUser as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= $currentType === $t ? 'selected' : '' ?>><?= $t === 'static' ? 'Static / Overview' : ($t === 'performance' ? 'Performance' : 'Behavioral (Activity)') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="chartsRow" class="row g-4 mb-4">
        <div id="chartFeatureWrap" class="col-lg-6 d-none">
            <div class="card bg-secondary border-dark h-100">
                <div class="card-body">
                    <h2 class="h6 card-title text-light">Browser Feature Support</h2>
                    <p class="small text-secondary mb-2">% of sessions with feature enabled.</p>
                    <div style="height: 280px;"><canvas id="chartFeature"></canvas></div>
                </div>
            </div>
        </div>
        <div id="chartLineWrap" class="col-12 d-none">
            <div class="card bg-secondary border-dark">
                <div class="card-body">
                    <h2 class="h6 card-title text-light">Average Load Time Over Time</h2>
                    <p class="small text-secondary mb-2">Mean load time (ms) by date.</p>
                    <div style="height: 280px;"><canvas id="chartLine"></canvas></div>
                </div>
            </div>
        </div>
        <div id="chartStackedWrap" class="col-12 d-none">
            <div class="card bg-secondary border-dark">
                <div class="card-body">
                    <h2 class="h6 card-title text-light">Idle Time vs. Active Time</h2>
                    <p class="small text-secondary mb-2">Per session (seconds).</p>
                    <div style="height: 280px;"><canvas id="chartStacked"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div id="tableSection" class="card bg-secondary border-dark mb-4">
        <div class="card-body">
            <h2 class="h6 card-title text-light">Data table</h2>
            <div id="status" class="text-secondary">Loading data...</div>
            <div class="table-responsive d-none" id="tableWrap">
                <table class="table table-dark table-striped mb-0">
                    <thead><tr><th>ID</th><th>Received at</th><th>Session ID</th><th>Payload</th></tr></thead>
                    <tbody id="reportContent"></tbody>
                </table>
            </div>
        </div>
    </div>

    <section class="card bg-secondary border-dark">
        <div class="card-body">
            <h2 class="h5 card-title text-light">Section Observations</h2>
            <?php foreach ($comments as $c): ?>
            <div class="border-bottom border-dark py-2 mb-2">
                <small class="text-secondary"><?= htmlspecialchars($c['username']) ?> · <?= htmlspecialchars($c['created_at']) ?></small>
                <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($c['comment_text'])) ?></p>
            </div>
            <?php endforeach; ?>
            <?php if (empty($comments)): ?><p class="text-secondary mb-0">No observations yet.</p><?php endif; ?>

            <?php if (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN): ?>
            <form method="post" action="table.php?type=<?= rawurlencode($currentType) ?>" class="mt-3">
                <input type="hidden" name="type" value="<?= htmlspecialchars($currentType) ?>">
                <label for="comment_text" class="form-label">Enter observations:</label>
                <textarea name="comment_text" id="comment_text" class="form-control" rows="3" required placeholder="e.g. Static data shows most sessions have JS and cookies enabled."></textarea>
                <button type="submit" class="btn btn-primary mt-2">Post Comment</button>
            </form>
            <?php endif; ?>
        </div>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="js/chart-data.js"></script>
<script>
document.getElementById('saveReportModal') && document.getElementById('saveReportModal').addEventListener('show.bs.modal', function() {
    var sel = document.getElementById('resourceSelect');
    var hid = document.getElementById('saveReportType');
    if (sel && hid) { hid.value = sel.value; }
});
</script>
<script>
(function() {
    const chartOpt = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: CHART_COLORS.text } } }, scales: { x: { ticks: { color: CHART_COLORS.text }, grid: { color: CHART_COLORS.grid } }, y: { ticks: { color: CHART_COLORS.text }, grid: { color: CHART_COLORS.grid } } } };
    let chartInstances = { feature: null, line: null, stacked: null };

    function destroyCharts() {
        ['feature','line','stacked'].forEach(k => { if (chartInstances[k]) { chartInstances[k].destroy(); chartInstances[k] = null; } });
    }

    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    document.getElementById('resourceSelect').addEventListener('change', function() {
        window.location = 'table.php?type=' + encodeURIComponent(this.value);
    });

    async function loadData(resourceType) {
        const status = document.getElementById('status');
        const tableWrap = document.getElementById('tableWrap');
        const content = document.getElementById('reportContent');
        const apiType = resourceType === 'activity' ? 'activity' : resourceType;
        status.textContent = 'Loading...';
        tableWrap.classList.add('d-none');
        content.innerHTML = '';
        document.querySelectorAll('[id^="chart"][id$="Wrap"]').forEach(el => el.classList.add('d-none'));
        destroyCharts();

        try {
            const res = await fetch('api/' + apiType);
            const data = await res.json();
            const arr = Array.isArray(data) ? data : [];

            if (arr.length === 0) {
                status.textContent = 'No ' + resourceType + ' records.';
                return;
            }

            if (resourceType === 'static') {
                const feat = staticFeatureSupport(arr);
                document.getElementById('chartFeatureWrap').classList.remove('d-none');
                const ctxF = document.getElementById('chartFeature').getContext('2d');
                chartInstances.feature = new Chart(ctxF, {
                    type: 'bar',
                    data: { labels: feat.labels, datasets: [{ label: '% enabled', data: feat.values, backgroundColor: CHART_COLORS.indigo, borderColor: CHART_COLORS.violet, borderWidth: 1 }] },
                    options: { ...chartOpt, scales: { ...chartOpt.scales, y: { ...chartOpt.scales.y, max: 100, title: { display: true, text: 'Percentage (%)', color: CHART_COLORS.text } } } }
                });
            } else if (resourceType === 'performance') {
                const lt = performanceLoadTimeOverTime(arr);
                if (lt.labels.length) {
                    document.getElementById('chartLineWrap').classList.remove('d-none');
                    const ctx = document.getElementById('chartLine').getContext('2d');
                    chartInstances.line = new Chart(ctx, {
                        type: 'line',
                        data: { labels: lt.labels, datasets: [{ label: 'Total load time (ms)', data: lt.values, borderColor: CHART_COLORS.primary, backgroundColor: 'rgba(99, 102, 241, 0.15)', fill: true, tension: 0.2 }] },
                        options: { ...chartOpt, scales: { ...chartOpt.scales, y: { ...chartOpt.scales.y, title: { display: true, text: 'Milliseconds (ms)', color: CHART_COLORS.text } } } }
                    });
                }
            } else if (resourceType === 'activity') {
                const idleActive = activityIdleVsActive(arr);
                if (idleActive.labels.length) {
                    document.getElementById('chartStackedWrap').classList.remove('d-none');
                    const ctx = document.getElementById('chartStacked').getContext('2d');
                    chartInstances.stacked = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: idleActive.labels,
                            datasets: [
                                { label: 'Active time (s)', data: idleActive.activeData, backgroundColor: CHART_COLORS.primary, borderColor: CHART_COLORS.primary, borderWidth: 1 },
                                { label: 'Idle time (s)', data: idleActive.idleData, backgroundColor: CHART_COLORS.violet, borderColor: CHART_COLORS.violet, borderWidth: 1 }
                            ]
                        },
                        options: { ...chartOpt, scales: { x: { ...chartOpt.scales.x, stacked: true, title: { display: true, text: 'Session ID', color: CHART_COLORS.text } }, y: { ...chartOpt.scales.y, stacked: true, title: { display: true, text: 'Time (seconds)', color: CHART_COLORS.text } } } }
                    });
                }
            }

            status.classList.add('d-none');
            tableWrap.classList.remove('d-none');
            arr.forEach(r => {
                const tr = document.createElement('tr');
                const pl = typeof r.payload === 'object' ? JSON.stringify(r.payload, null, 2) : (r.payload || '');
                tr.innerHTML = '<td>' + escapeHtml(String(r.id)) + '</td><td>' + escapeHtml(String(r.received_at || '')) + '</td><td>' + escapeHtml(String(r.session_id || '')) + '</td><td><pre class="mb-0 small">' + escapeHtml(pl) + '</pre></td>';
                content.appendChild(tr);
            });
        } catch (err) {
            status.textContent = 'Error: ' + err.message;
        }
    }

    loadData('<?= addslashes($currentType) ?>');
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
