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
    <style>
        /* Global: white/light text on all pages for readability */
        body { background: #1a1d29 !important; color: #ffffff !important; }
        main, .container { color: #ffffff !important; }
        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 { color: #ffffff !important; }
        .card-title { color: #ffffff !important; }
        .card-body, .card p, .card .mb-0 { color: #e4e6eb !important; }
        .text-secondary { color: #c7d2fe !important; }
        .form-label, label { color: #e4e6eb !important; }
        .form-control, .form-select, textarea.form-control {
            background: #1a1d29 !important; color: #ffffff !important; border-color: #3f4556 !important;
        }
        .form-control::placeholder { color: #9ca3af; }
        .form-control:focus, .form-select:focus { border-color: #6366f1; color: #ffffff !important; }
        .form-select option { background: #252836; color: #ffffff; }
        small { color: #c7d2fe !important; }
        .table, .table-dark { color: #ffffff !important; --bs-table-bg: #252836; --bs-table-border-color: #3f4556; }
        .table th { color: #e4e6eb !important; }
        .table td { color: #ffffff !important; }
        .list-group-item { background: #252836 !important; color: #ffffff !important; border-color: #3f4556 !important; }
        .list-group-item-action:hover { background: #2d3142 !important; color: #ffffff !important; }
        .navbar { background: #252836 !important; border-bottom: 1px solid #3f4556 !important; }
        .nav-link { color: #a5b4fc !important; }
        .nav-link:hover { color: #c7d2fe !important; text-decoration: underline; }
        .navbar-text { color: #e4e6eb !important; }
        .btn-primary { background: #6366f1 !important; border-color: #6366f1 !important; color: #ffffff !important; }
        .btn-primary:hover { background: #4f46e5 !important; border-color: #4f46e5 !important; color: #ffffff !important; }
        .btn-outline-light { color: #e4e6eb !important; border-color: #6b7280; }
        .btn-outline-light:hover { background: #6366f1 !important; border-color: #6366f1 !important; color: #ffffff !important; }
        .btn-outline-danger:hover { color: #ffffff !important; }
        .card.bg-secondary { background: #252836 !important; border: 1px solid #3f4556 !important; }
        pre { color: #e4e6eb !important; }
    </style>
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
                    <li class="nav-item"><a class="nav-link" href="charts.php">Charts</a></li>
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
