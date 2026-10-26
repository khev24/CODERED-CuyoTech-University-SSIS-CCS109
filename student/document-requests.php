<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('STUDENT');
$params = [$user['student_id']];
$filter = '';
if (input('status') !== '') {
    $filter = ' AND r.request_status=?';
    $params[] = choice('status', ['PENDING', 'UNDER_REVIEW', 'AWAITING_PAYMENT', 'READY', 'RELEASED', 'REJECTED']);
}
$list = rows('SELECT r.*,d.document_name FROM document_requests r JOIN document_types d ON d.document_type_id=r.document_type_id WHERE r.student_id=?' . $filter . ' ORDER BY r.request_id DESC' . page_limit(), $params);
pager($list);
$title = 'Document Requests';
require __DIR__ . '/../includes/header.php';
?>
<form class="toolbar" method="get">
    <label>Status<select name="status">
            <option value="">All statuses</option>
            <?php foreach (['PENDING', 'UNDER_REVIEW', 'AWAITING_PAYMENT', 'READY', 'RELEASED', 'REJECTED'] as $status): ?>
                <option value="<?= e($status) ?>" <?= input('status') === $status ? 'selected' : '' ?>>
                    <?= e(ui_label($status)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="toolbar-actions">
        <button type="submit" class="secondary"><?= icon('search') ?> Apply Filter</button>
        <a class="button" href="<?= e(url('student/request-create.php')) ?>"><?= icon('add') ?> New Request</a>
    </div>
</form>
<section class="panel flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Request / Document</th>
                    <th>Submitted</th>
                    <th>Assessed Fee</th>
                    <th>Status</th>
                    <th>Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>#<?= e($row['request_id']) ?>
                            <span class="muted">
                                <?= e($row['document_name']) ?>
                            </span>
                        </td>
                        <td>
                            <?= e(date_label($row['requested_at'])) ?>
                        </td>
                        <td>
                            <?= e(money($row['assessed_fee'])) ?>
                        </td>
                        <td>
                            <?= badge($row['request_status']) ?>
                        </td>
                        <td>
                            <a class="button secondary compact" href="<?= e(url('student/request-details.php?id=' . $row['request_id'])) ?>"><?= icon('eye') ?> View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="5" class="empty">No requests match this selection.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>