<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('DEPARTMENT');
if (!$user['department_id'] || $user['access_scope'] !== 'DEPARTMENT') {
    abort_page(403, 'Your department assignment is not configured.');
}
$department = one('SELECT department_name FROM departments WHERE department_id=?', [$user['department_id']]);
$students = scalar('SELECT COUNT(*) FROM students WHERE department_id=?', [$user['department_id']]);
$counts = one("SELECT SUM(c.clearance_status='PENDING') AS pending,SUM(c.clearance_status='CLEARED') AS cleared FROM clearance_records c JOIN students s ON s.student_id=c.student_id WHERE c.department_id=? AND s.department_id=?", [$user['department_id'], $user['department_id']]);
$queue = rows("SELECT c.clearance_id,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,s.student_number,t.academic_year,t.term_code FROM clearance_records c JOIN students s ON s.student_id=c.student_id JOIN academic_terms t ON t.term_id=c.term_id WHERE c.department_id=? AND s.department_id=? AND c.clearance_status='PENDING' ORDER BY c.clearance_id LIMIT 8", [$user['department_id'], $user['department_id']]);
$title = 'Department Desk';
$subtitle = $department['department_name'];
require __DIR__ . '/../includes/header.php';
?>
<section class="stats">
    <article class="stat">
        <small><?= icon('users') ?> Department students</small>
        <strong>
            <?= e($students) ?>
        </strong>
        <p>Assigned to your department</p>
    </article>
    <article class="stat">
        <small><?= icon('check') ?> Pending clearances</small>
        <strong>
            <?= e($counts['pending'] ?? 0) ?>
        </strong>
        <p>Awaiting a department decision</p>
    </article>
    <article class="stat">
        <small><?= icon('check') ?> Cleared records</small>
        <strong>
            <?= e($counts['cleared'] ?? 0) ?>
        </strong>
        <p>Across recorded academic terms</p>
    </article>
</section>
<section class="panel">
    <h2>Keep Student Clearance Moving</h2>
    <p class="muted">Create a clearance record for a student and academic term, review their outstanding requirements, and record your decision.</p>
    <a class="button" href="<?= e(url('department/clearances.php')) ?>">Process clearances</a>
</section>
<section class="panel flush">
    <div class="panel-title">
        <h2>Pending Clearance Decisions</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Academic Term</th>
                    <th>Review</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($queue as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['full_name']) ?>
                            <span class="muted">
                                <?= e($row['student_number']) ?>
                            </span>
                        </td>
                        <td>
                            <?= e($row['academic_year'] . ' / ' . $row['term_code']) ?>
                        </td>
                        <td>
                            <a class="button secondary compact" href="<?= e(url('department/clearance-details.php?id=' . $row['clearance_id'])) ?>">Review clearance</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$queue): ?>
                    <tr>
                        <td colspan="3" class="empty">No pending clearances in your department.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>