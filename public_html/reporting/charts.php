<?php
/**
 * Charts: protected page with Chart.js; data from collector_log via API.
 */
require_once __DIR__ . '/auth.php';
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charts – Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; margin: 0; background: #1a1d29; color: #e4e6eb; min-height: 100vh; }
        .header { background: #252836; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #3f4556; }
        .header h1 { margin: 0; font-size: 1.25rem; font-weight: 600; }
        .nav { display: flex; gap: 1rem; align-items: center; }
        .nav a { color: #a5b4fc; text-decoration: none; font-size: 0.95rem; }
        .nav a:hover { text-decoration: underline; }
        .nav .user { color: #9ca3af; font-size: 0.9rem; }
        main { padding: 1.5rem; max-width: 1000px; margin: 0 auto; }
        .chart-wrap { background: #252836; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid #3f4556; }
        .chart-wrap h2 { margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600; color: #d1d5db; }
        .chart-container { position: relative; height: 280px; }
        #status { color: #9ca3af; padding: 1rem 0; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Charts</h1>
        <nav class="nav">
            <a href="reports.php">Dashboard</a>
            <a href="table.php">Data Table</a>
            <a href="charts.php">Charts</a>
            <span class="user"><?= htmlspecialchars($user) ?></span>
            <a href="logout.php">Log out</a>
        </nav>
    </header>
    <main>
        <div id="status">Loading chart data...</div>
        <div class="chart-wrap" id="wrapCounts" style="display:none;">
            <h2>Events by type (from collector_log)</h2>
            <div class="chart-container"><canvas id="chartCounts"></canvas></div>
        </div>
        <div class="chart-wrap" id="wrapTime" style="display:none;">
            <h2>Events over time (last 7 days)</h2>
            <div class="chart-container"><canvas id="chartTime"></canvas></div>
        </div>
    </main>
    <script>
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#9ca3af' } }
            },
            scales: {
                x: { ticks: { color: '#9ca3af' }, grid: { color: '#3f4556' } },
                y: { ticks: { color: '#9ca3af' }, grid: { color: '#3f4556' } }
            }
        };

        async function loadCharts() {
            const status = document.getElementById('status');
            try {
                const [staticRes, perfRes, activityRes] = await Promise.all([
                    fetch('api/static'),
                    fetch('api/performance'),
                    fetch('api/activity')
                ]);
                const staticData = await staticRes.json();
                const perfData = await perfRes.json();
                const activityData = await activityRes.json();

                const counts = {
                    static: Array.isArray(staticData) ? staticData.length : 0,
                    performance: Array.isArray(perfData) ? perfData.length : 0,
                    activity: Array.isArray(activityData) ? activityData.length : 0
                };

                // Chart 1: Bar – counts by type
                document.getElementById('wrapCounts').style.display = 'block';
                new Chart(document.getElementById('chartCounts'), {
                    type: 'bar',
                    data: {
                        labels: ['Static', 'Performance', 'Activity'],
                        datasets: [{
                            label: 'Events',
                            data: [counts.static, counts.performance, counts.activity],
                            backgroundColor: ['#6366f1', '#8b5cf6', '#a855f7'],
                            borderColor: ['#4f46e5', '#7c3aed', '#9333ea'],
                            borderWidth: 1
                        }]
                    },
                    options: chartOptions
                });

                // Chart 2: Line – events over time (merge all, group by date)
                const all = []
                    .concat((Array.isArray(staticData) ? staticData : []).map(r => ({ received_at: r.received_at })))
                    .concat((Array.isArray(perfData) ? perfData : []).map(r => ({ received_at: r.received_at })))
                    .concat((Array.isArray(activityData) ? activityData : []).map(r => ({ received_at: r.received_at })));
                const byDate = {};
                const now = new Date();
                for (let i = 0; i < 7; i++) {
                    const d = new Date(now);
                    d.setDate(d.getDate() - (6 - i));
                    const key = d.toISOString().slice(0, 10);
                    byDate[key] = 0;
                }
                all.forEach(function(r) {
                    if (r.received_at) {
                        const key = String(r.received_at).slice(0, 10);
                        if (!byDate[key]) byDate[key] = 0;
                        byDate[key]++;
                    }
                });
                const dates = Object.keys(byDate).sort();
                const values = dates.map(function(d) { return byDate[d]; });

                document.getElementById('wrapTime').style.display = 'block';
                new Chart(document.getElementById('chartTime'), {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Events per day',
                            data: values,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            fill: true,
                            tension: 0.2
                        }]
                    },
                    options: chartOptions
                });

                status.textContent = '';
            } catch (err) {
                status.textContent = 'Error loading chart data: ' + err.message;
            }
        }

        loadCharts();
    </script>
</body>
</html>
