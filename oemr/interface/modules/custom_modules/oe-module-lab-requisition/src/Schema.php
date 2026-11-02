<?php

namespace OpenEMR\Modules\LabRequisition;

/** Creates the module tables if they are missing, so a Register-then-Enable install still works. */
class Schema
{
    public static function ensure(): void
    {
        if (Db::tableExists('labreq_order') && Db::tableExists('labreq_order_item') && Db::tableExists('labreq_event')) {
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
