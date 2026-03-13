<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<main class="container py-4">
    <h1 class="h2 mb-4">Dashboard</h1>
    <p class="text-secondary mb-4">Select a report below. Access to sections depends on your role.</p>
    <div class="row g-3">
        <?php if (!canOnlyViewSavedReports()): ?>
            <div class="col-md-6 col-lg-4">
                <a href="table.php" class="card text-decoration-none text-light h-100 border-secondary hover-shadow card-dark-link">
                    <div class="card-body bg-secondary border-dark">
                        <h2 class="h5 card-title text-light">Data Table</h2>
                        <p class="card-text text-secondary small">View static, performance, and activity data with charts and analyst comments.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="charts.php" class="card text-decoration-none text-light h-100 border-secondary hover-shadow card-dark-link">
                    <div class="card-body bg-secondary border-dark">
                        <h2 class="h5 card-title text-light">Charts</h2>
                        <p class="card-text text-secondary small">Events by type, load time over time, idle vs active, and feature support.</p>
                    </div>
                </a>
            </div>
            <?php if (canAccessSection('performance')): ?>
            <div class="col-md-6 col-lg-4">
                <a href="report-performance.php" class="card text-decoration-none text-light h-100 border-secondary hover-shadow card-dark-link">
                    <div class="card-body bg-secondary border-dark">
                        <h2 class="h5 card-title text-light">Performance Report</h2>
                        <p class="card-text text-secondary small">Metrics from the collector: load times, resource timing, and performance events.</p>
                    </div>
                </a>
            </div>
            <?php endif; ?>
            <?php if (canAccessSection('behavioral')): ?>
            <div class="col-md-6 col-lg-4">
                <a href="report-behavioral.php" class="card text-decoration-none text-light h-100 border-secondary hover-shadow card-dark-link">
                    <div class="card-body bg-secondary border-dark">
                        <h2 class="h5 card-title text-light">Behavioral Report</h2>
                        <p class="card-text text-secondary small">User activity and interaction events from the collector.</p>
                    </div>
                </a>
            </div>
            <?php endif; ?>
            <?php if (canAccessSection('static')): ?>
            <div class="col-md-6 col-lg-4">
                <a href="report-static.php" class="card text-decoration-none text-light h-100 border-secondary hover-shadow card-dark-link">
                    <div class="card-body bg-secondary border-dark">
                        <h2 class="h5 card-title text-light">Static / Overview</h2>
                        <p class="card-text text-secondary small">Static context and overview of collected data.</p>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="col-md-6 col-lg-4">
            <a href="saved-reports.php" class="card text-decoration-none text-light h-100 border-secondary hover-shadow card-dark-link">
                <div class="card-body bg-secondary border-dark">
                    <h2 class="h5 card-title text-light">Saved Reports</h2>
                    <p class="card-text text-secondary small">View and manage saved report views.</p>
                </div>
            </a>
        </div>
    </div>
</main>
<style>.hover-shadow:hover { box-shadow: 0 .5rem 1rem rgba(99, 102, 241, 0.2); } .card-dark-link { background: #252836; border-color: #3f4556 !important; } .card-dark-link:hover { border-color: #6366f1 !important; }</style>
<?php require __DIR__ . '/includes/footer.php'; ?>
