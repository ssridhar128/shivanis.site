<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/comments.php';
requireSection('behavioral');
$pageTitle = 'Behavioral Report';
$category = 'behavioral';
require __DIR__ . '/includes/header.php';
$comments = getReportComments($category);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN)) {
    addReportComment($category, (string) $_POST['comment_text'], null);
    header('Location: report-behavioral.php');
    exit;
}
?>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Behavioral Report</h1>
        <a href="export-pdf.php?category=behavioral" class="btn btn-outline-light" target="_blank">Export PDF</a>
    </div>
    <p class="text-secondary">User activity and interaction events. Use Section Observations to interpret engagement and idle patterns.</p>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <section class="card bg-secondary border-dark h-100">
                <div class="card-body">
                    <h2 class="h5 card-title text-light">Idle Time vs. Active Time</h2>
                    <p class="small text-secondary mb-2">Per session (seconds). Stacked view of engagement.</p>
                    <div style="height: 280px;"><canvas id="chartStacked"></canvas></div>
                </div>
            </section>
        </div>
        <div class="col-lg-6">
            <section class="card bg-secondary border-dark h-100">
                <div class="card-body">
                    <h2 class="h5 card-title text-light">Session Engagement Hotspots</h2>
                    <p class="small text-secondary mb-2">Click / scroll / mousemove positions (px).</p>
                    <div style="height: 280px;"><canvas id="chartBubble"></canvas></div>
                </div>
            </section>
        </div>
    </div>

    <section class="card bg-secondary border-dark mb-4">
        <div class="card-body">
            <h2 class="h5 card-title text-light">Activity data table</h2>
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
    fetch('api/activity').then(r => r.json()).then(data => {
        const arr = Array.isArray(data) ? data : [];
        const idleActive = activityIdleVsActive(arr);
        if (idleActive.labels.length) {
            new Chart(document.getElementById('chartStacked').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: idleActive.labels,
                    datasets: [
                        { label: 'Active time (s)', data: idleActive.activeData, backgroundColor: CHART_COLORS.teal, borderColor: CHART_COLORS.teal, borderWidth: 1 },
                        { label: 'Idle time (s)', data: idleActive.idleData, backgroundColor: CHART_COLORS.amber, borderColor: CHART_COLORS.amber, borderWidth: 1 }
                    ]
                },
                options: { ...chartOpt, scales: { x: { ...chartOpt.scales.x, stacked: true, title: { display: true, text: 'Session ID', color: CHART_COLORS.text } }, y: { ...chartOpt.scales.y, stacked: true, title: { display: true, text: 'Time (seconds)', color: CHART_COLORS.text } } } }
            });
        }
        const hotspots = activityEngagementHotspots(arr);
        if (hotspots.length) {
            const bubbleData = hotspots.slice(0, 200).map(p => ({ x: p.x, y: p.y, r: 4 }));
            new Chart(document.getElementById('chartBubble').getContext('2d'), {
                type: 'bubble',
                data: [{ label: 'Activity hotspots', data: bubbleData, backgroundColor: 'rgba(244, 63, 94, 0.5)', borderColor: CHART_COLORS.rose, borderWidth: 1 }],
                options: { ...chartOpt, scales: { ...chartOpt.scales, x: { ...chartOpt.scales.x, title: { display: true, text: 'X (px)', color: CHART_COLORS.text } }, y: { ...chartOpt.scales.y, title: { display: true, text: 'Y (px)', color: CHART_COLORS.text } } } }
            });
        }
        const status = document.getElementById('tableStatus');
        const wrap = document.getElementById('tableWrap');
        const body = document.getElementById('tableBody');
        if (arr.length === 0) { status.textContent = 'No activity records.'; return; }
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
