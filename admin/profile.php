<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('ADMIN');
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
        <dd>
            <?= e($user['email']) ?>
        </dd>
        <dt>Role</dt>
        <dd>Administrator</dd>
        <dt>Scope</dt>
        <dd>
            <?= e(ui_label($user['access_scope'])) ?>
        </dd>
    </dl>
    <div class="actions">
        <a class="button" href="<?= e(url('admin/account-edit.php?id=' . $user['account_id'])) ?>"><?= icon('edit') ?> Edit My Account</a>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>