<?php
$menus = [
    'ADMIN' => ['dashboard' => 'Overview', 'accounts' => 'User accounts', 'service-catalog' => 'Service catalog', 'departments' => 'Departments', 'courses' => 'Courses', 'subjects' => 'Subjects', 'terms' => 'Academic terms', 'activity-log' => 'Request activity'],
    'STUDENT' => ['dashboard' => 'My overview', 'subjects' => 'Enrolled subjects', 'grades' => 'My grades', 'enrollments' => 'Enrollment history', 'document-requests' => 'Document requests', 'payments' => 'Payment history', 'receipts' => 'Receipts', 'clearance' => 'Clearance'],
    'REGISTRAR' => ['dashboard' => 'Registrar desk', 'enrollments' => 'Enrollments', 'grades' => 'Grade records', 'document-requests' => 'Document requests', 'clearance-status' => 'Clearance status'],
    'CASHIER' => ['dashboard' => 'Cashier desk', 'payments' => 'Payment verification', 'receipts' => 'Issued receipts'],
    'DEPARTMENT' => ['dashboard' => 'Department desk', 'clearances' => 'Clearance processing', 'students' => 'Department students'],
];
$page = basename($_SERVER['SCRIPT_NAME'], '.php');
$parentPages = [
    'account-create' => 'accounts',
    'account-edit' => 'accounts',
    'account-toggle' => 'accounts',
    'request-create' => 'document-requests',
    'request-details' => 'document-requests',
    'enrollment-details' => 'enrollments',
    'grade-entry' => 'grades',
    'payment-create' => 'payments',
    'payment-details' => 'payments',
    'verify-payment' => 'payments',
    'clearance-details' => 'clearances'
];
$activePage = $parentPages[$page] ?? $page;
?>
<nav class="sidebar" id="portal-nav" aria-label="Portal navigation">
    <a class="brand" href="<?= e(url(portal($user) . '/dashboard.php')) ?>">
        <span class="brand-emblem"><img class="university-logo" src="<?= e(url('assets/images/CuyoTech-Logo.png')) ?>" alt="" width="80" height="80"></span>
        <div>CUYOTECH<small>STUDENT SERVICES</small>
        </div>
    </a>
    <div class="label"><?= e(ui_label($user['role'])) ?> Workspace</div>
    <?php foreach ($menus[$user['role']] as $file => $label): ?>
        <a <?= $activePage === $file ? 'aria-current="page"' : '' ?> class="<?= $activePage === $file ? 'active' : '' ?>" href="<?= e(url(portal($user) . '/' . $file . '.php')) ?>">
            <?= icon($file) ?>
            <?= e(ucwords($label)) ?>
        </a>
    <?php endforeach; ?>
</nav>