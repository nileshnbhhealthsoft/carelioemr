<?php

/**
 * Carelio EMR Module Manager Listener
 * Handles module lifecycle actions (install, enable, disable, unregister) directly
 * from the OpenEMR Web UI (Administration -> System -> Modules).
 *
 * @package   OpenEMR Modules
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use OpenEMR\Core\AbstractModuleActionListener;
use OpenEMR\Modules\SiteAdmin\Installer\SiteAdminInstaller;

class ModuleManagerListener extends AbstractModuleActionListener
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Dispatches Module Manager actions.
     *
     * @param string $methodName Action name: install, enable, disable, etc.
     * @param int|string $modId Module ID in OpenEMR modules table
     * @param string $currentActionStatus Action status from OpenEMR
     * @return string Status to return to OpenEMR Module Manager
     */
    public function moduleManagerAction($methodName, $modId, string $currentActionStatus = 'Success'): string
    {
        if (method_exists($this, $methodName)) {
            return $this->{$methodName}($modId, $currentActionStatus);
        }

        return $currentActionStatus;
    }

    /**
     * Return PSR-4 namespace for this module
     *
     * @return string
     */
    public static function getModuleNamespace(): string
    {
        return 'OpenEMR\\Modules\\SiteAdmin\\';
    }

    /**
     * Return listener instance for Laminas Module Manager
     *
     * @return ModuleManagerListener
     */
    public static function initListenerSelf(): ModuleManagerListener
    {
        return new self();
    }

    /**
     * Fired when admin clicks "Install" in OpenEMR Modules UI
     *
     * @param int|string $modId
     * @param string $currentActionStatus
     * @return mixed
     */
    private function install($modId, $currentActionStatus): mixed
    {
        try {
            SiteAdminInstaller::install();
            self::setModuleActiveState($modId, '0', '1');
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('SiteAdmin ModuleManagerListener install error: ' . $e->getMessage());
            return 'Install warning: ' . $e->getMessage();
        }
    }

    /**
     * Fired when admin clicks "Install SQL" in OpenEMR Modules UI
     *
     * @param int|string $modId
     * @param string $currentActionStatus
     * @return mixed
     */
    private function install_sql($modId, $currentActionStatus): mixed
    {
        try {
            SiteAdminInstaller::install();
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('SiteAdmin ModuleManagerListener install_sql error: ' . $e->getMessage());
            return 'Install SQL warning: ' . $e->getMessage();
        }
    }

    /**
     * Fired when admin clicks "Upgrade SQL" in OpenEMR Modules UI
     *
     * @param int|string $modId
     * @param string $currentActionStatus
     * @return mixed
     */
    private function upgrade_sql($modId, $currentActionStatus): mixed
    {
        try {
            SiteAdminInstaller::install();
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('SiteAdmin ModuleManagerListener upgrade_sql error: ' . $e->getMessage());
            return 'Upgrade SQL warning: ' . $e->getMessage();
        }
    }

    /**
     * Fired when admin clicks "Upgrade" in OpenEMR Modules UI
     *
     * @param int|string $modId
     * @param string $currentActionStatus
     * @return mixed
     */
    private function upgrade($modId, $currentActionStatus): mixed
    {
        try {
            SiteAdminInstaller::install();
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('SiteAdmin ModuleManagerListener upgrade error: ' . $e->getMessage());
            return 'Upgrade warning: ' . $e->getMessage();
        }
    }

    /**
     * Fired when admin clicks "Enable" in OpenEMR Modules UI
     *
     * @param int|string $modId
     * @param string $currentActionStatus
     * @return mixed
     */
    private function enable($modId, $currentActionStatus): mixed
    {
        try {
            SiteAdminInstaller::install();
            self::setModuleActiveState($modId, '1', '0');
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('SiteAdmin ModuleManagerListener enable error: ' . $e->getMessage());
            return 'Enable warning: ' . $e->getMessage();
        }
    }

    /**
     * Fired when admin clicks "Disable" in OpenEMR Modules UI
     *
     * @param int|string $modId
     * @param string $currentActionStatus
     * @return mixed
     */
    private function disable($modId, $currentActionStatus): mixed
    {
        try {
            SiteAdminInstaller::disable();
            self::setModuleActiveState($modId, '0', '1');
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('SiteAdmin ModuleManagerListener disable error: ' . $e->getMessage());
            return 'Disable warning: ' . $e->getMessage();
        }
    }

    /**
     * Fired when admin clicks "Unregister" in OpenEMR Modules UI
     *
     * @param int|string $modId
     * @param string $currentActionStatus
     * @return mixed
     */
    private function unregister($modId, $currentActionStatus): mixed
    {
        try {
            SiteAdminInstaller::disable();
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('SiteAdmin ModuleManagerListener unregister error: ' . $e->getMessage());
            return $currentActionStatus;
        }
    }
}
