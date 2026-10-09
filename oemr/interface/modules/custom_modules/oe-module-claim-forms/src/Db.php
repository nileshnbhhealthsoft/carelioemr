<?php

namespace OpenEMR\Modules\ClaimForms;

/**
 * Thin wrapper over OpenEMR's global SQL helpers. Every query takes bound
 * parameters; nothing in this module builds SQL from request data.
 */
class Db
{
    /** @return array<int,array<string,mixed>> */
    public static function all(string $sql, array $binds = []): array
    {
        $rows = [];
        $res = \sqlStatement($sql, $binds);
        while ($row = \sqlFetchArray($res)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function one(string $sql, array $binds = []): ?array
    {
        $row = \sqlQuery($sql, $binds);
        return is_array($row) && $row ? $row : null;
    }

    public static function exec(string $sql, array $binds = []): void
    {
        \sqlStatementNoLog($sql, $binds);
    }

    public static function insert(string $sql, array $binds = []): int
    {
        return (int)\sqlInsert($sql, $binds);
    }

    public static function tableExists(string $table): bool
    {
        return self::one(
            "SELECT 1 AS present FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
            [$table]
        ) !== null;
    }

    /** @return int[] ids safe for IN() lists */
    public static function ints(array $values): array
    {
        return array_values(array_filter(array_map('intval', $values), fn($v) => $v > 0));
    }

    public static function placeholders(int $n): string
    {
        return implode(',', array_fill(0, max(1, $n), '?'));
    }
}
