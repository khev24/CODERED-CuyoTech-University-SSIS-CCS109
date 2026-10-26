<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('ADMIN');
$list = rows('SELECT h.*,TRIM(CONCAT(f.first_name,CHAR(32),f.last_name)) AS full_name,s.student_number FROM request_status_events h JOIN document_requests r ON r.request_id=h.request_id JOIN students s ON s.student_id=r.student_id LEFT JOIN staff_users f ON f.staff_id=h.changed_by_staff_id WHERE s.student_number LIKE ? ORDER BY h.event_id DESC' . page_limit(), ['%' . input('q') . '%']);
pager($list);
$title = 'Request Activity';
$subtitle = 'Recorded document-request transitions, including the responsible reviewer.';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" class="toolbar">
    <label>Student Number<input name="q" value="<?= e(input('q')) ?>">
    </label>
    <div class="toolbar-actions">
        <button type="submit"><?= icon('search') ?> Filter History</button>
    </div>
</form>
<section class="panel flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Request / Student</th>
                    <th>Transition</th>
                    <th>Actor</th>
                    <th>Recorded / Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>#<?= e($row['request_id']) ?>
                            <span class="muted">
                                <?= e($row['student_number']) ?>
                            </span>
                        </td>
                        <td>
                            <?= e(ui_label($row['old_status'] ?: 'SUBMITTED')) ?> → <?= badge($row['new_status']) ?>
                        </td>
                        <td>
                            <?= e($row['full_name'] ?: 'Student submission') ?>
                        </td>
                        <td>
                            <?= e(date_label($row['changed_at'])) ?>
                            <span class="muted">
                                <?= e($row['remarks']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="4" class="empty">No request activity found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>