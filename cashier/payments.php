<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('CASHIER');
[$scope, $params] = scope_sql($user);
$search = '%' . input('q') . '%';
$params = array_merge($params, [$search, $search, $search]);
$filter = '';
if (input('status') !== '') {
    $filter = ' AND p.payment_status=?';
    $params[] = choice('status', ['PENDING', 'VERIFIED', 'REJECTED']);
}
$list = rows('SELECT p.*,d.document_name,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,s.student_number FROM payments p JOIN document_requests r ON r.request_id=p.request_id JOIN document_types d ON d.document_type_id=r.document_type_id JOIN students s ON s.student_id=r.student_id WHERE ' . $scope . ' AND (TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) LIKE ? OR s.student_number LIKE ? OR p.transaction_ref LIKE ?)' . $filter . ' ORDER BY p.payment_id DESC' . page_limit(), $params);
pager($list);
$title = 'Payment Verification';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" class="toolbar">
    <label>Student Or Reference<input name="q" value="<?= e(input('q')) ?>">
    </label>
    <label>Status<select name="status">
            <option value="">All statuses</option>
            <?php foreach (['PENDING', 'VERIFIED', 'REJECTED'] as $status): ?>
                <option value="<?= e($status) ?>" <?= input('status') === $status ? 'selected' : '' ?>>
                    <?= e(ui_label($status)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="toolbar-actions">
        <button type="submit"><?= icon('search') ?> Search Payments</button>
        <a class="button" href="payment-create.php"><?= icon('add') ?> Record Counter Payment</a>
    </div>
</form>
<section class="panel flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Document / Reference</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['full_name']) ?>
                            <span class="muted">
                                <?= e($row['student_number']) ?>
                            </span>
                        </td>
                        <td>
                            <?= e($row['document_name']) ?><span class="muted"><?= e($row['transaction_ref']) ?></span>
                        </td>
                        <td>
                            <?= e(money($row['amount'])) ?>
                        </td>
                        <td>
                            <?= badge($row['payment_status']) ?>
                        </td>
                        <td>
                            <a class="button secondary compact" href="<?= e(url('cashier/payment-details.php?id=' . $row['payment_id'])) ?>"><?= icon('eye') ?> Open Payment</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="5" class="empty">No matching payment records.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>