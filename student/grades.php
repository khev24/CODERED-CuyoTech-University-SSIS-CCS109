<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('STUDENT');
$params = [$user['student_id']];
$filter = '';
if (input('term_id') !== '') {
    $filter = ' AND e.term_id=?';
    $params[] = positive_id('term_id');
}
$list = rows('SELECT b.subject_code,b.subject_title,t.academic_year,t.term_code,g.grade_value,g.remarks,g.updated_at,es.subject_status FROM enrollments e JOIN enrollment_subjects es ON es.enrollment_id=e.enrollment_id JOIN subjects b ON b.subject_id=es.subject_id JOIN academic_terms t ON t.term_id=e.term_id LEFT JOIN grades g ON g.enrollment_subject_id=es.enrollment_subject_id WHERE e.student_id=?' . $filter . ' ORDER BY t.starts_on DESC,b.subject_code' . page_limit(), $params);
pager($list);
$title = 'My Grades';
$subtitle = 'Subject results encoded by the Registrar. Unencoded results are not zero grades.';
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
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Term</th>
                    <th>Grade</th>
                    <th>Subject Enrollment Status</th>
                    <th>Remarks</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['subject_code']) ?>
                        </td>
                        <td><?= e($row['subject_title']) ?>
                        </td>
                        <td>
                            <?= e($row['academic_year'] . ' / ' . $row['term_code']) ?>
                        </td>
                        <td>
                            <strong>
                                <?= e($row['grade_value'] ?? 'Not yet encoded') ?>
                            </strong>
                        </td>
                        <td><?= badge($row['subject_status']) ?></td>
                        <td>
                            <?= e($row['remarks']) ?>
                        </td>
                        <td>
                            <?= e(date_label($row['updated_at'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="7" class="empty">No subject records for this selection.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>