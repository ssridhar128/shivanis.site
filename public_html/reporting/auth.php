<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
const AUTH_USER = 'grader';
const AUTH_PASS = 'grader';

function requireLogin(): void
{
    if (!isset($_SESSION['user']) || $_SESSION['user'] !== AUTH_USER) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'reports.php');
        header('Location: login.php?redirect=' . $redirect);
        exit;
    }
}

function getCurrentUser(): ?string
{
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function checkCredentials(string $user, string $pass): bool
{
    return $user === AUTH_USER && $pass === AUTH_PASS;
}
