<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('ADMIN');
handle_post(function (): string {
    $values = [required('document_name', 120), decimal_input('fee_amount'), isset($_POST['clearance_required']) ? 1 : 0, isset($_POST['is_active']) ? 1 : 0];
    if (input('edit_id') !== '') {
        if (!one('SELECT document_type_id FROM document_types WHERE document_type_id=?', [positive_id('edit_id')])) {
            throw new DomainException('This record no longer exists. Cancel editing and reload the list.');
        }
        $values[] = positive_id('edit_id');
        query('UPDATE document_types SET document_name=?,fee_amount=?,clearance_required=?,is_active=? WHERE document_type_id=?', $values);
    } else {
        query('INSERT INTO document_types (document_name,fee_amount,clearance_required,is_active) VALUES (?,?,?,?)', $values);
    }
    return 'Service saved. Existing request assessments remain unchanged.';
}, 'admin/service-catalog.php' . list_query());
$edit = input('edit') !== '' ? one('SELECT * FROM document_types WHERE document_type_id=?', [positive_id('edit')]) : null;
if (input('edit') !== '' && !$edit) {
    abort_page(404, 'The selected record does not exist.');
}
$list = rows('SELECT * FROM document_types ORDER BY document_name' . page_limit());
pager($list);
$title = 'Document Service Catalog';
$subtitle = 'Fees and clearance rules apply to new requests.';
require __DIR__ . '/../includes/header.php';
?>
<div class="management-grid">
    <div class="management-list">
        <section class="panel flush">
            <div class="panel-title">
                <h2>Document Services</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Fee</th>
                            <th>Clearance</th>
                            <th>Availability</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td>
                                    <?= e($row['document_name']) ?>
                                </td>
                                <td>
                                    <?= e(money($row['fee_amount'])) ?>
                                </td>
                                <td>
                                    <?= $row['clearance_required'] ? 'Required' : 'Not required' ?>
                                </td>
                                <td>
                                    <?= badge($row['is_active'] ? 'ACTIVE' : 'INACTIVE') ?>
                                </td>
                                <td>
                                    <a class="button edit compact" href="<?= e(list_query(['edit' => $row['document_type_id']])) ?>"><?= icon('edit') ?> Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$list): ?>
                            <tr>
                                <td colspan="5" class="empty">Add the university's document services to get started.</td>
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
            <input type="hidden" name="edit_id" value="<?= e($edit['document_type_id'] ?? '') ?>">
            <h2>
                <?= $edit ? 'Edit Service' : 'Add Service' ?>
            </h2>
            <div class="field-grid">
                <label>Document Name<input name="document_name" required maxlength="120" value="<?= e(input('document_name', (string)($edit['document_name'] ?? ''))) ?>">
                </label>
                <label>Fee (PHP)<input type="number" name="fee_amount" min="0" max="99999999.99" step="0.01" required value="<?= e(input('fee_amount', (string)($edit['fee_amount'] ?? '0.00'))) ?>">
                </label>
            </div>
            <label class="check">
                <input type="checkbox" name="clearance_required" <?= !empty($edit['clearance_required']) ? 'checked' : '' ?>>Requires the student's home department clearance</label>
            <label class="check">
                <input type="checkbox" name="is_active" <?= !$edit || $edit['is_active'] ? 'checked' : '' ?>>Available for new requests</label>
            <div class="actions"><button type="submit"><?= icon('check') ?> <?= $edit ? 'Update Service' : 'Save Service' ?></button> <?php if ($edit): ?>
                    <a class="button secondary" href="<?= e(url('admin/service-catalog.php' . list_query())) ?>">Cancel Editing</a>
                <?php else: ?>
                    <button type="reset" class="button secondary" data-clear-form>Clear Form</button>
                <?php endif; ?>
            </div>
        </form>
    </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>