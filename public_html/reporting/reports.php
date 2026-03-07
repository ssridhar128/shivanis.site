<?php
/**
 * Dashboard: protected; links to Data Table and Charts.
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
    <title>Reports – Analytics</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; margin: 0; background: #1a1d29; color: #e4e6eb; min-height: 100vh; }
        .header { background: #252836; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #3f4556; }
        .header h1 { margin: 0; font-size: 1.25rem; font-weight: 600; }
        .nav { display: flex; gap: 1rem; align-items: center; }
        .nav a { color: #a5b4fc; text-decoration: none; font-size: 0.95rem; }
        .nav a:hover { text-decoration: underline; }
        .nav .user { color: #9ca3af; font-size: 0.9rem; }
        main { padding: 2rem 1.5rem; max-width: 800px; margin: 0 auto; }
        h2 { margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 600; color: #d1d5db; }
        .cards { display: grid; gap: 1rem; }
        .card { background: #252836; border-radius: 10px; padding: 1.25rem; border: 1px solid #3f4556; text-decoration: none; color: inherit; display: block; transition: border-color 0.2s; }
        .card:hover { border-color: #6366f1; }
        .card h3 { margin: 0 0 0.35rem 0; font-size: 1rem; color: #e4e6eb; }
        .card p { margin: 0; font-size: 0.9rem; color: #9ca3af; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Analytics Reporting</h1>
        <nav class="nav">
            <a href="reports.php">Dashboard</a>
            <a href="table.php">Data Table</a>
            <a href="charts.php">Charts</a>
            <span class="user"><?= htmlspecialchars($user) ?></span>
            <a href="logout.php">Log out</a>
        </nav>
    </header>
    <main>
        <h2>Dashboard</h2>
        <p style="color:#9ca3af; margin-bottom:1.5rem;">Select a report below. Protected pages (Table, Charts) require authentication</p>
        <div class="cards">
            <a class="card" href="index.html">
                <h3>Dashboard (This is the table I made for the last hw to show my data)</h3>
                <p>Main analytics dashboard with data table from the database (index.html).</p>
            </a>
            <a class="card" href="table.php">
                <h3>Data Table (protected)</h3>
                <p>This is the data table I made for this HW by converting my last hw html to php so that it is password protected.</p>
            </a>
            <a class="card" href="charts.php">
                <h3>Charts (protected)</h3>
                <p>This is the charts page I made for this HW using Chart.js. Hover over the points on the line graph and the bars on the bar chart for more info.</p>
            </a>
        </div>
    </main>
</body>
</html>
