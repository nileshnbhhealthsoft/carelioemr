<?php

namespace OpenEMR\Modules\CarelioSubscription\Security;

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\Header;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Modules\CarelioSubscription\Services\SubscriptionManagerService;

class SubscriptionEnforcementService
{
    public const SUBSCRIPTION_MODULE_PATH = '/interface/modules/custom_modules/carelio_subscription/public/index.php';

    /**
     * Server-side subscription check on incoming requests
     */
    public static function enforce(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        if (self::isExemptRequest()) {
            return;
        }

        if (!function_exists('sqlQuery')) {
            return;
        }

        $session = SessionWrapperFactory::getInstance()->getActiveSession();
        $userId = (int) ($session->get('authUserID') ?? 0);
        if ($userId <= 0) {
            return;
        }

        // Fetch subscription
        $sub = SubscriptionManagerService::getCurrentSubscription();
        if (empty($sub)) {
            return;
        }

        $status = strtoupper((string) ($sub['status'] ?? 'ACTIVE'));

        if ($status !== SubscriptionManagerService::STATUS_DEACTIVATED) {
            return;
        }

        // Status is DEACTIVATED!
        $isSiteAdmin = AclMain::aclCheckCore('admin', 'users');

        if ($isSiteAdmin) {
            // Site Admin must be redirected to renewal screen
            $target = OEGlobalsBag::getInstance()->getWebRoot() . self::SUBSCRIPTION_MODULE_PATH;
            if (!headers_sent()) {
                header('Location: ' . $target);
                exit;
            }
            echo '<script>top.location.href=' . json_encode($target) . ';</script>';
            exit;
        }

        // Regular users get blocked screen
        self::renderBlockedScreen();
        exit;
    }

    private static function isExemptRequest(): bool
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        if (str_contains($script, '/interface/login/login.php')
            || str_contains($script, '/interface/logout.php')
            || str_contains($script, '/interface/login_screen.php')
            || str_contains($script, self::SUBSCRIPTION_MODULE_PATH)
            || str_contains($script, '/public/assets/')
            || str_contains($script, '/images/')
        ) {
            return true;
        }

        return false;
    }

    private static function renderBlockedScreen(): void
    {
        http_response_code(403);
        $webRoot = OEGlobalsBag::getInstance()->getWebRoot();
        ?>
        <!doctype html>
        <html>
        <head>
            <?php Header::setupHeader(); ?>
            <title><?php echo xlt('Subscription Inactive - CarelioEMR'); ?></title>
            <style>
                body { background: #f8fafc; font-family: -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .card-blocked { max-width: 540px; background: #fff; border: 1px solid #fee2e2; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.06); padding: 36px; text-align: center; }
                .icon-lock { color: #dc2626; font-size: 54px; margin-bottom: 18px; }
                h2 { color: #0f172a; font-size: 1.45rem; font-weight: 700; margin-bottom: 12px; }
                p { color: #475569; font-size: 0.95rem; line-height: 1.5; margin-bottom: 24px; }
            </style>
        </head>
        <body>
            <div class="card-blocked">
                <div class="icon-lock"><i class="fa fa-lock"></i></div>
                <h2><?php echo xlt('Account Deactivated'); ?></h2>
                <p>
                    <?php echo xlt('This clinic site has been deactivated because its subscription expired and passed the 2-day grace period.'); ?><br><br>
                    <strong><?php echo xlt('All clinical, patient, and billing data are completely preserved.'); ?></strong><br>
                    <?php echo xlt('Please contact your Site Administrator to renew and restore access.'); ?>
                </p>
                <a href="<?php echo attr($webRoot . '/interface/logout.php'); ?>" class="btn btn-secondary">
                    <i class="fa fa-sign-out mr-1"></i> <?php echo xlt('Log Out'); ?>
                </a>
            </div>
        </body>
        </html>
        <?php
    }
}

