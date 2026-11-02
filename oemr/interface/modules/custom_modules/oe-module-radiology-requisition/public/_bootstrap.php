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
    $prefix = 'OpenEMR\\Modules\\RadiologyRequisition\\';
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

\OpenEMR\Modules\RadiologyRequisition\Schema::ensure();

/** Reads a value from the OpenEMR session (OpenEMR 8 keeps it in a session object, not always in $_SESSION). */
function radreq_session(string $key): mixed
{
    return SessionWrapperFactory::getInstance()->getActiveSession()->get($key) ?? ($_SESSION[$key] ?? null);
}

function radreq_pid(): int
{
    return (int)radreq_session('pid');
}

function radreq_encounter(): int
{
    return (int)radreq_session('encounter');
}

function radreq_user_id(): int
{
    return (int)radreq_session('authUserID');
}

function radreq_csrf(): string
{
    return CsrfUtils::collectCsrfToken(SessionWrapperFactory::getInstance()->getActiveSession());
}

function radreq_verify_csrf(?string $token): void
{
    if (!$token || !CsrfUtils::verifyCsrfToken($token, SessionWrapperFactory::getInstance()->getActiveSession())) {
        CsrfUtils::csrfNotVerified();
    }
}

/** Loads an order and refuses anything that belongs to a different patient than the open chart. */
function radreq_load_order(int $id, int $pid): array
{
    $order = \OpenEMR\Modules\RadiologyRequisition\OrderRepository::get($id);
    if (!$order || (int)$order['pid'] !== $pid) {
        http_response_code(404);
        echo xlt('Requisition not found');
        exit;
    }
    return $order;
}
