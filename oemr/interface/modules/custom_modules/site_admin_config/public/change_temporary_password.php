<?php

/**
 * Forced temporary-password change screen for the Carelio Site Admin module.
 */

$sessionAllowWrite = true;
require_once(__DIR__ . "/../../../../globals.php");

use OpenEMR\Common\Auth\AuthUtils;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\Header;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Modules\SiteAdmin\Security\TemporaryPasswordService;

$session = SessionWrapperFactory::getInstance()->getActiveSession();
$userId = (int) ($session->get('authUserID') ?? 0);
$siteId = (string) ($session->get('site_id') ?? ($_SESSION['site_id'] ?? ($_GET['site'] ?? 'default')));
$loginUrl = OEGlobalsBag::getInstance()->getWebRoot() . '/interface/login/login.php?site=' . rawurlencode($siteId);

if ($userId <= 0) {
    if (!headers_sent()) {
        header('Location: ' . $loginUrl);
        exit;
    }
    echo '<script>window.location.href=' . json_encode($loginUrl) . ';</script>';
    exit;
}

$message = '';
$success = false;

if (!TemporaryPasswordService::requiresChange($userId)) {
    if (!headers_sent()) {
        header('Location: ' . $loginUrl);
        exit;
    }
    echo '<script>window.location.href=' . json_encode($loginUrl) . ';</script>';
    exit;
}

if (!empty($_POST)) {
    CsrfUtils::checkCsrfInput(INPUT_POST, dieOnFail: true);

    $currentPassword = (string) ($_POST['curPass'] ?? '');
    $newPassword = (string) ($_POST['newPass'] ?? '');
    $newPasswordConfirm = (string) ($_POST['newPass2'] ?? '');

    if ($newPassword !== $newPasswordConfirm) {
        $message = xl("Passwords Don't match!");
    } elseif ($currentPassword === $newPassword) {
        $message = xl("New password must be different from the temporary password.");
    } else {
        $authUtils = new AuthUtils();
        if ($authUtils->updatePassword($userId, $userId, $currentPassword, $newPassword)) {
            TemporaryPasswordService::clearTemporary($userId);
            if (!headers_sent()) {
                header('Location: ' . $loginUrl);
                exit;
            }
            echo '<script>top.location.href=' . json_encode($loginUrl) . ';</script>';
            exit;
        } else {
            $message = $authUtils->getErrorMessage() ?: xl("Password update error!");
        }
    }
}

$user = sqlQuery("SELECT `fname`, `lname`, `username` FROM `users` WHERE `id` = ? LIMIT 1", [$userId]) ?: [];
$displayName = trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? '')) ?: ($user['username'] ?? '');

$csrfToken = '';
if (!$success) {
    if (empty($session->get('csrf_private_key'))) {
        CsrfUtils::setupCsrfKey($session);
    }
    try {
        $csrfToken = CsrfUtils::collectCsrfToken(session: $session);
    } catch (\Throwable) {
        CsrfUtils::setupCsrfKey($session);
        $csrfToken = CsrfUtils::collectCsrfToken(session: $session);
    }
}
?>
<!doctype html>
<html>
<head>
    <?php Header::setupHeader(); ?>
    <title><?php echo xlt('Change Temporary Password'); ?></title>
    <?php if ($success) { ?>
        <meta http-equiv="refresh" content="2;url=<?php echo attr($loginUrl); ?>">
    <?php } ?>
    <style>
        body { background: #f8fafc; }
        .carelio-password-card {
            max-width: 520px;
            margin: 8vh auto;
            background: #fff;
            border: 1px solid #dbe4ee;
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            padding: 28px;
        }
        .carelio-password-card h1 { font-size: 1.35rem; font-weight: 700; margin-bottom: 8px; }
        .carelio-password-card p { color: #475569; }
        .carelio-password-field { position: relative; }
        .carelio-password-field .form-control { padding-right: 44px; }
        .carelio-toggle-password {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border: 0;
            background: transparent;
            color: #64748b;
            cursor: pointer;
        }
        .carelio-toggle-password:hover { color: #0f172a; }
    </style>
</head>
<body>
<div class="carelio-password-card">
    <h1><?php echo xlt('Change Temporary Password'); ?></h1>
    <p><?php echo xlt('For security, you must set a new password before using CarelioEMR.'); ?></p>
    <p><strong><?php echo text($displayName); ?></strong></p>

    <?php if ($message !== '') { ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo text($message); ?>
        </div>
    <?php } ?>

    <?php if (!$success) { ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrfToken); ?>">
            <div class="form-group">
                <label><?php echo xlt('Temporary Password'); ?></label>
                <div class="carelio-password-field">
                    <input id="curPass" class="form-control" type="password" name="curPass" required autofocus>
                    <button class="carelio-toggle-password" type="button" data-toggle-password="curPass" aria-label="<?php echo xla('Show password'); ?>">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label><?php echo xlt('New Password'); ?></label>
                <div class="carelio-password-field">
                    <input id="newPass" class="form-control" type="password" name="newPass" required>
                    <button class="carelio-toggle-password" type="button" data-toggle-password="newPass" aria-label="<?php echo xla('Show password'); ?>">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label><?php echo xlt('Confirm New Password'); ?></label>
                <div class="carelio-password-field">
                    <input id="newPass2" class="form-control" type="password" name="newPass2" required>
                    <button class="carelio-toggle-password" type="button" data-toggle-password="newPass2" aria-label="<?php echo xla('Show password'); ?>">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>
            <button class="btn btn-primary btn-block" type="submit">
                <?php echo xlt('Change Password and Continue'); ?>
            </button>
        </form>
    <?php } else { ?>
        <a class="btn btn-primary btn-block" href="<?php echo attr($loginUrl); ?>">
            <?php echo xlt('Continue to Login'); ?>
        </a>
    <?php } ?>
</div>
<script>
document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
    button.addEventListener('click', function () {
        var input = document.getElementById(button.getAttribute('data-toggle-password'));
        var icon = button.querySelector('i');
        if (!input) {
            return;
        }

        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        if (icon) {
            icon.className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
        }
        button.setAttribute('aria-label', show ? <?php echo js_escape(xl('Hide password')); ?> : <?php echo js_escape(xl('Show password')); ?>);
    });
});
</script>
</body>
</html>
