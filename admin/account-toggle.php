<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('ADMIN');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort_page(405, 'Use the account management form.');
}
$id = positive_id();
handle_post(function () use ($id, $user): string {
    if ($id === (int)$user['account_id']) {
        throw new DomainException('You cannot deactivate your own account.');
    }
    $active = choice('is_active', ['0', '1']);
    query('UPDATE accounts SET is_active=? WHERE account_id=?', [$active, $id]);
    return 'Account availability updated.';
}, 'admin/account-edit.php?id=' . $id);
$title = 'Account Availability';
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <p>The account change was not applied.</p>
    <a class="button edit compact" href="<?= e(url('admin/account-edit.php?id=' . $id)) ?>">Return to account</a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>