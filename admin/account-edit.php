<?php
require_once __DIR__ . '/../includes/accounts.php';
$user = require_role('ADMIN');
$id = positive_id();
$account = one('SELECT a.account_id,a.email,a.account_role,a.is_active,COALESCE(s.first_name,f.first_name) AS first_name,COALESCE(s.last_name,f.last_name) AS last_name,COALESCE(TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)),TRIM(CONCAT(f.first_name,CHAR(32),f.last_name))) AS full_name,COALESCE(s.department_id,f.department_id) AS department_id,s.student_number,s.academic_status,f.staff_role,f.access_scope FROM accounts a LEFT JOIN students s ON s.account_id=a.account_id LEFT JOIN staff_users f ON f.account_id=a.account_id WHERE a.account_id=?', [$id]);
if (!$account) {
    abort_page(404, 'Account not found.');
}
handle_post(fn() => save_account($user, $id), 'admin/account-edit.php?id=' . $id);
$departments = rows('SELECT * FROM departments ORDER BY department_name');
$title = 'Edit Account';
$subtitle = 'Account classification is fixed; staff responsibilities can be reassigned.';
require __DIR__ . '/../includes/header.php';
?>
<form method="post" class="panel" data-account-form data-confirm="Save changes to this account’s identity and access settings?">
    <?= csrf() ?>
    <input type="hidden" name="id" value="<?= e($id) ?>">
    <div class="field-grid">
        <label>First Name<input name="first_name" required maxlength="200" autocomplete="given-name" value="<?= e(input('first_name', $account['first_name'])) ?>"></label>
        <label>Last Name<input name="last_name" required maxlength="200" autocomplete="family-name" value="<?= e(input('last_name', $account['last_name'])) ?>"></label>
        <label>Email<input type="email" name="email" required maxlength="254" value="<?= e(input('email', $account['email'])) ?>">
        </label>
        <label>New Password <span class="muted">(leave blank to retain)</span>
            <input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password">
        </label>
        <label>Role<select name="role">
                <?php foreach ($account['account_role'] === 'STUDENT' ? ['STUDENT'] : ['ADMIN', 'REGISTRAR', 'CASHIER', 'DEPARTMENT'] as $role): ?>
                    <option value="<?= e($role) ?>" <?= input('role', $account['staff_role'] ?: 'STUDENT') === $role ? 'selected' : '' ?>>
                        <?= e(ui_label($role)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Department<select name="department_id">
                <option value="">University-wide / not assigned</option>
                <?php options($departments, 'department_id', 'department_name', input('department_id', (string)$account['department_id'])); ?>
            </select>
        </label>
        <?php if ($account['account_role'] === 'STUDENT'): ?>
            <label>Student Number<input name="student_number" required maxlength="30" value="<?= e(input('student_number', $account['student_number'])) ?>">
            </label>
            <label>Academic Status<select name="academic_status">
                    <?php foreach (['ACTIVE', 'ON_LEAVE', 'GRADUATED', 'WITHDRAWN'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= input('academic_status', $account['academic_status']) === $status ? 'selected' : '' ?>>
                            <?= e(ui_label($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php else: ?>
            <label>Access Scope<select name="access_scope">
                    <?php foreach (['UNIVERSITY', 'DEPARTMENT'] as $scope): ?>
                        <option value="<?= e($scope) ?>" <?= input('access_scope', $account['access_scope']) === $scope ? 'selected' : '' ?>>
                            <?= e(ui_label($scope)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
    </div>
    <button type="submit"><?= icon('check') ?> Save Account</button>
</form>
<?php if ($id !== (int)$user['account_id']): ?>
    <form class="panel" method="post" action="<?= e(url('admin/account-toggle.php')) ?>" data-confirm="Change this account’s sign-in access?">
        <?= csrf() ?>
        <input type="hidden" name="id" value="<?= e($id) ?>">
        <input type="hidden" name="is_active" value="<?= $account['is_active'] ? '0' : '1' ?>">
        <h2>Sign-In Access</h2>
        <p>Current status: <?= badge($account['is_active'] ? 'ACTIVE' : 'INACTIVE') ?>. Academic and payment records are retained.</p>
        <button type="submit" class="secondary">
            <?= $account['is_active'] ? 'Deactivate' : 'Activate' ?> Account</button>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>