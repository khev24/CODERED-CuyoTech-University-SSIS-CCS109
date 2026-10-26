<?php
require_once __DIR__ . '/../includes/workflow.php';
$user = require_role('CASHIER');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort_page(405, 'Use the payment verification form.');
}
$id = positive_id();
handle_post(fn() => verify_payment($user, $id), 'cashier/payment-details.php?id=' . $id);
$title = 'Payment Decision';
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <p>The payment decision was not saved.</p>
    <a class="button secondary compact" href="<?= e(url('cashier/payment-details.php?id=' . $id)) ?>">Return to payment details</a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>