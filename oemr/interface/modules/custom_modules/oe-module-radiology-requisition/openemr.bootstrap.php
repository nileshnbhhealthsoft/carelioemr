<?php

/**
 * Radiology Requisition module entry point. OpenEMR includes this file for every enabled
 * module, with $eventDispatcher and $classLoader already in scope.
 */

use OpenEMR\Modules\RadiologyRequisition\Bootstrap;

/** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher */
/** @var \OpenEMR\Core\ModulesClassLoader $classLoader */

$classLoader->registerNamespaceIfNotExists(
    'OpenEMR\\Modules\\RadiologyRequisition\\',
    __DIR__ . DIRECTORY_SEPARATOR . 'src'
);

$bootstrap = new Bootstrap($eventDispatcher);
$bootstrap->subscribeToEvents();
