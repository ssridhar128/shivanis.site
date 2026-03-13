<?php
require_once __DIR__ . '/auth.php';
requireSuperAdmin();

$pdo = getDb();
$message = '';
$error = '';

// Process all POST requests and redirects BEFORE drawing any HTML
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
        
        // Check if the target is the protected grader account
        $stmtCheck = $pdo->prepare('SELECT username FROM reporting_users WHERE id = ?');
        $stmtCheck->execute([$userId]);
        $targetUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($targetUser && $targetUser['username'] === 'grader') {
            $error = 'The primary grader account cannot be modified.';
        } else {
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
        }
    } elseif ($action === 'delete' && isset($_POST['user_id'])) {
        $userId = (int) $_POST['user_id'];
        
        // Check if the target is the protected grader account
        $stmtCheck = $pdo->prepare('SELECT username FROM reporting_users WHERE id = ?');
        $stmtCheck->execute([$userId]);
        $targetUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($targetUser && $targetUser['username'] === 'grader') {
            $error = 'The primary grader account cannot be deleted.';
        } elseif ($userId !== getCurrentUserId()) {
            $stmt = $pdo->prepare('DELETE FROM reporting_users WHERE id = ?');
            $stmt->execute([$userId]);
            $message = 'User deleted.';
        } else {
            $error = 'You cannot delete yourself.';
        }
    }
    
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

// NOW we can safely include the header and draw the page
$pageTitle = 'User management';
require __DIR__ . '/includes/header.php';

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
$users = $pdo->query('SELECT id, username, role, sections, created_at FROM reporting_users ORDER BY username')->fetchAll(PDO::FETCH_ASSOC);
?>
<main class="container py-4">
    <h1 class="h2 mb-4">User management</h1>
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card bg-secondary border-dark mb-4">
        <div class="card-body">
            <h2 class="h5 card-title">Add user</h2>
            <form method="post" class="row g-2 align-items-end">
                <input type="hidden" name="action" value="create">
                <div class="col-md-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username" class="form-control" required placeholder="Username">
                </div>
                <div class="col-md-2">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required minlength="6" placeholder="Password">
                </div>
                <div class="col-md-2">
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-select">
                        <option value="viewer">Viewer</option>
                        <option value="analyst">Analyst</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                <div class="col-md-3" id="add-sections-container" style="display: none;">
                    <label class="form-label d-block">Sections (analyst)</label>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start bg-dark text-light border-secondary" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            Select Sections...
                        </button>
                        <div class="dropdown-menu dropdown-menu-dark p-2 w-100">
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="sections[]" value="performance" id="addSecP">
                                <label class="form-check-label" for="addSecP">Performance</label>
                            </div>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="sections[]" value="behavioral" id="addSecB">
                                <label class="form-check-label" for="addSecB">Behavioral</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sections[]" value="static" id="addSecS">
                                <label class="form-check-label" for="addSecS">Static</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Add</button></div>
            </form>
        </div>
    </div>

    <table class="table table-dark table-striped">
        <thead><tr><th>Username</th><th>Role</th><th>Created</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u):
            $raw = $u['sections'] ?? null;
            $sections = is_array($raw) ? $raw : (is_string($raw) && $raw !== '' ? json_decode($raw, true) : null);
            $sections = is_array($sections) ? $sections : [];
        ?>
        <tr>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['role']) ?></td>
            <td><?= htmlspecialchars($u['created_at']) ?></td>
            <td>
                <?php if ($u['username'] === 'grader'): ?>
                    <span class="text-secondary fst-italic">Cannot Update grader's Role</span>
                <?php else: ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Update this user?');">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <select name="role" class="form-select form-select-sm d-inline-block w-auto update-role-select">
                            <option value="viewer" <?= $u['role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                            <option value="analyst" <?= $u['role'] === 'analyst' ? 'selected' : '' ?>>Analyst</option>
                            <option value="super_admin" <?= $u['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                        </select>
                        
                        <?php $uSections = $sections; ?>
                        <div class="update-sections-container dropdown ms-1" style="<?= $u['role'] === 'analyst' ? 'display: inline-block;' : 'display: none;' ?>">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                Sections
                            </button>
                            <div class="dropdown-menu dropdown-menu-dark p-2 text-light" style="min-width: 140px;">
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="sections[]" value="performance" id="secP_<?= $u['id'] ?>" <?= in_array('performance', $uSections, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="secP_<?= $u['id'] ?>">Performance</label>
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="sections[]" value="behavioral" id="secB_<?= $u['id'] ?>" <?= in_array('behavioral', $uSections, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="secB_<?= $u['id'] ?>">Behavioral</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sections[]" value="static" id="secS_<?= $u['id'] ?>" <?= in_array('static', $uSections, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="secS_<?= $u['id'] ?>">Static</label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-sm btn-outline-light ms-1">Update</button>
                    </form>
                    <?php if ((int) $u['id'] !== getCurrentUserId()): ?>
                    <form method="post" class="d-inline ms-1" onsubmit="return confirm('Delete this user?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Handle the "Add User" form dropdown at the top
    const addRoleSelect = document.getElementById('role');
    const addSectionsContainer = document.getElementById('add-sections-container');
    
    if (addRoleSelect && addSectionsContainer) {
        addRoleSelect.addEventListener('change', function() {
            addSectionsContainer.style.display = (this.value === 'analyst') ? 'block' : 'none';
        });
        addRoleSelect.dispatchEvent(new Event('change'));
    }

    // 2. Handle all the "Update" form dropdowns in the table rows
    const updateRoleSelects = document.querySelectorAll('.update-role-select');
    updateRoleSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            const sectionsContainer = this.closest('form').querySelector('.update-sections-container');
            if (sectionsContainer) {
                sectionsContainer.style.display = (this.value === 'analyst') ? 'inline-block' : 'none';
            }
        });
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>