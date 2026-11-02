<?php

use OpenEMR\Core\AbstractModuleActionListener;
use OpenEMR\Core\OEGlobalsBag;

/**
 * Module Manager Listener for oe-module-ipd (Inpatient & Hospital Management)
 *
 * Implements OpenEMR Laminas Module Manager lifecycle hooks
 * (install, enable, disable, unregister, install_sql, upgrade_sql).
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class ModuleManagerListener extends AbstractModuleActionListener
{
    private $authUser;

    public function __construct()
    {
        parent::__construct();
        $this->authUser = (int)$this->getSession('authUserID');
    }

    /**
     * @param string $methodName
     * @param int|string $modId
     * @param string $currentActionStatus
     * @return string
     */
    public function moduleManagerAction($methodName, $modId, string $currentActionStatus = 'Success'): string
    {
        if (method_exists($this, $methodName)) {
            return (string)$this->$methodName($modId, $currentActionStatus);
        } else {
            return "Module cleanup method $methodName does not exist.";
        }
    }

    /**
     * Namespace for the IPD Module
     *
     * @return string
     */
    public static function getModuleNamespace(): string
    {
        return 'OpenEMR\\Modules\\Ipd\\';
    }

    /**
     * Factory method required by Laminas Module Manager
     *
     * @return ModuleManagerListener
     */
    public static function initListenerSelf(): ModuleManagerListener
    {
        return new self();
    }

    /**
     * Lifecycle Action: Install
     */
    private function install($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * Lifecycle Action: Enable
     */
    private function enable($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * Lifecycle Action: Disable
     */
    private function disable($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * Lifecycle Action: Unregister
     */
    private function unregister($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * Lifecycle Action: Install SQL
     */
    private function install_sql($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * Lifecycle Action: Upgrade SQL
     */
    private function upgrade_sql($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }
}
