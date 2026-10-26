<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('STUDENT');
$list = rows('SELECT e.*,c.course_code,c.course_title,t.academic_year,t.term_code FROM enrollments e JOIN courses c ON c.course_id=e.course_id JOIN academic_terms t ON t.term_id=e.term_id WHERE e.student_id=? ORDER BY t.starts_on DESC' . page_limit(), [$user['student_id']]);
pager($list);
$title = 'Enrollment History';
require __DIR__ . '/../includes/header.php';
?>
<section class="panel flush">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Academic Term</th>
                    <th>Program</th>
                    <th>Status</th>
                    <th>Recorded</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['academic_year'] . ' / ' . $row['term_code']) ?>
                        </td>
                        <td>
                            <?= e($row['course_code']) ?>
                            <span class="muted">
                                <?= e($row['course_title']) ?>
                            </span>
                        </td>
                        <td>
                            <?= badge($row['enrollment_status']) ?>
                        </td>
                        <td>
                            <?= e(date_label($row['enrolled_at'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                    <tr>
                        <td colspan="4" class="empty">No enrollment records have been recorded yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php pagination(); ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>