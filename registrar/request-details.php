<?php
require_once __DIR__ . '/../includes/workflow.php';
$user = require_role('REGISTRAR');
$id = positive_id();
owned_request($user, $id);
handle_post(fn() => review_request($user, $id), 'registrar/request-details.php?id=' . $id);
$request = request_details($user, $id);
$payments = rows('SELECT p.*,TRIM(CONCAT(f.first_name,CHAR(32),f.last_name)) AS full_name FROM payments p LEFT JOIN staff_users f ON f.staff_id=p.verified_by_staff_id WHERE p.request_id=? ORDER BY p.payment_id DESC', [$id]);
$events = rows('SELECT h.*,TRIM(CONCAT(f.first_name,CHAR(32),f.last_name)) AS full_name FROM request_status_events h LEFT JOIN staff_users f ON f.staff_id=h.changed_by_staff_id WHERE h.request_id=? ORDER BY h.event_id DESC', [$id]);
$title = 'Review Request #' . $id;
$subtitle = $request['student_number'] . ' · ' . $request['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="grid">
    <section class="panel">
        <h2>
            <?= e($request['document_name']) ?>
        </h2>
        <dl>
            <dt>Status</dt>
            <dd>
                <?= badge($request['request_status']) ?>
            </dd>
            <dt>Purpose</dt>
            <dd>
                <?= e($request['purpose']) ?>
            </dd>
            <dt>Term</dt>
            <dd>
                <?= e($request['academic_year'] . ' / ' . $request['term_code']) ?>
            </dd>
            <dt>Submitted</dt>
            <dd>
                <?= e(date_label($request['requested_at'])) ?>
            </dd>
            <dt>Assessed Fee</dt>
            <dd>
                <?= e(money($request['assessed_fee'])) ?>
            </dd>
        </dl>
    </section>
    <section class="panel">
        <h2>Requirement Check</h2>
        <dl>
            <dt>Eligibility</dt>
            <dd>
                <?= badge($request['eligibility_status']) ?>
            </dd>
            <dt>Clearance</dt>
            <dd>
                <?= $request['clearance_department_id'] ? (clearance_ok($request) ? 'Cleared' : 'Not yet cleared') : 'Not required' ?>
            </dd>
            <dt>Payment</dt>
            <dd>
                <?= payment_ok($request) ? ((float)$request['assessed_fee'] > 0 ? 'Verified in full' : 'No fee') : 'Awaiting full verification' ?>
            </dd>
            <dt>Reviewer</dt>
            <dd>
                <?= e($request['reviewer'] ?? 'Not reviewed') ?>
            </dd>
            <dt>Reviewed</dt>
            <dd>
                <?= e(date_label($request['reviewed_at'])) ?>
            </dd>
        </dl>
    </section>
</div>
<section class="panel section-gap">
    <h2>Registrar Action</h2>
    <?php if ($request['request_status'] === 'PENDING'): ?>
        <form method="post">
            <?= csrf() ?>
            <input type="hidden" name="action" value="start">
            <p class="muted">Begin the review before recording a decision.</p>
            <button type="submit"><?= icon('edit') ?> Begin Review</button>
        </form>
    <?php elseif ($request['request_status'] === 'UNDER_REVIEW'): ?>
        <form method="post" data-confirm="Approve eligibility and clearance for this request?">
            <?= csrf() ?>
            <input type="hidden" name="action" value="approve">
            <label class="check">
                <input type="checkbox" name="eligibility_confirmed" required>I reviewed the student’s eligibility for this document.</label>
            <p class="muted">Required clearance must already be granted. A fee-free request becomes Ready; otherwise it proceeds to payment verification.</p>
            <button type="submit" class="success"><?= icon('check') ?> Approve Requirements</button>
        </form>
    <?php elseif ($request['request_status'] === 'AWAITING_PAYMENT'): ?>
        <form method="post" data-confirm="Mark this document ready for release?">
            <?= csrf() ?>
            <input type="hidden" name="action" value="ready">
            <p class="muted">Clearance and verified payment will be checked again when you save.</p>
            <button type="submit" class="success"><?= icon('check') ?> Mark Ready</button>
        </form>
    <?php elseif ($request['request_status'] === 'READY'): ?>
        <form method="post" data-confirm="Confirm that this document has actually been handed to the student?">
            <?= csrf() ?>
            <input type="hidden" name="action" value="release">
            <p class="muted">Only record release after the document has actually been issued.</p>
            <button type="submit" class="success"><?= icon('check') ?> Confirm Document Release</button>
        </form>
    <?php else: ?>
        <p>This request is <?= e(strtolower($request['request_status'])) ?>.</p>
        <?php if ($request['rejection_reason']): ?>
            <p>
                <?= e($request['rejection_reason']) ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (in_array($request['request_status'], ['UNDER_REVIEW', 'AWAITING_PAYMENT'], true)): ?>
        <details class="section-gap">
            <summary>Reject request</summary>
            <form method="post" data-confirm="Reject this request with the recorded reason?">
                <?= csrf() ?>
                <input type="hidden" name="action" value="reject">
                <label>Reason<textarea name="rejection_reason" required maxlength="1000"><?= e(input('rejection_reason')) ?></textarea>
                </label>
                <button type="submit" class="danger"><?= icon('close') ?> Reject Request</button>
            </form>
        </details>
    <?php endif; ?>
</section>
<section class="panel flush">
    <div class="panel-title">
        <h2>Payment Evidence</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th>Decision</th>
                    <th>Cashier / Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td>
                            <?= e($payment['transaction_ref']) ?>
                        </td>
                        <td>
                            <?= e(money($payment['amount'])) ?>
                        </td>
                        <td>
                            <?= badge($payment['payment_status']) ?>
                        </td>
                        <td>
                            <?= e($payment['full_name']) ?>
                            <span class="muted">
                                <?= e($payment['remarks']) ?>
                            </span>
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
    <h2>Request History</h2>
    <ol class="timeline">
        <?php foreach ($events as $event): ?>
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