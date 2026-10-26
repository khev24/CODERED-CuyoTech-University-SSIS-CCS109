<?php
require_once __DIR__ . '/../includes/workflow.php';
$user = require_role('STUDENT');
handle_post(function () use ($user): string {
    return transaction(function () use ($user): string {
        require_student_scope($user, (int)$user['student_id'], true);
        $type = positive_id('document_type_id');
        $term = positive_id('term_id');
        create_document_request((int)$user['student_id'], $type, $term, required('purpose', 1000));
        return 'Document request submitted. The Registrar will review your eligibility and clearance.';
    });
}, 'student/document-requests.php');
$types = rows('SELECT * FROM document_types WHERE is_active=1 ORDER BY document_name');
$title = 'Request A University Document';
$subtitle = 'Choose a service and describe why you need it.';
require __DIR__ . '/../includes/header.php';
?>
<form method="post" class="panel">
    <?= csrf() ?>
    <div class="field-grid">
        <label>Document Service<select name="document_type_id" required>
                <option value="">Select document</option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= e($type['document_type_id']) ?>" <?= input('document_type_id') === (string)$type['document_type_id'] ? 'selected' : '' ?>>
                        <?= e($type['document_name'] . ' — ' . money($type['fee_amount']) . ($type['clearance_required'] ? ' · Clearance required' : '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Relevant Academic / Clearance Term<select name="term_id" required>
                <option value="">Select term</option>
                <?php options(terms(), 'term_id', 'label', input('term_id')); ?>
            </select>
        </label>
    </div>
    <label>Purpose<textarea name="purpose" required maxlength="1000" placeholder="Describe the intended use of this document.">
<?= e(input('purpose')) ?>
</textarea>
    </label>
    <p class="muted">The fee is fixed when you submit. Wait for the Registrar review before submitting a payment reference. This portal does not collect payments online.</p>
    <div class="actions">
        <button type="submit"><?= icon('check') ?> Submit Request</button>
        <a class="button secondary" href="<?= e(url('student/document-requests.php')) ?>">Cancel</a>
    </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>