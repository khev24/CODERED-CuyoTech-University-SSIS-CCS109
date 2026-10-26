<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('REGISTRAR');
[$scope, $params] = scope_sql($user);
$search = '%' . input('q') . '%';
$params = array_merge($params, [$search, $search]);
$filter = '';
if (input('term_id') !== '') {
    $filter = ' AND e.term_id=?';
    $params[] = positive_id('term_id');
}
// The parent is enrollment. Correlated counts cannot multiply its rows or pagination.
$list = rows('SELECT e.enrollment_id,e.enrollment_status,s.student_number,
    TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,
    c.course_code,c.course_title,t.academic_year,t.term_code,
    (SELECT COUNT(*) FROM enrollment_subjects es WHERE es.enrollment_id=e.enrollment_id) AS subject_count,
    (SELECT COUNT(*) FROM enrollment_subjects es WHERE es.enrollment_id=e.enrollment_id AND es.subject_status<>\'DROPPED\') AS eligible_count,
    (SELECT COUNT(*) FROM enrollment_subjects es JOIN grades g ON g.enrollment_subject_id=es.enrollment_subject_id
        WHERE es.enrollment_id=e.enrollment_id AND es.subject_status<>\'DROPPED\') AS encoded_count
    FROM enrollments e JOIN students s ON s.student_id=e.student_id
    JOIN courses c ON c.course_id=e.course_id JOIN academic_terms t ON t.term_id=e.term_id
    WHERE ' . $scope . ' AND (TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) LIKE ? OR s.student_number LIKE ?)' . $filter . '
    ORDER BY t.starts_on DESC,s.last_name,s.first_name,e.enrollment_id DESC' . page_limit(), $params);
pager($list);
$title = 'Grade Records';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" class="toolbar">
    <label>Student
        <input name="q" value="<?= e(input('q')) ?>" placeholder="Name or student number">
    </label>
    <label>Academic Term
        <select name="term_id">
            <option value="">All terms</option>
            <?php options(terms(), 'term_id', 'label', input('term_id')); ?>
        </select>
    </label>
    <div class="toolbar-actions">
        <button type="submit"><?= icon('search') ?> Search Records</button>
    </div>
</form>
<section class="panel flush">
    <div class="table-wrap">
        <table class="wide-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Program</th>
                    <th>Academic Term</th>
                    <th>Enrollment Status</th>
                    <th>Subjects</th>
                    <th>Grade Progress</th>
                    <th>Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td><?= e($row['full_name']) ?><span class="muted"><?= e($row['student_number']) ?></span></td>
                        <td><?= e($row['course_code']) ?><span class="muted"><?= e($row['course_title']) ?></span></td>
                        <td><?= e($row['academic_year'] . ' / ' . $row['term_code']) ?></td>
                        <td><?= badge($row['enrollment_status']) ?></td>
                        <td><?= e($row['subject_count']) ?></td>
                        <td><strong><?= e($row['encoded_count']) ?> / <?= e($row['eligible_count']) ?></strong> encoded<span class="muted">Excludes dropped subjects</span></td>
                        <td><a class="button edit compact" href="<?= e(url('registrar/grade-entry.php' . list_query(['enrollment_id' => $row['enrollment_id']]))) ?>"><?= icon('edit') ?> Encode / Update Grades</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="7" class="empty">No enrollments match this selection.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>