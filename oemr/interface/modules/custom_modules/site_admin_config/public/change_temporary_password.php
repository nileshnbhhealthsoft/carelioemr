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

if ($userId <= 0) {
    header('Location: ' . OEGlobalsBag::getInstance()->get('login_screen'));
    exit;
}

$message = '';
$success = false;

if (!TemporaryPasswordService::requiresChange($userId)) {
    header('Location: ' . OEGlobalsBag::getInstance()->getWebRoot() . '/interface/main/main_screen.php');
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
            $success = true;
            $message = xl("Password change successful. Opening CarelioEMR.");
        } else {
            $message = $authUtils->getErrorMessage() ?: xl("Password update error!");
        }
    }
}

$user = sqlQuery("SELECT `fname`, `lname`, `username` FROM `users` WHERE `id` = ? LIMIT 1", [$userId]) ?: [];
$displayName = trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? '')) ?: ($user['username'] ?? '');
$csrfToken = CsrfUtils::collectCsrfToken(session: $session);
$mainUrl = OEGlobalsBag::getInstance()->getWebRoot() . '/interface/main/main_screen.php';
?>
<!doctype html>
<html>
<head>
    <?php Header::setupHeader(); ?>
    <title><?php echo xlt('Change Temporary Password'); ?></title>
    <?php if ($success) { ?>
        <meta http-equiv="refresh" content="1;url=<?php echo attr($mainUrl); ?>">
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
                <input class="form-control" type="password" name="curPass" required autofocus>
            </div>
            <div class="form-group">
                <label><?php echo xlt('New Password'); ?></label>
                <input class="form-control" type="password" name="newPass" required>
            </div>
            <div class="form-group">
                <label><?php echo xlt('Confirm New Password'); ?></label>
                <input class="form-control" type="password" name="newPass2" required>
            </div>
            <button class="btn btn-primary btn-block" type="submit">
                <?php echo xlt('Change Password and Continue'); ?>
            </button>
        </form>
    <?php } else { ?>
        <a class="btn btn-primary btn-block" href="<?php echo attr($mainUrl); ?>">
            <?php echo xlt('Continue to CarelioEMR'); ?>
        </a>
    <?php } ?>
</div>
</body>
</html>
