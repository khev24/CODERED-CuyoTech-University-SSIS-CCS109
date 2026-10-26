<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('ADMIN');
handle_post(function (): string {
    $code = required('department_code', 20);
    $name = required('department_name', 150);
    if (input('edit_id') !== '') {
        if (!one('SELECT department_id FROM departments WHERE department_id=?', [positive_id('edit_id')])) {
            throw new DomainException('This record no longer exists. Cancel editing and reload the list.');
        }
        query('UPDATE departments SET department_code=?,department_name=? WHERE department_id=?', [$code, $name, positive_id('edit_id')]);
    } else {
        query('INSERT INTO departments (department_code,department_name) VALUES (?,?)', [$code, $name]);
    }
    return 'Department saved.';
}, 'admin/departments.php' . list_query());
$edit = input('edit') !== '' ? one('SELECT * FROM departments WHERE department_id=?', [positive_id('edit')]) : null;
if (input('edit') !== '' && !$edit) {
    abort_page(404, 'The selected record does not exist.');
}
$list = rows('SELECT * FROM departments WHERE department_name LIKE ? OR department_code LIKE ? ORDER BY department_code' . page_limit(), ['%' . input('q') . '%', '%' . input('q') . '%']);
pager($list);
$title = 'Departments';
require __DIR__ . '/../includes/header.php';
?>
<div class="management-grid">
    <div class="management-list">
        <form method="get" class="toolbar">
            <label>Search<input name="q" value="<?= e(input('q')) ?>">
            </label>
            <div class="toolbar-actions">
                <button type="submit"><?= icon('search') ?> Search</button>
            </div>
        </form>
        <section class="panel flush">
            <div class="panel-title">
                <h2>Departments</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td>
                                    <?= e($row['department_code']) ?>
                                </td>
                                <td>
                                    <?= e($row['department_name']) ?>
                                </td>
                                <td>
                                    <a class="button edit compact" href="<?= e(list_query(['edit' => $row['department_id']])) ?>"><?= icon('edit') ?> Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$list): ?>
                            <tr>
                                <td colspan="3" class="empty">No departments found.</td>
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
            <input type="hidden" name="edit_id" value="<?= e($edit['department_id'] ?? '') ?>">
            <h2>
                <?= $edit ? 'Edit Department' : 'Add Department' ?>
            </h2>
            <div class="field-grid">
                <label>Code<input name="department_code" required maxlength="20" value="<?= e(input('department_code', (string)($edit['department_code'] ?? ''))) ?>">
                </label>
                <label>Name<input name="department_name" required maxlength="150" value="<?= e(input('department_name', (string)($edit['department_name'] ?? ''))) ?>">
                </label>
            </div>
            <div class="actions"><button type="submit"><?= icon('check') ?> <?= $edit ? 'Update Department' : 'Save Department' ?></button> <?php if ($edit): ?>
                    <a class="button secondary" href="<?= e(url('admin/departments.php' . list_query())) ?>">Cancel Editing</a>
                <?php else: ?>
                    <button type="reset" class="button secondary" data-clear-form>Clear Form</button>
                <?php endif; ?>
            </div>
        </form>
    </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>