<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('STUDENT');
$list = rows('SELECT c.*,d.department_name,t.academic_year,t.term_code FROM clearance_records c JOIN departments d ON d.department_id=c.department_id JOIN academic_terms t ON t.term_id=c.term_id WHERE c.student_id=? ORDER BY t.starts_on DESC' . page_limit(), [$user['student_id']]);
pager($list);
$title = 'My Clearance';
$subtitle = 'Department verification for each academic term. Missing clearance is not approved clearance.';
require __DIR__ . '/../includes/header.php';
?>
<section class="panel flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Term</th>
                    <th>Department</th>
                    <th>Decision</th>
                    <th>Verified</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['academic_year'] . ' / ' . $row['term_code']) ?>
                        </td>
                        <td>
                            <?= e($row['department_name']) ?>
                        </td>
                        <td>
                            <?= badge($row['clearance_status']) ?>
                        </td>
                        <td>
                            <?= e(date_label($row['verified_at'])) ?>
                        </td>
                        <td>
                            <?= e($row['remarks']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="5" class="empty">No clearance records yet. Contact your department for processing.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>