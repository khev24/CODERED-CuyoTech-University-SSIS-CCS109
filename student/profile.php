<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('STUDENT');
$student = one('SELECT s.*,TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS full_name,d.department_name FROM students s JOIN departments d ON d.department_id=s.department_id WHERE student_id=?', [$user['student_id']]);
$title = 'My Student Profile';
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <dl>
        <dt>Student Number</dt>
        <dd>
            <?= e($student['student_number']) ?>
        </dd>
        <dt>First Name</dt>
        <dd><?= e($student['first_name']) ?></dd>
        <dt>Last Name</dt>
        <dd><?= e($student['last_name']) ?></dd>
        <dt>Email</dt>
        <dd>
            <?= e($user['email']) ?>
        </dd>
        <dt>Department</dt>
        <dd>
            <?= e($student['department_name']) ?>
        </dd>
        <dt>Academic Status</dt>
        <dd>
            <?= badge($student['academic_status']) ?>
        </dd>
    </dl>
</section>
<p class="muted">For corrections to official identity or academic information, contact the university administrator or Registrar.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>