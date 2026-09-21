<?php

namespace OpenEMR\Modules\SiteAdmin\Security;

use OpenEMR\Common\Session\SessionWrapperFactory;

class SiteAdminGuard
{
    /**
     * Group value identifier for Site Administrator in phpGACL
     */
    public const GROUP_VALUE = 'site_admin';

    /**
     * Checks whether the specified user (or currently logged in user) is a Site Administrator.
     *
     * @param string|null $username
     * @return bool
     */
    public static function isSiteAdmin(?string $username = null): bool
    {
        $resolvedUsername = $username ?? self::getCurrentUsername();
        if (empty($resolvedUsername)) {
            return false;
        }

        // The root super administrator is NEVER a Site Administrator
        if (strcasecmp($resolvedUsername, 'admin') === 0) {
            return false;
        }

        // Query phpGACL for membership in the 'site_admin' group
        if (function_exists('sqlQuery')) {
            $row = sqlQuery(
                "SELECT 1 FROM gacl_groups_aro_map gam " .
                "JOIN gacl_aro_groups g ON gam.group_id = g.id " .
                "JOIN gacl_aro a ON gam.aro_id = a.id " .
                "WHERE a.section_value = 'users' AND a.value = ? AND g.value = ? " .
                "LIMIT 1",
                [$resolvedUsername, self::GROUP_VALUE]
            );

            return !empty($row);
        }

        if (class_exists(\OpenEMR\Gacl\GaclApi::class)) {
            try {
                $gacl = new \OpenEMR\Gacl\GaclApi();
                $userAroId = $gacl->get_object_id('users', $resolvedUsername, 'ARO');
                $siteAdminId = $gacl->get_group_id(self::GROUP_VALUE, null, 'ARO');
                if ($userAroId && $siteAdminId) {
                    $groups = $gacl->get_object_groups($userAroId, 'ARO', 'RECURSE');
                    return in_array($siteAdminId, $groups, true);
                }
            } catch (\Throwable $e) {
                // Fallback failed
            }
        }

        return false;
    }

    /**
     * Checks whether the specified user (or currently logged in user) is a Super Administrator.
     *
     * @param string|null $username
     * @return bool
     */
    public static function isSuperAdmin(?string $username = null): bool
    {
        $resolvedUsername = $username ?? self::getCurrentUsername();
        if (empty($resolvedUsername)) {
            return false;
        }

        if (strcasecmp($resolvedUsername, 'admin') === 0) {
            return true;
        }

        if (function_exists('sqlQuery')) {
            $row = sqlQuery(
                "SELECT 1 FROM gacl_groups_aro_map gam " .
                "JOIN gacl_aro_groups g ON gam.group_id = g.id " .
                "JOIN gacl_aro a ON gam.aro_id = a.id " .
                "WHERE a.section_value = 'users' AND a.value = ? AND g.value = 'admin' " .
                "LIMIT 1",
                [$resolvedUsername]
            );

            return !empty($row);
        }

        if (class_exists(\OpenEMR\Gacl\GaclApi::class)) {
            try {
                $gacl = new \OpenEMR\Gacl\GaclApi();
                $userAroId = $gacl->get_object_id('users', $resolvedUsername, 'ARO');
                $superAdminId = $gacl->get_group_id('admin', null, 'ARO');
                if ($userAroId && $superAdminId) {
                    $groups = $gacl->get_object_groups($userAroId, 'ARO', 'RECURSE');
                    return in_array($superAdminId, $groups, true);
                }
            } catch (\Throwable $e) {
                // Fallback failed
            }
        }

        return false;
    }

    /**
     * Resolve the current username from session or globals.
     *
     * @return string|null
     */
    public static function getCurrentUsername(): ?string
    {
        try {
            if (class_exists(SessionWrapperFactory::class)) {
                $session = SessionWrapperFactory::getInstance()->getActiveSession();
                if ($session) {
                    $user = $session->get('authUser');
                    if (!empty($user)) {
                        return $user;
                    }

                    $authUserId = $session->get('authUserID');
                    if (!empty($authUserId) && function_exists('sqlQuery')) {
                        $row = sqlQuery("SELECT username FROM users WHERE id = ?", [$authUserId]);
                        if (!empty($row['username'])) {
                            return $row['username'];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Session not initialized or available
        }

        if (!empty($_SESSION['authUser'])) {
            return $_SESSION['authUser'];
        }

        if (!empty($GLOBALS['authUser'])) {
            return $GLOBALS['authUser'];
        }

        return null;
    }
}
