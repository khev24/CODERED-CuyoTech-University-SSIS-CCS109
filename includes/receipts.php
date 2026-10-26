<?php
require_once __DIR__ . '/auth.php';
function receipt_record(array $user, int $id): array
{
    $receipt = one('SELECT z.*,p.request_id,p.amount,p.transaction_ref,p.verified_at,r.student_id,d.document_name,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,s.student_number,TRIM(CONCAT(f.first_name,CHAR(32),f.last_name)) AS issuer FROM receipts z JOIN payments p ON p.payment_id=z.payment_id JOIN document_requests r ON r.request_id=p.request_id JOIN document_types d ON d.document_type_id=r.document_type_id JOIN students s ON s.student_id=r.student_id JOIN staff_users f ON f.staff_id=z.issued_by_staff_id WHERE z.receipt_id=?', [$id]);
    if (!$receipt) {
        throw new DomainException('Receipt not found.');
    }
    require_student_scope($user, (int)$receipt['student_id']);
    return $receipt;
}
