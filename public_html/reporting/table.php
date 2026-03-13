<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/comments.php';
requireLogin();
if (canOnlyViewSavedReports()) {
    header('Location: 403.php');
    exit;
}

$typeToSection = ['static' => 'static', 'performance' => 'performance', 'activity' => 'behavioral'];
$allTypes = ['static', 'performance', 'activity'];

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
$exportCategory = $commentCategory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_report' && isset($_POST['report_title'], $_POST['type']) && (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN)) {
    $title = trim((string) $_POST['report_title']);
    $saveType = (string) $_POST['type'];
    
    $analystComments = trim((string) ($_POST['analyst_comments'] ?? ''));
    $filtersJson = (string) ($_POST['filters'] ?? '[]');
    $filters = json_decode($filtersJson, true) ?: [];

    if ($title !== '' && in_array($saveType, $allowedTypesForUser, true) && canAccessSection($typeToSection[$saveType])) {
        $cat = $saveType === 'activity' ? 'behavioral' : $saveType;
        $pdo = getDb();
        require_once __DIR__ . '/includes/pdf-helper.php';
        
        $result = buildReportPdf($cat, $title, null, $pdo, $analystComments, $filters);
        
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
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saveReportModal">Generate Report</button>
    </div>
    <div class="modal fade" id="saveReportModal" tabindex="-1" aria-labelledby="saveReportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-secondary border-dark">
                <div class="modal-header border-dark">
                    <h5 class="modal-title text-light" id="saveReportModalLabel">Generate & Save Report</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="table.php?type=<?= rawurlencode($currentType) ?>" id="saveReportForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_report">
                        <input type="hidden" name="type" id="saveReportType" value="<?= htmlspecialchars($currentType) ?>">
                        <input type="hidden" name="filters" id="saveReportFilters" value="[]">
                        
                        <label for="report_title" class="form-label text-light">Report Name</label>
                        <input type="text" name="report_title" id="report_title" class="form-control" required placeholder="e.g. Q1 Performance Summary" maxlength="255">
                        
                        <label for="analyst_comments" class="form-label text-light mt-3">Analyst Comments</label>
                        <textarea name="analyst_comments" id="analyst_comments" class="form-control" rows="4" placeholder="Add explanatory text to be printed on this report..."></textarea>
                        
                        <p class="small text-secondary mt-3 mb-0">This will generate a PDF with your applied filters and comments, and add it to the Saved Reports list.</p>
                    </div>
                    <div class="modal-footer border-dark">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Generate PDF Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="mb-4">
        <label for="resourceSelect" class="form-label">Report Type:</label>
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 card-title text-light mb-0">Data table</h2>
            </div>
            
            <div id="filterSection" class="mb-3 p-3 bg-dark rounded border border-secondary d-none">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <strong class="text-light">Filters:</strong>
                    <div id="filterCheckboxes" class="d-flex flex-wrap gap-3"></div>
                </div>
                <div class="text-warning small mt-2 d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-exclamation-triangle-fill me-1" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                    Please note that the chart will not reflect any of the filters that were applied.
                </div>
            </div>

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

    // Added the new slow_load filter here!
    const filterDefinitions = {
        'static': [
            { id: 'js_enabled', label: 'JS Enabled' },
            { id: 'cookies_enabled', label: 'Cookies Enabled' }
        ],
        'performance': [
            { id: 'fast_load', label: 'Load Time < 1000ms' },
            { id: 'slow_load', label: 'Load Time >= 1000ms' }
        ],
        'activity': [
            { id: 'idle_breaks', label: 'Includes Idle Breaks' }
        ]
    };

    let currentMasterData = [];

    function destroyCharts() {
        ['feature','line','stacked'].forEach(k => { if (chartInstances[k]) { chartInstances[k].destroy(); chartInstances[k] = null; } });
    }

    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    document.getElementById('resourceSelect').addEventListener('change', function() {
        window.location = 'table.php?type=' + encodeURIComponent(this.value);
    });

    function setupFilters(type) {
        const section = document.getElementById('filterSection');
        const box = document.getElementById('filterCheckboxes');
        box.innerHTML = '';
        
        if (!filterDefinitions[type] || filterDefinitions[type].length === 0) {
            section.classList.add('d-none');
            return;
        }
        
        section.classList.remove('d-none');
        filterDefinitions[type].forEach(f => {
            box.innerHTML += `
                <div class="form-check form-check-inline m-0">
                    <input class="form-check-input data-filter" type="checkbox" value="${f.id}" id="filter_${f.id}">
                    <label class="form-check-label text-light" style="cursor:pointer;" for="filter_${f.id}">${f.label}</label>
                </div>
            `;
        });

        document.querySelectorAll('.data-filter').forEach(cb => {
            cb.addEventListener('change', updateTableAndFilters);
        });
    }

    function updateTableAndFilters() {
        const activeFilters = Array.from(document.querySelectorAll('.data-filter:checked')).map(cb => cb.value);
        document.getElementById('saveReportFilters').value = JSON.stringify(activeFilters);

        const filtered = currentMasterData.filter(r => {
            let pl = typeof r.payload === 'object' ? r.payload : JSON.parse(r.payload || '{}');
            
            if (activeFilters.includes('js_enabled') && !pl.jsEnabled) return false;
            if (activeFilters.includes('cookies_enabled') && !pl.cookiesEnabled) return false;
            
            // Logic for the fast_load filter
            if (activeFilters.includes('fast_load')) {
                let t = pl.totalLoadTime || (pl.loadEventEnd ? pl.loadEventEnd - pl.startTime : null) || (pl.timingObject ? pl.timingObject.loadEventEnd - pl.timingObject.startTime : null);
                if (!t || t >= 1000) return false;
            }
            
            // Logic for the new slow_load filter
            if (activeFilters.includes('slow_load')) {
                let t = pl.totalLoadTime || (pl.loadEventEnd ? pl.loadEventEnd - pl.startTime : null) || (pl.timingObject ? pl.timingObject.loadEventEnd - pl.timingObject.startTime : null);
                if (!t || t < 1000) return false;
            }

            if (activeFilters.includes('idle_breaks') && pl.event !== 'idle_break') return false;
            
            return true;
        });

        const status = document.getElementById('status');
        const wrap = document.getElementById('tableWrap');
        const content = document.getElementById('reportContent');
        content.innerHTML = '';

        if (filtered.length === 0) {
            status.textContent = 'No records match the selected filters.';
            status.classList.remove('d-none');
            wrap.classList.add('d-none');
        } else {
            status.classList.add('d-none');
            wrap.classList.remove('d-none');
            filtered.forEach(r => {
                const tr = document.createElement('tr');
                const plStr = typeof r.payload === 'object' ? JSON.stringify(r.payload, null, 2) : (r.payload || '');
                tr.innerHTML = '<td>' + escapeHtml(String(r.id)) + '</td><td>' + escapeHtml(String(r.received_at || '')) + '</td><td>' + escapeHtml(String(r.session_id || '')) + '</td><td><pre class="mb-0 small">' + escapeHtml(plStr) + '</pre></td>';
                content.appendChild(tr);
            });
        }
    }

    async function loadData(resourceType) {
        const apiType = resourceType === 'activity' ? 'activity' : resourceType;
        document.getElementById('status').textContent = 'Loading...';
        document.querySelectorAll('[id^="chart"][id$="Wrap"]').forEach(el => el.classList.add('d-none'));
        destroyCharts();

        try {
            const res = await fetch('api/' + apiType);
            const data = await res.json();
            const arr = Array.isArray(data) ? data : [];

            if (arr.length === 0) {
                document.getElementById('status').textContent = 'No ' + resourceType + ' records.';
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

            currentMasterData = arr;
            setupFilters(resourceType);
            updateTableAndFilters();

        } catch (err) {
            document.getElementById('status').textContent = 'Error: ' + err.message;
        }
    }

    loadData('<?= addslashes($currentType) ?>');
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>