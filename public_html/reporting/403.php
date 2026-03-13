<?php
require_once __DIR__ . '/auth.php';
$loggedIn = getCurrentUser() !== null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden – Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="text-center">
        <h1 class="display-1 fw-bold">403</h1>
        <p class="lead">You don't have permission to view this page.</p>
        <p class="text-secondary">If you believe this is an error, contact your administrator.</p>
        <a href="<?= $loggedIn ? 'reports.php' : 'login.php' ?>" class="btn btn-primary mt-3"><?= $loggedIn ? 'Back to Dashboard' : 'Go to Login' ?></a>
    </div>
</body>
</html>
