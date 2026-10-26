<?php
require_once __DIR__ . '/auth.php';

function clearance_ok(array $request): bool
{
    if ($request['clearance_department_id'] === null) {
        return true;
    }
    return (bool) one("SELECT clearance_id FROM clearance_records WHERE student_id=? AND department_id=? AND term_id=? AND clearance_status='CLEARED'", [$request['student_id'], $request['clearance_department_id'], $request['term_id']]);
}
function payment_ok(array $request): bool
{
    return (float)$request['assessed_fee'] === 0.0 || (bool) one("SELECT payment_id FROM payments WHERE request_id=? AND payment_status='VERIFIED' AND amount=?", [$request['request_id'], $request['assessed_fee']]);
}
function submit_payment(array $user, int $requestId): string
{
    return transaction(function () use ($user, $requestId): string {
        $request = owned_request($user, $requestId, true);
        if ($request['request_status'] !== 'AWAITING_PAYMENT' || (float)$request['assessed_fee'] <= 0) {
            throw new DomainException('This request is not awaiting payment.');
        }
        if (one("SELECT payment_id FROM payments WHERE request_id=? AND payment_status IN ('PENDING','VERIFIED')", [$requestId])) {
            throw new DomainException('This request already has a pending or verified payment. Wait for the Cashier decision.');
        }
        $amount = decimal_input('amount', false);
        if ($amount !== number_format((float)$request['assessed_fee'], 2, '.', '')) {
            field_error('amount', 'The payment must equal the assessed fee.');
        }
        $reference = required('transaction_ref', 100);
        if (one('SELECT payment_id FROM payments WHERE transaction_ref=?', [$reference])) {
            field_error('transaction_ref', 'This transaction reference is already recorded. Use the correct unique reference.');
        }
        query('INSERT INTO payments (request_id,amount,transaction_ref) VALUES (?,?,?)', [$requestId, $amount, $reference]);
        return 'Payment reference submitted for Cashier verification.';
    });
}
// Call these helpers inside the same transaction as the related form changes.
// The student form uses this insertion logic.
function create_document_request(int $studentId, int $typeId, int $termId, string $purpose): int
{
    $student = one('SELECT department_id FROM students WHERE student_id=? FOR UPDATE', [$studentId]);
    $type = one('SELECT * FROM document_types WHERE document_type_id=? AND is_active=1', [$typeId]);
    if (!$student || !$type || !one('SELECT term_id FROM academic_terms WHERE term_id=?', [$termId])) {
        throw new DomainException('Select a valid student, available document and academic term.');
    }
    if (trim($purpose) === '' || strlen($purpose) > 1000) {
        throw new DomainException('Enter a purpose of up to 1000 characters.');
    }
    $departmentId = $type['clearance_required'] ? $student['department_id'] : null;
    query('INSERT INTO document_requests
        (student_id,document_type_id,term_id,assessed_fee,clearance_department_id,purpose)
        VALUES (?,?,?,?,?,?)', [$studentId, $typeId, $termId, $type['fee_amount'], $departmentId, $purpose]);
    $requestId = (int) db()->lastInsertId();
    query("INSERT INTO request_status_events (request_id,old_status,new_status,remarks)
        VALUES (?,NULL,'PENDING','Student submitted request')", [$requestId]);
    return $requestId;
}

// A small shared update keeps the status, milestone date and history together.
function set_request_status(int $requestId, string $status, int $staffId, string $remarks): void
{
    $request = one('SELECT * FROM document_requests WHERE request_id=? FOR UPDATE', [$requestId]);
    if (!$request) {
        throw new DomainException('Request not found.');
    }
    $allowed = [
        'PENDING' => ['UNDER_REVIEW'],
        'UNDER_REVIEW' => ['AWAITING_PAYMENT', 'READY', 'REJECTED'],
        'AWAITING_PAYMENT' => ['READY', 'REJECTED'],
        'READY' => ['RELEASED'],
        'REJECTED' => [],
        'RELEASED' => [],
    ];
    if (!in_array($status, $allowed[$request['request_status']] ?? [], true)) {
        throw new DomainException('That status change is not allowed.');
    }
    if (in_array($status, ['AWAITING_PAYMENT', 'READY', 'RELEASED'], true)) {
        if (
            $request['eligibility_status'] !== 'ELIGIBLE' || !$request['reviewed_by_staff_id']
            || !$request['reviewed_at'] || !clearance_ok($request)
        ) {
            throw new DomainException('Eligibility review and required clearance must be completed first.');
        }
    }
    if ($status === 'AWAITING_PAYMENT' && (float)$request['assessed_fee'] <= 0) {
        throw new DomainException('A fee-free request does not need payment.');
    }
    if (in_array($status, ['READY', 'RELEASED'], true) && !payment_ok($request)) {
        throw new DomainException('The full payment must be verified first.');
    }
    if ($status === 'REJECTED' && trim($request['rejection_reason'] ?? '') === '') {
        throw new DomainException('Record a rejection reason first.');
    }
    query("UPDATE document_requests SET request_status=?,
        ready_at=CASE WHEN ?='READY' THEN UTC_TIMESTAMP(6) ELSE ready_at END,
        released_at=CASE WHEN ?='RELEASED' THEN UTC_TIMESTAMP(6) ELSE released_at END
        WHERE request_id=?", [$status, $status, $status, $requestId]);
    query(
        'INSERT INTO request_status_events
        (request_id,changed_by_staff_id,old_status,new_status,remarks) VALUES (?,?,?,?,?)',
        [$requestId, $staffId, $request['request_status'], $status, $remarks]
    );
}

function review_request(array $user, int $requestId): string
{
    return transaction(function () use ($user, $requestId): string {
        $request = owned_request($user, $requestId, true);
        $action = choice('action', ['start', 'approve', 'reject', 'ready', 'release']);
        $staffId = (int) $user['staff_id'];
        if ($action === 'start') {
            set_request_status($requestId, 'UNDER_REVIEW', $staffId, 'Registrar began review');
        } elseif ($action === 'reject') {
            if (!in_array($request['request_status'], ['UNDER_REVIEW', 'AWAITING_PAYMENT'], true)) {
                throw new DomainException('This request cannot be rejected at its current stage.');
            }
            $reason = required('rejection_reason', 1000);
            query("UPDATE document_requests SET eligibility_status='INELIGIBLE',
                reviewed_by_staff_id=?,reviewed_at=UTC_TIMESTAMP(6),rejection_reason=?
                WHERE request_id=?", [$staffId, $reason, $requestId]);
            set_request_status($requestId, 'REJECTED', $staffId, $reason);
        } elseif ($action === 'approve') {
            if ($request['request_status'] !== 'UNDER_REVIEW' || !isset($_POST['eligibility_confirmed'])) {
                throw new DomainException('Begin review and confirm student eligibility first.');
            }
            query("UPDATE document_requests SET eligibility_status='ELIGIBLE',
                reviewed_by_staff_id=?,reviewed_at=UTC_TIMESTAMP(6) WHERE request_id=?", [$staffId, $requestId]);
            $next = payment_ok($request) ? 'READY' : 'AWAITING_PAYMENT';
            set_request_status($requestId, $next, $staffId, 'Eligibility and clearance reviewed');
        } elseif ($action === 'ready') {
            set_request_status($requestId, 'READY', $staffId, 'All requirements satisfied');
        } else {
            set_request_status($requestId, 'RELEASED', $staffId, 'Registrar confirmed physical release');
        }
        return 'Request status updated.';
    });
}

function verify_payment(array $user, int $paymentId): string
{
    return transaction(function () use ($user, $paymentId): string {
        $payment = one('SELECT * FROM payments WHERE payment_id=?', [$paymentId]);
        if (!$payment) {
            throw new DomainException('Payment not found.');
        }
        $request = owned_request($user, (int)$payment['request_id'], true);
        $payment = one('SELECT * FROM payments WHERE payment_id=? FOR UPDATE', [$paymentId]);
        if ($payment['payment_status'] !== 'PENDING') {
            throw new DomainException('This payment already has a decision.');
        }
        $decision = choice('decision', ['VERIFIED', 'REJECTED']);
        $remarks = input('remarks');
        if (strlen($remarks) > 1000 || ($decision === 'REJECTED' && $remarks === '')) {
            field_error('remarks', 'Provide a rejection reason (up to 1000 characters).');
        }
        if ($decision === 'VERIFIED') {
            if (!isset($_POST['reference_confirmed'])) {
                field_error('reference_confirmed', 'Confirm the payment reference against institutional records.');
            }
            if ($request['request_status'] !== 'AWAITING_PAYMENT' || $request['eligibility_status'] !== 'ELIGIBLE' || $payment['amount'] !== $request['assessed_fee']) {
                throw new DomainException('The request or payment amount is not eligible for verification.');
            }
            if (one("SELECT payment_id FROM payments WHERE request_id=? AND payment_status='VERIFIED'", [$request['request_id']])) {
                throw new DomainException('A verified payment already exists for this request.');
            }
            if (!$payment['transaction_ref']) {
                throw new DomainException('A payment reference is required.');
            }
        }
        query('UPDATE payments SET payment_status=?,verified_by_staff_id=?,verified_at=UTC_TIMESTAMP(6),remarks=? WHERE payment_id=?', [$decision, $user['staff_id'], $remarks ?: null, $paymentId]);
        if ($decision === 'VERIFIED') {
            $receipt = 'CTU-' . gmdate('Y') . '-' . str_pad((string)$paymentId, 8, '0', STR_PAD_LEFT);
            query('INSERT INTO receipts (payment_id,receipt_number,issued_by_staff_id) VALUES (?,?,?)', [$paymentId, $receipt, $user['staff_id']]);
        }
        return $decision === 'VERIFIED' ? 'Payment verified and receipt issued. The Registrar can now mark the request READY.' : 'Payment rejected. The student can submit a corrected reference.';
    });
}
function request_details(array $user, int $id): array
{
    owned_request($user, $id);
    return one('SELECT r.*,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,s.student_number,d.document_name,t.academic_year,t.term_code,TRIM(CONCAT(f.first_name,CHAR(32),f.last_name)) AS reviewer FROM document_requests r JOIN students s ON s.student_id=r.student_id JOIN document_types d ON d.document_type_id=r.document_type_id JOIN academic_terms t ON t.term_id=r.term_id LEFT JOIN staff_users f ON f.staff_id=r.reviewed_by_staff_id WHERE r.request_id=?', [$id]);
}
