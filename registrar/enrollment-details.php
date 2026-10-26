<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('REGISTRAR');
$id = positive_id();
$enrollment = one('SELECT e.*,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,s.student_number,c.course_title,t.academic_year,t.term_code FROM enrollments e JOIN students s ON s.student_id=e.student_id JOIN courses c ON c.course_id=e.course_id JOIN academic_terms t ON t.term_id=e.term_id WHERE e.enrollment_id=?', [$id]);
if (!$enrollment) {
    abort_page(404, 'Enrollment not found.');
}
require_student_scope($user, (int)$enrollment['student_id']);
handle_post(function () use ($user, $id, $enrollment): string {
    return transaction(function () use ($user, $id, $enrollment): string {
        require_student_scope($user, (int)$enrollment['student_id'], true);
        $record = one('SELECT * FROM enrollments WHERE enrollment_id=? FOR UPDATE', [$id]);
        $action = choice('action', ['status', 'add_subject', 'subject_status']);
        if ($action === 'status') {
            $next = choice('enrollment_status', ['PENDING', 'ENROLLED', 'COMPLETED', 'WITHDRAWN']);
            $current = $record['enrollment_status'];
            $transitions = ['PENDING' => ['ENROLLED', 'WITHDRAWN'], 'ENROLLED' => ['COMPLETED', 'WITHDRAWN'], 'COMPLETED' => [], 'WITHDRAWN' => []];
            if ($next === $current) throw new DomainException('The enrollment already has that status.');
            if (!in_array($next, $transitions[$current], true)) {
                throw new DomainException('That transition is not allowed. Completed and withdrawn enrollments cannot be reopened.');
            }
            if ($next === 'COMPLETED') {
                $active = (int)scalar("SELECT COUNT(*) FROM enrollment_subjects WHERE enrollment_id=? AND subject_status<>'DROPPED'", [$id]);
                $unfinished = (int)scalar("SELECT COUNT(*) FROM enrollment_subjects es LEFT JOIN grades g ON g.enrollment_subject_id=es.enrollment_subject_id WHERE es.enrollment_id=? AND es.subject_status<>'DROPPED' AND (es.subject_status<>'COMPLETED' OR g.enrollment_subject_id IS NULL)", [$id]);
                if (!$active || $unfinished) throw new DomainException('Complete and grade every non-dropped subject before completing this enrollment.');
            }
            if ($next === 'WITHDRAWN' && scalar('SELECT COUNT(*) FROM grades g JOIN enrollment_subjects es ON es.enrollment_subject_id=g.enrollment_subject_id WHERE es.enrollment_id=?', [$id])) {
                throw new DomainException('An enrollment with encoded grades cannot be withdrawn. Its academic history must be preserved.');
            }
            if ($next === 'WITHDRAWN') {
                query("UPDATE enrollment_subjects SET subject_status='DROPPED' WHERE enrollment_id=?", [$id]);
            }
            query('UPDATE enrollments SET enrollment_status=?,processed_by_staff_id=? WHERE enrollment_id=?', [$next, $user['staff_id'], $id]);
        } elseif ($action === 'add_subject') {
            if (!in_array($record['enrollment_status'], ['PENDING', 'ENROLLED'], true)) {
                throw new DomainException('Subjects can be added only to pending or enrolled records.');
            }
            $subjectId = positive_id('subject_id');
            if (one('SELECT enrollment_subject_id FROM enrollment_subjects WHERE enrollment_id=? AND subject_id=?', [$id, $subjectId])) {
                field_error('subject_id', 'This subject is already assigned to the enrollment.');
            }
            if (!one('SELECT subject_id FROM subjects WHERE subject_id=?', [$subjectId])) field_error('subject_id', 'Select an existing subject.');
            query('INSERT INTO enrollment_subjects (enrollment_id,subject_id) VALUES (?,?)', [$id, $subjectId]);
        } else {
            $subjectId = positive_id('enrollment_subject_id');
            if (!in_array($record['enrollment_status'], ['PENDING', 'ENROLLED'], true)) {
                throw new DomainException('Subject statuses are locked for completed or withdrawn enrollments.');
            }
            $subject = one('SELECT subject_status FROM enrollment_subjects WHERE enrollment_subject_id=? AND enrollment_id=? FOR UPDATE', [$subjectId, $id]);
            if (!$subject) throw new DomainException('Subject does not belong to this enrollment.');
            $next = choice('subject_status', ['ENROLLED', 'COMPLETED', 'DROPPED']);
            if ($next === $subject['subject_status']) throw new DomainException('The subject already has that status.');
            if ($subject['subject_status'] !== 'ENROLLED') throw new DomainException('Completed and dropped subjects cannot be reopened.');
            $hasGrade = (bool)one('SELECT enrollment_subject_id FROM grades WHERE enrollment_subject_id=?', [$subjectId]);
            if ($next === 'DROPPED' && $hasGrade) throw new DomainException('A graded subject cannot be dropped. Its grade must be preserved.');
            if ($next === 'COMPLETED' && ($record['enrollment_status'] !== 'ENROLLED' || !$hasGrade)) {
                throw new DomainException('A subject can be completed only in an enrolled record after its grade is encoded.');
            }
            query('UPDATE enrollment_subjects SET subject_status=? WHERE enrollment_subject_id=?', [$next, $subjectId]);
        }
        return 'Enrollment updated.';
    });
}, 'registrar/enrollment-details.php?id=' . $id);
$subjects = rows('SELECT es.*,b.subject_code,b.subject_title,b.units FROM enrollment_subjects es JOIN subjects b ON b.subject_id=es.subject_id WHERE es.enrollment_id=? ORDER BY b.subject_code', [$id]);
$available = rows('SELECT b.subject_id,CONCAT(b.subject_code,\' · \',b.subject_title) AS label FROM subjects b WHERE NOT EXISTS (SELECT 1 FROM enrollment_subjects es WHERE es.subject_id=b.subject_id AND es.enrollment_id=?) ORDER BY b.subject_code', [$id]);
$title = 'Enrollment #' . $id;
$subtitle = $enrollment['student_number'] . ' · ' . $enrollment['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <dl>
        <dt>Program</dt>
        <dd>
            <?= e($enrollment['course_title']) ?>
        </dd>
        <dt>Term</dt>
        <dd>
            <?= e($enrollment['academic_year'] . ' / ' . $enrollment['term_code']) ?>
        </dd>
        <dt>Status</dt>
        <dd>
            <?= badge($enrollment['enrollment_status']) ?>
        </dd>
    </dl>
</section>
<p class="notice">Enrollments progress from Pending to Enrolled to Completed. Complete and grade all active subjects first. Withdrawal is allowed only before any grades are encoded and marks subjects Dropped. Completed and withdrawn enrollments, and completed or dropped subject statuses, cannot be reopened. Grade corrections remain available for completed enrollments.</p>
<div class="grid">
    <form method="post" class="panel" data-confirm="Save this change to the selected enrollment or subject?">
        <?= csrf() ?>
        <input type="hidden" name="action" value="status">
        <h2>Enrollment Status</h2>
        <label>Status<select name="enrollment_status" <?= in_array($enrollment['enrollment_status'], ['COMPLETED', 'WITHDRAWN'], true) ? 'disabled' : '' ?>>
                <?php foreach (['PENDING', 'ENROLLED', 'COMPLETED', 'WITHDRAWN'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= $enrollment['enrollment_status'] === $status ? 'selected' : '' ?>>
                        <?= e(ui_label($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" <?= in_array($enrollment['enrollment_status'], ['COMPLETED', 'WITHDRAWN'], true) ? 'disabled' : '' ?>>Update Status</button>
    </form>
    <form method="post" class="panel" data-confirm="Save this change to the selected enrollment or subject?">
        <?= csrf() ?>
        <input type="hidden" name="action" value="add_subject">
        <h2>Assign Subject</h2>
        <label>Available Subject<select name="subject_id" required>
                <option value="">Select subject</option>
                <?php options($available, 'subject_id', 'label'); ?>
            </select>
        </label>
        <button type="submit"><?= icon('add') ?> Add Subject</button>
    </form>
</div>
<section class="panel flush section-gap">
    <div class="panel-title">
        <h2>Assigned Subjects</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Units</th>
                    <th>Status</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['subject_code']) ?>
                            <span class="muted">
                                <?= e($row['subject_title']) ?>
                            </span>
                        </td>
                        <td>
                            <?= e($row['units']) ?>
                        </td>
                        <td>
                            <form method="post" data-confirm="Save this change to the selected enrollment or subject?">
                                <?= csrf() ?>
                                <input type="hidden" name="action" value="subject_status">
                                <input type="hidden" name="enrollment_subject_id" value="<?= e($row['enrollment_subject_id']) ?>">
                                <label class="sr-only" for="status-<?= e($row['enrollment_subject_id']) ?>">Subject status</label>
                                <select id="status-<?= e($row['enrollment_subject_id']) ?>" name="subject_status">
                                    <?php foreach (['ENROLLED', 'COMPLETED', 'DROPPED'] as $status): ?>
                                        <option value="<?= e($status) ?>" <?= $row['subject_status'] === $status ? 'selected' : '' ?>>
                                            <?= e(ui_label($status)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="secondary"><?= icon('check') ?> Save Status</button>
                            </form>
                        </td>
                        <td>
                            <a class="button secondary compact" href="<?= e(url('registrar/grade-entry.php' . list_query(['enrollment_id' => $id]))) ?>">Enter Grades</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$subjects): ?>
                    <tr>
                        <td colspan="4" class="empty">No subjects assigned yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>