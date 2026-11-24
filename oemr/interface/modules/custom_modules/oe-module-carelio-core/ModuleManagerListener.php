<?php

/**
 * Carelio Hospital EHR - CORE Foundation Module Manager Listener
 *
 * @package   OpenEMR Modules
 * @link      https://www.carelioemr.com
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use OpenEMR\Core\AbstractModuleActionListener;

class ModuleManagerListener extends AbstractModuleActionListener
{
    public function __construct()
    {
        parent::__construct();
    }

    public function moduleManagerAction($methodName, $modId, string $currentActionStatus = 'Success'): string
    {
        if (method_exists($this, $methodName)) {
            return $this->{$methodName}($modId, $currentActionStatus);
        }
        return $currentActionStatus;
    }

    public static function getModuleNamespace(): string
    {
        return 'OpenEMR\\Modules\\CarelioCore\\';
    }

    public static function initListenerSelf(): ModuleManagerListener
    {
        return new self();
    }

    private function install($modId, $currentActionStatus): mixed
    {
        try {
            self::setModuleActiveState($modId, '0', '1');
            return $currentActionStatus;
        } catch (\Throwable $e) {
            error_log('CarelioCore install error: ' . $e->getMessage());
            return 'Install warning: ' . $e->getMessage();
        }
    }

    private function enable($modId, $currentActionStatus): mixed
    {
        self::setModuleActiveState($modId, '0', '1');
        return $currentActionStatus;
    }

    private function disable($modId, $currentActionStatus): mixed
    {
        self::setModuleActiveState($modId, '1', '0');
        return $currentActionStatus;
    }
}
