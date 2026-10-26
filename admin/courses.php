<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('ADMIN');
handle_post(function (): string {
    $values = [positive_id('department_id'), required('course_code', 30), required('course_title', 180), input('units') === '' ? null : decimal_input('units', false)];
    if ($values[3] !== null && (float)$values[3] > 9999.99) {
        throw new DomainException('Program units exceed the permitted size.');
    }
    if (input('edit_id') !== '') {
        if (!one('SELECT course_id FROM courses WHERE course_id=?', [positive_id('edit_id')])) {
            throw new DomainException('This record no longer exists. Cancel editing and reload the list.');
        }
        $values[] = positive_id('edit_id');
        query('UPDATE courses SET department_id=?,course_code=?,course_title=?,units=? WHERE course_id=?', $values);
    } else {
        query('INSERT INTO courses (department_id,course_code,course_title,units) VALUES (?,?,?,?)', $values);
    }
    return 'Course saved.';
}, 'admin/courses.php' . list_query());
$edit = input('edit') !== '' ? one('SELECT * FROM courses WHERE course_id=?', [positive_id('edit')]) : null;
$departments = rows('SELECT * FROM departments ORDER BY department_name');
if (input('edit') !== '' && !$edit) {
    abort_page(404, 'The selected record does not exist.');
}
$list = rows('SELECT c.*,d.department_code FROM courses c JOIN departments d ON d.department_id=c.department_id WHERE c.course_code LIKE ? OR c.course_title LIKE ? ORDER BY c.course_code' . page_limit(), ['%' . input('q') . '%', '%' . input('q') . '%']);
pager($list);
$title = 'Courses & Programs';
$subtitle = 'Degree programs selected during enrollment; individual classes belong in Subjects.';
require __DIR__ . '/../includes/header.php';
?>
<div class="management-grid">
    <div class="management-list">
        <form method="get" class="toolbar">
            <label>Search Programs<input name="q" value="<?= e(input('q')) ?>">
            </label>
            <div class="toolbar-actions">
                <button type="submit"><?= icon('search') ?> Search</button>
            </div>
        </form>
        <section class="panel flush">
            <div class="panel-title">
                <h2>Courses</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Units</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td>
                                    <?= e($row['course_code']) ?>
                                </td>
                                <td>
                                    <?= e($row['course_title']) ?>
                                </td>
                                <td>
                                    <?= e($row['department_code']) ?>
                                </td>
                                <td>
                                    <?= e($row['units'] ?? 'Not specified') ?>
                                </td>
                                <td>
                                    <a class="button edit compact" href="<?= e(list_query(['edit' => $row['course_id']])) ?>"><?= icon('edit') ?> Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$list): ?>
                            <tr>
                                <td colspan="5" class="empty">No programs found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php pagination(); ?>
    </div>
    <aside class="management-editor" aria-label="Record Editor">
        <form method="post" class="panel" <?= $edit ? 'data-confirm="Save changes to this catalog record?"' : '' ?>>
            <?= csrf() ?>
            <input type="hidden" name="edit_id" value="<?= e($edit['course_id'] ?? '') ?>">
            <h2>
                <?= $edit ? 'Edit Program' : 'Add Program' ?>
            </h2>
            <div class="field-grid">
                <label>Code<input name="course_code" required maxlength="30" value="<?= e(input('course_code', (string)($edit['course_code'] ?? ''))) ?>">
                </label>
                <label>Title<input name="course_title" required maxlength="180" value="<?= e(input('course_title', (string)($edit['course_title'] ?? ''))) ?>">
                </label>
                <label>Department<select name="department_id" required>
                        <option value="">Select department</option>
                        <?php options($departments, 'department_id', 'department_name', $edit['department_id'] ?? ''); ?>
                    </select>
                </label>
                <label>Prescribed Program Units (optional)<input type="number" min="0.01" max="9999.99" step="0.01" name="units" value="<?= e(input('units', (string)($edit['units'] ?? ''))) ?>">
                </label>
            </div>
            <div class="actions"><button type="submit"><?= icon('check') ?> <?= $edit ? 'Update Program' : 'Save Program' ?></button> <?php if ($edit): ?>
                    <a class="button secondary" href="<?= e(url('admin/courses.php' . list_query())) ?>">Cancel Editing</a>
                <?php else: ?>
                    <button type="reset" class="button secondary" data-clear-form>Clear Form</button>
                <?php endif; ?>
            </div>
        </form>
    </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>