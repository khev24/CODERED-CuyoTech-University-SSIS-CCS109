<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('REGISTRAR');
[$scope, $params] = scope_sql($user);
$search = '%' . input('q') . '%';
$params = array_merge($params, [$search, $search]);
$filter = '';
if (input('status') !== '') {
    $filter = ' AND r.request_status=?';
    $params[] = choice('status', ['PENDING', 'UNDER_REVIEW', 'AWAITING_PAYMENT', 'READY', 'REJECTED', 'RELEASED']);
}
$list = rows('SELECT r.*,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,s.student_number,d.document_name FROM document_requests r JOIN students s ON s.student_id=r.student_id JOIN document_types d ON d.document_type_id=r.document_type_id WHERE ' . $scope . ' AND (TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) LIKE ? OR s.student_number LIKE ?)' . $filter . ' ORDER BY r.requested_at DESC' . page_limit(), $params);
pager($list);
$title = 'Document Request Queue';
$subtitle = 'Review eligibility, verify required clearance and manage document release.';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" class="toolbar">
    <label>Student<input name="q" value="<?= e(input('q')) ?>" placeholder="Name or student number">
    </label>
    <label>Status<select name="status">
            <option value="">All statuses</option>
            <?php foreach (['PENDING', 'UNDER_REVIEW', 'AWAITING_PAYMENT', 'READY', 'REJECTED', 'RELEASED'] as $status): ?>
                <option value="<?= e($status) ?>" <?= input('status') === $status ? 'selected' : '' ?>>
                    <?= e(ui_label($status)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="toolbar-actions">
        <button type="submit"><?= icon('search') ?> Search Requests</button>
    </div>
</form>
<section class="panel flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Document / Fee</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Review</th>
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
                            <?= e($row['document_name']) ?>
                            <span class="muted">
                                <?= e(money($row['assessed_fee'])) ?>
                            </span>
                        </td>
                        <td>
                            <?= badge($row['request_status']) ?>
                        </td>
                        <td>
                            <?= e(date_label($row['requested_at'])) ?>
                        </td>
                        <td>
                            <a class="button secondary compact" href="<?= e(url('registrar/request-details.php?id=' . $row['request_id'])) ?>">Open #<?= e($row['request_id']) ?>
                            </a>
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