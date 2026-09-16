<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

class OpenemrDemographicsLayoutSeeder extends Seeder
{
    /**
     * Comprehensive array of typical unused OpenEMR demographic fields.
     * In OpenEMR layout_options:
     * uor = 0 : Unused / Hidden
     * uor = 1 : Optional
     * uor = 2 : Required
     */
    protected array $unusedFields = [
        'ssn',
        'ss',
        'drivers_license',
        'mothers_name',
        'guardiansname',
        'usertext1',
        'usertext2',
        'usertext3',
        'usertext4',
        'userdate1',
        'userdate2',
        'userssn1',
        'userssn2',
        'pubpid',
        'referral_source',
        'tribal_affiliate',
        'ethnic_group',
    ];

    /**
     * Optional connection or database name passed upon instantiation.
     */
    public function __construct(
        protected ?string $connection = null
    ) {}

    /**
     * Run the database seeds to hide unused OpenEMR demographic fields.
     *
     * @param string|null $connection Dynamic connection name or target tenant database name
     * @return int Number of updated layout option rows
     */
    public function run(?string $connection = null): int
    {
        $target = $connection ?? $this->connection;

        // If target is empty, use current default connection from DB facade
        if (empty($target)) {
            $connName = config('database.default', 'mysql');
        } else {
            $connName = $target;
        }

        // Check if $connName is an existing connection or a dynamic database name
        if (!config("database.connections.{$connName}")) {
            // Dynamically register a tenant connection using default driver credentials
            $defaultDriver = config('database.default', 'mysql');
            $tenantConfig = config("database.connections.{$defaultDriver}", []);
            $tenantConfig['database'] = $connName;
            config(["database.connections.{$connName}" => $tenantConfig]);
            DB::purge($connName);
        }

        $db = DB::connection($connName);

        $affected = $db->table('layout_options')
            ->where('form_id', 'DEM')
            ->whereIn('field_id', $this->unusedFields)
            ->update(['uor' => 0]);

        $message = "OpenEMR Demographics Layout Seeder: Set uor = 0 (Unused/Hidden) for {$affected} demographic fields on [{$connName}].";

        if ($this->command) {
            $this->command->info($message);
        }

        Log::info($message, [
            'form_id' => 'DEM',
            'updated_count' => $affected,
            'connection' => $connName,
        ]);

        return $affected;
    }

    /**
     * Directly seed a PDO connection (e.g. within provisioning pipeline).
     *
     * @param PDO $pdo
     * @return int
     */
    public function runOnPdo(PDO $pdo): int
    {
        $placeholders = implode(',', array_fill(0, count($this->unusedFields), '?'));
        $sql = "UPDATE layout_options SET uor = 0 WHERE form_id = 'DEM' AND field_id IN ({$placeholders})";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($this->unusedFields);

        $affected = $stmt->rowCount();
        Log::info("OpenEMR Demographics Layout Seeder (PDO): Set uor = 0 for {$affected} fields on tenant database.");

        return $affected;
    }

    /**
     * Get the list of unused fields configured in this seeder.
     *
     * @return array
     */
    public function getUnusedFields(): array
    {
        return $this->unusedFields;
    }
}

