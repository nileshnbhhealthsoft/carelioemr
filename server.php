<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * This file allows us to emulate Apache's "mod_rewrite" functionality from the
 * built-in PHP web server, including internal rewrite for OpenEMR paths.
 */

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Normalize list navigation POST in edit_list.php to GET so read-only dropdown switches don't trigger CSRF rejection
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && str_ends_with($uri, 'edit_list.php') && empty($_POST['formaction']) && !empty($_POST['list_id'])) {
    $params = ['list_id' => $_POST['list_id']];
    if (!empty($_GET['site'])) {
        $params['site'] = $_GET['site'];
    }
    if (!empty($_POST['list_from'])) {
        $params['list_from'] = $_POST['list_from'];
    }
    header('Location: ' . $uri . '?' . http_build_query($params), true, 303);
    exit;
}

// If request is for an existing static file or directory in public/
if ($uri !== '/' && file_exists($publicPath . $uri)) {
    return false;
}

// Emulate Apache rewrite for OpenEMR paths: /interface/... -> /oemr/interface/...
if (preg_match('#^/(interface|portal|apis|library|custom|sites|templates|swagger|ccdaservice)(/.*)?$#', $uri)) {
    $target = $publicPath . '/oemr' . $uri;
    if (file_exists($target)) {
        if (!is_dir($target)) {
            $_SERVER['SCRIPT_FILENAME'] = $target;
            $_SERVER['SCRIPT_NAME'] = $uri;
            $_SERVER['PHP_SELF'] = $uri;
            chdir(dirname($target));
            require $target;
            return true;
        }
        return false;
    }
}

require_once $publicPath . '/index.php';

