<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('DEPARTMENT');
$search = '%' . input('q') . '%';
$list = rows('SELECT student_number,TRIM(CONCAT(first_name,CHAR(32),last_name)) AS full_name,academic_status FROM students WHERE department_id=? AND (TRIM(CONCAT(first_name,CHAR(32),last_name)) LIKE ? OR student_number LIKE ?) ORDER BY full_name' . page_limit(), [$user['department_id'], $search, $search]);
pager($list);
$title = 'Department Students';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" class="toolbar">
    <label>Search Students<input name="q" value="<?= e(input('q')) ?>">
    </label>
    <div class="toolbar-actions">
        <button type="submit"><?= icon('search') ?> Search</button>
        <a class="button secondary" href="<?= e(url('department/clearances.php')) ?>">Process clearance</a>
    </div>
</form>
<section class="panel flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>Full Name</th>
                    <th>Academic Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['student_number']) ?>
                        </td>
                        <td>
                            <?= e($row['full_name']) ?>
                        </td>
                        <td>
                            <?= badge($row['academic_status']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="3" class="empty">No matching department students.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>