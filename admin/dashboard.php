<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('ADMIN');
$stats = one("SELECT SUM(account_role='STUDENT') AS students,SUM(account_role='STAFF') AS staff,SUM(is_active=1) AS active FROM accounts");
$recent = rows('SELECT h.event_id,h.request_id,h.new_status,h.changed_at,s.student_number FROM request_status_events h JOIN document_requests r ON r.request_id=h.request_id JOIN students s ON s.student_id=r.student_id ORDER BY h.event_id DESC LIMIT 6');
$title = 'University Overview';

require __DIR__ . '/../includes/header.php';
?>
<section class="stats" aria-label="Account totals">
    <article class="stat">
        <small><?= icon('users') ?> Student accounts</small>
        <strong>
            <?= e($stats['students'] ?? 0) ?>
        </strong>
        <p>University-issued access</p>
    </article>
    <article class="stat">
        <small><?= icon('users') ?> Staff accounts</small>
        <strong>
            <?= e($stats['staff'] ?? 0) ?>
        </strong>
        <p>Assigned by responsibility</p>
    </article>
    <article class="stat">
        <small><?= icon('users') ?> Active accounts</small>
        <strong>
            <?= e($stats['active'] ?? 0) ?>
        </strong>
        <p>Currently permitted to sign in</p>
    </article>
</section>
<div class="grid">
    <section class="panel">
        <h2>Keep Campus Services Organized</h2>
        <p class="muted">Create accounts, maintain academic records and configure document services from one workspace.</p>
        <div class="actions">
            <a class="button" href="<?= e(url('admin/account-create.php')) ?>"><?= icon('add') ?> Create Account</a>
            <a class="button secondary" href="<?= e(url('admin/service-catalog.php')) ?>">Service catalog</a>
        </div>
    </section>
    <section class="panel">
        <h2>Academic Setup</h2>
        <p class="muted">Maintain departments, degree programs, individual subjects and academic terms before processing enrollments.</p>
        <div class="actions">
            <a class="button secondary compact" href="<?= e(url('admin/terms.php')) ?>">Academic terms →</a>
            <a class="button secondary compact" href="<?= e(url('admin/subjects.php')) ?>">Subject catalog →</a>
        </div>
    </section>
</div>
<section class="panel flush section-gap">
    <div class="panel-title">
        <h2>Recent Request Activity</h2>
        <a class="button secondary compact" href="<?= e(url('admin/activity-log.php')) ?>"><?= icon('eye') ?> View History</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Request</th>
                    <th>Student</th>
                    <th>State</th>
                    <th>Recorded</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td>#<?= e($row['request_id']) ?>
                        </td>
                        <td>
                            <?= e($row['student_number']) ?>
                        </td>
                        <td>
                            <?= badge($row['new_status']) ?>
                        </td>
                        <td>
                            <?= e(date_label($row['changed_at'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?>
                    <tr>
                        <td colspan="4" class="empty">Request activity will appear after students submit documents.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>