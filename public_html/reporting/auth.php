<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ROLE_SUPER_ADMIN = 'super_admin';
const ROLE_ANALYST     = 'analyst';
const ROLE_VIEWER     = 'viewer';

const SECTIONS = ['performance', 'behavioral', 'static'];

function getCurrentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function getCurrentUser(): ?string
{
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function getCurrentRole(): ?string
{
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

/** @return string[]|null NULL = all sections allowed (super_admin or analyst with no restriction) */
function getCurrentSections(): ?array
{
    if (!isset($_SESSION['sections'])) {
        return null;
    }
    return is_array($_SESSION['sections']) ? $_SESSION['sections'] : null;
}

function requireLogin(): void
{
    if (getCurrentUser() === null) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'reports.php');
        header('Location: login.php?redirect=' . $redirect);
        exit;
    }
}

function requireSuperAdmin(): void
{
    requireLogin();
    if (getCurrentRole() !== ROLE_SUPER_ADMIN) {
        header('Location: 403.php');
        exit;
    }
}

function requireAnalystOrAbove(): void
{
    requireLogin();
    $role = getCurrentRole();
    if ($role !== ROLE_SUPER_ADMIN && $role !== ROLE_ANALYST) {
        header('Location: 403.php');
        exit;
    }
}

/** Viewer can only access saved reports and logout; analysts and above can access report sections. */
function canManageUsers(): bool
{
    return getCurrentRole() === ROLE_SUPER_ADMIN;
}

function canOnlyViewSavedReports(): bool
{
    return getCurrentRole() === ROLE_VIEWER;
}

/** Check if current user can access a report section (performance, behavioral, static). */
function canAccessSection(string $section): bool
{
    if (!in_array($section, SECTIONS, true)) {
        return false;
    }
    $role = getCurrentRole();
    if ($role === ROLE_SUPER_ADMIN) {
        return true;
    }
    if ($role === ROLE_VIEWER) {
        return false;
    }
    if ($role === ROLE_ANALYST) {
        $allowed = getCurrentSections();
        return $allowed === null || in_array($section, $allowed, true);
    }
    return false;
}

/** Redirect to 403 if user cannot access the given section. */
function requireSection(string $section): void
{
    requireLogin();
    if (canOnlyViewSavedReports()) {
        header('Location: 403.php');
        exit;
    }
    if (!canAccessSection($section)) {
        header('Location: 403.php');
        exit;
    }
}

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $configPath = __DIR__ . '/config.php';
        if (!is_file($configPath)) {
            throw new RuntimeException('Config not found');
        }
        $config = require $configPath;
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'] ?? 3306,
            $config['dbname'],
            $config['charset'] ?? 'utf8mb4'
        );
        $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    return $pdo;
}

/** Verify credentials and return user row (id, username, role, sections) or null. */
function verifyCredentials(string $username, string $password): ?array
{
    try {
        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role, sections FROM reporting_users WHERE username = ? LIMIT 1');
        $stmt->execute([trim($username)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($password, $row['password_hash'])) {
            return null;
        }
        $sections = null;
        if (!empty($row['sections'])) {
            $decoded = json_decode($row['sections'], true);
            if (is_array($decoded)) {
                $sections = $decoded;
            }
        }
        return [
            'id'       => (int) $row['id'],
            'username' => $row['username'],
            'role'     => $row['role'],
            'sections' => $sections,
        ];
    } catch (PDOException $e) {
        return null;
    }
}
