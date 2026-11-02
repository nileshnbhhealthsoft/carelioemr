<?php

/**
 * Lab Requisition module entry point. OpenEMR includes this file for every enabled
 * module, with $eventDispatcher and $classLoader already in scope.
 */

use OpenEMR\Modules\LabRequisition\Bootstrap;

/** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher */
/** @var \OpenEMR\Core\ModulesClassLoader $classLoader */

$classLoader->registerNamespaceIfNotExists(
    'OpenEMR\\Modules\\LabRequisition\\',
    __DIR__ . DIRECTORY_SEPARATOR . 'src'
);

$bootstrap = new Bootstrap($eventDispatcher);
$bootstrap->subscribeToEvents();
