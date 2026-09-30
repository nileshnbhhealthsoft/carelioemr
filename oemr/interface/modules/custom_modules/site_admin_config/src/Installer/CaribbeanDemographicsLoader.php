<?php

namespace OpenEMR\Modules\SiteAdmin\Installer;

use PDO;
use RuntimeException;

class CaribbeanDemographicsLoader
{
    public const DATASET_NAME = 'CarelioEMR Caribbean Geographic Demographics';
    public const DATASET_VERSION = '1.0.0';
    public const EXPECTED_CHECKSUM = '7c039e6bc88b2634bb1706b1a5092a29e023afa9559de4b13b15cc02667035ee';

    /**
     * Saint Lucia 10 Official CSO Administrative Districts
     */
    public const SAINT_LUCIA_DISTRICTS = [
        'LC-CSO-DIST-CASTRIES' => ['id' => 'LC_CAS', 'title' => 'Castries', 'seq' => 1],
        'LC-CSO-DIST-GROS-ISLET' => ['id' => 'LC_GRO', 'title' => 'Gros Islet', 'seq' => 2],
        'LC-CSO-DIST-VIEUX-FORT' => ['id' => 'LC_VIF', 'title' => 'Vieux Fort', 'seq' => 3],
        'LC-CSO-DIST-SOUFRIERE' => ['id' => 'LC_SOU', 'title' => 'Soufrière', 'seq' => 4],
        'LC-CSO-DIST-DENNERY' => ['id' => 'LC_DEN', 'title' => 'Dennery', 'seq' => 5],
        'LC-CSO-DIST-MICOUD' => ['id' => 'LC_MIC', 'title' => 'Micoud', 'seq' => 6],
        'LC-CSO-DIST-ANSE-LA-RAYE' => ['id' => 'LC_ALR', 'title' => 'Anse La Raye', 'seq' => 7],
        'LC-CSO-DIST-CANARIES' => ['id' => 'LC_CAN', 'title' => 'Canaries', 'seq' => 8],
        'LC-CSO-DIST-CHOISEUL' => ['id' => 'LC_CHO', 'title' => 'Choiseul', 'seq' => 9],
        'LC-CSO-DIST-LABORIE' => ['id' => 'LC_LAB', 'title' => 'Laborie', 'seq' => 10],
    ];

    /**
     * Run full sync of Caribbean demographics dataset and OpenEMR list_options
     *
     * @param PDO $pdo
     * @param string|null $sqlFilePath
     * @return array Summary of operations
     */
    public static function sync(PDO $pdo, ?string $sqlFilePath = null): array
    {
        try {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        } catch (\Throwable $e) {
        }

        // 1. Ensure core schema exists
        self::ensureSchema($pdo);

        // 2. Load dataset if not already installed
        $datasetLoaded = false;
        if (!self::isLoaded($pdo)) {
            $path = $sqlFilePath ?? dirname(__DIR__, 2) . '/sql/carelio_caribbean_demographics_v1.0.sql';
            if (file_exists($path)) {
                self::loadDataset($pdo, $path);
                $datasetLoaded = true;
            }
        }

        // 3. Synchronize list_options (country, state, county)
        $listCounts = self::syncListOptions($pdo);

        // 4. Synchronize terminology configuration table
        self::syncTerminology($pdo);

        // 5. Synchronize dynamic cascading script in layout_options
        self::syncCascadingScript($pdo);

        return [
            'status' => 'success',
            'dataset_loaded' => $datasetLoaded,
            'list_options' => $listCounts,
        ];
    }

    /**
     * Ensure the 4 core geographic tables exist
     */
    public static function ensureSchema(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS carelio_geo_countries (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            iso2 CHAR(2) NOT NULL,
            iso3 CHAR(3) NOT NULL,
            iso_numeric CHAR(3) NOT NULL,
            name VARCHAR(200) NOT NULL,
            source_standard VARCHAR(100) NOT NULL,
            source_ref VARCHAR(160) NOT NULL,
            source_url VARCHAR(1024) NOT NULL,
            status ENUM('VERIFIED','REVIEW_REQUIRED','DEPRECATED') NOT NULL,
            active TINYINT UNSIGNED NOT NULL DEFAULT 1,
            notes TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT geo_country_provenance CHECK (CHAR_LENGTH(TRIM(source_ref)) > 0 AND CHAR_LENGTH(TRIM(source_standard)) > 0 AND source_url LIKE 'https://%'),
            CONSTRAINT geo_country_active CHECK (active IN (0,1) AND (status <> 'DEPRECATED' OR active = 0)),
            UNIQUE KEY uq_geo_country_iso2 (iso2),
            UNIQUE KEY uq_geo_country_iso3 (iso3),
            UNIQUE KEY uq_geo_country_numeric (iso_numeric),
            UNIQUE KEY uq_geo_country_source (source_ref),
            CONSTRAINT geo_country_codes CHECK (iso2 REGEXP '^[A-Z]{2}$' AND iso3 REGEXP '^[A-Z]{3}$' AND iso_numeric REGEXP '^[0-9]{3}$'),
            CONSTRAINT geo_country_name CHECK (CHAR_LENGTH(TRIM(name)) > 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Carelio geographic schema 1.0.0'");

        $pdo->exec("CREATE TABLE IF NOT EXISTS carelio_geo_admin_areas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            country_id BIGINT UNSIGNED NOT NULL,
            parent_admin_area_id BIGINT UNSIGNED NULL,
            admin_level TINYINT UNSIGNED NOT NULL,
            admin_code VARCHAR(100) NULL,
            name VARCHAR(200) NOT NULL,
            admin_type VARCHAR(60) NOT NULL,
            name_key VARCHAR(200) GENERATED ALWAYS AS (LCASE(TRIM(name))) STORED,
            parent_key BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(parent_admin_area_id,0)) STORED,
            source_standard VARCHAR(100) NOT NULL,
            source_ref VARCHAR(160) NOT NULL,
            source_url VARCHAR(1024) NOT NULL,
            status ENUM('VERIFIED','REVIEW_REQUIRED','DEPRECATED') NOT NULL,
            active TINYINT UNSIGNED NOT NULL DEFAULT 1,
            notes TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT geo_admin_provenance CHECK (CHAR_LENGTH(TRIM(source_ref)) > 0 AND CHAR_LENGTH(TRIM(source_standard)) > 0 AND source_url LIKE 'https://%'),
            CONSTRAINT geo_admin_active CHECK (active IN (0,1) AND (status <> 'DEPRECATED' OR active = 0)),
            UNIQUE KEY uq_geo_admin_country_id (country_id,id),
            UNIQUE KEY uq_geo_admin_source (country_id,source_ref),
            UNIQUE KEY uq_geo_admin_name (country_id,parent_key,admin_level,name_key),
            KEY ix_geo_admin_parent (country_id,parent_admin_area_id),
            CONSTRAINT fk_geo_admin_country FOREIGN KEY (country_id) REFERENCES carelio_geo_countries(id),
            CONSTRAINT fk_geo_admin_parent FOREIGN KEY (country_id,parent_admin_area_id) REFERENCES carelio_geo_admin_areas(country_id,id),
            CONSTRAINT geo_admin_level CHECK (admin_level <= 4 AND ((admin_level <= 1 AND parent_admin_area_id IS NULL) OR (admin_level > 1 AND parent_admin_area_id IS NOT NULL))),
            CONSTRAINT geo_admin_name CHECK (CHAR_LENGTH(TRIM(name)) > 0 AND CHAR_LENGTH(TRIM(admin_type)) > 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Carelio geographic schema 1.0.0'");

        $pdo->exec("CREATE TABLE IF NOT EXISTS carelio_geo_localities (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            country_id BIGINT UNSIGNED NOT NULL,
            admin_area_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(200) NOT NULL,
            locality_type VARCHAR(60) NOT NULL,
            name_key VARCHAR(200) GENERATED ALWAYS AS (LCASE(TRIM(name))) STORED,
            source_standard VARCHAR(100) NOT NULL,
            source_ref VARCHAR(160) NOT NULL,
            source_url VARCHAR(1024) NOT NULL,
            status ENUM('VERIFIED','REVIEW_REQUIRED','DEPRECATED') NOT NULL,
            active TINYINT UNSIGNED NOT NULL DEFAULT 1,
            notes TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT geo_locality_provenance CHECK (CHAR_LENGTH(TRIM(source_ref)) > 0 AND CHAR_LENGTH(TRIM(source_standard)) > 0 AND source_url LIKE 'https://%'),
            CONSTRAINT geo_locality_active CHECK (active IN (0,1) AND (status <> 'DEPRECATED' OR active = 0)),
            UNIQUE KEY uq_geo_locality_source (country_id,source_ref),
            UNIQUE KEY uq_geo_locality_name (country_id,admin_area_id,name_key),
            KEY ix_geo_locality_admin (country_id,admin_area_id),
            CONSTRAINT fk_geo_locality_country FOREIGN KEY (country_id) REFERENCES carelio_geo_countries(id),
            CONSTRAINT fk_geo_locality_admin FOREIGN KEY (country_id,admin_area_id) REFERENCES carelio_geo_admin_areas(country_id,id),
            CONSTRAINT geo_locality_name CHECK (CHAR_LENGTH(TRIM(name)) > 0 AND CHAR_LENGTH(TRIM(locality_type)) > 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Carelio geographic schema 1.0.0'");

        $pdo->exec("CREATE TABLE IF NOT EXISTS carelio_geo_dataset_versions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            dataset_name VARCHAR(160) NOT NULL,
            dataset_version VARCHAR(32) NOT NULL,
            source TEXT NOT NULL,
            source_url VARCHAR(1024) NOT NULL,
            release_date DATE NOT NULL,
            imported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            record_count BIGINT UNSIGNED NOT NULL,
            checksum CHAR(64) NOT NULL,
            status ENUM('VERIFIED','REVIEW_REQUIRED','DEPRECATED') NOT NULL,
            country_count BIGINT UNSIGNED NOT NULL,
            admin_area_count BIGINT UNSIGNED NOT NULL,
            locality_count BIGINT UNSIGNED NOT NULL,
            UNIQUE KEY uq_geo_dataset_version (dataset_name,dataset_version),
            CONSTRAINT geo_dataset_checksum CHECK (checksum REGEXP '^[0-9a-f]{64}$'),
            CONSTRAINT geo_dataset_count CHECK (record_count = country_count + admin_area_count + locality_count)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Carelio geographic schema 1.0.0'");
    }

    /**
     * Check if dataset version 1.0.0 is already imported
     */
    public static function isLoaded(PDO $pdo): bool
    {
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM carelio_geo_dataset_versions 
                WHERE dataset_name = ? AND dataset_version = ? AND checksum = ?
            ");
            $stmt->execute([self::DATASET_NAME, self::DATASET_VERSION, self::EXPECTED_CHECKSUM]);
            return ((int) $stmt->fetchColumn()) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Load raw SQL dataset cleanly into MySQL
     */
    public static function loadDataset(PDO $pdo, string $sqlFilePath): void
    {
        if (!file_exists($sqlFilePath)) {
            throw new RuntimeException("Geography SQL file not found: {$sqlFilePath}");
        }

        // Staging tables
        $pdo->exec("DROP TABLE IF EXISTS carelio_stage_localities");
        $pdo->exec("DROP TABLE IF EXISTS carelio_stage_admins");
        $pdo->exec("DROP TABLE IF EXISTS carelio_stage_countries");

        $pdo->exec("CREATE TABLE IF NOT EXISTS carelio_stage_countries (
            iso2 CHAR(2) PRIMARY KEY, iso3 CHAR(3) UNIQUE, iso_numeric CHAR(3) UNIQUE,
            name VARCHAR(200), source_standard VARCHAR(100), source_ref VARCHAR(160) UNIQUE,
            source_url VARCHAR(1024), status VARCHAR(20), active TINYINT, notes TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");

        $pdo->exec("CREATE TABLE IF NOT EXISTS carelio_stage_admins (
            iso2 CHAR(2), parent_ref VARCHAR(160), admin_level TINYINT,
            admin_code VARCHAR(100), name VARCHAR(200), admin_type VARCHAR(60),
            source_standard VARCHAR(100), source_ref VARCHAR(160), source_url VARCHAR(1024),
            status VARCHAR(20), active TINYINT, notes TEXT, PRIMARY KEY(iso2,source_ref)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");

        $pdo->exec("CREATE TABLE IF NOT EXISTS carelio_stage_localities (
            iso2 CHAR(2), admin_ref VARCHAR(160), name VARCHAR(200), locality_type VARCHAR(60),
            source_standard VARCHAR(100), source_ref VARCHAR(160), source_url VARCHAR(1024),
            status VARCHAR(20), active TINYINT, notes TEXT, PRIMARY KEY(iso2,source_ref),
            UNIQUE KEY uq_stage_locality_name (iso2,admin_ref,name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");

        // Stream and execute staging INSERT statements
        $fp = fopen($sqlFilePath, 'r');
        if (!$fp) {
            throw new RuntimeException("Could not open SQL file for reading.");
        }

        $inInsert = false;
        $buffer = '';
        $pdo->beginTransaction();

        try {
            while (($line = fgets($fp)) !== false) {
                $trimmed = trim($line);
                if (strpos($trimmed, 'INSERT INTO carelio_stage_') === 0) {
                    $inInsert = true;
                    $buffer = $line;
                } elseif ($inInsert) {
                    $buffer .= $line;
                }

                if ($inInsert && substr(rtrim($trimmed), -1) === ';') {
                    $pdo->exec($buffer);
                    $buffer = '';
                    $inInsert = false;
                }
            }
            fclose($fp);
            $pdo->commit();
        } catch (\Throwable $e) {
            fclose($fp);
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        // Copy from staging into real tables
        $pdo->beginTransaction();
        try {
            // Countries
            $pdo->exec("INSERT INTO carelio_geo_countries (iso2,iso3,iso_numeric,name,source_standard,source_ref,source_url,status,active,notes)
                SELECT s.iso2,s.iso3,s.iso_numeric,s.name,s.source_standard,s.source_ref,s.source_url,s.status,s.active,s.notes FROM carelio_stage_countries s
                WHERE NOT EXISTS (SELECT 1 FROM carelio_geo_countries c WHERE c.iso2=s.iso2)");

            // Admin areas levels 0 to 4
            for ($lvl = 0; $lvl <= 4; $lvl++) {
                $pdo->exec("INSERT INTO carelio_geo_admin_areas
                  (country_id,parent_admin_area_id,admin_level,admin_code,name,admin_type,source_standard,source_ref,source_url,status,active,notes)
                SELECT c.id,p.id,s.admin_level,s.admin_code,s.name,s.admin_type,s.source_standard,s.source_ref,s.source_url,s.status,s.active,s.notes
                FROM carelio_stage_admins s JOIN carelio_geo_countries c ON c.iso2=s.iso2
                LEFT JOIN carelio_geo_admin_areas p ON p.country_id=c.id AND p.source_ref=s.parent_ref
                WHERE s.admin_level={$lvl} AND NOT EXISTS
                  (SELECT 1 FROM carelio_geo_admin_areas a WHERE a.country_id=c.id AND a.source_ref=s.source_ref)");
            }

            // Localities
            $pdo->exec("INSERT INTO carelio_geo_localities
                (country_id,admin_area_id,name,locality_type,source_standard,source_ref,source_url,status,active,notes)
              SELECT c.id,a.id,s.name,s.locality_type,s.source_standard,s.source_ref,s.source_url,s.status,s.active,s.notes
              FROM carelio_stage_localities s JOIN carelio_geo_countries c ON c.iso2=s.iso2
              JOIN carelio_geo_admin_areas a ON a.country_id=c.id AND a.source_ref=s.admin_ref
              WHERE NOT EXISTS (SELECT 1 FROM carelio_geo_localities l WHERE l.country_id=c.id AND l.source_ref=s.source_ref)");

            // Dataset version record
            $pdo->exec("INSERT INTO carelio_geo_dataset_versions
                (dataset_name,dataset_version,source,source_url,release_date,record_count,checksum,status,country_count,admin_area_count,locality_count)
              SELECT 'CarelioEMR Caribbean Geographic Demographics','1.0.0',
                'Saint Lucia CSO 2010; TCI Statistics 2012; BVI Tourist Board; GeoNames CC BY 4.0; TT RDLG terminology',
                'https://www.geonames.org/export/','2026-09-18',
                (SELECT COUNT(*) FROM carelio_stage_countries)+(SELECT COUNT(*) FROM carelio_stage_admins)+(SELECT COUNT(*) FROM carelio_stage_localities),
                '" . self::EXPECTED_CHECKSUM . "','REVIEW_REQUIRED',
                (SELECT COUNT(*) FROM carelio_stage_countries),(SELECT COUNT(*) FROM carelio_stage_admins),(SELECT COUNT(*) FROM carelio_stage_localities)
              WHERE NOT EXISTS (SELECT 1 FROM carelio_geo_dataset_versions WHERE dataset_name='CarelioEMR Caribbean Geographic Demographics' AND dataset_version='1.0.0')");

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        } finally {
            // Drop staging tables
            $pdo->exec("DROP TABLE IF EXISTS carelio_stage_localities");
            $pdo->exec("DROP TABLE IF EXISTS carelio_stage_admins");
            $pdo->exec("DROP TABLE IF EXISTS carelio_stage_countries");
        }
    }

    /**
     * Synchronize Caribbean countries, admin areas, and Saint Lucia communities into list_options
     */
    public static function syncListOptions(PDO $pdo): array
    {
        $stmtInsert = $pdo->prepare("
            INSERT INTO list_options (
                list_id, option_id, title, seq, is_default, option_value,
                mapping, notes, codes, toggle_setting_1, toggle_setting_2,
                activity, subtype, edit_options
            ) VALUES (
                ?, ?, ?, ?, 0, 0,
                ?, '', '', 0, 0,
                1, '', 1
            ) ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                seq = VALUES(seq),
                mapping = VALUES(mapping),
                activity = VALUES(activity)
        ");

        $countryCount = 0;
        $stateCount = 0;
        $countyCount = 0;

        // 1. Sync Countries (All 28 Caribbean Countries)
        $countries = $pdo->query("SELECT iso2, name FROM carelio_geo_countries ORDER BY (iso2 = 'LC') DESC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $cSeq = 10;
        foreach ($countries as $c) {
            $stmtInsert->execute(['country', $c['iso2'], $c['name'], $cSeq++, 'CB']);
            $countryCount++;
        }

        // 2. Sync Saint Lucia Districts into state
        foreach (self::SAINT_LUCIA_DISTRICTS as $sourceRef => $info) {
            $stmtInsert->execute(['state', $info['id'], $info['title'], $info['seq'], 'LC']);
            $stateCount++;
        }

        // 3. Sync Saint Lucia Communities into county
        $stmtComm = $pdo->query("
            SELECT l.id, l.name, a.source_ref as admin_ref
            FROM carelio_geo_localities l
            JOIN carelio_geo_admin_areas a ON a.id = l.admin_area_id
            WHERE l.country_id = (SELECT id FROM carelio_geo_countries WHERE iso2='LC')
            ORDER BY a.name, l.name
        ");
        $rawCommunities = $stmtComm->fetchAll(PDO::FETCH_ASSOC);

        $seq = 100;
        foreach ($rawCommunities as $comm) {
            if (!isset(self::SAINT_LUCIA_DISTRICTS[$comm['admin_ref']])) {
                continue;
            }
            $districtId = self::SAINT_LUCIA_DISTRICTS[$comm['admin_ref']]['id'];
            $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', trim($comm['name'])));
            $slug = trim($slug, '_');
            $optId = $districtId . '_' . substr($slug, 0, 75);
            $title = self::formatTitle($comm['name']);

            $stmtInsert->execute(['county', $optId, $title, $seq++, $districtId]);
            $countyCount++;
        }

        return [
            'countries' => $countryCount,
            'states' => $stateCount,
            'counties' => $countyCount,
        ];
    }

    /**
     * Synchronize terminology table for Caribbean countries
     */
    public static function syncTerminology(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS mod_site_admin_location_terminology (
            country_code VARCHAR(10) NOT NULL,
            region_type VARCHAR(50) NOT NULL DEFAULT 'DEFAULT',
            state_label VARCHAR(100) NOT NULL DEFAULT 'State',
            county_label VARCHAR(100) NOT NULL DEFAULT 'County',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (country_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("
            INSERT INTO mod_site_admin_location_terminology (country_code, region_type, state_label, county_label)
            SELECT iso2, 'CARIBBEAN', 'Parish / District', 'Community'
            FROM carelio_geo_countries
            ON DUPLICATE KEY UPDATE
                region_type = VALUES(region_type),
                state_label = VALUES(state_label),
                county_label = VALUES(county_label)
        ");

        $pdo->exec("
            INSERT INTO mod_site_admin_location_terminology (country_code, region_type, state_label, county_label)
            VALUES 
                ('US', 'US', 'State', 'County'),
                ('USA', 'US', 'State', 'County')
            ON DUPLICATE KEY UPDATE 
                region_type = VALUES(region_type), 
                state_label = VALUES(state_label), 
                county_label = VALUES(county_label)
        ");
    }

    /**
     * Update client-side location cascading script in layout_options
     */
    public static function syncCascadingScript(PDO $pdo): void
    {
        // Fetch states grouped by country
        $states = $pdo->query("
            SELECT list_id, option_id, title, mapping 
            FROM list_options 
            WHERE list_id = 'state' AND activity = 1 
            ORDER BY seq ASC, title ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $countryStates = [];
        $stateToCountry = [];
        $allStates = [];
        foreach ($states as $s) {
            $c = $s['mapping'];
            if ($c) {
                $countryStates[$c][] = ['id' => $s['option_id'], 'title' => $s['title']];
                $stateToCountry[$s['option_id']] = $c;
            }
            $allStates[] = ['id' => $s['option_id'], 'title' => $s['title']];
        }

        // Fetch counties grouped by state
        $counties = $pdo->query("
            SELECT list_id, option_id, title, mapping 
            FROM list_options 
            WHERE list_id = 'county' AND activity = 1 
            ORDER BY seq ASC, title ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $stateCounties = [];
        $countyToState = [];
        foreach ($counties as $co) {
            $st = $co['mapping'];
            if ($st) {
                $stateCounties[$st][] = ['id' => $co['option_id'], 'title' => $co['title']];
                $countyToState[$co['option_id']] = $st;
            }
        }

        $jsonCountryStates = json_encode($countryStates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $jsonStateCounties = json_encode($stateCounties, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $jsonStateToCountry = json_encode($stateToCountry, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $jsonCountyToState = json_encode($countyToState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $jsonAllStates = json_encode($allStates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $scriptHtml = '<div style="display:none;" id="carelio_cascading_locations_container">
<script>
(function() {
    var countryStates = ' . $jsonCountryStates . ';
    var stateCounties = ' . $jsonStateCounties . ';
    var stateToCountry = ' . $jsonStateToCountry . ';
    var countyToState = ' . $jsonCountyToState . ';
    var allStates = ' . $jsonAllStates . ';

    var terminologyMap = {
        "LC": { state: "Parish / District", county: "Community" },
        "BS": { state: "Parish / District", county: "Community" },
        "BB": { state: "Parish / District", county: "Community" },
        "JM": { state: "Parish / District", county: "Community" },
        "BM": { state: "Parish / District", county: "Community" },
        "DM": { state: "Parish / District", county: "Community" },
        "TT": { state: "Parish / District", county: "Community" },
        "AG": { state: "Parish / District", county: "Community" },
        "KN": { state: "Parish / District", county: "Community" },
        "VC": { state: "Parish / District", county: "Community" },
        "GD": { state: "Parish / District", county: "Community" },
        "KY": { state: "Parish / District", county: "Community" },
        "US": { state: "State", county: "County" },
        "USA": { state: "State", county: "County" }
    };
    var defaultTerminology = { state: "State", county: "County" };

    function updateTerminology(countryCode) {
        var terms = terminologyMap[countryCode] || defaultTerminology;

        function setFieldLabel(fieldId, text) {
            var labelIds = ["label_" + fieldId, "label_id_text_" + fieldId, "label_id_" + fieldId];
            var foundAny = false;
            for (var i = 0; i < labelIds.length; i++) {
                var el = document.getElementById(labelIds[i]);
                if (el) {
                    foundAny = true;
                    var hadColon = el.textContent.trim().endsWith(":");
                    var colon = hadColon ? ":" : "";
                    var reqChild = el.querySelector ? el.querySelector(".required-indicator, .text-danger, sup") : null;
                    if (reqChild) {
                        el.innerHTML = reqChild.outerHTML + " " + text + colon;
                    } else {
                        el.textContent = text + colon;
                    }
                }
            }
            if (!foundAny) {
                var inputEl = document.getElementById("form_" + fieldId);
                if (inputEl) {
                    var row = inputEl.closest ? (inputEl.closest(".form-group") || inputEl.closest(".row") || inputEl.parentElement) : inputEl.parentElement;
                    if (row && row.querySelector) {
                        var el = row.querySelector(".label_custom, [id^=\'label_\'], label");
                        if (el) {
                            var hadColon = el.textContent.trim().endsWith(":");
                            var colon = hadColon ? ":" : "";
                            var reqChild = el.querySelector(".required-indicator, .text-danger, sup");
                            if (reqChild) {
                                el.innerHTML = reqChild.outerHTML + " " + text + colon;
                            } else {
                                el.textContent = text + colon;
                            }
                        }
                    }
                }
            }
        }

        setFieldLabel("state", terms.state);
        setFieldLabel("county", terms.county);
    }

    function setupLocationCascade() {
        var countryEl = document.getElementById("form_country_code") || document.getElementById("form_country");
        var stateEl = document.getElementById("form_state");
        var countyEl = document.getElementById("form_county");
        if (!countryEl || !stateEl) return;
        if (countryEl.getAttribute("data-cascade-ready") === "1") return;
        countryEl.setAttribute("data-cascade-ready", "1");

        function populateSelect(selectEl, items, desiredVal, emptyLabel) {
            if (!selectEl) return;
            var currentVal = (desiredVal !== undefined && desiredVal !== null) ? desiredVal : selectEl.value;
            selectEl.innerHTML = "";
            var optEmpty = document.createElement("option");
            optEmpty.value = "";
            optEmpty.textContent = emptyLabel || " ";
            selectEl.appendChild(optEmpty);
            var found = false;
            if (items && items.length > 0) {
                for (var i = 0; i < items.length; i++) {
                    var opt = document.createElement("option");
                    opt.value = items[i].id;
                    opt.textContent = items[i].title;
                    if (items[i].id === currentVal) {
                        opt.selected = true;
                        found = true;
                    }
                    selectEl.appendChild(opt);
                }
            }
            if (!found && currentVal) {
                var optCustom = document.createElement("option");
                optCustom.value = currentVal;
                optCustom.textContent = currentVal;
                optCustom.selected = true;
                selectEl.appendChild(optCustom);
            }
            if (window.jQuery && window.jQuery(selectEl).data("select2")) {
                window.jQuery(selectEl).trigger("change.select2");
            }
        }

        function syncStates(countryVal, preserveVal) {
            var targetVal = preserveVal ? stateEl.value : "";
            var list = countryVal ? (countryStates[countryVal] || []) : allStates;
            var terms = terminologyMap[countryVal] || defaultTerminology;
            var placeholder = (terms.state === "State") ? "Select State / Locality" : ("Select " + terms.state);
            populateSelect(stateEl, list, targetVal, placeholder);
            syncCounties(stateEl.value, preserveVal);
        }

        function syncCounties(stateVal, preserveVal) {
            if (!countyEl) return;
            var targetVal = preserveVal ? countyEl.value : "";
            var list = stateVal ? (stateCounties[stateVal] || []) : [];
            var cVal = countryEl ? countryEl.value : "";
            var terms = terminologyMap[cVal] || defaultTerminology;
            var placeholder = (terms.county === "County") ? "Select County / District" : ("Select " + terms.county);
            populateSelect(countyEl, list, targetVal, placeholder);
        }

        var initC = countryEl.value;
        var initS = stateEl.value;
        var initCo = countyEl ? countyEl.value : "";
        if (!initC && initS && stateToCountry[initS]) {
            initC = stateToCountry[initS];
            countryEl.value = initC;
            if (window.jQuery && window.jQuery(countryEl).data("select2")) {
                window.jQuery(countryEl).trigger("change.select2");
            }
        }
        if (!initS && initCo && countyToState[initCo]) {
            initS = countyToState[initCo];
            stateEl.value = initS;
            if (!initC && stateToCountry[initS]) {
                initC = stateToCountry[initS];
                countryEl.value = initC;
                if (window.jQuery && window.jQuery(countryEl).data("select2")) {
                    window.jQuery(countryEl).trigger("change.select2");
                }
            }
        }

        updateTerminology(initC);
        if (initC) syncStates(initC, true);
        else if (initS) syncCounties(initS, true);

        countryEl.addEventListener("change", function() {
            updateTerminology(this.value);
            syncStates(this.value, false);
        });

        stateEl.addEventListener("change", function() {
            var stVal = this.value;
            if (stVal && stateToCountry[stVal] && countryEl.value !== stateToCountry[stVal]) {
                countryEl.value = stateToCountry[stVal];
                if (window.jQuery && window.jQuery(countryEl).data("select2")) {
                    window.jQuery(countryEl).trigger("change.select2");
                }
                updateTerminology(countryEl.value);
            }
            syncCounties(stVal, false);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", setupLocationCascade);
    } else {
        setupLocationCascade();
    }
    setTimeout(setupLocationCascade, 250);
    setTimeout(setupLocationCascade, 800);
})();
</script>
</div>';

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
    }

    /**
     * Gracefully deactivate cascading script and reset labels when module is disabled
     */
    public static function deactivate(PDO $pdo): void
    {
        // 1. Deactivate layout script (set uor = 0 so it does not render on demographics form)
        $pdo->exec("UPDATE layout_options SET uor = 0 WHERE form_id = 'DEM' AND field_id = 'location_cascading_script'");

        // 2. Reset demographics labels to standard OpenEMR labels
        $pdo->exec("UPDATE layout_options SET title = 'State' WHERE form_id = 'DEM' AND field_id = 'state'");
        $pdo->exec("UPDATE layout_options SET title = 'County' WHERE form_id = 'DEM' AND field_id = 'county'");
        $pdo->exec("UPDATE layout_options SET title = 'Country' WHERE form_id = 'DEM' AND field_id = 'country'");
    }

    /**
     * Re-activate cascading script and terminology when module is enabled
     */
    public static function activate(PDO $pdo): void
    {
        // 1. Re-activate layout script (set uor = 1)
        $pdo->exec("UPDATE layout_options SET uor = 1 WHERE form_id = 'DEM' AND field_id = 'location_cascading_script'");

        // 2. Ensure cascading script and terminology are up-to-date
        self::syncCascadingScript($pdo);
    }

    /**
     * Helper to format community/locality title into clean Title Case
     */
    public static function formatTitle(string $raw): string
    {
        $parts = explode('/', $raw);
        $cleanParts = array_map(function ($p) {
            return ucwords(strtolower(trim($p)));
        }, $parts);
        return implode(' / ', $cleanParts);
    }
}
