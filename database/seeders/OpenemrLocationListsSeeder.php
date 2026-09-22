<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

class OpenemrLocationListsSeeder extends Seeder
{
    /**
     * Optional connection or database name passed upon instantiation.
     */
    public function __construct(
        protected ?string $connection = null
    ) {}

    /**
     * Run the database seeds for OpenEMR Location Lists (Country, State, County).
     *
     * @param string|null $connection Dynamic connection name or target tenant database name
     * @return array Summary of affected records per list_id
     */
    public function run(?string $connection = null): array
    {
        $target = $connection ?? $this->connection;

        if (empty($target)) {
            $connName = config('database.default', 'mysql');
        } else {
            $connName = $target;
        }

        // Dynamically register tenant connection if not already registered
        if (!config("database.connections.{$connName}")) {
            $defaultDriver = config('database.default', 'mysql');
            $tenantConfig = config("database.connections.{$defaultDriver}", []);
            $tenantConfig['database'] = $connName;
            config(["database.connections.{$connName}" => $tenantConfig]);
            DB::purge($connName);
        }

        $pdo = DB::connection($connName)->getPdo();
        $results = $this->runOnPdo($pdo);

        $message = "OpenEMR Location Lists Seeder completed on [{$connName}]: " .
            "Countries: {$results['country']}, States/Provinces: {$results['state']}, Counties/Districts: {$results['county']}.";

        if ($this->command) {
            $this->command->info($message);
        }

        Log::info($message, [
            'connection' => $connName,
            'summary' => $results,
        ]);

        return $results;
    }

    /**
     * Directly seed an active PDO connection (used in tenant provisioning pipeline).
     *
     * @param PDO $pdo
     * @return array
     */
    public function runOnPdo(PDO $pdo): array
    {
        $countries = $this->getCountryData();
        $states = $this->getStateData();
        $counties = $this->getCountyData();

        $cCount = $this->upsertListOptions($pdo, $countries);
        $sCount = $this->upsertListOptions($pdo, $states);
        $coCount = $this->upsertListOptions($pdo, $counties);

        // Configure demographics layout options (Country, State, County + dynamic cascading script)
        $this->configureDemographicsLayout($pdo, $countries, $states, $counties);

        return [
            'country' => $cCount,
            'state' => $sCount,
            'county' => $coCount,
            'total' => $cCount + $sCount + $coCount,
        ];
    }

    /**
     * Configure layout_options for patient demographics (form_id = 'DEM'):
     * 1. Re-orders location fields: Country (seq 4), State (seq 5), County (seq 6), Postal Code (seq 7).
     * 2. Binds them to their respective lists with data_type = 26 (List Box with Add) and uor = 1 (Optional).
     * 3. Injects a client-side cascading script (data_type = 31, Static Text) to link Country -> State -> County dynamically.
     *
     * @param PDO $pdo
     * @param array|null $countries
     * @param array|null $states
     * @param array|null $counties
     * @return void
     */
    public function configureDemographicsLayout(PDO $pdo, ?array $countries = null, ?array $states = null, ?array $counties = null): void
    {
        $countries = $countries ?? $this->getCountryData();
        $states = $states ?? $this->getStateData();
        $counties = $counties ?? $this->getCountyData();

        // 1. Ensure location fields have data_type = 26, proper list_id, and logical sequence
        $pdo->exec("
            UPDATE layout_options 
            SET data_type = 26, list_id = 'country', uor = 1, seq = 4, title = 'Country' 
            WHERE form_id = 'DEM' AND field_id = 'country_code'
        ");

        $pdo->exec("
            UPDATE layout_options 
            SET data_type = 26, list_id = 'country', uor = 1, seq = 4, title = 'Country' 
            WHERE form_id = 'DEM' AND field_id = 'country'
        ");

        $pdo->exec("
            UPDATE layout_options 
            SET data_type = 26, list_id = 'state', uor = 1, seq = 5, title = 'State' 
            WHERE form_id = 'DEM' AND field_id = 'state'
        ");

        $pdo->exec("
            UPDATE layout_options 
            SET data_type = 26, list_id = 'county', uor = 1, seq = 6, title = 'County' 
            WHERE form_id = 'DEM' AND field_id = 'county'
        ");

        $pdo->exec("
            UPDATE layout_options 
            SET seq = 7 
            WHERE form_id = 'DEM' AND field_id = 'postal_code'
        ");

        // 2. Build cascading lookup maps
        $countryStatesMap = [];
        $stateToCountry = [];
        foreach ($states as $s) {
            $cCode = $s['mapping'] ?? '';
            if ($cCode) {
                $countryStatesMap[$cCode][] = [
                    'id' => $s['option_id'],
                    'title' => $s['title'],
                ];
                $stateToCountry[$s['option_id']] = $cCode;
            }
        }

        $stateCountiesMap = [];
        $countyToState = [];
        foreach ($counties as $co) {
            $sCode = $co['mapping'] ?? '';
            if ($sCode) {
                $stateCountiesMap[$sCode][] = [
                    'id' => $co['option_id'],
                    'title' => $co['title'],
                ];
                $countyToState[$co['option_id']] = $sCode;
            }
        }

        $allStatesList = [];
        foreach ($states as $s) {
            $allStatesList[] = [
                'id' => $s['option_id'],
                'title' => $s['title'],
            ];
        }

        $jsonCountryStates = json_encode($countryStatesMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $jsonStateCounties = json_encode($stateCountiesMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $jsonStateToCountry = json_encode($stateToCountry, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $jsonCountyToState = json_encode($countyToState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $jsonAllStates = json_encode($allStatesList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        // 3. Construct lightweight client-side cascading script
        $scriptHtml = '<div style="display:none;" id="carelio_cascading_locations_container">' . "\n" .
'<script>' . "\n" .
'(function() {' . "\n" .
'    var countryStates = ' . $jsonCountryStates . ';' . "\n" .
'    var stateCounties = ' . $jsonStateCounties . ';' . "\n" .
'    var stateToCountry = ' . $jsonStateToCountry . ';' . "\n" .
'    var countyToState = ' . $jsonCountyToState . ';' . "\n" .
'    var allStates = ' . $jsonAllStates . ';' . "\n" .
'    function setupLocationCascade() {' . "\n" .
'        var countryEl = document.getElementById("form_country_code") || document.getElementById("form_country");' . "\n" .
'        var stateEl = document.getElementById("form_state");' . "\n" .
'        var countyEl = document.getElementById("form_county");' . "\n" .
'        if (!countryEl || !stateEl) return;' . "\n" .
'        if (countryEl.getAttribute("data-cascade-ready") === "1") return;' . "\n" .
'        countryEl.setAttribute("data-cascade-ready", "1");' . "\n" .
'        function populateSelect(selectEl, items, desiredVal, emptyLabel) {' . "\n" .
'            if (!selectEl) return;' . "\n" .
'            var currentVal = (desiredVal !== undefined && desiredVal !== null) ? desiredVal : selectEl.value;' . "\n" .
'            selectEl.innerHTML = "";' . "\n" .
'            var optEmpty = document.createElement("option");' . "\n" .
'            optEmpty.value = "";' . "\n" .
'            optEmpty.textContent = emptyLabel || " ";' . "\n" .
'            selectEl.appendChild(optEmpty);' . "\n" .
'            var found = false;' . "\n" .
'            if (items && items.length > 0) {' . "\n" .
'                for (var i = 0; i < items.length; i++) {' . "\n" .
'                    var opt = document.createElement("option");' . "\n" .
'                    opt.value = items[i].id;' . "\n" .
'                    opt.textContent = items[i].title;' . "\n" .
'                    if (items[i].id === currentVal) {' . "\n" .
'                        opt.selected = true;' . "\n" .
'                        found = true;' . "\n" .
'                    }' . "\n" .
'                    selectEl.appendChild(opt);' . "\n" .
'                }' . "\n" .
'            }' . "\n" .
'            if (!found && currentVal) {' . "\n" .
'                var optCustom = document.createElement("option");' . "\n" .
'                optCustom.value = currentVal;' . "\n" .
'                optCustom.textContent = currentVal;' . "\n" .
'                optCustom.selected = true;' . "\n" .
'                selectEl.appendChild(optCustom);' . "\n" .
'            }' . "\n" .
'            if (window.jQuery && window.jQuery(selectEl).data("select2")) {' . "\n" .
'                window.jQuery(selectEl).trigger("change.select2");' . "\n" .
'            }' . "\n" .
'        }' . "\n" .
'        function syncStates(countryVal, preserveVal) {' . "\n" .
'            var targetVal = preserveVal ? stateEl.value : "";' . "\n" .
'            var list = countryVal ? (countryStates[countryVal] || []) : allStates;' . "\n" .
'            populateSelect(stateEl, list, targetVal, "Select State / Locality");' . "\n" .
'            syncCounties(stateEl.value, preserveVal);' . "\n" .
'        }' . "\n" .
'        function syncCounties(stateVal, preserveVal) {' . "\n" .
'            if (!countyEl) return;' . "\n" .
'            var targetVal = preserveVal ? countyEl.value : "";' . "\n" .
'            var list = stateVal ? (stateCounties[stateVal] || []) : [];' . "\n" .
'            populateSelect(countyEl, list, targetVal, "Select County / District");' . "\n" .
'        }' . "\n" .
'        var initC = countryEl.value;' . "\n" .
'        var initS = stateEl.value;' . "\n" .
'        var initCo = countyEl ? countyEl.value : "";' . "\n" .
'        if (!initC && initS && stateToCountry[initS]) {' . "\n" .
'            initC = stateToCountry[initS];' . "\n" .
'            countryEl.value = initC;' . "\n" .
'            if (window.jQuery && window.jQuery(countryEl).data("select2")) {' . "\n" .
'                window.jQuery(countryEl).trigger("change.select2");' . "\n" .
'            }' . "\n" .
'        }' . "\n" .
'        if (!initS && initCo && countyToState[initCo]) {' . "\n" .
'            initS = countyToState[initCo];' . "\n" .
'            stateEl.value = initS;' . "\n" .
'            if (!initC && stateToCountry[initS]) {' . "\n" .
'                initC = stateToCountry[initS];' . "\n" .
'                countryEl.value = initC;' . "\n" .
'                if (window.jQuery && window.jQuery(countryEl).data("select2")) {' . "\n" .
'                    window.jQuery(countryEl).trigger("change.select2");' . "\n" .
'                }' . "\n" .
'            }' . "\n" .
'        }' . "\n" .
'        if (initC) syncStates(initC, true);' . "\n" .
'        else if (initS) syncCounties(initS, true);' . "\n" .
'        countryEl.addEventListener("change", function() {' . "\n" .
'            syncStates(this.value, false);' . "\n" .
'        });' . "\n" .
'        stateEl.addEventListener("change", function() {' . "\n" .
'            var stVal = this.value;' . "\n" .
'            if (stVal && stateToCountry[stVal] && countryEl.value !== stateToCountry[stVal]) {' . "\n" .
'                countryEl.value = stateToCountry[stVal];' . "\n" .
'                if (window.jQuery && window.jQuery(countryEl).data("select2")) {' . "\n" .
'                    window.jQuery(countryEl).trigger("change.select2");' . "\n" .
'                }' . "\n" .
'            }' . "\n" .
'            syncCounties(stVal, false);' . "\n" .
'        });' . "\n" .
'    }' . "\n" .
'    if (document.readyState === "loading") {' . "\n" .
'        document.addEventListener("DOMContentLoaded", setupLocationCascade);' . "\n" .
'    } else {' . "\n" .
'        setupLocationCascade();' . "\n" .
'    }' . "\n" .
'    setTimeout(setupLocationCascade, 250);' . "\n" .
'    setTimeout(setupLocationCascade, 800);' . "\n" .
'})();' . "\n" .
'</script>' . "\n" .
'</div>';

        // 4. Upsert location_cascading_script into layout_options (data_type = 31 Static Text)
        $stmtScript = $pdo->prepare("
            INSERT INTO layout_options (
                form_id, field_id, group_id, title, seq, data_type, uor,
                fld_length, max_length, list_id, titlecols, datacols,
                default_value, edit_options, description
            ) VALUES (
                'DEM', 'location_cascading_script', 2, '', 8, 31, 1,
                0, 0, '', 0, 4,
                '', '', ?
            ) ON DUPLICATE KEY UPDATE
                group_id = VALUES(group_id),
                title = VALUES(title),
                seq = VALUES(seq),
                data_type = VALUES(data_type),
                uor = VALUES(uor),
                description = VALUES(description)
        ");
        $stmtScript->execute([$scriptHtml]);

        Log::info("Configured Demographics layout options and location cascading dropdowns on tenant database.");
    }

    /**
     * Batch upsert list_options entries idempotently using INSERT ... ON DUPLICATE KEY UPDATE.
     *
     * @param PDO $pdo
     * @param array $rows
     * @return int Count of items processed
     */
    protected function upsertListOptions(PDO $pdo, array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $sql = "INSERT INTO list_options (
                    list_id, option_id, title, seq, is_default, option_value,
                    mapping, notes, codes, toggle_setting_1, toggle_setting_2,
                    activity, subtype, edit_options
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    seq = VALUES(seq),
                    mapping = VALUES(mapping),
                    activity = VALUES(activity),
                    edit_options = VALUES(edit_options)";

        $stmt = $pdo->prepare($sql);

        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $stmt->execute([
                    $row['list_id'],
                    $row['option_id'],
                    $row['title'],
                    $row['seq'] ?? 0,
                    $row['is_default'] ?? 0,
                    $row['option_value'] ?? 0,
                    $row['mapping'] ?? '',
                    $row['notes'] ?? null,
                    $row['codes'] ?? '',
                    $row['toggle_setting_1'] ?? 0,
                    $row['toggle_setting_2'] ?? 0,
                    $row['activity'] ?? 1,
                    $row['subtype'] ?? '',
                    $row['edit_options'] ?? 1,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return count($rows);
    }

    /**
     * 7 Supported Global Regions: Country List
     */
    public function getCountryData(): array
    {
        return [
            // 1. Caribbean Hub
            ['list_id' => 'country', 'option_id' => 'LC', 'title' => 'Saint Lucia', 'seq' => 10, 'mapping' => 'CB'],
            ['list_id' => 'country', 'option_id' => 'BS', 'title' => 'Bahamas', 'seq' => 11, 'mapping' => 'CB'],
            ['list_id' => 'country', 'option_id' => 'BB', 'title' => 'Barbados', 'seq' => 12, 'mapping' => 'CB'],
            ['list_id' => 'country', 'option_id' => 'JM', 'title' => 'Jamaica', 'seq' => 13, 'mapping' => 'CB'],
            ['list_id' => 'country', 'option_id' => 'BM', 'title' => 'Bermuda', 'seq' => 14, 'mapping' => 'CB'],
            ['list_id' => 'country', 'option_id' => 'DM', 'title' => 'Dominica', 'seq' => 15, 'mapping' => 'CB'],
            ['list_id' => 'country', 'option_id' => 'TT', 'title' => 'Trinidad and Tobago', 'seq' => 16, 'mapping' => 'CB'],
            ['list_id' => 'country', 'option_id' => 'AG', 'title' => 'Antigua and Barbuda', 'seq' => 17, 'mapping' => 'CB'],
            ['list_id' => 'country', 'option_id' => 'KN', 'title' => 'Saint Kitts and Nevis', 'seq' => 18, 'mapping' => 'CB'],
            ['list_id' => 'country', 'option_id' => 'VC', 'title' => 'Saint Vincent & Grenadines', 'seq' => 19, 'mapping' => 'CB'],
            ['list_id' => 'country', 'option_id' => 'GD', 'title' => 'Grenada', 'seq' => 20, 'mapping' => 'CB'],
            ['list_id' => 'country', 'option_id' => 'KY', 'title' => 'Cayman Islands', 'seq' => 21, 'mapping' => 'CB'],

            // 2. United States
            ['list_id' => 'country', 'option_id' => 'US', 'title' => 'United States', 'seq' => 1, 'mapping' => 'US'],
            ['list_id' => 'country', 'option_id' => 'USA', 'title' => 'United States of America', 'seq' => 2, 'mapping' => 'US'],

            // 3. India
            ['list_id' => 'country', 'option_id' => 'IN', 'title' => 'India', 'seq' => 30, 'mapping' => 'IN'],

            // 4. UAE
            ['list_id' => 'country', 'option_id' => 'AE', 'title' => 'United Arab Emirates', 'seq' => 40, 'mapping' => 'AE'],

            // 5. Europe
            ['list_id' => 'country', 'option_id' => 'GB', 'title' => 'United Kingdom', 'seq' => 50, 'mapping' => 'EU'],
            ['list_id' => 'country', 'option_id' => 'IE', 'title' => 'Ireland', 'seq' => 51, 'mapping' => 'EU'],
            ['list_id' => 'country', 'option_id' => 'DE', 'title' => 'Germany', 'seq' => 52, 'mapping' => 'EU'],
            ['list_id' => 'country', 'option_id' => 'FR', 'title' => 'France', 'seq' => 53, 'mapping' => 'EU'],
            ['list_id' => 'country', 'option_id' => 'NL', 'title' => 'Netherlands', 'seq' => 54, 'mapping' => 'EU'],
            ['list_id' => 'country', 'option_id' => 'ES', 'title' => 'Spain', 'seq' => 55, 'mapping' => 'EU'],
            ['list_id' => 'country', 'option_id' => 'IT', 'title' => 'Italy', 'seq' => 56, 'mapping' => 'EU'],
            ['list_id' => 'country', 'option_id' => 'CH', 'title' => 'Switzerland', 'seq' => 57, 'mapping' => 'EU'],
            ['list_id' => 'country', 'option_id' => 'BE', 'title' => 'Belgium', 'seq' => 58, 'mapping' => 'EU'],
            ['list_id' => 'country', 'option_id' => 'SE', 'title' => 'Sweden', 'seq' => 59, 'mapping' => 'EU'],

            // 6. Africa
            ['list_id' => 'country', 'option_id' => 'ZA', 'title' => 'South Africa', 'seq' => 70, 'mapping' => 'AF'],
            ['list_id' => 'country', 'option_id' => 'NG', 'title' => 'Nigeria', 'seq' => 71, 'mapping' => 'AF'],
            ['list_id' => 'country', 'option_id' => 'KE', 'title' => 'Kenya', 'seq' => 72, 'mapping' => 'AF'],
            ['list_id' => 'country', 'option_id' => 'GH', 'title' => 'Ghana', 'seq' => 73, 'mapping' => 'AF'],
            ['list_id' => 'country', 'option_id' => 'EG', 'title' => 'Egypt', 'seq' => 74, 'mapping' => 'AF'],

            // 7. Australia
            ['list_id' => 'country', 'option_id' => 'AU', 'title' => 'Australia', 'seq' => 80, 'mapping' => 'AU'],
        ];
    }

    /**
     * States, Provinces, Parishes & Emirates mapped to parent Country codes
     */
    public function getStateData(): array
    {
        return [
            // --- Caribbean States / Districts / Parishes ---
            // Saint Lucia (LC)
            ['list_id' => 'state', 'option_id' => 'LC_CAS', 'title' => 'Castries', 'seq' => 1, 'mapping' => 'LC'],
            ['list_id' => 'state', 'option_id' => 'LC_GRO', 'title' => 'Gros Islet', 'seq' => 2, 'mapping' => 'LC'],
            ['list_id' => 'state', 'option_id' => 'LC_VIF', 'title' => 'Vieux Fort', 'seq' => 3, 'mapping' => 'LC'],
            ['list_id' => 'state', 'option_id' => 'LC_SOU', 'title' => 'Soufrière', 'seq' => 4, 'mapping' => 'LC'],
            ['list_id' => 'state', 'option_id' => 'LC_DEN', 'title' => 'Dennery', 'seq' => 5, 'mapping' => 'LC'],
            ['list_id' => 'state', 'option_id' => 'LC_MIC', 'title' => 'Micoud', 'seq' => 6, 'mapping' => 'LC'],
            // Jamaica (JM)
            ['list_id' => 'state', 'option_id' => 'JM_KIN', 'title' => 'Kingston', 'seq' => 10, 'mapping' => 'JM'],
            ['list_id' => 'state', 'option_id' => 'JM_AND', 'title' => 'St. Andrew', 'seq' => 11, 'mapping' => 'JM'],
            ['list_id' => 'state', 'option_id' => 'JM_CAT', 'title' => 'St. Catherine', 'seq' => 12, 'mapping' => 'JM'],
            ['list_id' => 'state', 'option_id' => 'JM_JAM', 'title' => 'St. James (Montego Bay)', 'seq' => 13, 'mapping' => 'JM'],
            // Barbados (BB)
            ['list_id' => 'state', 'option_id' => 'BB_MIC', 'title' => 'St. Michael (Bridgetown)', 'seq' => 20, 'mapping' => 'BB'],
            ['list_id' => 'state', 'option_id' => 'BB_CHR', 'title' => 'Christ Church', 'seq' => 21, 'mapping' => 'BB'],
            ['list_id' => 'state', 'option_id' => 'BB_JAM', 'title' => 'St. James', 'seq' => 22, 'mapping' => 'BB'],
            // Bahamas (BS)
            ['list_id' => 'state', 'option_id' => 'BS_NP', 'title' => 'New Providence (Nassau)', 'seq' => 30, 'mapping' => 'BS'],
            ['list_id' => 'state', 'option_id' => 'BS_GB', 'title' => 'Grand Bahama (Freeport)', 'seq' => 31, 'mapping' => 'BS'],
            // Trinidad and Tobago (TT)
            ['list_id' => 'state', 'option_id' => 'TT_POS', 'title' => 'Port of Spain', 'seq' => 40, 'mapping' => 'TT'],
            ['list_id' => 'state', 'option_id' => 'TT_SFO', 'title' => 'San Fernando', 'seq' => 41, 'mapping' => 'TT'],
            ['list_id' => 'state', 'option_id' => 'TT_TOB', 'title' => 'Tobago', 'seq' => 42, 'mapping' => 'TT'],

            // --- United States (Mapped to US) ---
            ['list_id' => 'state', 'option_id' => 'AL', 'title' => 'Alabama', 'seq' => 101, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'AK', 'title' => 'Alaska', 'seq' => 102, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'AZ', 'title' => 'Arizona', 'seq' => 103, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'AR', 'title' => 'Arkansas', 'seq' => 104, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'CA', 'title' => 'California', 'seq' => 105, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'CO', 'title' => 'Colorado', 'seq' => 106, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'CT', 'title' => 'Connecticut', 'seq' => 107, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'DE', 'title' => 'Delaware', 'seq' => 108, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'FL', 'title' => 'Florida', 'seq' => 109, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'GA', 'title' => 'Georgia', 'seq' => 110, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'HI', 'title' => 'Hawaii', 'seq' => 111, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'ID', 'title' => 'Idaho', 'seq' => 112, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'IL', 'title' => 'Illinois', 'seq' => 113, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'IN_US', 'title' => 'Indiana (US)', 'seq' => 114, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'IA', 'title' => 'Iowa', 'seq' => 115, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'KS', 'title' => 'Kansas', 'seq' => 116, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'KY_US', 'title' => 'Kentucky', 'seq' => 117, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'LA', 'title' => 'Louisiana', 'seq' => 118, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'ME', 'title' => 'Maine', 'seq' => 119, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'MD', 'title' => 'Maryland', 'seq' => 120, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'MA', 'title' => 'Massachusetts', 'seq' => 121, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'MI', 'title' => 'Michigan', 'seq' => 122, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'MN', 'title' => 'Minnesota', 'seq' => 123, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'MS', 'title' => 'Mississippi', 'seq' => 124, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'MO', 'title' => 'Missouri', 'seq' => 125, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'MT', 'title' => 'Montana', 'seq' => 126, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'NE', 'title' => 'Nebraska', 'seq' => 127, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'NV', 'title' => 'Nevada', 'seq' => 128, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'NH', 'title' => 'New Hampshire', 'seq' => 129, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'NJ', 'title' => 'New Jersey', 'seq' => 130, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'NM', 'title' => 'New Mexico', 'seq' => 131, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'NY', 'title' => 'New York', 'seq' => 132, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'NC', 'title' => 'North Carolina', 'seq' => 133, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'ND', 'title' => 'North Dakota', 'seq' => 134, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'OH', 'title' => 'Ohio', 'seq' => 135, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'OK', 'title' => 'Oklahoma', 'seq' => 136, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'OR', 'title' => 'Oregon', 'seq' => 137, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'PA', 'title' => 'Pennsylvania', 'seq' => 138, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'RI', 'title' => 'Rhode Island', 'seq' => 139, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'SC', 'title' => 'South Carolina', 'seq' => 140, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'SD', 'title' => 'South Dakota', 'seq' => 141, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'TN', 'title' => 'Tennessee', 'seq' => 142, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'TX', 'title' => 'Texas', 'seq' => 143, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'UT', 'title' => 'Utah', 'seq' => 144, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'VT', 'title' => 'Vermont', 'seq' => 145, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'VA', 'title' => 'Virginia', 'seq' => 146, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'WA', 'title' => 'Washington', 'seq' => 147, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'WV', 'title' => 'West Virginia', 'seq' => 148, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'WI', 'title' => 'Wisconsin', 'seq' => 149, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'WY', 'title' => 'Wyoming', 'seq' => 150, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'DC', 'title' => 'District of Columbia', 'seq' => 151, 'mapping' => 'US'],
            ['list_id' => 'state', 'option_id' => 'PR', 'title' => 'Puerto Rico', 'seq' => 152, 'mapping' => 'US'],

            // --- India (Mapped to IN) ---
            ['list_id' => 'state', 'option_id' => 'IN_MH', 'title' => 'Maharashtra', 'seq' => 201, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_DL', 'title' => 'Delhi (NCT)', 'seq' => 202, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_KA', 'title' => 'Karnataka', 'seq' => 203, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_TN', 'title' => 'Tamil Nadu', 'seq' => 204, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_TG', 'title' => 'Telangana', 'seq' => 205, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_GJ', 'title' => 'Gujarat', 'seq' => 206, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_UP', 'title' => 'Uttar Pradesh', 'seq' => 207, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_WB', 'title' => 'West Bengal', 'seq' => 208, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_KL', 'title' => 'Kerala', 'seq' => 209, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_RJ', 'title' => 'Rajasthan', 'seq' => 210, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_AP', 'title' => 'Andhra Pradesh', 'seq' => 211, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_MP', 'title' => 'Madhya Pradesh', 'seq' => 212, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_PB', 'title' => 'Punjab', 'seq' => 213, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_HR', 'title' => 'Haryana', 'seq' => 214, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_BR', 'title' => 'Bihar', 'seq' => 215, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_OD', 'title' => 'Odisha', 'seq' => 216, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_AS', 'title' => 'Assam', 'seq' => 217, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_JK', 'title' => 'Jammu & Kashmir', 'seq' => 218, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_GA', 'title' => 'Goa', 'seq' => 219, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_UT', 'title' => 'Uttarakhand', 'seq' => 220, 'mapping' => 'IN'],
            ['list_id' => 'state', 'option_id' => 'IN_CH', 'title' => 'Chandigarh', 'seq' => 221, 'mapping' => 'IN'],

            // --- UAE Emirates (Mapped to AE) ---
            ['list_id' => 'state', 'option_id' => 'AE_DXB', 'title' => 'Dubai', 'seq' => 301, 'mapping' => 'AE'],
            ['list_id' => 'state', 'option_id' => 'AE_AUH', 'title' => 'Abu Dhabi', 'seq' => 302, 'mapping' => 'AE'],
            ['list_id' => 'state', 'option_id' => 'AE_SHJ', 'title' => 'Sharjah', 'seq' => 303, 'mapping' => 'AE'],
            ['list_id' => 'state', 'option_id' => 'AE_AJM', 'title' => 'Ajman', 'seq' => 304, 'mapping' => 'AE'],
            ['list_id' => 'state', 'option_id' => 'AE_RAK', 'title' => 'Ras Al Khaimah', 'seq' => 305, 'mapping' => 'AE'],
            ['list_id' => 'state', 'option_id' => 'AE_FUJ', 'title' => 'Fujairah', 'seq' => 306, 'mapping' => 'AE'],
            ['list_id' => 'state', 'option_id' => 'AE_UAQ', 'title' => 'Umm Al Quwain', 'seq' => 307, 'mapping' => 'AE'],

            // --- Europe (UK, Ireland, Germany, France, Spain) ---
            // United Kingdom (GB)
            ['list_id' => 'state', 'option_id' => 'GB_ENG', 'title' => 'England', 'seq' => 401, 'mapping' => 'GB'],
            ['list_id' => 'state', 'option_id' => 'GB_SCT', 'title' => 'Scotland', 'seq' => 402, 'mapping' => 'GB'],
            ['list_id' => 'state', 'option_id' => 'GB_WLS', 'title' => 'Wales', 'seq' => 403, 'mapping' => 'GB'],
            ['list_id' => 'state', 'option_id' => 'GB_NIR', 'title' => 'Northern Ireland', 'seq' => 404, 'mapping' => 'GB'],
            // Ireland (IE)
            ['list_id' => 'state', 'option_id' => 'IE_LEI', 'title' => 'Leinster (Dublin)', 'seq' => 410, 'mapping' => 'IE'],
            ['list_id' => 'state', 'option_id' => 'IE_MUN', 'title' => 'Munster (Cork)', 'seq' => 411, 'mapping' => 'IE'],
            ['list_id' => 'state', 'option_id' => 'IE_CON', 'title' => 'Connacht (Galway)', 'seq' => 412, 'mapping' => 'IE'],
            ['list_id' => 'state', 'option_id' => 'IE_ULS', 'title' => 'Ulster', 'seq' => 413, 'mapping' => 'IE'],
            // Germany (DE)
            ['list_id' => 'state', 'option_id' => 'DE_BY', 'title' => 'Bavaria (Bayern)', 'seq' => 420, 'mapping' => 'DE'],
            ['list_id' => 'state', 'option_id' => 'DE_BE', 'title' => 'Berlin', 'seq' => 421, 'mapping' => 'DE'],
            ['list_id' => 'state', 'option_id' => 'DE_NW', 'title' => 'North Rhine-Westphalia', 'seq' => 422, 'mapping' => 'DE'],
            ['list_id' => 'state', 'option_id' => 'DE_BW', 'title' => 'Baden-Württemberg', 'seq' => 423, 'mapping' => 'DE'],
            ['list_id' => 'state', 'option_id' => 'DE_HE', 'title' => 'Hesse (Frankfurt)', 'seq' => 424, 'mapping' => 'DE'],
            // France (FR)
            ['list_id' => 'state', 'option_id' => 'FR_IDF', 'title' => 'Île-de-France (Paris)', 'seq' => 430, 'mapping' => 'FR'],
            ['list_id' => 'state', 'option_id' => 'FR_ARA', 'title' => 'Auvergne-Rhône-Alpes', 'seq' => 431, 'mapping' => 'FR'],
            ['list_id' => 'state', 'option_id' => 'FR_PAC', 'title' => 'Provence-Alpes-Côte d\'Azur', 'seq' => 432, 'mapping' => 'FR'],
            // Spain (ES)
            ['list_id' => 'state', 'option_id' => 'ES_MD', 'title' => 'Community of Madrid', 'seq' => 440, 'mapping' => 'ES'],
            ['list_id' => 'state', 'option_id' => 'ES_CT', 'title' => 'Catalonia (Barcelona)', 'seq' => 441, 'mapping' => 'ES'],
            ['list_id' => 'state', 'option_id' => 'ES_AN', 'title' => 'Andalusia', 'seq' => 442, 'mapping' => 'ES'],

            // --- Africa (South Africa, Nigeria, Kenya, Ghana, Egypt) ---
            // South Africa (ZA)
            ['list_id' => 'state', 'option_id' => 'ZA_GP', 'title' => 'Gauteng (Johannesburg/Pretoria)', 'seq' => 501, 'mapping' => 'ZA'],
            ['list_id' => 'state', 'option_id' => 'ZA_WC', 'title' => 'Western Cape (Cape Town)', 'seq' => 502, 'mapping' => 'ZA'],
            ['list_id' => 'state', 'option_id' => 'ZA_KZN', 'title' => 'KwaZulu-Natal (Durban)', 'seq' => 503, 'mapping' => 'ZA'],
            ['list_id' => 'state', 'option_id' => 'ZA_EC', 'title' => 'Eastern Cape', 'seq' => 504, 'mapping' => 'ZA'],
            // Nigeria (NG)
            ['list_id' => 'state', 'option_id' => 'NG_LA', 'title' => 'Lagos', 'seq' => 510, 'mapping' => 'NG'],
            ['list_id' => 'state', 'option_id' => 'NG_FC', 'title' => 'Federal Capital Territory (Abuja)', 'seq' => 511, 'mapping' => 'NG'],
            ['list_id' => 'state', 'option_id' => 'NG_KN', 'title' => 'Kano', 'seq' => 512, 'mapping' => 'NG'],
            ['list_id' => 'state', 'option_id' => 'NG_RI', 'title' => 'Rivers (Port Harcourt)', 'seq' => 513, 'mapping' => 'NG'],
            // Kenya (KE)
            ['list_id' => 'state', 'option_id' => 'KE_NBO', 'title' => 'Nairobi County', 'seq' => 520, 'mapping' => 'KE'],
            ['list_id' => 'state', 'option_id' => 'KE_MBA', 'title' => 'Mombasa County', 'seq' => 521, 'mapping' => 'KE'],
            ['list_id' => 'state', 'option_id' => 'KE_KIS', 'title' => 'Kisumu County', 'seq' => 522, 'mapping' => 'KE'],
            ['list_id' => 'state', 'option_id' => 'KE_KIA', 'title' => 'Kiambu County', 'seq' => 523, 'mapping' => 'KE'],
            // Ghana (GH)
            ['list_id' => 'state', 'option_id' => 'GH_AA', 'title' => 'Greater Accra', 'seq' => 530, 'mapping' => 'GH'],
            ['list_id' => 'state', 'option_id' => 'GH_AH', 'title' => 'Ashanti (Kumasi)', 'seq' => 531, 'mapping' => 'GH'],
            // Egypt (EG)
            ['list_id' => 'state', 'option_id' => 'EG_CAI', 'title' => 'Cairo Governorate', 'seq' => 540, 'mapping' => 'EG'],
            ['list_id' => 'state', 'option_id' => 'EG_ALX', 'title' => 'Alexandria Governorate', 'seq' => 541, 'mapping' => 'EG'],

            // --- Australia (AU) ---
            ['list_id' => 'state', 'option_id' => 'AU_NSW', 'title' => 'New South Wales', 'seq' => 601, 'mapping' => 'AU'],
            ['list_id' => 'state', 'option_id' => 'AU_VIC', 'title' => 'Victoria', 'seq' => 602, 'mapping' => 'AU'],
            ['list_id' => 'state', 'option_id' => 'AU_QLD', 'title' => 'Queensland', 'seq' => 603, 'mapping' => 'AU'],
            ['list_id' => 'state', 'option_id' => 'AU_WA', 'title' => 'Western Australia', 'seq' => 604, 'mapping' => 'AU'],
            ['list_id' => 'state', 'option_id' => 'AU_SA', 'title' => 'South Australia', 'seq' => 605, 'mapping' => 'AU'],
            ['list_id' => 'state', 'option_id' => 'AU_TAS', 'title' => 'Tasmania', 'seq' => 606, 'mapping' => 'AU'],
            ['list_id' => 'state', 'option_id' => 'AU_ACT', 'title' => 'Australian Capital Territory', 'seq' => 607, 'mapping' => 'AU'],
            ['list_id' => 'state', 'option_id' => 'AU_NT', 'title' => 'Northern Territory', 'seq' => 608, 'mapping' => 'AU'],
        ];
    }

    /**
     * Counties, Districts & Municipalities mapped to parent State codes
     */
    public function getCountyData(): array
    {
        return [
            // --- US Major Counties (Florida, California, New York, Texas) ---
            ['list_id' => 'county', 'option_id' => 'FL_MIAMI_DADE', 'title' => 'Miami-Dade County', 'seq' => 1, 'mapping' => 'FL'],
            ['list_id' => 'county', 'option_id' => 'FL_BROWARD', 'title' => 'Broward County', 'seq' => 2, 'mapping' => 'FL'],
            ['list_id' => 'county', 'option_id' => 'FL_PALM_BEACH', 'title' => 'Palm Beach County', 'seq' => 3, 'mapping' => 'FL'],
            ['list_id' => 'county', 'option_id' => 'FL_ORANGE', 'title' => 'Orange County', 'seq' => 4, 'mapping' => 'FL'],
            ['list_id' => 'county', 'option_id' => 'FL_HILLSBOROUGH', 'title' => 'Hillsborough County', 'seq' => 5, 'mapping' => 'FL'],

            ['list_id' => 'county', 'option_id' => 'CA_LOS_ANGELES', 'title' => 'Los Angeles County', 'seq' => 10, 'mapping' => 'CA'],
            ['list_id' => 'county', 'option_id' => 'CA_ORANGE', 'title' => 'Orange County', 'seq' => 11, 'mapping' => 'CA'],
            ['list_id' => 'county', 'option_id' => 'CA_SAN_DIEGO', 'title' => 'San Diego County', 'seq' => 12, 'mapping' => 'CA'],
            ['list_id' => 'county', 'option_id' => 'CA_SANTA_CLARA', 'title' => 'Santa Clara County', 'seq' => 13, 'mapping' => 'CA'],
            ['list_id' => 'county', 'option_id' => 'CA_SAN_FRANCISCO', 'title' => 'San Francisco County', 'seq' => 14, 'mapping' => 'CA'],

            ['list_id' => 'county', 'option_id' => 'NY_NEW_YORK', 'title' => 'New York County (Manhattan)', 'seq' => 20, 'mapping' => 'NY'],
            ['list_id' => 'county', 'option_id' => 'NY_KINGS', 'title' => 'Kings County (Brooklyn)', 'seq' => 21, 'mapping' => 'NY'],
            ['list_id' => 'county', 'option_id' => 'NY_QUEENS', 'title' => 'Queens County', 'seq' => 22, 'mapping' => 'NY'],
            ['list_id' => 'county', 'option_id' => 'NY_BRONX', 'title' => 'Bronx County', 'seq' => 23, 'mapping' => 'NY'],
            ['list_id' => 'county', 'option_id' => 'NY_NASSAU', 'title' => 'Nassau County', 'seq' => 24, 'mapping' => 'NY'],

            ['list_id' => 'county', 'option_id' => 'TX_HARRIS', 'title' => 'Harris County (Houston)', 'seq' => 30, 'mapping' => 'TX'],
            ['list_id' => 'county', 'option_id' => 'TX_DALLAS', 'title' => 'Dallas County', 'seq' => 31, 'mapping' => 'TX'],
            ['list_id' => 'county', 'option_id' => 'TX_TARRANT', 'title' => 'Tarrant County (Fort Worth)', 'seq' => 32, 'mapping' => 'TX'],
            ['list_id' => 'county', 'option_id' => 'TX_TRAVIS', 'title' => 'Travis County (Austin)', 'seq' => 33, 'mapping' => 'TX'],
            ['list_id' => 'county', 'option_id' => 'TX_BEXAR', 'title' => 'Bexar County (San Antonio)', 'seq' => 34, 'mapping' => 'TX'],

            // --- India Major Districts ---
            ['list_id' => 'county', 'option_id' => 'IN_MUMBAI', 'title' => 'Mumbai District', 'seq' => 40, 'mapping' => 'IN_MH'],
            ['list_id' => 'county', 'option_id' => 'IN_PUNE', 'title' => 'Pune District', 'seq' => 41, 'mapping' => 'IN_MH'],
            ['list_id' => 'county', 'option_id' => 'IN_NAGPUR', 'title' => 'Nagpur District', 'seq' => 42, 'mapping' => 'IN_MH'],
            ['list_id' => 'county', 'option_id' => 'IN_THANE', 'title' => 'Thane District', 'seq' => 43, 'mapping' => 'IN_MH'],
            ['list_id' => 'county', 'option_id' => 'IN_BLR_URBAN', 'title' => 'Bengaluru Urban', 'seq' => 44, 'mapping' => 'IN_KA'],
            ['list_id' => 'county', 'option_id' => 'IN_MYSURU', 'title' => 'Mysuru District', 'seq' => 45, 'mapping' => 'IN_KA'],
            ['list_id' => 'county', 'option_id' => 'IN_CHENNAI', 'title' => 'Chennai District', 'seq' => 46, 'mapping' => 'IN_TN'],
            ['list_id' => 'county', 'option_id' => 'IN_HYDERABAD', 'title' => 'Hyderabad District', 'seq' => 47, 'mapping' => 'IN_TG'],
            ['list_id' => 'county', 'option_id' => 'IN_AHMEDABAD', 'title' => 'Ahmedabad District', 'seq' => 48, 'mapping' => 'IN_GJ'],

            // --- UAE Major Regions / Municipalities ---
            ['list_id' => 'county', 'option_id' => 'AE_DXB_DEIRA', 'title' => 'Deira', 'seq' => 50, 'mapping' => 'AE_DXB'],
            ['list_id' => 'county', 'option_id' => 'AE_DXB_BURDUBAI', 'title' => 'Bur Dubai', 'seq' => 51, 'mapping' => 'AE_DXB'],
            ['list_id' => 'county', 'option_id' => 'AE_DXB_DOWNTOWN', 'title' => 'Downtown / Business Bay', 'seq' => 52, 'mapping' => 'AE_DXB'],
            ['list_id' => 'county', 'option_id' => 'AE_DXB_JUMEIRAH', 'title' => 'Jumeirah', 'seq' => 53, 'mapping' => 'AE_DXB'],
            ['list_id' => 'county', 'option_id' => 'AE_AUH_CITY', 'title' => 'Abu Dhabi City Central', 'seq' => 54, 'mapping' => 'AE_AUH'],
            ['list_id' => 'county', 'option_id' => 'AE_AUH_ALAIN', 'title' => 'Al Ain Region', 'seq' => 55, 'mapping' => 'AE_AUH'],
            ['list_id' => 'county', 'option_id' => 'AE_AUH_DHAFRA', 'title' => 'Al Dhafra Region', 'seq' => 56, 'mapping' => 'AE_AUH'],

            // --- Australia Major Metro Regions ---
            ['list_id' => 'county', 'option_id' => 'AU_SYDNEY', 'title' => 'Greater Sydney', 'seq' => 60, 'mapping' => 'AU_NSW'],
            ['list_id' => 'county', 'option_id' => 'AU_HUNTER', 'title' => 'Hunter Region', 'seq' => 61, 'mapping' => 'AU_NSW'],
            ['list_id' => 'county', 'option_id' => 'AU_MELBOURNE', 'title' => 'Greater Melbourne', 'seq' => 62, 'mapping' => 'AU_VIC'],
            ['list_id' => 'county', 'option_id' => 'AU_GEELONG', 'title' => 'Geelong', 'seq' => 63, 'mapping' => 'AU_VIC'],
            ['list_id' => 'county', 'option_id' => 'AU_BRISBANE', 'title' => 'Greater Brisbane', 'seq' => 64, 'mapping' => 'AU_QLD'],
            ['list_id' => 'county', 'option_id' => 'AU_GOLDCOAST', 'title' => 'Gold Coast', 'seq' => 65, 'mapping' => 'AU_QLD'],
            ['list_id' => 'county', 'option_id' => 'AU_PERTH', 'title' => 'Perth Metropolitan', 'seq' => 66, 'mapping' => 'AU_WA'],
        ];
    }
}

