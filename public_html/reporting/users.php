<?php
require_once __DIR__ . '/auth.php';
requireSuperAdmin();

$pdo = getDb();
$message = '';
$error = '';

// 1. Process all database updates and redirects FIRST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'viewer');
        $sections = null;
        if ($role === 'analyst' && !empty($_POST['sections']) && is_array($_POST['sections'])) {
            $sections = json_encode(array_values(array_intersect($_POST['sections'], ['performance', 'behavioral', 'static'])));
        }
        if ($role === 'analyst' && ($sections === null || $sections === '[]')) {
            $sections = null; // null = all sections
        }
        if ($username !== '' && strlen($password) >= 6 && in_array($role, ['super_admin', 'analyst', 'viewer'], true)) {
            try {
                $stmt = $pdo->prepare('INSERT INTO reporting_users (username, password_hash, role, sections) VALUES (?, ?, ?, ?)');
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $sections]);
                $message = 'User created.';
            } catch (PDOException $e) {
                $error = 'Username may already exist.';
            }
        } else {
            $error = 'Username required, password at least 6 characters, and valid role.';
        }
    } elseif ($action === 'update' && isset($_POST['user_id'])) {
        $userId = (int) $_POST['user_id'];
        $role = (string) ($_POST['role'] ?? 'viewer');
        $sections = null;
        if ($role === 'analyst' && !empty($_POST['sections']) && is_array($_POST['sections'])) {
            $sections = json_encode(array_values(array_intersect($_POST['sections'], ['performance', 'behavioral', 'static'])));
        }
        if ($role === 'analyst' && ($sections === null || $sections === '[]')) {
            $sections = null;
        }
        if (in_array($role, ['super_admin', 'analyst', 'viewer'], true)) {
            $stmt = $pdo->prepare('UPDATE reporting_users SET role = ?, sections = ? WHERE id = ?');
            $stmt->execute([$role, $sections, $userId]);
            $message = 'User updated. Section changes take effect after the user logs out and logs back in.';
        }
    } elseif ($action === 'delete' && isset($_POST['user_id'])) {
        $userId = (int) $_POST['user_id'];
        if ($userId !== getCurrentUserId()) {
            $stmt = $pdo->prepare('DELETE FROM reporting_users WHERE id = ?');
            $stmt->execute([$userId]);
            $message = 'User deleted.';
        } else {
            $error = 'You cannot delete yourself.';
        }
    }
    
    // Redirect happens safely here, before any HTML is sent!
    if ($message || $error) {
        $base = dirname($_SERVER['SCRIPT_NAME']);
        if ($base === '/' || $base === '\\' || $base === '.') {
            $base = '';
        } else {
            $base = rtrim($base, '/');
        }
        $url = $base . '/users.php?message=' . urlencode($message) . '&error=' . urlencode($error);
        header('Location: ' . $url, true, 303);
        exit;
    }
}

$pageTitle = 'User management';
require __DIR__ . '/includes/header.php';

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
$users = $pdo->query('SELECT id, username, role, sections, created_at FROM reporting_users ORDER BY username')->fetchAll(PDO::FETCH_ASSOC);
?>