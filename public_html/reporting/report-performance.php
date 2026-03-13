<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/comments.php';
requireSection('performance');
$pageTitle = 'Performance Report';
$category = 'performance';
require __DIR__ . '/includes/header.php';
$comments = getReportComments($category);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN)) {
    addReportComment($category, (string) $_POST['comment_text'], null);
    header('Location: report-performance.php');
    exit;
}
?>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Performance Report</h1>
        <a href="export-pdf.php?category=performance" class="btn btn-outline-light" target="_blank">Export PDF</a>
    </div>
    <p class="text-secondary">Load times, resource timing, and performance events from the collector. Use Section Observations to decode what the data means.</p>

    <section class="card bg-secondary border-dark mb-4">
        <div class="card-body">
            <h2 class="h5 card-title text-light">Average Load Time Over Time</h2>
            <p class="small text-secondary mb-2">Mean load time (ms) by date — helps spot regressions or traffic spikes.</p>
            <div style="height: 280px;"><canvas id="chartLine"></canvas></div>
        </div>
    </section>

    <section class="card bg-secondary border-dark mb-4">
        <div class="card-body">
            <h2 class="h5 card-title text-light">Performance data table</h2>
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
    fetch('api/performance').then(r => r.json()).then(data => {
        const arr = Array.isArray(data) ? data : [];
        const lt = performanceLoadTimeOverTime(arr);
        if (lt.labels.length) {
            new Chart(document.getElementById('chartLine').getContext('2d'), {
                type: 'line',
                data: { labels: lt.labels, datasets: [{ label: 'Total load time (ms)', data: lt.values, borderColor: CHART_COLORS.primary, backgroundColor: 'rgba(99, 102, 241, 0.15)', fill: true, tension: 0.2 }] },
                options: { ...chartOpt, scales: { ...chartOpt.scales, y: { ...chartOpt.scales.y, title: { display: true, text: 'Milliseconds (ms)', color: CHART_COLORS.text } } } }
            });
        }
        const status = document.getElementById('tableStatus');
        const wrap = document.getElementById('tableWrap');
        const body = document.getElementById('tableBody');
        if (arr.length === 0) { status.textContent = 'No performance records.'; return; }
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
