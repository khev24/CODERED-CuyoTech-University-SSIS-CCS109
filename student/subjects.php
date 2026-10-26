<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('STUDENT');
$params = [$user['student_id']];
$filter = '';
if (input('term_id') !== '') {
    $filter = ' AND e.term_id=?';
    $params[] = positive_id('term_id');
}
$list = rows("SELECT b.subject_code,b.subject_title,b.units,es.subject_status,t.academic_year,t.term_code FROM enrollments e JOIN enrollment_subjects es ON es.enrollment_id=e.enrollment_id JOIN subjects b ON b.subject_id=es.subject_id JOIN academic_terms t ON t.term_id=e.term_id WHERE e.student_id=? AND e.enrollment_status IN ('ENROLLED','COMPLETED')" . $filter . ' ORDER BY t.starts_on DESC,b.subject_code' . page_limit(), $params);
pager($list);
$title = 'Enrolled Subjects';
require __DIR__ . '/../includes/header.php';
?>
<form class="toolbar" method="get">
    <label>Academic Term<select name="term_id">
            <option value="">All terms</option>
            <?php options(terms(), 'term_id', 'label', input('term_id')); ?>
        </select>
    </label>
    <div class="toolbar-actions">
        <button type="submit" class="secondary"><?= icon('search') ?> Apply Filter</button>
    </div>
</form>
<section class="panel flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Units</th>
                    <th>Academic Term</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['subject_code']) ?>
                            <span class="muted">
                                <?= e($row['subject_title']) ?>
                            </span>
                        </td>
                        <td>
                            <?= e($row['units']) ?>
                        </td>
                        <td>
                            <?= e($row['academic_year'] . ' / ' . $row['term_code']) ?>
                        </td>
                        <td>
                            <?= badge($row['subject_status']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="4" class="empty">No enrolled subjects for this selection.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>