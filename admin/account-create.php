<?php
require_once __DIR__ . '/../includes/accounts.php';
$user = require_role('ADMIN');
handle_post(fn() => save_account($user), 'admin/accounts.php');
$departments = rows('SELECT * FROM departments ORDER BY department_name');
$title = 'Create University Account';
$subtitle = 'Issue an individual account. Share its initial password through a private channel.';
require __DIR__ . '/../includes/header.php';
?>
<form method="post" class="panel" data-account-form>
    <?= csrf() ?>
    <div class="field-grid">
        <label>First Name<input name="first_name" required maxlength="200" autocomplete="given-name" value="<?= e(input('first_name')) ?>"></label>
        <label>Last Name<input name="last_name" required maxlength="200" autocomplete="family-name" value="<?= e(input('last_name')) ?>"></label>
        <label>Email<input type="email" name="email" required maxlength="254" value="<?= e(input('email')) ?>">
        </label>
        <label>Initial Password<input type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password">
        </label>
        <label>Role<select name="role">
                <?php foreach (['STUDENT', 'ADMIN', 'REGISTRAR', 'CASHIER', 'DEPARTMENT'] as $role): ?>
                    <option value="<?= e($role) ?>" <?= input('role', 'STUDENT') === $role ? 'selected' : '' ?>>
                        <?= e(ui_label($role)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Department<select name="department_id">
                <option value="">University-wide / not assigned</option>
                <?php options($departments, 'department_id', 'department_name', input('department_id')); ?>
            </select>
        </label>
        <label>Staff Access Scope<select name="access_scope">
                <option value="UNIVERSITY">University-wide</option>
                <option value="DEPARTMENT" <?= input('access_scope') === 'DEPARTMENT' ? 'selected' : '' ?>>Department</option>
            </select>
        </label>
        <label>Student Number <span class="muted">(students only)</span>
            <input name="student_number" maxlength="30" value="<?= e(input('student_number')) ?>">
        </label>
        <label>Academic Status <span class="muted">(students only)</span>
            <select name="academic_status">
                <?php foreach (['ACTIVE', 'ON_LEAVE', 'GRADUATED', 'WITHDRAWN'] as $status): ?>
                    <option value="<?= e($status) ?>">
                        <?= e(ui_label($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <p class="muted">Students require a department and student number. Department personnel require department scope. Administrators require university scope.</p>
    <div class="actions">
        <button type="submit"><?= icon('add') ?> Create Account</button>
        <a class="button secondary" href="<?= e(url('admin/accounts.php')) ?>">Cancel</a>
    </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>