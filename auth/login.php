<?php
require_once __DIR__ . '/../includes/auth.php';
$user = current_user();
if ($user) {
    redirect(portal($user) . '/dashboard.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        $email = required('email', 254);
        $password = $_POST['password'] ?? '';
        if (!is_string($password) || strlen($password) > 4096 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Enter your university email and password.');
        }
        $account = one('SELECT * FROM accounts WHERE email=?', [$email]);
        $valid = $account && password_verify($password, $account['password_hash']);
        if (!$account || !$valid || !$account['is_active']) {
            throw new DomainException('Unable to sign in with those credentials. Contact your university administrator if you need assistance.');
        }
        session_regenerate_id(true);
        $_SESSION = ['account_id' => $account['account_id']];
        $user = current_user();
        if (!$user) {
            throw new DomainException('Unable to sign in. Contact your university administrator.');
        }
        redirect(portal($user) . '/dashboard.php');
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        $error = 'The university database is unavailable. Please try again later or contact your administrator.';
        error_log('SSIS login database failure: ' . $exception->getCode());
    }
}
$campus = config()['campus_image'];
if ($campus === '') {
    $campus = 'https://upload.wikimedia.org/wikipedia/commons/1/16/The_UST_Main_Building.jpg';
}
// Configuration is trusted, but reject control characters or CSS delimiters.
if (!filter_var($campus, FILTER_VALIDATE_URL) || !str_starts_with($campus, 'https://') || preg_match('/[\x00-\x20\x22\x27()\\\\]/', $campus)) {
    $campus = '';
}
ob_start('render_forms');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sign in · CuyoTech University</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>

<body class="login-page" style="--campus-image:url('<?= e($campus) ?>')">
    <div class="login-wrap">
        <main class="login-card">
            <div class="brand">
                <span class="brand-emblem"><img class="university-logo" src="<?= e(url('assets/images/CuyoTech-Logo.png')) ?>" alt="" width="80" height="80"></span>
                <div>CUYOTECH<small>UNIVERSITY</small>
                </div>
            </div>
            <h1>Your Campus, Connected.</h1>
            <p class="muted">Sign in to the Student Services Information System.</p>
            <?php
            $GLOBALS['form_error'] = $error;
            require __DIR__ . '/../includes/flash.php';
            ?>
            <form method="post" id="login-form">
                <?= csrf() ?>
                <label>University Email<input name="email" type="email" required maxlength="254" autocomplete="username" value="<?= e(input('email')) ?>">
                </label>
                <label>Password<span class="password-wrap">
                        <input id="password" name="password" type="password" required maxlength="4096" autocomplete="current-password">
                        <button class="password-toggle" type="button" aria-controls="password" aria-pressed="false" aria-label="Show password"><?= icon('eye') ?> <span>Show</span></button>
                    </span>
                </label>
                <button type="submit" class="button"><?= icon('logout') ?> Sign In</button>
            </form>
            <p class="muted login-help">Use the account issued by your university. For account assistance, contact your administrator.</p>
        </main>
    </div>
    <script>
        document.querySelector('.password-toggle').addEventListener('click', function() {
            const input = document.getElementById('password');
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            this.querySelector('span').textContent = visible ? 'Hide' : 'Show';
            this.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            this.setAttribute('aria-pressed', String(visible));
        });
        document.getElementById('login-form').addEventListener('submit', function() {
            const button = this.querySelector('[type=submit]');
            button.disabled = true;
            button.textContent = 'Signing in…';
        });
    </script>
    <?php validation_script(); ?>
</body>

</html>