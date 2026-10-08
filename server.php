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

// Laravel public assets live under public/, but this dev server runs from the project root.
if ($uri !== '/' && file_exists($publicPath . '/public' . $uri)) {
    $asset = $publicPath . '/public' . $uri;
    if (is_file($asset)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
        ];
        $ext = strtolower(pathinfo($asset, PATHINFO_EXTENSION));
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        readfile($asset);
        return true;
    }
}

// OpenEMR may generate root-relative /index.php links when running at this port.
// Route those back to the bundled OpenEMR front controller instead of Laravel.
if ($uri === '/index.php' && !empty($_GET['site'])) {
    $target = $publicPath . '/oemr/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $target;
    $_SERVER['SCRIPT_NAME'] = $uri;
    $_SERVER['PHP_SELF'] = $uri;
    chdir(dirname($target));
    require $target;
    return true;
}

// OpenEMR's Laminas/Zend modules are served through their public index.php.
foreach (['/interface/modules/zend_modules/public', '/oemr/interface/modules/zend_modules/public'] as $zendPrefix) {
    if ($uri === $zendPrefix || str_starts_with($uri, $zendPrefix . '/')) {
        $pathInfo = substr($uri, strlen($zendPrefix));
        if ($pathInfo === '' || $pathInfo === '/') {
            $pathInfo = '/';
        }
        $target = $publicPath . '/oemr/interface/modules/zend_modules/public/index.php';
        $_SERVER['SCRIPT_FILENAME'] = $target;
        $_SERVER['SCRIPT_NAME'] = $zendPrefix . '/index.php';
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'] . ($pathInfo === '/' ? '' : $pathInfo);
        $_SERVER['PATH_INFO'] = $pathInfo;
        chdir(dirname($target));
        require $target;
        return true;
    }
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

require_once $publicPath . '/public/index.php';
