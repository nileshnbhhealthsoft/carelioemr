<?php

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
            return (string)$this->$methodName($modId, $currentActionStatus);
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
        return $currentActionStatus;
    }

    private function enable($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    private function disable($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    private function unregister($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    private function install_sql($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    private function upgrade_sql($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }
}
