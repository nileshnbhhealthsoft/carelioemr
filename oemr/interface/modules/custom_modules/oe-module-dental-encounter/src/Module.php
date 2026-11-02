<?php
namespace Carelio\DentalEncounter;

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;
use RuntimeException;

final class Module
{
    private static function activeSession(): mixed
    {
        return class_exists(SessionWrapperFactory::class)
            ? SessionWrapperFactory::getInstance()->getActiveSession()
            : null;
    }

    /** OpenEMR registers custom modules under their installed folder name. */
    public static function directory(): string
    {
        return basename(dirname(__DIR__));
    }

    public static function baseUrl(): string
    {
        return rtrim($GLOBALS['webroot'], '/') . '/interface/modules/custom_modules/'
            . rawurlencode(self::directory());
    }

    /** Read session state through OpenEMR for native sessions and newer wrapped sessions. */
    public static function session(string $key, mixed $default = null): mixed
    {
        if (self::activeSession() !== null) {
            $value = self::activeSession()->get($key);
        } else {
            // OpenEMR 7.0.3 and older supported 7.x releases use the native session array.
            $value = $_SESSION[$key] ?? null;
        }
        return $value ?? $default;
    }

    public static function userId(): int
    {
        $value = self::session('authUserID', 0);
        return is_numeric($value) ? (int)$value : 0;
    }

    public static function username(): string
    {
        return (string)self::session('authUser', '');
    }

    public static function provider(): string
    {
        return (string)self::session('authProvider', '');
    }

    public static function patientId(): int
    {
        $value = self::session('pid', 0);
        return is_numeric($value) ? (int)$value : 0;
    }

    public static function encounterId(): int
    {
        $value = self::session('encounter', 0);
        return is_numeric($value) ? (int)$value : 0;
    }

    public static function userAuthorized(): int
    {
        return empty(self::session('userauthorized')) ? 0 : 1;
    }

    /** Accommodate the CSRF API signatures used by OpenEMR 7.x, 8.0/8.1, and 8.2+. */
    public static function csrfToken(): string
    {
        $parameters = (new \ReflectionMethod(CsrfUtils::class, 'collectCsrfToken'))->getParameters();
        $session = self::activeSession();
        if (isset($parameters[0]) && $parameters[0]->getName() === 'session') {
            return (string)CsrfUtils::collectCsrfToken($session);
        }
        if (isset($parameters[1]) && $parameters[1]->getName() === 'session') {
            return (string)CsrfUtils::collectCsrfToken('default', $session);
        }
        return (string)CsrfUtils::collectCsrfToken();
    }

    public static function verifyCsrfToken(mixed $token): bool
    {
        $parameters = (new \ReflectionMethod(CsrfUtils::class, 'verifyCsrfToken'))->getParameters();
        $session = self::activeSession();
        if (isset($parameters[1]) && $parameters[1]->getName() === 'session') {
            return CsrfUtils::verifyCsrfToken($token, $session);
        }
        if (isset($parameters[2]) && $parameters[2]->getName() === 'session') {
            return CsrfUtils::verifyCsrfToken($token, 'default', $session);
        }
        return CsrfUtils::verifyCsrfToken($token);
    }

    public static function requireEnabled(): void
    {
        if (self::userId() < 1 || self::username() === '') {
            throw new RuntimeException('Your OpenEMR session has expired. Sign in again and reopen the dental encounter.', 401);
        }
        $directory = self::directory();
        $module = Encounter::row(
            'SELECT mod_id, mod_active FROM modules WHERE mod_directory = ? AND type != 1',
            [$directory]
        );
        if (!$module) {
            throw new RuntimeException(
                'The dental module folder "' . $directory . '" is not registered in this OpenEMR site. '
                . 'Ask an administrator to register, install, and enable it in Modules > Manage Modules.',
                503
            );
        }
        if ((int)$module['mod_active'] !== 1) {
            throw new RuntimeException(
                'The dental module "' . $directory . '" is disabled. '
                . 'Ask an administrator to enable it in Modules > Manage Modules, then reopen the encounter.',
                403
            );
        }
    }
}
