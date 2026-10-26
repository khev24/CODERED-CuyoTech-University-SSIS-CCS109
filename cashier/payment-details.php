<?php
require_once __DIR__ . '/../includes/workflow.php';
$user = require_role('CASHIER');
$id = positive_id();
handle_post(fn() => verify_payment($user, $id), 'cashier/payment-details.php?id=' . $id);
$payment = one('SELECT p.*,r.student_id,r.assessed_fee,r.request_status,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,s.student_number,d.document_name,TRIM(CONCAT(f.first_name,CHAR(32),f.last_name)) AS verifier,z.receipt_id FROM payments p JOIN document_requests r ON r.request_id=p.request_id JOIN students s ON s.student_id=r.student_id JOIN document_types d ON d.document_type_id=r.document_type_id LEFT JOIN staff_users f ON f.staff_id=p.verified_by_staff_id LEFT JOIN receipts z ON z.payment_id=p.payment_id WHERE p.payment_id=?', [$id]);
if (!$payment) {
    abort_page(404, 'Payment not found.');
}
require_student_scope($user, (int)$payment['student_id']);
$title = 'Payment #' . $id;
$subtitle = $payment['student_number'] . ' · ' . $payment['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <dl>
        <dt>Document Request</dt>
        <dd>#<?= e($payment['request_id']) ?> · <?= e($payment['document_name']) ?>
        </dd>
        <dt>Request Status</dt>
        <dd>
            <?= badge($payment['request_status']) ?>
        </dd>
        <dt>Payment Status</dt>
        <dd>
            <?= badge($payment['payment_status']) ?>
        </dd>
        <dt>Reference</dt>
        <dd>
            <?= e($payment['transaction_ref']) ?>
        </dd>
        <dt>Submitted Amount</dt>
        <dd>
            <?= e(money($payment['amount'])) ?>
        </dd>
        <dt>Assessed Fee</dt>
        <dd>
            <?= e(money($payment['assessed_fee'])) ?>
        </dd>
        <dt>Submitted</dt>
        <dd>
            <?= e(date_label($payment['submitted_at'])) ?>
        </dd>
        <dt>Verified / Decided</dt>
        <dd>
            <?= e(date_label($payment['verified_at'])) ?> · <?= e($payment['verifier']) ?>
        </dd>
        <dt>Remarks</dt>
        <dd>
            <?= e($payment['remarks']) ?>
        </dd>
    </dl>
    <?php if ($payment['receipt_id']): ?>
        <div class="actions">
            <a class="button" href="<?= e(url('cashier/receipts.php?id=' . $payment['receipt_id'])) ?>"><?= icon('eye') ?> View Receipt</a>
        </div>
    <?php endif; ?>
</section>
<?php if ($payment['payment_status'] === 'PENDING'): ?>
    <form method="post" class="panel" data-confirm="Record this payment decision? Verified or rejected attempts cannot be edited.">
        <?= csrf() ?>
        <input type="hidden" name="id" value="<?= e($id) ?>">
        <h2>Record Verification Decision</h2>
        <label>Decision<select name="decision">
                <option value="VERIFIED">Verify payment and issue receipt</option>
                <option value="REJECTED">Reject payment reference</option>
            </select>
        </label>
        <label class="check">
            <input type="checkbox" name="reference_confirmed">I checked this reference against actual university payment evidence.</label>
        <label>Remarks / Rejection Reason<textarea name="remarks" maxlength="1000">
</textarea>
        </label>
        <p class="muted">Verification requires the exact assessed amount and a confirmed reference. Rejection requires a reason. This is a manual verification process, not an online gateway.</p>
        <button type="submit"><?= icon('add') ?> Record Cashier Decision</button>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>