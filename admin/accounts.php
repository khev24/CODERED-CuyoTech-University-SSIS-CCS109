<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('ADMIN');
$search = '%' . input('q') . '%';
$list = rows('SELECT a.account_id,a.email,a.account_role,a.is_active,
    COALESCE(TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)),TRIM(CONCAT(f.first_name,CHAR(32),f.last_name))) AS full_name,s.student_number,f.staff_role,f.access_scope
    FROM accounts a
    LEFT JOIN students s ON s.account_id=a.account_id
    LEFT JOIN staff_users f ON f.account_id=a.account_id
    WHERE (a.email LIKE ? OR COALESCE(TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)),TRIM(CONCAT(f.first_name,CHAR(32),f.last_name))) LIKE ? OR s.student_number LIKE ?)
    ORDER BY a.account_id DESC' . page_limit(), [$search, $search, $search]);
pager($list);
$title = 'User Accounts';

require __DIR__ . '/../includes/header.php';
?>
<form class="toolbar" method="get">
    <label>Search Accounts<input name="q" value="<?= e(input('q')) ?>" placeholder="Name, email or student number">
    </label>
    <div class="toolbar-actions">
        <button type="submit"><?= icon('search') ?> Search</button>
        <a class="button" href="<?= e(url('admin/account-create.php')) ?>"><?= icon('add') ?> New Account</a>
    </div>
</form>
<section class="panel flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Role / Scope</th>
                    <th>Status</th>
                    <th>Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['full_name']) ?>
                            <span class="muted">
                                <?= e($row['email']) ?>
                            </span>
                            <?= e($row['student_number']) ?>
                        </td>
                        <td>
                            <?= e(ui_label($row['staff_role'] ?: $row['account_role'])) ?>
                            <span class="muted">
                                <?= e(ui_label($row['access_scope'] ?? '')) ?>
                            </span>
                        </td>
                        <td>
                            <?= badge($row['is_active'] ? 'ACTIVE' : 'INACTIVE') ?>
                        </td>
                        <td>
                            <a class="button edit compact" href="<?= e(url('admin/account-edit.php?id=' . $row['account_id'])) ?>"><?= icon('edit') ?> Edit Account</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="4" class="empty">No matching accounts.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>