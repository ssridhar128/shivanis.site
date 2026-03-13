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
<body class="min-vh-100 d-flex align-items-center justify-content-center" style="background: #1a1d29; color: #e4e6eb;">
    <div class="text-center">
        <h1 class="display-1 fw-bold">403</h1>
        <p class="lead">You don't have permission to view this page.</p>
        <p class="text-secondary">If you believe this is an error, contact your administrator.</p>
        <a href="<?= $loggedIn ? 'reports.php' : 'login.php' ?>" class="btn mt-3" style="background: #6366f1; border-color: #6366f1; color: #fff;"><?= $loggedIn ? 'Back to Dashboard' : 'Go to Login' ?></a>
    </div>
</body>
</html>
