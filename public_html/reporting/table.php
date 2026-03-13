<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/comments.php';
requireLogin();
if (canOnlyViewSavedReports()) {
    header('Location: 403.php');
    exit;
}

$allowedTypes = ['static', 'performance', 'activity'];
$currentType = isset($_GET['type']) && in_array($_GET['type'], $allowedTypes, true) ? $_GET['type'] : 'static';
$commentCategory = $currentType === 'activity' ? 'behavioral' : $currentType;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'], $_POST['type']) && (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN)) {
    $postType = $_POST['type'];
    if (in_array($postType, $allowedTypes, true)) {
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
    <h1 class="h2 mb-4">Data Table</h1>
    <div class="mb-4">
        <label for="resourceSelect" class="form-label text-secondary">Data type:</label>
        <select id="resourceSelect" class="form-select" style="max-width: 220px;">
            <option value="static" <?= $currentType === 'static' ? 'selected' : '' ?>>Static</option>
            <option value="performance" <?= $currentType === 'performance' ? 'selected' : '' ?>>Performance</option>
            <option value="activity" <?= $currentType === 'activity' ? 'selected' : '' ?>>Activity</option>
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
            <p class="small text-secondary mb-3">Analyst comments for this data type. Each comment is stored with the user and timestamp.</p>
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
                <label for="comment_text" class="form-label">Enter observations (decode the meaning of the data)...</label>
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
