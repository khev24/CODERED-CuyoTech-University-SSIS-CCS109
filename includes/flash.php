<?php
$notifications = [];
if (!empty($_SESSION['flash'])) {
    $notifications[] = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
if (!empty($GLOBALS['form_error'])) {
    $notifications[] = ['error', $GLOBALS['form_error']];
}
?>
<div class="toast-region" aria-label="Notifications">
    <?php foreach ($notifications as [$kind, $message]):
        $kind = in_array($kind, ['success', 'warning', 'error'], true) ? $kind : 'info';
    ?>
        <div class="toast <?= e($kind) ?>" data-toast="<?= e($kind) ?>" <?= $kind === 'error' ? 'data-toast-key="server-error"' : '' ?> role="<?= $kind === 'error' ? 'alert' : 'status' ?>" aria-atomic="true">
            <span><strong><?= e(ucfirst($kind)) ?></strong><span class="toast-message"><?= e($message) ?></span></span>
            <button type="button" class="toast-close" aria-label="Dismiss notification" hidden>&times;</button>
        </div>
    <?php endforeach; ?>
</div>
<script>
    function dismissToast(key) {
        document.querySelectorAll('[data-toast-key]').forEach(toast => {
            if (toast.dataset.toastKey === key) toast.remove();
        });
    }

    function prepareToast(toast) {
        const close = toast.querySelector('.toast-close');
        close.hidden = false;
        close.addEventListener('click', () => toast.remove());
        // Errors remain until dismissed; noncritical notifications pause on focus/hover.
        if (toast.dataset.toast === 'error' || toast.dataset.toast === 'warning') return;
        let timer;
        const pause = () => clearTimeout(timer);
        const resume = () => {
            pause();
            timer = setTimeout(() => toast.remove(), 7000);
        };
        toast.addEventListener('mouseenter', pause);
        toast.addEventListener('mouseleave', resume);
        toast.addEventListener('focusin', pause);
        toast.addEventListener('focusout', resume);
        resume();
    }

    function showToast(message, kind = 'error', key = '') {
        if (key) dismissToast(key);
        const toast = document.createElement('div');
        kind = ['success', 'warning', 'error', 'info'].includes(kind) ? kind : 'info';
        toast.className = 'toast ' + kind;
        toast.dataset.toast = kind;
        if (key) toast.dataset.toastKey = key;
        toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-atomic', 'true');
        const text = document.createElement('span');
        text.textContent = message;
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast-close';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.textContent = '×';
        toast.append(text, close);
        document.querySelector('.toast-region').append(toast);
        prepareToast(toast);
        return toast;
    }
    document.querySelectorAll('[data-toast]').forEach(prepareToast);
</script>