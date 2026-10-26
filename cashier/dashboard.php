<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('CASHIER');
[$scope, $params] = scope_sql($user);
$counts = one("SELECT SUM(p.payment_status='PENDING') AS pending,SUM(p.payment_status='REJECTED') AS rejected,COALESCE(SUM(CASE WHEN p.payment_status='VERIFIED' THEN p.amount ELSE 0 END),0) AS verified_amount FROM payments p JOIN document_requests r ON r.request_id=p.request_id JOIN students s ON s.student_id=r.student_id WHERE " . $scope, $params);
$queue = rows("SELECT p.payment_id,p.transaction_ref,p.amount,p.submitted_at,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name FROM payments p JOIN document_requests r ON r.request_id=p.request_id JOIN students s ON s.student_id=r.student_id WHERE " . $scope . " AND p.payment_status='PENDING' ORDER BY p.submitted_at LIMIT 8", $params);
$title = 'Cashier Desk';
require __DIR__ . '/../includes/header.php';
?>
<section class="stats">
    <article class="stat">
        <small><?= icon('file') ?> Awaiting verification</small>
        <strong>
            <?= e($counts['pending'] ?? 0) ?>
        </strong>
        <p>Submitted references to review</p>
    </article>
    <article class="stat">
        <small><?= icon('payment') ?> Verified amount</small>
        <strong class="summary-amount">
            <?= e(money($counts['verified_amount'])) ?>
        </strong>
        <p>All recorded verified document fees</p>
    </article>
    <article class="stat">
        <small><?= icon('file') ?> Rejected attempts</small>
        <strong>
            <?= e($counts['rejected'] ?? 0) ?>
        </strong>
        <p>Evidence retained for reference</p>
    </article>
</section>
<section class="panel flush">
    <div class="panel-title">
        <h2>Next Payments To Verify</h2>
        <a class="button secondary compact" href="<?= e(url('cashier/payments.php')) ?>">All payments</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Reference</th>
                    <th>Amount</th>
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
                            <?= e($row['transaction_ref']) ?>
                        </td>
                        <td>
                            <?= e(money($row['amount'])) ?>
                        </td>
                        <td>
                            <a class="button secondary compact" href="<?= e(url('cashier/payment-details.php?id=' . $row['payment_id'])) ?>"><?= icon('check') ?> Verify Evidence</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$queue): ?>
                    <tr>
                        <td colspan="4" class="empty">No payments are awaiting verification.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel">
    <h2>Payment And Release Are Separate Decisions</h2>
    <p class="muted">Verify the reference against university records. A successful verification issues a receipt; the Registrar remains responsible for document readiness and release.</p>
    <a class="button secondary compact" href="<?= e(url('cashier/receipts.php')) ?>">Browse issued receipts →</a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>