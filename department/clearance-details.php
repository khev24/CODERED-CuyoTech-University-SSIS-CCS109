<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('DEPARTMENT');
$id = positive_id();
$clearance = one('SELECT c.*,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,s.student_number,t.academic_year,t.term_code,TRIM(CONCAT(f.first_name,CHAR(32),f.last_name)) AS verifier FROM clearance_records c JOIN students s ON s.student_id=c.student_id JOIN academic_terms t ON t.term_id=c.term_id LEFT JOIN staff_users f ON f.staff_id=c.verified_by_staff_id WHERE c.clearance_id=? AND c.department_id=?', [$id, $user['department_id']]);
if (!$clearance) {
    abort_page(404, 'Clearance not found in your department.');
}
require_student_scope($user, (int)$clearance['student_id']);
handle_post(function () use ($user, $clearance, $id): string {
    return transaction(function () use ($user, $clearance, $id): string {
        require_student_scope($user, (int)$clearance['student_id'], true);
        $current = one('SELECT * FROM clearance_records WHERE clearance_id=? AND department_id=? FOR UPDATE', [$id, $user['department_id']]);
        if (!$current) {
            throw new DomainException('Clearance is no longer available.');
        }
        $decision = choice('clearance_status', ['CLEARED', 'NOT_CLEARED']);
        $remarks = input('remarks');
        if (strlen($remarks) > 500 || ($decision === 'NOT_CLEARED' && $remarks === '')) {
            throw new DomainException('Record the outstanding requirement or rejection reason, up to 500 characters.');
        }
        query('UPDATE clearance_records SET clearance_status=?,verified_by_staff_id=?,verified_at=UTC_TIMESTAMP(6),remarks=? WHERE clearance_id=? AND department_id=?', [$decision, $user['staff_id'], $remarks ?: null, $id, $user['department_id']]);
        return 'Department clearance decision saved.';
    });
}, 'department/clearance-details.php?id=' . $id);
$title = 'Clearance #' . $id;
$subtitle = $clearance['student_number'] . ' · ' . $clearance['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <dl>
        <dt>Academic Term</dt>
        <dd>
            <?= e($clearance['academic_year'] . ' / ' . $clearance['term_code']) ?>
        </dd>
        <dt>Current Decision</dt>
        <dd>
            <?= badge($clearance['clearance_status']) ?>
        </dd>
        <dt>Last Verifier</dt>
        <dd>
            <?= e($clearance['verifier'] ?: 'Not yet reviewed') ?>
        </dd>
        <dt>Last Verification</dt>
        <dd>
            <?= e(date_label($clearance['verified_at'])) ?>
        </dd>
        <dt>Remarks</dt>
        <dd>
            <?= e($clearance['remarks']) ?>
        </dd>
    </dl>
</section>
<form method="post" class="panel" data-confirm="Save this department clearance decision?">
    <?= csrf() ?>
    <h2>Record Department Decision</h2>
    <label>Decision<select name="clearance_status">
            <option value="CLEARED">Cleared</option>
            <option value="NOT_CLEARED" <?= $clearance['clearance_status'] === 'NOT_CLEARED' ? 'selected' : '' ?>>Not cleared</option>
        </select>
    </label>
    <label>Remarks / Outstanding Requirements<textarea name="remarks" maxlength="500">
<?= e(input('remarks', $clearance['remarks'] ?? '')) ?>
</textarea>
    </label>
    <button type="submit"><?= icon('check') ?> Save Clearance Decision</button>
</form>
<p class="muted">This record retains its most recent verification. The existing database does not store every clearance revision.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>