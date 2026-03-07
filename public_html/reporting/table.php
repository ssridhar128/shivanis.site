<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Table – Analytics</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; margin: 0; background: #1a1d29; color: #e4e6eb; min-height: 100vh; }
        .header { background: #252836; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #3f4556; }
        .header h1 { margin: 0; font-size: 1.25rem; font-weight: 600; }
        .nav { display: flex; gap: 1rem; align-items: center; }
        .nav a { color: #a5b4fc; text-decoration: none; font-size: 0.95rem; }
        .nav a:hover { text-decoration: underline; }
        .nav .user { color: #9ca3af; font-size: 0.9rem; }
        main { padding: 1.5rem; overflow-x: auto; }
        .controls { background: #252836; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #3f4556; }
        .controls label { margin-right: 0.5rem; color: #9ca3af; }
        select { padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #3f4556; background: #1a1d29; color: #e4e6eb; font-size: 1rem; }
        table { width: 100%; border-collapse: collapse; background: #252836; border-radius: 8px; overflow: hidden; border: 1px solid #3f4556; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #3f4556; }
        th { background: #31344a; color: #a5b4fc; font-weight: 600; font-size: 0.9rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #2d3142; }
        pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; font-size: 12px; color: #9ca3af; max-width: 400px; }
        #status { color: #9ca3af; padding: 1rem; }
        #reportTable { display: none; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Data Table</h1>
        <nav class="nav">
            <a href="reports.php">Dashboard</a>
            <a href="table.php">Data Table</a>
            <a href="charts.php">Charts</a>
            <span class="user"><?= htmlspecialchars($user) ?></span>
            <a href="logout.php">Log out</a>
        </nav>
    </header>
    <main>
        <div class="controls">
            <label for="resourceSelect">Data type:</label>
            <select id="resourceSelect" onchange="loadData(this.value)">
                <option value="static">Static</option>
                <option value="performance">Performance</option>
                <option value="activity">Activity</option>
            </select>
        </div>
        <div id="status">Loading data...</div>
        <table id="reportTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Received at</th>
                    <th>Session ID</th>
                    <th>Payload</th>
                </tr>
            </thead>
            <tbody id="reportContent"></tbody>
        </table>
    </main>
    <script>
        async function loadData(resourceType) {
            const table = document.getElementById('reportTable');
            const content = document.getElementById('reportContent');
            const status = document.getElementById('status');
            content.innerHTML = '';
            status.style.display = 'block';
            status.textContent = 'Fetching ' + resourceType + ' data...';
            table.style.display = 'none';

            try {
                const response = await fetch('api/' + resourceType);
                const data = await response.json();

                if (!Array.isArray(data) || data.length === 0) {
                    status.textContent = 'No ' + resourceType + ' records in the database.';
                    return;
                }

                data.forEach(function(row) {
                    const tr = document.createElement('tr');
                    const payloadStr = typeof row.payload === 'object' ? JSON.stringify(row.payload, null, 2) : (row.payload || '');
                    tr.innerHTML = '<td>' + escapeHtml(String(row.id)) + '</td><td>' + escapeHtml(String(row.received_at)) + '</td><td>' + escapeHtml(String(row.session_id)) + '</td><td><pre>' + escapeHtml(payloadStr) + '</pre></td>';
                    content.appendChild(tr);
                });

                status.style.display = 'none';
                table.style.display = 'table';
            } catch (err) {
                status.textContent = 'Error loading data: ' + err.message;
            }
        }

        function escapeHtml(s) {
            const div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML;
        }

        loadData('static');
    </script>
</body>
</html>
