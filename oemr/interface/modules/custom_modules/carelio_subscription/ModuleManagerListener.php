<?php

use OpenEMR\Core\AbstractModuleActionListener;
use OpenEMR\Modules\CarelioSubscription\Installer\SubscriptionInstaller;

class ModuleManagerListener extends AbstractModuleActionListener
{
    public function __construct()
    {
        parent::__construct();
    }

    public function moduleManagerAction($methodName, $modId, string $currentActionStatus = 'Success'): string
    {
        if (method_exists(self::class, $methodName)) {
            return self::$methodName($modId, $currentActionStatus);
        }

        return $currentActionStatus;
    }

    public static function getModuleNamespace(): string
    {
        return 'OpenEMR\\Modules\\CarelioSubscription\\';
    }

    public static function initListenerSelf(): ModuleManagerListener
    {
        return new self();
    }

    private function install($modId, $currentActionStatus): mixed
    {
        try {
            self::ensureInstallerLoaded();
            SubscriptionInstaller::install();
            self::setModuleActiveState($modId, '0', '1');
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('Carelio Subscription install error: ' . $e->getMessage());
            return 'Install warning: ' . $e->getMessage();
        }
    }

    private function install_sql($modId, $currentActionStatus): mixed
    {
        try {
            self::ensureInstallerLoaded();
            SubscriptionInstaller::install();
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('Carelio Subscription install_sql error: ' . $e->getMessage());
            return 'Install SQL warning: ' . $e->getMessage();
        }
    }

    private function enable($modId, $currentActionStatus): mixed
    {
        try {
            self::ensureInstallerLoaded();
            SubscriptionInstaller::install();
            self::setModuleActiveState($modId, '1', '0');
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('Carelio Subscription enable error: ' . $e->getMessage());
            return 'Enable warning: ' . $e->getMessage();
        }
    }

    private function disable($modId, $currentActionStatus): mixed
    {
        try {
            self::ensureInstallerLoaded();
            SubscriptionInstaller::disable();
            self::setModuleActiveState($modId, '0', '1');
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('Carelio Subscription disable error: ' . $e->getMessage());
            return 'Disable warning: ' . $e->getMessage();
        }
    }

    private function unregister($modId, $currentActionStatus): mixed
    {
        try {
            self::ensureInstallerLoaded();
            SubscriptionInstaller::disable();
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('Carelio Subscription unregister error: ' . $e->getMessage());
            return $currentActionStatus;
        }
    }

    private static function ensureInstallerLoaded(): void
    {
        if (class_exists(SubscriptionInstaller::class)) {
            return;
        }

        $installer = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Installer' . DIRECTORY_SEPARATOR . 'SubscriptionInstaller.php';
        if (is_file($installer)) {
            require_once $installer;
        }
    }
}
