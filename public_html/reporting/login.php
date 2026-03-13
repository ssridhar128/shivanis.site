<?php
require_once __DIR__ . '/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $info = $user !== '' ? verifyCredentials($user, $pass) : null;
    if ($info !== null) {
        $_SESSION['user_id']  = $info['id'];
        $_SESSION['user']     = $info['username'];
        $_SESSION['role']     = $info['role'];
        $_SESSION['sections'] = $info['sections'] ?? null;
        $redirect = $_GET['redirect'] ?? 'reports.php';
        $redirect = preg_replace('#^/+|\.\./#', '', $redirect) ?: 'reports.php';
        $allowed = ['reports.php', 'table.php', 'charts.php', 'saved-reports.php', 'view-report.php', 'report-performance.php', 'report-behavioral.php', 'report-static.php', 'users.php', 'export-pdf.php'];
        $base = strtok($redirect, '?');
        if (!in_array($base, $allowed, true)) {
            $redirect = 'reports.php';
        }
        if (canOnlyViewSavedReports()) {
            $redirect = 'saved-reports.php';
        }
        header('Location: ' . $redirect);
        exit;
    }
    $error = 'Invalid username or password.';
}

if (getCurrentUser() !== null) {
    $dest = canOnlyViewSavedReports() ? 'saved-reports.php' : 'reports.php';
    header('Location: ' . $dest);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Analytics Reporting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card bg-secondary border-dark" style="width: 100%; max-width: 360px;">
        <div class="card-body">
            <h1 class="h4 card-title mb-3">Analytics Reporting</h1>
            <p class="text-secondary small mb-3">Sign in to view reports.</p>
            <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post" action="login.php<?= isset($_GET['redirect']) ? '?redirect=' . htmlspecialchars($_GET['redirect']) : '' ?>">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control bg-dark text-light border-dark mb-2" autocomplete="username" required autofocus>
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control bg-dark text-light border-dark mb-3" autocomplete="current-password" required>
                <button type="submit" class="btn btn-primary w-100">Sign in</button>
            </form>
        </div>
    </div>
</body>
</html>