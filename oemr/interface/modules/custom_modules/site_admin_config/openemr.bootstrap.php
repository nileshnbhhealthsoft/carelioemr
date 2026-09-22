<?php

/**
 * Bootstrap for CarelioEMR Site Administrator Custom Module.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\SiteAdmin;

use OpenEMR\Core\OEGlobalsBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Register PSR-4 autoloader for this module
 * @var \OpenEMR\Core\ModulesClassLoader $classLoader
 */
if (isset($classLoader) && is_object($classLoader) && method_exists($classLoader, 'registerNamespaceIfNotExists')) {
    $classLoader->registerNamespaceIfNotExists('OpenEMR\\Modules\\SiteAdmin\\', __DIR__ . DIRECTORY_SEPARATOR . 'src');
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'OpenEMR\\Modules\\SiteAdmin\\';
        $base_dir = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

/**
 * Resolve event dispatcher safely
 * @var EventDispatcherInterface $eventDispatcher
 */
$dispatcher = null;
if (isset($eventDispatcher) && $eventDispatcher instanceof EventDispatcherInterface) {
    $dispatcher = $eventDispatcher;
} elseif (class_exists(OEGlobalsBag::class) && OEGlobalsBag::getInstance()->hasKernel()) {
    $dispatcher = OEGlobalsBag::getInstance()->getKernel()->getEventDispatcher();
}

if ($dispatcher) {
    $bootstrap = new Bootstrap($dispatcher);
    $bootstrap->subscribeToEvents();
}

