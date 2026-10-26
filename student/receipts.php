<?php
require_once __DIR__ . '/../includes/receipts.php';
$user = require_role('STUDENT');
[$scope, $params] = scope_sql($user);
$receipt = input('id') !== '' ? receipt_record($user, positive_id()) : null;
$list = rows('SELECT z.receipt_id,z.receipt_number,z.issued_at,p.amount,s.student_number,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name FROM receipts z JOIN payments p ON p.payment_id=z.payment_id JOIN document_requests r ON r.request_id=p.request_id JOIN students s ON s.student_id=r.student_id WHERE ' . $scope . ' ORDER BY z.receipt_id DESC' . page_limit(), $params);
pager($list);
$title = $receipt ? 'Payment receipt' : 'Issued receipts';
$subtitle = 'Verified payments with a permanent receipt number.';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($receipt): ?>
    <article class="panel receipt">
        <div class="eyebrow">CuyoTech University · Student Services</div>
        <h2>Payment Acknowledgement</h2>
        <p>
            <?= e($receipt['receipt_number']) ?>
        </p>
        <dl>
            <dt>Student</dt>
            <dd>
                <?= e($receipt['full_name']) ?>
            </dd>
            <dt>Student Number</dt>
            <dd>
                <?= e($receipt['student_number']) ?>
            </dd>
            <dt>Document Service</dt>
            <dd>
                <?= e($receipt['document_name']) ?>
            </dd>
            <dt>Request</dt>
            <dd>#<?= e($receipt['request_id']) ?>
            </dd>
            <dt>Payment Reference</dt>
            <dd>
                <?= e($receipt['transaction_ref']) ?>
            </dd>
            <dt>Issued By</dt>
            <dd>
                <?= e($receipt['issuer']) ?>
            </dd>
            <dt>Issued At</dt>
            <dd>
                <?= e(date_label($receipt['issued_at'])) ?>
            </dd>
        </dl>
        <div class="total">
            <?= e(money($receipt['amount'])) ?>
        </div>
        <p class="muted">Payment verified. This receipt does not by itself indicate document release.</p>
        <div class="actions no-print">
            <button type="button" class="button" data-print><?= icon('print') ?> Print Receipt</button>
            <a class="button secondary" href="receipts.php">All receipts</a>
        </div>
    </article>
<?php else: ?>
    <section class="panel flush">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Issued</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $row): ?>
                        <tr>
                            <td>
                                <?= e($row['receipt_number']) ?>
                            </td>
                            <td>
                                <?= e($row['full_name']) ?>
                                <span class="muted">
                                    <?= e($row['student_number']) ?>
                                </span>
                            </td>
                            <td>
                                <?= e(money($row['amount'])) ?>
                            </td>
                            <td>
                                <?= e(date_label($row['issued_at'])) ?>
                            </td>
                            <td>
                                <a class="button secondary compact" href="?id=<?= e($row['receipt_id']) ?>"><?= icon('eye') ?> View / Print</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$list): ?>
                        <tr>
                            <td colspan="5" class="empty">No receipts have been issued.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php pagination(); ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>