<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('REGISTRAR');
[$scope, $params] = scope_sql($user);
handle_post(function () use ($user): string {
    return transaction(function () use ($user): string {
        $studentId = positive_id('student_id');
        require_student_scope($user, $studentId, true);
        $courseId = positive_id('course_id');
        $termId = positive_id('term_id');
        if (one('SELECT enrollment_id FROM enrollments WHERE student_id=? AND term_id=?', [$studentId, $termId])) {
            field_error('term_id', 'This student already has an enrollment for this term. Open its details instead.');
        }
        $course = one('SELECT * FROM courses WHERE course_id=?', [$courseId]);
        if (!$course || ($user['access_scope'] === 'DEPARTMENT' && (int)$course['department_id'] !== (int)$user['department_id'])) {
            throw new DomainException('Select a course within your assigned scope.');
        }
        query('INSERT INTO enrollments (student_id,course_id,term_id,enrollment_status,processed_by_staff_id) VALUES (?,?,?,?,?)', [$studentId, $courseId, $termId, choice('enrollment_status', ['PENDING', 'ENROLLED']), $user['staff_id']]);
        return 'Enrollment created. Open its details to assign subjects.';
    });
}, 'registrar/enrollments.php');
$students = rows('SELECT s.student_id,CONCAT(s.student_number,\' · \',TRIM(CONCAT(s.first_name,CHAR(32),s.last_name))) AS label FROM students s WHERE ' . $scope . ' ORDER BY TRIM(CONCAT(s.first_name,CHAR(32),s.last_name))', $params);
$courses = $user['access_scope'] === 'DEPARTMENT' ? rows('SELECT * FROM courses WHERE department_id=? ORDER BY course_code', [$user['department_id']]) : rows('SELECT * FROM courses ORDER BY course_code');
$search = '%' . input('q') . '%';
$list = rows('SELECT e.*,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,s.student_number,c.course_code,t.academic_year,t.term_code FROM enrollments e JOIN students s ON s.student_id=e.student_id JOIN courses c ON c.course_id=e.course_id JOIN academic_terms t ON t.term_id=e.term_id WHERE ' . $scope . ' AND (TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) LIKE ? OR s.student_number LIKE ?) ORDER BY e.enrollment_id DESC' . page_limit(), array_merge($params, [$search, $search]));
pager($list);
$title = 'Enrollment Management';
$subtitle = 'Record one program enrollment per student and term, then assign subjects.';
require __DIR__ . '/../includes/header.php';
?>
<div class="management-grid">
    <div class="management-list">
        <form method="get" class="toolbar">
            <label>Search Student<input name="q" value="<?= e(input('q')) ?>" placeholder="Name or student number">
            </label>
            <div class="toolbar-actions">
                <button type="submit"><?= icon('search') ?> Search</button>
            </div>
        </form>
        <section class="panel flush">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Program</th>
                            <th>Academic Term</th>
                            <th>Status</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td>
                                    <?= e($row['full_name']) ?>
                                    <span class="muted">
                                        <?= e($row['student_number']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= e($row['course_code']) ?>
                                </td>
                                <td>
                                    <?= e($row['academic_year'] . ' / ' . $row['term_code']) ?>
                                </td>
                                <td>
                                    <?= badge($row['enrollment_status']) ?>
                                </td>
                                <td>
                                    <a class="button secondary compact" href="<?= e(url('registrar/enrollment-details.php?id=' . $row['enrollment_id'])) ?>">Subjects & details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$list): ?>
                            <tr>
                                <td colspan="5" class="empty">No enrollment records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php pagination(); ?>
    </div>
    <aside class="management-editor" aria-label="Create Enrollment">
        <section class="panel">
            <h2>Create Enrollment</h2>
            <p class="muted">After saving, open Subjects & details to assign subjects.</p>
            <form method="post" class="section-gap">
                <?= csrf() ?>
                <div class="field-grid">
                    <label>Student<select name="student_id" required>
                            <option value="">Select student</option>
                            <?php options($students, 'student_id', 'label'); ?>
                        </select>
                    </label>
                    <label>Program<select name="course_id" required>
                            <option value="">Select course</option>
                            <?php options($courses, 'course_id', 'course_title'); ?>
                        </select>
                    </label>
                    <label>Term<select name="term_id" required>
                            <option value="">Select term</option>
                            <?php options(terms(), 'term_id', 'label'); ?>
                        </select>
                    </label>
                    <label>Initial Status<select name="enrollment_status">
                            <option value="PENDING">Pending</option>
                            <option value="ENROLLED">Enrolled</option>
                        </select>
                    </label>
                </div>
                <button type="submit"><?= icon('add') ?> Create Enrollment</button>
            </form>
        </section>
    </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>