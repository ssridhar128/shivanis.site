<?php
require_once __DIR__ . '/auth.php';
requireLogin();
if (canOnlyViewSavedReports()) {
    header('Location: 403.php');
    exit;
}
$pageTitle = 'Data Table';
require __DIR__ . '/includes/header.php';
?>
<main class="container py-4">
    <h1 class="h2 mb-4">Data Table</h1>
    <div class="mb-4">
        <label for="resourceSelect" class="form-label text-secondary">Data type</label>
        <select id="resourceSelect" class="form-select form-select-lg bg-secondary text-light border-dark" style="max-width: 220px;">
            <option value="static">Static</option>
            <option value="performance">Performance</option>
            <option value="activity">Activity</option>
        </select>
    </div>

    <div id="chartsRow" class="row g-4 mb-4">
        <div id="chartScatterWrap" class="col-lg-6 d-none">
            <div class="card bg-secondary border-dark h-100">
                <div class="card-body">
                    <h2 class="h6 card-title text-light">Screen vs. Window Resolution</h2>
                    <p class="small text-secondary mb-2">Physical screen sizes vs. browser window sizes (px).</p>
                    <div style="height: 280px;"><canvas id="chartScatter"></canvas></div>
                </div>
            </div>
        </div>
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
        <div id="chartStackedWrap" class="col-lg-6 d-none">
            <div class="card bg-secondary border-dark h-100">
                <div class="card-body">
                    <h2 class="h6 card-title text-light">Idle Time vs. Active Time</h2>
                    <p class="small text-secondary mb-2">Per session (seconds).</p>
                    <div style="height: 280px;"><canvas id="chartStacked"></canvas></div>
                </div>
            </div>
        </div>
        <div id="chartBubbleWrap" class="col-lg-6 d-none">
            <div class="card bg-secondary border-dark h-100">
                <div class="card-body">
                    <h2 class="h6 card-title text-light">Session Engagement Hotspots</h2>
                    <p class="small text-secondary mb-2">Click / scroll / mousemove positions (px).</p>
                    <div style="height: 280px;"><canvas id="chartBubble"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div id="tableSection" class="card bg-secondary border-dark">
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
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="js/chart-data.js"></script>
<script>
(function() {
    const chartOpt = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: CHART_COLORS.text } } }, scales: { x: { ticks: { color: CHART_COLORS.text }, grid: { color: CHART_COLORS.grid } }, y: { ticks: { color: CHART_COLORS.text }, grid: { color: CHART_COLORS.grid } } } };
    let chartInstances = { scatter: null, feature: null, line: null, stacked: null, bubble: null };

    function destroyCharts() {
        ['scatter','feature','line','stacked','bubble'].forEach(k => { if (chartInstances[k]) { chartInstances[k].destroy(); chartInstances[k] = null; } });
    }

    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

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
                const { screen, window } = staticScreenVsWindow(arr);
                if (screen.length || window.length) {
                    document.getElementById('chartScatterWrap').classList.remove('d-none');
                    const ctx = document.getElementById('chartScatter').getContext('2d');
                    chartInstances.scatter = new Chart(ctx, {
                        type: 'scatter',
                        data: [
                            { label: 'Physical screen sizes', data: screen, backgroundColor: CHART_COLORS.rose, borderColor: CHART_COLORS.rose, borderWidth: 1 },
                            { label: 'Browser window sizes', data: window, backgroundColor: CHART_COLORS.primary, borderColor: CHART_COLORS.primary, borderWidth: 1 }
                        ],
                        options: { ...chartOpt, scales: { ...chartOpt.scales, x: { ...chartOpt.scales.x, title: { display: true, text: 'Width (px)', color: CHART_COLORS.text } }, y: { ...chartOpt.scales.y, title: { display: true, text: 'Height (px)', color: CHART_COLORS.text } } } }
                    });
                }
                const feat = staticFeatureSupport(arr);
                document.getElementById('chartFeatureWrap').classList.remove('d-none');
                const ctxF = document.getElementById('chartFeature').getContext('2d');
                chartInstances.feature = new Chart(ctxF, {
                    type: 'bar',
                    data: { labels: feat.labels, datasets: [{ label: '% enabled', data: feat.values, backgroundColor: CHART_COLORS.teal, borderColor: CHART_COLORS.teal, borderWidth: 1 }] },
                    options: { ...chartOpt, scales: { ...chartOpt.scales, y: { ...chartOpt.scales.y, max: 100, title: { display: true, text: 'Percentage (%)', color: CHART_COLORS.text } } } }
                });
            } else if (resourceType === 'performance') {
                const lt = performanceLoadTimeOverTime(arr);
                if (lt.labels.length) {
                    document.getElementById('chartLineWrap').classList.remove('d-none');
                    const ctx = document.getElementById('chartLine').getContext('2d');
                    chartInstances.line = new Chart(ctx, {
                        type: 'line',
                        data: { labels: lt.labels, datasets: [{ label: 'Total load time (ms)', data: lt.values, borderColor: CHART_COLORS.teal, backgroundColor: 'rgba(20, 184, 166, 0.1)', fill: true, tension: 0.2 }] },
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
                                { label: 'Active time (s)', data: idleActive.activeData, backgroundColor: CHART_COLORS.teal, borderColor: CHART_COLORS.teal, borderWidth: 1 },
                                { label: 'Idle time (s)', data: idleActive.idleData, backgroundColor: CHART_COLORS.amber, borderColor: CHART_COLORS.amber, borderWidth: 1 }
                            ]
                        },
                        options: { ...chartOpt, scales: { x: { ...chartOpt.scales.x, stacked: true, title: { display: true, text: 'Session ID', color: CHART_COLORS.text } }, y: { ...chartOpt.scales.y, stacked: true, title: { display: true, text: 'Time (seconds)', color: CHART_COLORS.text } } } }
                    });
                }
                const hotspots = activityEngagementHotspots(arr);
                if (hotspots.length) {
                    document.getElementById('chartBubbleWrap').classList.remove('d-none');
                    const bubbleData = hotspots.slice(0, 200).map(p => ({ x: p.x, y: p.y, r: 4 }));
                    const ctxB = document.getElementById('chartBubble').getContext('2d');
                    chartInstances.bubble = new Chart(ctxB, {
                        type: 'bubble',
                        data: [{ label: 'Activity hotspots', data: bubbleData, backgroundColor: 'rgba(244, 63, 94, 0.5)', borderColor: CHART_COLORS.rose, borderWidth: 1 }],
                        options: { ...chartOpt, scales: { ...chartOpt.scales, x: { ...chartOpt.scales.x, title: { display: true, text: 'X (px)', color: CHART_COLORS.text } }, y: { ...chartOpt.scales.y, title: { display: true, text: 'Y (px)', color: CHART_COLORS.text } } } }
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

    document.getElementById('resourceSelect').addEventListener('change', function() { loadData(this.value); });
    loadData('static');
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
