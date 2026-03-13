<?php
require_once __DIR__ . '/auth.php';
requireLogin();
if (canOnlyViewSavedReports()) {
    header('Location: 403.php');
    exit;
}
$pageTitle = 'Graphs';
require __DIR__ . '/includes/header.php';
?>
<main class="container py-4">
    <h1 class="h2 mb-4">Visual Analytics</h1>
    <p class="text-secondary mb-4">Charts built from collector data. Same chart types as on the Data Table page, with a consistent color palette.</p>

    <section class="mb-5">
        <h2 class="h5 text-light border-bottom border-secondary pb-2 mb-4">Static data</h2>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card bg-secondary border-dark h-100">
                    <div class="card-body">
                        <h3 class="h6 card-title text-light">Screen vs. Window Resolution</h3>
                        <p class="small text-secondary mb-2">Physical screen sizes vs. browser window sizes (px).</p>
                        <div style="height: 280px;"><canvas id="chartScatter"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card bg-secondary border-dark h-100">
                    <div class="card-body">
                        <h3 class="h6 card-title text-light">Browser Feature Support</h3>
                        <p class="small text-secondary mb-2">% of sessions with feature enabled.</p>
                        <div style="height: 280px;"><canvas id="chartFeature"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="h5 text-light border-bottom border-secondary pb-2 mb-4">Performance</h2>
        <div class="card bg-secondary border-dark">
            <div class="card-body">
                <h3 class="h6 card-title text-light">Average Load Time Over Time</h3>
                <p class="small text-secondary mb-2">Mean load time (ms) by date.</p>
                <div style="height: 280px;"><canvas id="chartLine"></canvas></div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="h5 text-light border-bottom border-secondary pb-2 mb-4">Activity</h2>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card bg-secondary border-dark h-100">
                    <div class="card-body">
                        <h3 class="h6 card-title text-light">Idle Time vs. Active Time</h3>
                        <p class="small text-secondary mb-2">Per session (seconds).</p>
                        <div style="height: 280px;"><canvas id="chartStacked"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card bg-secondary border-dark h-100">
                    <div class="card-body">
                        <h3 class="h6 card-title text-light">Session Engagement Hotspots</h3>
                        <p class="small text-secondary mb-2">Click / scroll / mousemove positions (px).</p>
                        <div style="height: 280px;"><canvas id="chartBubble"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="card bg-secondary border-dark">
        <div class="card-body">
            <h3 class="h6 card-title text-light">Events by type</h3>
            <div style="height: 260px;"><canvas id="chartCounts"></canvas></div>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="js/chart-data.js"></script>
<script>
(function() {
    const chartOpt = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: CHART_COLORS.text } } }, scales: { x: { ticks: { color: CHART_COLORS.text }, grid: { color: CHART_COLORS.grid } }, y: { ticks: { color: CHART_COLORS.text }, grid: { color: CHART_COLORS.grid } } } };

    async function loadAll() {
        const [staticRes, perfRes, activityRes] = await Promise.all([fetch('api/static'), fetch('api/performance'), fetch('api/activity')]);
        const staticData = await staticRes.json();
        const perfData = await perfRes.json();
        const activityData = await activityRes.json();
        const arrS = Array.isArray(staticData) ? staticData : [];
        const arrP = Array.isArray(perfData) ? perfData : [];
        const arrA = Array.isArray(activityData) ? activityData : [];

        const { screen, window } = staticScreenVsWindow(arrS);
        if (screen.length || window.length) {
            new Chart(document.getElementById('chartScatter').getContext('2d'), {
                type: 'scatter',
                data: [
                    { label: 'Physical screen sizes', data: screen, backgroundColor: CHART_COLORS.rose, borderColor: CHART_COLORS.rose, borderWidth: 1 },
                    { label: 'Browser window sizes', data: window, backgroundColor: CHART_COLORS.primary, borderColor: CHART_COLORS.primary, borderWidth: 1 }
                ],
                options: { ...chartOpt, scales: { ...chartOpt.scales, x: { ...chartOpt.scales.x, title: { display: true, text: 'Width (px)', color: CHART_COLORS.text } }, y: { ...chartOpt.scales.y, title: { display: true, text: 'Height (px)', color: CHART_COLORS.text } } } }
            });
        }
        const feat = staticFeatureSupport(arrS);
        new Chart(document.getElementById('chartFeature').getContext('2d'), {
            type: 'bar',
            data: { labels: feat.labels, datasets: [{ label: '% enabled', data: feat.values, backgroundColor: CHART_COLORS.teal, borderColor: CHART_COLORS.teal, borderWidth: 1 }] },
            options: { ...chartOpt, scales: { ...chartOpt.scales, y: { ...chartOpt.scales.y, max: 100, title: { display: true, text: 'Percentage (%)', color: CHART_COLORS.text } } } }
        });

        const lt = performanceLoadTimeOverTime(arrP);
        if (lt.labels.length) {
            new Chart(document.getElementById('chartLine').getContext('2d'), {
                type: 'line',
                data: { labels: lt.labels, datasets: [{ label: 'Total load time (ms)', data: lt.values, borderColor: CHART_COLORS.teal, backgroundColor: 'rgba(20, 184, 166, 0.1)', fill: true, tension: 0.2 }] },
                options: { ...chartOpt, scales: { ...chartOpt.scales, y: { ...chartOpt.scales.y, title: { display: true, text: 'Milliseconds (ms)', color: CHART_COLORS.text } } } }
            });
        } else {
            document.getElementById('chartLine').parentElement.innerHTML = '<p class="text-secondary small">No performance data by date.</p>';
        }

        const idleActive = activityIdleVsActive(arrA);
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
        const hotspots = activityEngagementHotspots(arrA);
        if (hotspots.length) {
            const bubbleData = hotspots.slice(0, 200).map(p => ({ x: p.x, y: p.y, r: 4 }));
            new Chart(document.getElementById('chartBubble').getContext('2d'), {
                type: 'bubble',
                data: [{ label: 'Activity hotspots', data: bubbleData, backgroundColor: 'rgba(244, 63, 94, 0.5)', borderColor: CHART_COLORS.rose, borderWidth: 1 }],
                options: { ...chartOpt, scales: { ...chartOpt.scales, x: { ...chartOpt.scales.x, title: { display: true, text: 'X (px)', color: CHART_COLORS.text } }, y: { ...chartOpt.scales.y, title: { display: true, text: 'Y (px)', color: CHART_COLORS.text } } } }
            });
        }

        const counts = [arrS.length, arrP.length, arrA.length];
        new Chart(document.getElementById('chartCounts').getContext('2d'), {
            type: 'bar',
            data: { labels: ['Static', 'Performance', 'Activity'], datasets: [{ label: 'Event count', data: counts, backgroundColor: [CHART_COLORS.indigo, CHART_COLORS.teal, CHART_COLORS.violet], borderWidth: 1 }] },
            options: chartOpt
        });
    }

    loadAll().catch(() => {});
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
