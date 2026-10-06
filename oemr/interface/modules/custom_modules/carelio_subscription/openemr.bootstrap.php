<?php

/**
 * Bootstrap for the independent Carelio Subscription module.
 */

namespace OpenEMR\Modules\CarelioSubscription;

use OpenEMR\Core\OEGlobalsBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

if (isset($classLoader) && is_object($classLoader) && method_exists($classLoader, 'registerNamespaceIfNotExists')) {
    $classLoader->registerNamespaceIfNotExists('OpenEMR\\Modules\\CarelioSubscription\\', __DIR__ . DIRECTORY_SEPARATOR . 'src');
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'OpenEMR\\Modules\\CarelioSubscription\\';
        $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        $length = strlen($prefix);
        if (strncmp($prefix, $class, $length) !== 0) {
            return;
        }
        $relativeClass = substr($class, $length);
        $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

$dispatcher = null;
if (isset($eventDispatcher) && $eventDispatcher instanceof EventDispatcherInterface) {
    $dispatcher = $eventDispatcher;
} elseif (class_exists(OEGlobalsBag::class) && OEGlobalsBag::getInstance()->hasKernel()) {
    $dispatcher = OEGlobalsBag::getInstance()->getKernel()->getEventDispatcher();
}

if ($dispatcher) {
    (new Bootstrap($dispatcher))->subscribeToEvents();
}

if (class_exists(\OpenEMR\Modules\CarelioSubscription\Security\SubscriptionEnforcementService::class)) {
    \OpenEMR\Modules\CarelioSubscription\Security\SubscriptionEnforcementService::enforce();
}
