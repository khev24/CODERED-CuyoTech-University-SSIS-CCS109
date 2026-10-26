<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('STUDENT');
$list = rows('SELECT p.*,d.document_name,z.receipt_id FROM payments p JOIN document_requests r ON r.request_id=p.request_id JOIN document_types d ON d.document_type_id=r.document_type_id LEFT JOIN receipts z ON z.payment_id=p.payment_id WHERE r.student_id=? ORDER BY p.payment_id DESC' . page_limit(), [$user['student_id']]);
pager($list);
$title = 'Payment History';
$subtitle = 'All submitted references and Cashier decisions for your document requests.';
require __DIR__ . '/../includes/header.php';
?>
<section class="panel flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Document / Reference</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['document_name']) ?>
                            <span class="muted">
                                <?= e($row['transaction_ref']) ?>
                            </span>
                        </td>
                        <td>
                            <?= e(money($row['amount'])) ?>
                        </td>
                        <td>
                            <?= badge($row['payment_status']) ?>
                        </td>
                        <td>
                            <?= e(date_label($row['submitted_at'])) ?>
                        </td>
                        <td>
                            <a class="button secondary compact" href="<?= e(url('student/request-details.php?id=' . $row['request_id'])) ?>">Request #<?= e($row['request_id']) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="5" class="empty">No payment references submitted yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>