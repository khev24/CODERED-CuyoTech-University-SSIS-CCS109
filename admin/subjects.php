<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('ADMIN');
handle_post(function (): string {
    $values = [required('subject_code', 30), required('subject_title', 180), decimal_input('units', false)];
    if ((float)$values[2] > 99.99) {
        throw new DomainException('Subject units must not exceed 99.99.');
    }
    if (input('edit_id') !== '') {
        if (!one('SELECT subject_id FROM subjects WHERE subject_id=?', [positive_id('edit_id')])) {
            throw new DomainException('This record no longer exists. Cancel editing and reload the list.');
        }
        $values[] = positive_id('edit_id');
        query('UPDATE subjects SET subject_code=?,subject_title=?,units=? WHERE subject_id=?', $values);
    } else {
        query('INSERT INTO subjects (subject_code,subject_title,units) VALUES (?,?,?)', $values);
    }
    return 'Subject saved.';
}, 'admin/subjects.php' . list_query());
$edit = input('edit') !== '' ? one('SELECT * FROM subjects WHERE subject_id=?', [positive_id('edit')]) : null;
if (input('edit') !== '' && !$edit) {
    abort_page(404, 'The selected record does not exist.');
}
$list = rows('SELECT * FROM subjects WHERE subject_code LIKE ? OR subject_title LIKE ? ORDER BY subject_code' . page_limit(), ['%' . input('q') . '%', '%' . input('q') . '%']);
pager($list);
$title = 'Subject Catalog';
require __DIR__ . '/../includes/header.php';
?>
<div class="management-grid">
    <div class="management-list">
        <form method="get" class="toolbar">
            <label>Search Subjects<input name="q" value="<?= e(input('q')) ?>">
            </label>
            <div class="toolbar-actions">
                <button type="submit"><?= icon('search') ?> Search</button>
            </div>
        </form>
        <section class="panel flush">
            <div class="panel-title">
                <h2>Subjects</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Subject</th>
                            <th>Units</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td>
                                    <?= e($row['subject_code']) ?>
                                </td>
                                <td>
                                    <?= e($row['subject_title']) ?>
                                </td>
                                <td>
                                    <?= e($row['units']) ?>
                                </td>
                                <td>
                                    <a class="button edit compact" href="<?= e(list_query(['edit' => $row['subject_id']])) ?>"><?= icon('edit') ?> Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$list): ?>
                            <tr>
                                <td colspan="4" class="empty">No subjects found.</td>
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
            <input type="hidden" name="edit_id" value="<?= e($edit['subject_id'] ?? '') ?>">
            <h2>
                <?= $edit ? 'Edit Subject' : 'Add Subject' ?>
            </h2>
            <div class="field-grid">
                <label>Code<input name="subject_code" required maxlength="30" value="<?= e(input('subject_code', (string)($edit['subject_code'] ?? ''))) ?>">
                </label>
                <label>Title<input name="subject_title" required maxlength="180" value="<?= e(input('subject_title', (string)($edit['subject_title'] ?? ''))) ?>">
                </label>
                <label>Units<input type="number" name="units" min="0.01" max="99.99" step="0.01" required value="<?= e(input('units', (string)($edit['units'] ?? ''))) ?>">
                </label>
            </div>
            <div class="actions"><button type="submit"><?= icon('check') ?> <?= $edit ? 'Update Subject' : 'Save Subject' ?></button> <?php if ($edit): ?>
                    <a class="button secondary" href="<?= e(url('admin/subjects.php' . list_query())) ?>">Cancel Editing</a>
                <?php else: ?>
                    <button type="reset" class="button secondary" data-clear-form>Clear Form</button>
                <?php endif; ?>
            </div>
        </form>
    </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>