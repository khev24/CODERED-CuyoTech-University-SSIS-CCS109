<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('ADMIN');
handle_post(function (): string {
    $year = required('academic_year', 9);
    if (!preg_match('/^(\d{4})-(\d{4})$/D', $year, $match) || (int)$match[2] !== (int)$match[1] + 1) {
        throw new DomainException('Use an academic year such as 2026-2027.');
    }
    $start = required('starts_on', 10);
    $end = required('ends_on', 10);
    foreach ([$start, $end] as $date) {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new DomainException('Enter valid term dates.');
        }
    }
    if ($end < $start) {
        throw new DomainException('End date must not precede start date.');
    }
    $values = [$year, required('term_code', 20), $start, $end];
    if (input('edit_id') !== '') {
        if (!one('SELECT term_id FROM academic_terms WHERE term_id=?', [positive_id('edit_id')])) {
            throw new DomainException('This record no longer exists. Cancel editing and reload the list.');
        }
        $values[] = positive_id('edit_id');
        query('UPDATE academic_terms SET academic_year=?,term_code=?,starts_on=?,ends_on=? WHERE term_id=?', $values);
    } else {
        query('INSERT INTO academic_terms (academic_year,term_code,starts_on,ends_on) VALUES (?,?,?,?)', $values);
    }
    return 'Academic term saved.';
}, 'admin/terms.php' . list_query());
$edit = input('edit') !== '' ? one('SELECT * FROM academic_terms WHERE term_id=?', [positive_id('edit')]) : null;
if (input('edit') !== '' && !$edit) {
    abort_page(404, 'The selected record does not exist.');
}
$list = rows('SELECT * FROM academic_terms ORDER BY starts_on DESC' . page_limit());
pager($list);
$title = 'Academic Terms';
require __DIR__ . '/../includes/header.php';
?>
<div class="management-grid">
    <div class="management-list">
        <section class="panel flush">
            <div class="panel-title">
                <h2>Academic Terms</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Academic Year</th>
                            <th>Term</th>
                            <th>Dates</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td>
                                    <?= e($row['academic_year']) ?>
                                </td>
                                <td>
                                    <?= e($row['term_code']) ?>
                                </td>
                                <td>
                                    <?= e($row['starts_on']) ?> — <?= e($row['ends_on']) ?>
                                </td>
                                <td>
                                    <a class="button edit compact" href="<?= e(list_query(['edit' => $row['term_id']])) ?>"><?= icon('edit') ?> Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$list): ?>
                            <tr>
                                <td colspan="4" class="empty">No academic terms yet.</td>
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
            <input type="hidden" name="edit_id" value="<?= e($edit['term_id'] ?? '') ?>">
            <h2>
                <?= $edit ? 'Edit Term' : 'Add Academic Term' ?>
            </h2>
            <div class="field-grid">
                <label>Academic Year<input name="academic_year" required pattern="[0-9]{4}-[0-9]{4}" value="<?= e(input('academic_year', (string)($edit['academic_year'] ?? ''))) ?>" placeholder="2026-2027">
                </label>
                <label>Term Code<input name="term_code" maxlength="20" required value="<?= e(input('term_code', (string)($edit['term_code'] ?? ''))) ?>" placeholder="1S">
                </label>
                <label>Start Date<input type="date" name="starts_on" required value="<?= e(input('starts_on', (string)($edit['starts_on'] ?? ''))) ?>">
                </label>
                <label>End Date<input type="date" name="ends_on" required value="<?= e(input('ends_on', (string)($edit['ends_on'] ?? ''))) ?>">
                </label>
            </div>
            <div class="actions"><button type="submit"><?= icon('check') ?> <?= $edit ? 'Update Term' : 'Save Term' ?></button> <?php if ($edit): ?>
                    <a class="button secondary" href="<?= e(url('admin/terms.php' . list_query())) ?>">Cancel Editing</a>
                <?php else: ?>
                    <button type="reset" class="button secondary" data-clear-form>Clear Form</button>
                <?php endif; ?>
            </div>
        </form>
    </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>