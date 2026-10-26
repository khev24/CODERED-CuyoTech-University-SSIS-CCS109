<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('REGISTRAR');
[$scope, $params] = scope_sql($user);
$counts = one("SELECT SUM(r.request_status IN ('PENDING','UNDER_REVIEW')) AS review,SUM(r.request_status='AWAITING_PAYMENT') AS payment,SUM(r.request_status='READY') AS ready FROM document_requests r JOIN students s ON s.student_id=r.student_id WHERE " . $scope, $params);
$queue = rows("SELECT r.request_id,r.request_status,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,d.document_name FROM document_requests r JOIN students s ON s.student_id=r.student_id JOIN document_types d ON d.document_type_id=r.document_type_id WHERE " . $scope . " AND r.request_status IN ('PENDING','UNDER_REVIEW') ORDER BY r.requested_at LIMIT 8", $params);
$title = 'Registrar Desk';
require __DIR__ . '/../includes/header.php';
?>
<section class="stats">
    <article class="stat">
        <small><?= icon('file') ?> For review</small>
        <strong>
            <?= e($counts['review'] ?? 0) ?>
        </strong>
        <p>Pending or under review</p>
    </article>
    <article class="stat">
        <small><?= icon('payment') ?> Awaiting payment</small>
        <strong>
            <?= e($counts['payment'] ?? 0) ?>
        </strong>
        <p>Approved for Cashier verification</p>
    </article>
    <article class="stat">
        <small><?= icon('check') ?> Ready for release</small>
        <strong>
            <?= e($counts['ready'] ?? 0) ?>
        </strong>
        <p>Confirm physical release</p>
    </article>
</section>
<div class="toolbar">
    <a class="button" href="<?= e(url('registrar/enrollments.php')) ?>">Manage enrollments</a>
    <a class="button secondary" href="<?= e(url('registrar/grades.php')) ?>">Encode grades</a>
</div>
<section class="panel flush">
    <div class="panel-title">
        <h2>Oldest Requests Awaiting Review</h2>
        <a class="button secondary compact" href="<?= e(url('registrar/document-requests.php')) ?>">Full queue</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Document</th>
                    <th>Status</th>
                    <th>Review</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($queue as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['full_name']) ?>
                        </td>
                        <td>
                            <?= e($row['document_name']) ?>
                        </td>
                        <td>
                            <?= badge($row['request_status']) ?>
                        </td>
                        <td>
                            <a class="button secondary compact" href="<?= e(url('registrar/request-details.php?id=' . $row['request_id'])) ?>">Open #<?= e($row['request_id']) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$queue): ?>
                    <tr>
                        <td colspan="4" class="empty">Your review queue is clear.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>