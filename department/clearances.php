<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('DEPARTMENT');
handle_post(function () use ($user): string {
    return transaction(function () use ($user): string {
        $studentId = positive_id('student_id');
        require_student_scope($user, $studentId, true);
        $termId = positive_id('term_id');
        if (one('SELECT clearance_id FROM clearance_records WHERE student_id=? AND department_id=? AND term_id=?', [$studentId, $user['department_id'], $termId])) {
            field_error('term_id', 'A clearance record already exists for this student, department and term. Open Review instead.');
        }
        query('INSERT INTO clearance_records (student_id,department_id,term_id) VALUES (?,?,?)', [$studentId, $user['department_id'], $termId]);
        return 'Clearance record created. Open the record to enter your decision.';
    });
}, 'department/clearances.php');
$students = rows("SELECT student_id,CONCAT(student_number,' · ',TRIM(CONCAT(first_name,CHAR(32),last_name))) AS label FROM students WHERE department_id=? ORDER BY first_name,last_name", [$user['department_id']]);
$search = '%' . input('q') . '%';
$params = [$user['department_id'], $user['department_id'], $search, $search];
$filter = '';
if (input('status') !== '') {
    $filter = ' AND c.clearance_status=?';
    $params[] = choice('status', ['PENDING', 'CLEARED', 'NOT_CLEARED']);
}
$list = rows('SELECT c.*,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,s.student_number,t.academic_year,t.term_code FROM clearance_records c JOIN students s ON s.student_id=c.student_id JOIN academic_terms t ON t.term_id=c.term_id WHERE c.department_id=? AND s.department_id=? AND (TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) LIKE ? OR s.student_number LIKE ?)' . $filter . ' ORDER BY t.starts_on DESC,c.clearance_id DESC' . page_limit(), $params);
pager($list);
$title = 'Clearance Processing';
$subtitle = 'Term-specific decisions for students assigned to your department.';
require __DIR__ . '/../includes/header.php';
?>
<div class="management-grid">
    <div class="management-list">
        <form method="get" class="toolbar">
            <label>Student<input name="q" value="<?= e(input('q')) ?>">
            </label>
            <label>Status<select name="status">
                    <option value="">All statuses</option>
                    <?php foreach (['PENDING', 'CLEARED', 'NOT_CLEARED'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= input('status') === $status ? 'selected' : '' ?>>
                            <?= e(ui_label($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="toolbar-actions">
                <button type="submit"><?= icon('search') ?> Search Clearances</button>
            </div>
        </form>
        <section class="panel flush">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Academic Term</th>
                            <th>Status</th>
                            <th>Last Verified</th>
                            <th>Manage</th>
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
                                    <?= e($row['academic_year'] . ' / ' . $row['term_code']) ?>
                                </td>
                                <td>
                                    <?= badge($row['clearance_status']) ?>
                                </td>
                                <td>
                                    <?= e(date_label($row['verified_at'])) ?>
                                </td>
                                <td>
                                    <a class="button secondary compact" href="<?= e(url('department/clearance-details.php?id=' . $row['clearance_id'])) ?>">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$list): ?>
                            <tr>
                                <td colspan="5" class="empty">No matching clearance records.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php pagination(); ?>
    </div>
    <aside class="management-editor" aria-label="Create Clearance">
        <section class="panel">
            <h2>Create Clearance</h2>
            <p class="muted">Create a pending record, then open Review to record your decision.</p>
            <form method="post" class="section-gap">
                <?= csrf() ?>
                <div class="field-grid">
                    <label>Student<select name="student_id" required>
                            <option value="">Select student</option>
                            <?php options($students, 'student_id', 'label'); ?>
                        </select>
                    </label>
                    <label>Academic Term<select name="term_id" required>
                            <option value="">Select term</option>
                            <?php options(terms(), 'term_id', 'label'); ?>
                        </select>
                    </label>
                </div>
                <button type="submit"><?= icon('add') ?> Create Pending Clearance</button>
            </form>
        </section>
    </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>