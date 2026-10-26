<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('REGISTRAR');
// Explicit entity name prevents confusing enrollment IDs with subject IDs.
$id = positive_id('enrollment_id');
$enrollment = one('SELECT e.*,s.student_number,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,
    c.course_code,c.course_title,t.academic_year,t.term_code
    FROM enrollments e JOIN students s ON s.student_id=e.student_id
    JOIN courses c ON c.course_id=e.course_id JOIN academic_terms t ON t.term_id=e.term_id
    WHERE e.enrollment_id=?', [$id]);
if (!$enrollment) abort_page(404, 'Enrollment not found.');
require_student_scope($user, (int)$enrollment['student_id']);

handle_post(function () use ($user, $id, $enrollment): string {
    return transaction(function () use ($user, $id, $enrollment): string {
        require_student_scope($user, (int)$enrollment['student_id'], true);
        $current = one('SELECT enrollment_status FROM enrollments WHERE enrollment_id=? FOR UPDATE', [$id]);
        if (!$current || !in_array($current['enrollment_status'], ['ENROLLED', 'COMPLETED'], true)) {
            throw new DomainException('Grade entry requires an enrolled or completed enrollment.');
        }
        $subjects = rows('SELECT enrollment_subject_id,subject_status FROM enrollment_subjects WHERE enrollment_id=? FOR UPDATE', [$id]);
        $eligible = [];
        foreach ($subjects as $subject) {
            if ($subject['subject_status'] !== 'DROPPED') $eligible[(int)$subject['enrollment_subject_id']] = true;
        }
        $grades = $_POST['grades'] ?? [];
        $remarks = $_POST['remarks'] ?? [];
        if (!is_array($grades) || !is_array($remarks)) throw new DomainException('Invalid grade submission. Reload the form.');
        // Reject unrelated, dropped, malformed and altered subject identifiers, even when blank.
        foreach ([$grades, $remarks] as $values) {
            foreach ($values as $subjectId => $value) {
                if (
                    !preg_match('/^[1-9][0-9]*$/D', (string)$subjectId) || (string)(int)$subjectId !== (string)$subjectId
                    || !isset($eligible[(int)$subjectId]) || !is_string($value)
                ) {
                    throw new DomainException('A submitted subject is invalid, dropped, or does not belong to this enrollment. Reload the form.');
                }
            }
        }
        $updates = [];
        $entered = 0;
        $allowed = config()['grade_values'];
        foreach ($eligible as $subjectId => $_) {
            $value = trim($grades[$subjectId] ?? '');
            if ($value === '') continue; // Blank never clears a saved grade or its remarks.
            if (
                strlen($value) > 12 || ($allowed && !in_array($value, $allowed, true))
                || (!$allowed && !preg_match('/^(?:[0-9]{1,3}(?:\.[0-9]{1,2})?|[A-Z][A-Z0-9+\-]{0,11})$/D', $value))
            ) {
                field_error('grades[' . $subjectId . ']', 'Use an approved grade (up to 12 characters), numeric value or uppercase result code.');
            }
            $note = isset($remarks[$subjectId]) ? trim($remarks[$subjectId]) : null;
            if ($note !== null && strlen($note) > 500) {
                field_error('remarks[' . $subjectId . ']', 'Remarks must be at most 500 characters.');
            }
            $entered++;
            $old = one('SELECT grade_value,remarks FROM grades WHERE enrollment_subject_id=?', [$subjectId]);
            $note = $note ?? ($old['remarks'] ?? null);
            if ($old && $old['grade_value'] === $value && (string)($old['remarks'] ?? '') === (string)($note ?? '')) {
                continue; // Keep encoding and modification metadata for unchanged records.
            }
            $updates[] = [$subjectId, $value, $note];
        }
        if (!$entered) throw new DomainException('No grades entered. Please enter at least one grade before saving.');
        if (!$updates) throw new DomainException('No changes to save. The entered grades and remarks match the saved records.');
        if ($entered < count($eligible) && input('partial_confirmed') !== '1') {
            throw new DomainException('Some grades are blank. Confirm Save Entered Grades to continue; blank fields will remain unchanged.');
        }
        // Validation of the whole batch finishes before any write.
        foreach ($updates as [$subjectId, $value, $note]) {
            query(
                'INSERT INTO grades (enrollment_subject_id,grade_value,remarks,encoded_by_staff_id,updated_by_staff_id)
                VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE grade_value=?,remarks=?,updated_by_staff_id=?,updated_at=UTC_TIMESTAMP(6)',
                [$subjectId, $value, $note ?: null, $user['staff_id'], $user['staff_id'], $value, $note ?: null, $user['staff_id']]
            );
        }
        return count($updates) . ' grade(s) saved. Blank grade fields were left unchanged.';
    });
}, 'registrar/grade-entry.php' . list_query(['enrollment_id' => $id]));

$subjects = rows('SELECT es.enrollment_subject_id,es.subject_status,b.subject_code,b.subject_title,b.units,
    g.grade_value,g.remarks,g.updated_at FROM enrollment_subjects es JOIN subjects b ON b.subject_id=es.subject_id
    LEFT JOIN grades g ON g.enrollment_subject_id=es.enrollment_subject_id
    WHERE es.enrollment_id=? ORDER BY b.subject_code', [$id]);
$editable = in_array($enrollment['enrollment_status'], ['ENROLLED', 'COMPLETED'], true);
$canSave = $editable && count(array_filter($subjects, fn($s) => $s['subject_status'] !== 'DROPPED')) > 0;
$title = 'Enrollment Grades';
$subtitle = $enrollment['student_number'] . ' · ' . $enrollment['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <dl>
        <dt>Program</dt>
        <dd><?= e($enrollment['course_code'] . ' · ' . $enrollment['course_title']) ?></dd>
        <dt>Academic Term</dt>
        <dd><?= e($enrollment['academic_year'] . ' / ' . $enrollment['term_code']) ?></dd>
        <dt>Enrollment Status</dt>
        <dd><?= badge($enrollment['enrollment_status']) ?></dd>
    </dl>
</section>
<?php if (!$editable): ?>
    <div class="notice">Grades are read-only until this enrollment is Enrolled or Completed.</div>
<?php endif; ?>
<form method="post" class="panel flush" data-bulk-grades data-confirm="Save the entered grades for this enrollment?" data-confirm-label="Save Grades" data-confirm-title="Save Grades?">
    <?= csrf() ?>
    <input type="hidden" name="partial_confirmed" value="0">
    <div class="panel-title">
        <h2>Subject Grades</h2><span class="muted">Blank grades preserve saved values and remarks. To edit remarks, keep the saved grade selected.</span>
    </div>
    <div class="table-wrap">
        <table class="grade-table wide-table">
            <thead>
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Units</th>
                    <th>Status</th>
                    <th>Saved Grade</th>
                    <th>Grade Entry</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject):
                    $subjectId = (int)$subject['enrollment_subject_id'];
                    $value = isset($_POST['grades']) && is_array($_POST['grades']) && array_key_exists($subjectId, $_POST['grades'])
                        ? (is_string($_POST['grades'][$subjectId]) ? $_POST['grades'][$subjectId] : '') : ($subject['grade_value'] ?? '');
                    $note = isset($_POST['remarks']) && is_array($_POST['remarks']) && array_key_exists($subjectId, $_POST['remarks'])
                        ? (is_string($_POST['remarks'][$subjectId]) ? $_POST['remarks'][$subjectId] : '') : ($subject['remarks'] ?? '');
                    $gradeError = $GLOBALS['field_errors']['grades[' . $subjectId . ']'] ?? '';
                    $noteError = $GLOBALS['field_errors']['remarks[' . $subjectId . ']'] ?? '';
                ?>
                    <tr>
                        <td><?= e($subject['subject_code']) ?></td>
                        <td><?= e($subject['subject_title']) ?></td>
                        <td><?= e($subject['units']) ?></td>
                        <td><?= badge($subject['subject_status']) ?></td>
                        <td><?= e($subject['grade_value'] ?? 'Not encoded') ?><span class="muted"><?= e(date_label($subject['updated_at'])) ?></span></td>
                        <?php if ($editable && $subject['subject_status'] !== 'DROPPED'): ?>
                            <td>
                                <label class="sr-only" for="grade-<?= $subjectId ?>">Grade for <?= e($subject['subject_code']) ?></label>
                                <?php if (config()['grade_values']): ?>
                                    <select data-grade data-saved-grade="<?= e($subject['grade_value'] ?? '') ?>" data-saved-remarks="<?= e($subject['remarks'] ?? '') ?>" id="grade-<?= $subjectId ?>" name="grades[<?= $subjectId ?>]" <?= $gradeError ? 'aria-invalid="true" aria-describedby="grade-error-' . $subjectId . '"' : '' ?>>
                                        <option value="">Leave unchanged</option>
                                        <?php foreach (config()['grade_values'] as $option): ?>
                                            <option value="<?= e($option) ?>" <?= $value === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input data-grade data-saved-grade="<?= e($subject['grade_value'] ?? '') ?>" data-saved-remarks="<?= e($subject['remarks'] ?? '') ?>" id="grade-<?= $subjectId ?>" name="grades[<?= $subjectId ?>]" maxlength="12" value="<?= e($value) ?>" placeholder="Leave unchanged" <?= $gradeError ? 'aria-invalid="true" aria-describedby="grade-error-' . $subjectId . '"' : '' ?>>
                                <?php endif; ?>
                                <?php if ($gradeError): ?><span class="field-error" id="grade-error-<?= $subjectId ?>"><?= e($gradeError) ?></span><?php endif; ?>
                            </td>
                            <td>
                                <label class="sr-only" for="remarks-<?= $subjectId ?>">Remarks for <?= e($subject['subject_code']) ?></label>
                                <input class="grade-remarks" id="remarks-<?= $subjectId ?>" name="remarks[<?= $subjectId ?>]" maxlength="500" value="<?= e($note) ?>" <?= $noteError ? 'aria-invalid="true" aria-describedby="remarks-error-' . $subjectId . '"' : '' ?>>
                                <?php if ($noteError): ?><span class="field-error" id="remarks-error-<?= $subjectId ?>"><?= e($noteError) ?></span><?php endif; ?>
                            </td>
                        <?php else: ?><td>Read-only</td>
                            <td><?= e($subject['remarks']) ?></td><?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$subjects): ?><tr>
                        <td colspan="7" class="empty">No subjects are assigned to this enrollment.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <noscript>
        <p class="notice"><label class="check"><input type="checkbox" name="partial_confirmed" value="1">Confirm saving available grades. Blank fields retain their existing grade and remarks.</label></p>
    </noscript>
    <div class="form-actions">
        <?php if ($canSave): ?><button type="submit"><?= icon('check') ?> Save Grades</button><?php endif; ?>
        <a class="button secondary" href="<?= e(url('registrar/grades.php' . list_query())) ?>">Cancel / Grade Records</a>
    </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>