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
            <div class="col-12">
                <a href="table.php" class="card text-decoration-none text-light border-secondary hover-shadow card-dark-link">
                    <div class="card-body p-4">
                        <h2 class="h5 card-title text-light mb-2">Data Table</h2>
                        <p class="card-text text-secondary small mb-0">Static, performance, and activity data in data table, charts, and has comments at the bottom. Export PDF exports a report.</p>
                    </div>
                </a>
            </div>
            <div class="col-12">
                <a href="charts.php" class="card text-decoration-none text-light border-secondary hover-shadow card-dark-link">
                    <div class="card-body p-4">
                        <h2 class="h5 card-title text-light mb-2">Charts</h2>
                        <p class="card-text text-secondary small mb-0">Events by type, load time over time, idle vs active, and feature support.</p>
                    </div>
                </a>
            </div>
        <?php endif; ?>
        <div class="col-12">
            <a href="saved-reports.php" class="card text-decoration-none text-light border-secondary hover-shadow card-dark-link">
                <div class="card-body p-4">
                    <h2 class="h5 card-title text-light mb-2">Saved Reports</h2>
                    <p class="card-text text-secondary small mb-0">View and manage saved report views.</p>
                </div>
            </a>
        </div>
    </div>
</main>
<style>
    .card-dark-link { 
        background: #252836; 
        border-color: #3f4556 !important; 
        transition: all 0.2s ease-in-out; 
    } 
    .card-dark-link:hover { 
        border-color: #6366f1 !important; 
        box-shadow: 0 .5rem 1rem rgba(99, 102, 241, 0.2);
        transform: translateY(-2px);
    }
</style>
<?php require __DIR__ . '/includes/footer.php'; ?>