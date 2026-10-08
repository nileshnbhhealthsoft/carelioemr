<?php

namespace OpenEMR\Modules\ClaimForms;

/** Creates the module tables if they are missing, so a Register-then-Enable install still works. */
class Schema
{
    public static function ensure(): void
    {
        if (Db::tableExists('claimforms_claim') && Db::tableExists('claimforms_event')) {
            return;
        }
        $sql = file_get_contents(dirname(__DIR__) . '/sql/table.sql');
        foreach (preg_split('/;\s*\n/', (string)$sql) as $stmt) {
            $stmt = trim(preg_replace('/^\s*--.*$/m', '', $stmt));
            if ($stmt !== '') {
                Db::exec($stmt);
            }
        }
    }
}
