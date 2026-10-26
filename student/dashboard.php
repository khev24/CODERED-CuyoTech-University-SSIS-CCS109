<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('STUDENT');
$student = one('SELECT s.*,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,d.department_name FROM students s JOIN departments d ON d.department_id=s.department_id WHERE s.student_id=?', [$user['student_id']]);
$counts = one("SELECT COUNT(*) AS total,SUM(request_status='READY') AS ready,SUM(request_status='AWAITING_PAYMENT') AS payment FROM document_requests WHERE student_id=?", [$user['student_id']]);
$recent = rows('SELECT r.request_id,r.request_status,r.requested_at,d.document_name FROM document_requests r JOIN document_types d ON d.document_type_id=r.document_type_id WHERE r.student_id=? ORDER BY r.request_id DESC LIMIT 5', [$user['student_id']]);
$title = 'My Student Overview';
$subtitle = $student['student_number'] . ' · ' . $student['department_name'];
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="page-heading">
        <div>
            <h2>Welcome, <?= e($user['full_name']) ?>
            </h2>
            <p>Your academic records and university services are here.</p>
        </div>
        <?= badge($student['academic_status']) ?>
    </div>
    <a class="button" href="<?= e(url('student/request-create.php')) ?>">Request a document</a>
</section>
<section class="stats">
    <article class="stat">
        <small><?= icon('file') ?> Document requests</small>
        <strong>
            <?= e($counts['total']) ?>
        </strong>
        <p>Submitted through your account</p>
    </article>
    <article class="stat">
        <small><?= icon('check') ?> Ready for release</small>
        <strong>
            <?= e($counts['ready'] ?? 0) ?>
        </strong>
        <p>Check details with the Registrar</p>
    </article>
    <article class="stat">
        <small><?= icon('payment') ?> Awaiting payment</small>
        <strong>
            <?= e($counts['payment'] ?? 0) ?>
        </strong>
        <p>Submit your payment reference</p>
    </article>
</section>
<section class="panel flush">
    <div class="panel-title">
        <h2>Your Recent Requests</h2>
        <a class="button secondary compact" href="<?= e(url('student/document-requests.php')) ?>"><?= icon('eye') ?> View All</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['document_name']) ?>
                        </td>
                        <td>
                            <?= e(date_label($row['requested_at'])) ?>
                        </td>
                        <td>
                            <?= badge($row['request_status']) ?>
                        </td>
                        <td>
                            <a class="button secondary compact" href="<?= e(url('student/request-details.php?id=' . $row['request_id'])) ?>"><?= icon('eye') ?> View Request</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?>
                    <tr>
                        <td colspan="4" class="empty">You have not submitted any document requests yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<div class="grid">
    <section class="panel">
        <h2>Academic Records</h2>
        <p class="muted">View the subjects and grades recorded for each academic term.</p>
        <a class="button secondary compact" href="<?= e(url('student/grades.php')) ?>"><?= icon('eye') ?> View My Grades →</a>
    </section>
    <section class="panel">
        <h2>Department Clearance</h2>
        <p class="muted">Check your clearance before requesting documents that require it.</p>
        <a class="button secondary compact" href="<?= e(url('student/clearance.php')) ?>">Check clearance →</a>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>