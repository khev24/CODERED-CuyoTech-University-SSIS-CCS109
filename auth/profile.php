<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('REGISTRAR', 'CASHIER', 'DEPARTMENT');
$department = $user['department_id'] ? one('SELECT department_name FROM departments WHERE department_id=?', [$user['department_id']]) : null;
$title = 'My Profile';
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <dl>
        <dt>First Name</dt>
        <dd><?= e($user['first_name']) ?></dd>
        <dt>Last Name</dt>
        <dd><?= e($user['last_name']) ?></dd>
        <dt>Email</dt>
        <dd><?= e($user['email']) ?></dd>
        <dt>Role</dt>
        <dd><?= e(ui_label($user['role'])) ?></dd>
        <dt>Department</dt>
        <dd><?= e($department['department_name'] ?? 'University-wide') ?></dd>
        <dt>Access Scope</dt>
        <dd><?= e(ui_label($user['access_scope'])) ?></dd>
    </dl>
</section>
<p class="muted">Contact the administrator to correct your account details.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>