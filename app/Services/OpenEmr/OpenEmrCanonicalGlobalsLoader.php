<?php

namespace App\Services\OpenEmr;

use RuntimeException;
use Throwable;

class OpenEmrCanonicalGlobalsLoader
{
    protected static ?array $cachedCanonicalData = null;

    /**
     * Get path to OpenEMR base directory safely across CLI and web contexts
     */
    public function getOpenEmrBasePath(): string
    {
        return rtrim(
            (string) config('oemr.base_path', base_path('oemr')),
            '/\\'
        );
    }

    /**
     * Load all canonical globals dynamically from openemr/library/globals.inc.php
     *
     * @return array{
     *     total_discovered: int,
     *     canonical_globals: array<string, array{name: string, type: string, default: string, description: string, group: string, category: string}>,
     *     categories: array<string, array<string, array{name: string, type: string, default: string, description: string, group: string}>>,
     *     category_counts: array<string, int>
     * }
     */
    public function loadCanonicalGlobals(): array
    {
        if (self::$cachedCanonicalData !== null) {
            return self::$cachedCanonicalData;
        }

        $openEmrBasePath = $this->getOpenEmrBasePath();
        $globalsFile = $openEmrBasePath . '/library/globals.inc.php';
        $translationFile = $openEmrBasePath . '/library/translation.inc.php';

        if (!file_exists($globalsFile)) {
            throw new RuntimeException("Canonical OpenEMR globals file not found: {$globalsFile}");
        }

        // Include OpenEMR composer autoload if available
        $composerAutoload = $openEmrBasePath . '/vendor/autoload.php';
        if (file_exists($composerAutoload)) {
            require_once $composerAutoload;
        }

        // Define translation stubs expected by globals.inc.php
        if (!function_exists('xl')) {
            function xl($str, $type = 'translated', ...$args) {
                return $str;
            }
        }
        if (!function_exists('text')) {
            function text($str) {
                return $str;
            }
        }

        $GLOBALS['temp_skip_translations'] = true;
        $skipGlobalEvent = true;

        if (file_exists($translationFile)) {
            require_once $translationFile;
        }

        require_once $globalsFile;

        if (!isset($GLOBALS_METADATA) || !is_array($GLOBALS_METADATA)) {
            throw new RuntimeException("Authoritative \$GLOBALS_METADATA array not found in {$globalsFile}");
        }

        $canonicalGlobals = [];
        $categories = [
            'safe_static_default' => [],
            'generated_dynamic' => [],
            'environment_path' => [],
            'security_secret' => [],
            'tenant_specific' => [],
        ];

        foreach ($GLOBALS_METADATA as $grpName => $grparr) {
            if (!is_array($grparr)) {
                continue;
            }
            foreach ($grparr as $fldid => $fldarr) {
                if (!is_array($fldarr) || count($fldarr) < 3) {
                    continue;
                }
                [$fldname, $fldtype, $flddef] = $fldarr;
                $flddesc = $fldarr[3] ?? '';

                // Matching OpenEMR 8.3.0 Installer::insert_globals() filter:
                // Only insert if type is array or does not start with 'm_' (section headers)
                if (is_array($fldtype) || !str_starts_with((string) $fldtype, 'm_')) {
                    $category = $this->classifyGlobal((string) $fldid, (string) $fldname, (string) $grpName);
                    
                    $entry = [
                        'name' => (string) $fldname,
                        'type' => is_array($fldtype) ? 'array' : (string) $fldtype,
                        'default' => (string) $flddef,
                        'description' => (string) $flddesc,
                        'group' => (string) $grpName,
                        'category' => $category,
                    ];

                    $canonicalGlobals[(string) $fldid] = $entry;
                    $categories[$category][(string) $fldid] = $entry;
                }
            }
        }

        $categoryCounts = [];
        foreach ($categories as $cat => $items) {
            $categoryCounts[$cat] = count($items);
        }

        self::$cachedCanonicalData = [
            'total_discovered' => count($canonicalGlobals),
            'canonical_globals' => $canonicalGlobals,
            'categories' => $categories,
            'category_counts' => $categoryCounts,
        ];

        return self::$cachedCanonicalData;
    }

    /**
     * Classify a global key into one of the 5 canonical architectural categories
     */
    public function classifyGlobal(string $key, string $name = '', string $group = ''): string
    {
        $dynamicKeys = [
            'installation_id',
            'openemr_unique_installation_id',
            'system_uuid',
            'unique_installation_id',
            'couchdb_dbase',
        ];

        $envPathKeys = [
            'web_root',
            'site_id',
            'OE_SITE_DIR',
            'documents_path',
            'edi_history_path',
            'temporary_files_dir',
            'backup_log_dir',
            'server_document_root',
            'rest_api_url',
            'webserver_root',
            'couchdb_host',
            'mysql_binary_path',
            'document_storage_method',
        ];

        $securityKeys = [
            'drive_encryption',
            'oauth_private_key_path',
            'oauth_public_key_path',
            'rest_api_token_signature',
            'two_factor_auth',
            'crypto_passphrase',
        ];

        $tenantKeys = [
            'practice_name',
            'phone',
            'state_or_province',
            'default_country',
            'default_language',
            'login_tagline_text',
            'clinic_name',
            'facility_name',
            'css_header',
            'login_page_layout',
            'show_primary_logo',
            'primary_logo_width',
            'logo_position',
            'show_tagline_on_login',
            'show_labels_on_login_form',
            'language_menu_login',
            'language_menu_showall',
            'display_acknowledgements_on_login',
        ];

        if (in_array($key, $dynamicKeys, true) || str_contains($key, 'unique_id') || str_contains($key, 'uuid')) {
            return 'generated_dynamic';
        }

        if (in_array($key, $envPathKeys, true) || str_ends_with($key, '_path') || str_ends_with($key, '_dir') || str_contains($key, 'webserver_') || str_contains($key, 'host')) {
            return 'environment_path';
        }

        if (in_array($key, $securityKeys, true) || str_contains($key, 'encrypt') || str_contains($key, 'secret') || str_contains($key, 'token_signature')) {
            return 'security_secret';
        }

        if (in_array($key, $tenantKeys, true) || str_contains($key, 'tagline') || str_contains($key, 'practice_') || str_contains($key, 'clinic_')) {
            return 'tenant_specific';
        }

        return 'safe_static_default';
    }
}
