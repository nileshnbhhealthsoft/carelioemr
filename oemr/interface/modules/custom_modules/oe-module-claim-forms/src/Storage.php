<?php

namespace OpenEMR\Modules\ClaimForms;

/**
 * File storage under the site's documents folder, which OpenEMR already keeps
 * out of direct web access and includes in site backups.
 */
class Storage
{
    public static function root(): string
    {
        $dir = rtrim((string)($GLOBALS['OE_SITE_DIR'] ?? ''), '/') . '/documents/claim_forms';
        self::mkdir($dir);
        return $dir;
    }

    public static function signatureDir(): string
    {
        $d = self::root() . '/signatures';
        self::mkdir($d);
        return $d;
    }

    public static function claimDir(int $pid): string
    {
        $d = self::root() . '/claims/' . $pid;
        self::mkdir($d);
        return $d;
    }

    /** Never builds a path from a name: the file name is derived from the numeric user id only. */
    public static function userImagePath(int $userId, string $kind): ?string
    {
        if (!in_array($kind, ['signature', 'stamp'], true) || $userId <= 0) {
            return null;
        }
        return self::signatureDir() . '/user_' . $userId . '_' . $kind . '.png';
    }

    private static function mkdir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create storage folder');
        }
        $htaccess = self::rootNoCreate() . '/.htaccess';
        if (!is_file($htaccess) && is_dir(self::rootNoCreate())) {
            @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        }
    }

    private static function rootNoCreate(): string
    {
        return rtrim((string)($GLOBALS['OE_SITE_DIR'] ?? ''), '/') . '/documents/claim_forms';
    }
}
