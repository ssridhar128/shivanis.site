<?php
$currentUser = getCurrentUser();
$role = getCurrentRole();
$canManageUsers = canManageUsers();
$viewerOnly = canOnlyViewSavedReports();
$pageTitle = $pageTitle ?? 'Reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> – Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<noscript><div class="alert alert-warning text-dark m-2">JavaScript is required for charts and dynamic data. Please enable it for the best experience.</div></noscript>
<header class="navbar navbar-expand-lg navbar-dark bg-secondary border-bottom border-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $viewerOnly ? 'saved-reports.php' : 'reports.php' ?>">Analytics Reporting</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <nav class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <?php if (!$viewerOnly): ?>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="table.php">Data Table</a></li>
                    <li class="nav-item"><a class="nav-link" href="charts.php">Graphs</a></li>
                    <?php if (canAccessSection('performance')): ?><li class="nav-item"><a class="nav-link" href="report-performance.php">Performance</a></li><?php endif; ?>
                    <?php if (canAccessSection('behavioral')): ?><li class="nav-item"><a class="nav-link" href="report-behavioral.php">Behavioral</a></li><?php endif; ?>
                    <?php if (canAccessSection('static')): ?><li class="nav-item"><a class="nav-link" href="report-static.php">Static / Overview</a></li><?php endif; ?>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="saved-reports.php">Saved Reports</a></li>
                <?php if ($canManageUsers): ?>
                    <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><span class="navbar-text text-light me-3"><?= htmlspecialchars($currentUser ?? '') ?> (<?= htmlspecialchars($role ?? '') ?>)</span></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Log out</a></li>
            </ul>
        </nav>
    </div>
</header>
