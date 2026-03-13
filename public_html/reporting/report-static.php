<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/comments.php';
requireSection('static');
$pageTitle = 'Static / Overview';
$category = 'static';
require __DIR__ . '/includes/header.php';
$comments = getReportComments($category);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN)) {
    addReportComment($category, (string) $_POST['comment_text'], null);
    header('Location: report-static.php');
    exit;
}
?>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Static / Overview</h1>
        <a href="export-pdf.php?category=static" class="btn btn-outline-light" target="_blank">Export PDF</a>
    </div>
    <p class="text-secondary">Static context and overview: browser feature support. Use Section Observations to decode what the data means.</p>

    <section class="card bg-secondary border-dark mb-4">
        <div class="card-body">
            <h2 class="h5 card-title text-light">Browser Feature Support</h2>
            <p class="small text-secondary mb-2">% of sessions with feature enabled.</p>
            <div style="height: 280px;"><canvas id="chartFeature"></canvas></div>
        </div>
    </section>

    <section class="card bg-secondary border-dark mb-4">
        <div class="card-body">
            <h2 class="h5 card-title text-light">Static data table</h2>
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
<script src="js/chart-data.js"></script>
<script>
(function() {
    const chartOpt = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: CHART_COLORS.text } } }, scales: { x: { ticks: { color: CHART_COLORS.text }, grid: { color: CHART_COLORS.grid } }, y: { ticks: { color: CHART_COLORS.text }, grid: { color: CHART_COLORS.grid } } } };
    fetch('api/static').then(r => r.json()).then(data => {
        const arr = Array.isArray(data) ? data : [];
        const feat = staticFeatureSupport(arr);
        new Chart(document.getElementById('chartFeature').getContext('2d'), {
            type: 'bar',
            data: { labels: feat.labels, datasets: [{ label: '% enabled', data: feat.values, backgroundColor: CHART_COLORS.indigo, borderColor: CHART_COLORS.violet, borderWidth: 1 }] },
            options: { ...chartOpt, scales: { ...chartOpt.scales, y: { ...chartOpt.scales.y, max: 100, title: { display: true, text: 'Percentage (%)', color: CHART_COLORS.text } } } }
        });
        const status = document.getElementById('tableStatus');
        const wrap = document.getElementById('tableWrap');
        const body = document.getElementById('tableBody');
        if (arr.length === 0) { status.textContent = 'No static records.'; return; }
        status.classList.add('d-none');
        wrap.classList.remove('d-none');
        arr.forEach(r => {
            const pl = typeof r.payload === 'object' ? JSON.stringify(r.payload) : (r.payload || '');
            body.insertAdjacentHTML('beforeend', '<tr><td>' + r.id + '</td><td>' + (r.received_at || '') + '</td><td>' + (r.session_id || '') + '</td><td><pre class="mb-0 small">' + pl.replace(/</g, '&lt;') + '</pre></td></tr>');
        });
    }).catch(() => { document.getElementById('tableStatus').textContent = 'Error loading data.'; });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
