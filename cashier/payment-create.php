<?php
require_once __DIR__ . '/../includes/workflow.php';
$user = require_role('CASHIER');
handle_post(fn() => submit_payment($user, positive_id('request_id')), 'cashier/payments.php');
[$scope, $params] = scope_sql($user);
$requests = rows("SELECT r.request_id,r.assessed_fee,r.clearance_department_id,s.student_number,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,d.document_name FROM document_requests r JOIN students s ON s.student_id=r.student_id JOIN document_types d ON d.document_type_id=r.document_type_id WHERE " . $scope . " AND r.request_status='AWAITING_PAYMENT' AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.request_id=r.request_id AND p.payment_status IN ('PENDING','VERIFIED')) ORDER BY r.request_id DESC", $params);
$selected = null;
foreach ($requests as $candidate) {
    if ((string)$candidate['request_id'] === input('request_id')) $selected = $candidate;
}
$title = 'Record Counter Payment';
$subtitle = 'Record a payment reference for an approved request, then verify its evidence.';
require __DIR__ . '/../includes/header.php';
?>
<section class="notice"><strong>Eligible requests only.</strong> The Registrar must approve the request first. Requests with a pending or verified payment already have a payment record and are excluded here. <a href="<?= e(url('cashier/payments.php')) ?>">Open payment verification</a>.</section>
<?php if (!$requests): ?>
    <section class="panel">
        <h2>No requests need a new payment</h2>
        <p>Review pending payments in Payment Verification, or ask the Registrar to complete the request review. Fee-free services do not need a payment.</p>
    </section>
<?php else: ?>
    <form class="panel" method="post" data-confirm="Record this amount and reference against the selected document request?">
        <?= csrf() ?>
        <label>Document Request<select name="request_id" required>
                <option value="">Select request</option>
                <?php foreach ($requests as $request): ?>
                    <option value="<?= e($request['request_id']) ?>" data-fee="<?= e($request['assessed_fee']) ?>" data-student="<?= e($request['full_name']) ?>" data-document="<?= e($request['document_name']) ?>" data-clearance="<?= $request['clearance_department_id'] ? 'Department clearance required' : 'No clearance required' ?>" <?= input('request_id') === (string)$request['request_id'] ? 'selected' : '' ?>>
                        <?= e('#' . $request['request_id'] . ' · ' . $request['full_name'] . ' · ' . $request['student_number'] . ' · ' . $request['document_name'] . ' · ' . money($request['assessed_fee'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="notice" id="payment-summary" aria-live="polite">Select a request to see its student, service and assessed fee.</div>
        <div class="field-grid">
            <label>Amount Received (PHP)<input type="number" name="amount" min="0.01" max="99999999.99" step="0.01" required value="<?= e(input('amount')) ?>">
            </label>
            <label>Counter / Transaction Reference<input name="transaction_ref" required maxlength="100" value="<?= e(input('transaction_ref')) ?>">
            </label>
        </div>
        <p class="muted">The record starts Pending. Review and verify it from the payment queue to issue its receipt.</p>
        <button type="submit"><?= icon('add') ?> Record Payment Attempt</button>
    </form>
<?php endif; ?>
<script>
    const requestSelect = document.querySelector('[name="request_id"]');

    function showRequest(fillAmount = true) {
        const option = requestSelect?.selectedOptions[0];
        if (!requestSelect) return;
        if (!option?.value) {
            document.getElementById('payment-summary').textContent = 'Select a request to see its student, service and assessed fee.';
            document.querySelector('[name="amount"]').value = '';
            return;
        }
        document.getElementById('payment-summary').textContent = option.dataset.student + ' · ' + option.dataset.document + ' · PHP ' + option.dataset.fee + ' · ' + option.dataset.clearance;
        if (fillAmount) document.querySelector('[name="amount"]').value = option.dataset.fee;
    }
    requestSelect?.addEventListener('change', () => showRequest());
    if (requestSelect?.value) showRequest(false);
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>