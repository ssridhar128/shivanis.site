<?php
require_once __DIR__ . '/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (checkCredentials($user, $pass)) {
        $_SESSION['user'] = $user;
        $redirect = $_GET['redirect'] ?? 'reports.php';
        $redirect = preg_replace('#^/+|\.\./#', '', $redirect) ?: 'reports.php';
        if (!in_array($redirect, ['reports.php', 'table.php', 'charts.php'], true)) {
            $redirect = 'reports.php';
        }
        header('Location: ' . $redirect);
        exit;
    }
    $error = 'Invalid username or password.';
}

// If already logged in, redirect to dashboard
if (getCurrentUser() !== null) {
    header('Location: reports.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Analytics Reporting</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #1a1d29; color: #e4e6eb; }
        .card { background: #252836; padding: 2rem; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.3); width: 100%; max-width: 360px; }
        h1 { margin: 0 0 1.5rem 0; font-size: 1.35rem; font-weight: 600; }
        label { display: block; margin-bottom: 0.35rem; font-size: 0.9rem; color: #9ca3af; }
        input[type="text"], input[type="password"] { width: 100%; padding: 0.65rem 0.75rem; margin-bottom: 1rem; border: 1px solid #3f4556; border-radius: 6px; background: #1a1d29; color: #e4e6eb; font-size: 1rem; }
        input:focus { outline: none; border-color: #6366f1; }
        .error { color: #f87171; font-size: 0.9rem; margin-bottom: 1rem; }
        button { width: 100%; padding: 0.75rem; background: #6366f1; color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 500; cursor: pointer; }
        button:hover { background: #4f46e5; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Analytics Reporting</h1>
        <p style="margin:0 0 1rem 0; color:#9ca3af; font-size:0.9rem;">Sign in to view reports.</p>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post" action="login.php<?= isset($_GET['redirect']) ? '?redirect=' . htmlspecialchars($_GET['redirect']) : '' ?>">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" autocomplete="username" required autofocus>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <button type="submit">Sign in</button>
        </form>
    </div>
</body>
</html>
