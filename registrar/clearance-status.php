<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('REGISTRAR');
[$scope, $params] = scope_sql($user);
$search = '%' . input('q') . '%';
$params = array_merge($params, [$search, $search]);
$list = rows('SELECT c.*,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,s.student_number,d.department_name,t.academic_year,t.term_code,TRIM(CONCAT(f.first_name,CHAR(32),f.last_name)) AS verifier FROM clearance_records c JOIN students s ON s.student_id=c.student_id JOIN departments d ON d.department_id=c.department_id JOIN academic_terms t ON t.term_id=c.term_id LEFT JOIN staff_users f ON f.staff_id=c.verified_by_staff_id WHERE ' . $scope . ' AND (TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) LIKE ? OR s.student_number LIKE ?) ORDER BY t.starts_on DESC,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name))' . page_limit(), $params);
pager($list);
$title = 'Clearance Status';
$subtitle = 'Read department decisions before approving document requirements.';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" class="toolbar">
    <label>Student<input name="q" value="<?= e(input('q')) ?>">
    </label>
    <div class="toolbar-actions">
        <button type="submit">Find Clearance</button>
    </div>
</form>
<section class="panel flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Department</th>
                    <th>Academic Term</th>
                    <th>Decision</th>
                    <th>Verifier</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['full_name']) ?>
                            <span class="muted">
                                <?= e($row['student_number']) ?>
                            </span>
                        </td>
                        <td>
                            <?= e($row['department_name']) ?>
                        </td>
                        <td>
                            <?= e($row['academic_year'] . ' / ' . $row['term_code']) ?>
                        </td>
                        <td>
                            <?= badge($row['clearance_status']) ?>
                        </td>
                        <td>
                            <?= e($row['verifier']) ?>
                            <span class="muted">
                                <?= e(date_label($row['verified_at'])) ?>
                            </span>
                        </td>
                        <td>
                            <?= e($row['remarks']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="6" class="empty">No clearance decisions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>