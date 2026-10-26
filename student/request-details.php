<?php
require_once __DIR__ . '/../includes/workflow.php';
$user = require_role('STUDENT');
$id = positive_id();
owned_request($user, $id);
handle_post(fn() => submit_payment($user, $id), 'student/request-details.php?id=' . $id);
$request = request_details($user, $id);
$payments = rows('SELECT p.*,z.receipt_id,z.receipt_number FROM payments p LEFT JOIN receipts z ON z.payment_id=p.payment_id WHERE p.request_id=? ORDER BY p.payment_id DESC', [$id]);
$history = rows('SELECT h.*,TRIM(CONCAT(f.first_name,CHAR(32),f.last_name)) AS full_name FROM request_status_events h LEFT JOIN staff_users f ON f.staff_id=h.changed_by_staff_id WHERE h.request_id=? ORDER BY h.event_id', [$id]);
$title = 'Request #' . $id;
$subtitle = $request['document_name'];
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <dl>
        <dt>Status</dt>
        <dd>
            <?= badge($request['request_status']) ?>
        </dd>
        <dt>Purpose</dt>
        <dd>
            <?= e($request['purpose']) ?>
        </dd>
        <dt>Academic Term</dt>
        <dd>
            <?= e($request['academic_year'] . ' / ' . $request['term_code']) ?>
        </dd>
        <dt>Assessed Fee</dt>
        <dd>
            <?= e(money($request['assessed_fee'])) ?>
        </dd>
        <dt>Clearance</dt>
        <dd>
            <?= $request['clearance_department_id'] ? (clearance_ok($request) ? 'Cleared' : 'Required — not yet cleared') : 'Not required' ?>
        </dd>
        <dt>Submitted</dt>
        <dd>
            <?= e(date_label($request['requested_at'])) ?>
        </dd>
        <dt>Ready / Released</dt>
        <dd>
            <?= e(date_label($request['ready_at'])) ?> / <?= e(date_label($request['released_at'])) ?>
        </dd>
        <?php if ($request['rejection_reason']): ?>
            <dt>Rejection Reason</dt>
            <dd>
                <?= e($request['rejection_reason']) ?>
            </dd>
        <?php endif; ?>
    </dl>
</section>
<?php if ($request['request_status'] === 'AWAITING_PAYMENT' && !one("SELECT payment_id FROM payments WHERE request_id=? AND payment_status IN ('PENDING','VERIFIED')", [$id])): ?>
    <form class="panel" method="post" data-confirm="Submit this payment reference for verification?">
        <?= csrf() ?>
        <h2>Submit Payment Reference</h2>
        <p class="muted">Pay through the university's approved channel, then submit the reference for Cashier verification.</p>
        <div class="field-grid">
            <label>Amount Paid (PHP)<input type="number" step="0.01" min="0.01" name="amount" required value="<?= e($request['assessed_fee']) ?>">
            </label>
            <label>Transaction / Counter Reference<input name="transaction_ref" required maxlength="100" value="<?= e(input('transaction_ref')) ?>">
            </label>
        </div>
        <button type="submit"><?= icon('check') ?> Submit For Verification</button>
    </form>
<?php endif; ?>
<section class="panel flush">
    <div class="panel-title">
        <h2>Payment Attempts</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Cashier Remarks / Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['transaction_ref']) ?>
                        </td>
                        <td>
                            <?= e(money($row['amount'])) ?>
                        </td>
                        <td>
                            <?= badge($row['payment_status']) ?>
                        </td>
                        <td>
                            <?= e($row['remarks']) ?>
                            <?php if ($row['receipt_id']): ?>
                                <a class="button secondary compact" href="<?= e(url('student/receipts.php?id=' . $row['receipt_id'])) ?>">
                                    <?= e($row['receipt_number']) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$payments): ?>
                    <tr>
                        <td colspan="4" class="empty">No payment attempts recorded.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel">
    <h2>Request Timeline</h2>
    <ol class="timeline">
        <?php foreach ($history as $event): ?>
            <li>
                <?= badge($event['new_status']) ?>
                <small>
                    <?= e(date_label($event['changed_at'])) ?> · <?= e($event['full_name'] ?: 'Student submission') ?>
                </small>
                <?= e($event['remarks']) ?>
            </li>
        <?php endforeach; ?>
    </ol>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>