<?php
if (!isset($user, $title)) {
    http_response_code(404);
    exit;
}
ob_start('render_forms');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>
        <?= e(ucwords($title)) ?> · CuyoTech SSIS</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>

<body class="portal-layout">
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="topbar">
        <div class="workspace-name">CuyoTech University<small>Student Services Information System</small></div>
        <button type="button" class="menu-toggle" aria-controls="portal-nav" aria-expanded="false"><?= icon('menu') ?> Menu</button>
        <details class="profile-menu">
            <summary aria-label="Open account menu">
                <span class="avatar"><?= icon('profile') ?></span>
                <span class="profile-identity"><strong><?= e($user['full_name']) ?></strong><small><?= e(ui_label($user['role'])) ?></small></span>
                <?= icon('chevron') ?>
            </summary>
            <div class="profile-dropdown">
                <p><strong><?= e($user['full_name']) ?></strong><small><?= e(ui_label($user['role'])) ?></small></p>
                <a class="button secondary" href="<?= e(url(in_array($user['role'], ['ADMIN', 'STUDENT'], true) ? portal($user) . '/profile.php' : 'auth/profile.php')) ?>"><?= icon('profile') ?> My Profile</a>
                <form method="post" action="<?= e(url('auth/logout.php')) ?>" data-confirm="Are you sure you want to sign out of your CuyoTech SSIS account?" data-confirm-title="Sign Out?" data-confirm-label="Sign Out" data-confirm-style="danger">
                    <?= csrf() ?>
                    <button type="submit" class="secondary"><?= icon('logout') ?> Sign Out</button>
                </form>
            </div>
        </details>
    </header>
    <div class="shell">
        <?php require __DIR__ . '/sidebar.php'; ?>
        <main class="content" id="main" tabindex="-1">
            <div class="eyebrow">
                <?= e(ui_label($user['role'])) ?> / <?= e(ucwords($title)) ?>
            </div>
            <?php
            $backPages = [
                'account-create' => ['accounts', 'Accounts'],
                'account-edit' => ['accounts', 'Accounts'],
                'request-create' => ['document-requests', 'Document Requests'],
                'request-details' => ['document-requests', 'Document Requests'],
                'payment-create' => ['payments', 'Payments'],
                'payment-details' => ['payments', 'Payments'],
                'enrollment-details' => ['enrollments', 'Enrollments'],
                'grade-entry' => ['grades', 'Grades'],
                'clearance-details' => ['clearances', 'Clearances']
            ];
            $currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
            $back = $backPages[$currentPage] ?? null;
            if ($currentPage === 'receipts' && input('id') !== '') $back = ['receipts', 'Receipts'];
            if ($back): ?>
                <a class="button secondary back-button" href="<?= e(url(portal($user) . '/' . $back[0] . '.php' . list_query())) ?>"><?= icon('back') ?> Back to <?= e($back[1]) ?></a>
            <?php endif; ?>
            <div class="page-heading">
                <div>
                    <h1>
                        <?= e(ucwords($title)) ?>
                    </h1>
                    <?php if (!empty($subtitle)): ?><p><?= e($subtitle) ?></p><?php endif; ?>
                </div>
            </div>

            <?php require __DIR__ . '/flash.php'; ?>