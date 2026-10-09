<?php

/**
 * Shared start-up for every public page: loads OpenEMR, checks the user may be here,
 * and makes sure the module's tables exist. Being under the web root is not
 * authorization, so each page includes this first.
 */

$ignoreAuth = false;
require_once __DIR__ . '/../../../../globals.php';

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;

spl_autoload_register(function (string $class): void {
    $prefix = 'OpenEMR\\Modules\\ClaimForms\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

if (!AclMain::aclCheckCore('patients', 'med')) {
    http_response_code(403);
    echo xlt('Not authorized');
    exit;
}

\OpenEMR\Modules\ClaimForms\Schema::ensure();

/** Reads a value from the OpenEMR session (OpenEMR 8 keeps it in a session object, not always in $_SESSION). */
function claimforms_session(string $key): mixed
{
    return SessionWrapperFactory::getInstance()->getActiveSession()->get($key) ?? ($_SESSION[$key] ?? null);
}

function claimforms_pid(): int
{
    return (int)claimforms_session('pid');
}

function claimforms_user_id(): int
{
    return (int)claimforms_session('authUserID');
}

function claimforms_csrf(): string
{
    return CsrfUtils::collectCsrfToken(SessionWrapperFactory::getInstance()->getActiveSession());
}

function claimforms_verify_csrf(?string $token): void
{
    if (!$token || !CsrfUtils::verifyCsrfToken($token, SessionWrapperFactory::getInstance()->getActiveSession())) {
        CsrfUtils::csrfNotVerified();
    }
}

function claimforms_json(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
