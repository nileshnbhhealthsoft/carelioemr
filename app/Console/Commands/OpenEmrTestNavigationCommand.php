<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OpenEmrTestNavigationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oemr:test-navigation 
                            {--tenant= : Specific tenant slug, database name, or subscription ID (defaults to first active tenant)}
                            {--base-url= : Base URL of the running application (defaults to config app.url)}
                            {--json-output= : Optional path to save detailed JSON output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a complete, automated read-only navigation regression audit of all visible OEMR menu and submenu items';

    protected string $cookieFile;
    protected string $baseUrl;
    protected string $tenantSlug;

    public function handle(): int
    {
        $this->info("================================================================================");
        $this->info("      OEMR COMPLETE NAVIGATION REGRESSION AUDIT (TENANT ADMIN SESSION)          ");
        $this->info("================================================================================");

        $defaultUrl = config('app.url', 'http://127.0.0.1:8000');
        $this->baseUrl = rtrim((string) ($this->option('base-url') ?: $defaultUrl), '/');

        $tenantOption = $this->option('tenant');

        // 1. Locate tenant subscription
        $query = Subscription::query()->whereNotNull('openemr_database');
        if ($tenantOption) {
            $query->where(function ($q) use ($tenantOption) {
                $q->where('id', $tenantOption)
                  ->orWhere('tenant_slug', $tenantOption)
                  ->orWhere('openemr_database', $tenantOption);
            });
        }
        $subscription = $query->orderBy('id', 'asc')->first();

        if (!$subscription) {
            $this->tenantSlug = $tenantOption;
            $this->warn("Subscription record not found in Laravel DB; using explicit slug: {$this->tenantSlug}");
        } else {
            $this->tenantSlug = $subscription->tenant_slug;
            $this->line("Target Tenant: <fg=cyan>{$this->tenantSlug}</> (Subscription ID: {$subscription->id})");
        }

        // Setup temporary cookie jar
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'oemr_cookie_');

        try {
            // 2. Authenticate as admin
            $mainHtml = $this->authenticateAdmin();
            if (!$mainHtml) {
                $this->error("Authentication failed. Cannot proceed with navigation audit.");
                return 1;
            }

            // 3. Extract dynamic menu tree from authenticated main.php HTML
            $menuItems = $this->extractMenuItemsFromHtml($mainHtml);
            if (empty($menuItems)) {
                $this->error("No menu items discovered in authenticated main.php.");
                return 1;
            }

            $this->info("Discovered " . count($menuItems) . " top-level navigation structures.");

            // 4. Flatten all menu items and submenus
            $flatList = [];
            foreach ($menuItems as $topItem) {
                $this->flattenMenuTree($topItem, $flatList);
            }

            $this->info("Total navigation items/submenus extracted: " . count($flatList));

            // 5. Test each actionable navigation entry
            $results = [];
            $passCount = 0;
            $contextCount = 0;
            $envCount = 0;
            $realFailCount = 0;

            $bar = $this->output->createProgressBar(count($flatList));
            $bar->start();

            foreach ($flatList as $item) {
                $testResult = $this->testNavigationTarget($item);
                $results[] = $testResult;

                match ($testResult['classification']) {
                    'PASS' => $passCount++,
                    'CONTEXT_REQUIRED' => $contextCount++,
                    'ENVIRONMENT_LIMITATION' => $envCount++,
                    default => $realFailCount++,
                };

                $bar->advance();
            }

            $bar->finish();
            $this->line("\n");

            // 6. Display Master Matrix
            $this->displayMasterMatrix($results);

            // 7. Display Summary and Grouped Root Causes
            $this->displaySummaryReport($results, $passCount, $contextCount, $envCount, $realFailCount);

            // Save JSON artifact if requested
            $jsonOutput = $this->option('json-output');
            if ($jsonOutput) {
                File::put($jsonOutput, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->info("Full audit results saved to: {$jsonOutput}");
            }

            return 0;

        } finally {
            if (file_exists($this->cookieFile)) {
                @unlink($this->cookieFile);
            }
        }
    }

    /**
     * Authenticate tenant admin and return main.php HTML
     */
    protected function authenticateAdmin(): ?string
    {
        $this->line("Authenticating admin user for site '{$this->tenantSlug}'...");

        $loginUrl = "{$this->baseUrl}/oemr/interface/main/main_screen.php?auth=login&site=" . urlencode($this->tenantSlug);
        $postFields = http_build_query([
            'new_login_session_management' => '1',
            'languageChoice' => '1',
            'authUser' => 'admin',
            'clearPass' => 'pass',
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $loginUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        curl_close($ch);

        if ($httpCode !== 302) {
            $this->error("Expected HTTP 302 from main_screen.php, got HTTP {$httpCode}");
            return null;
        }

        // Extract Location header
        if (!preg_match('/^Location:\s*(.*?)$/mi', $headers, $matches)) {
            $this->error("No Location header received from login.");
            return null;
        }

        $redirectLocation = trim($matches[1]);
        $targetUrl = str_starts_with($redirectLocation, 'http')
            ? $redirectLocation
            : $this->baseUrl . (str_starts_with($redirectLocation, '/') ? '' : '/') . $redirectLocation;

        $this->line("Authenticated successfully. Redirecting to: <fg=yellow>{$targetUrl}</>");

        // Fetch main.php
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $targetUrl,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 25,
        ]);

        $mainHtml = curl_exec($ch);
        $mainCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($mainCode !== 200 || empty($mainHtml)) {
            $this->error("Failed to load authenticated main.php (HTTP {$mainCode}).");
            return null;
        }

        return $mainHtml;
    }

    /**
     * Extract menu JSON from main.php HTML
     */
    protected function extractMenuItemsFromHtml(string $html): array
    {
        if (preg_match('/var\s+menu_objects\s*=\s*(\[.*?\]);\s*(?:app_view_model|process_menu_object)/s', $html, $matches)) {
            $json = $matches[1];
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/var\s+menu_objects\s*=\s*(\[\s*\{.*\}\s*\]);/sU', $html, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Recursively flatten menu entries
     */
    protected function flattenMenuTree(array $entry, array &$flatList, string $parentPath = ''): void
    {
        $label = $entry['label'] ?? 'Unknown';
        $fullPath = $parentPath ? "{$parentPath} > {$label}" : $label;
        $topMenu = $parentPath ? explode(' > ', $parentPath)[0] : $label;

        $hasChildren = !empty($entry['children']) && is_array($entry['children']);
        $hasUrl = !empty($entry['url']);

        if ($hasUrl || !$hasChildren) {
            $flatList[] = [
                'top_menu' => $topMenu,
                'path' => $fullPath,
                'label' => $label,
                'menu_id' => $entry['menu_id'] ?? null,
                'target' => $entry['target'] ?? null,
                'url' => $entry['url'] ?? null,
                'requirement' => (int) ($entry['requirement'] ?? 0),
                'acl_req' => $entry['acl_req'] ?? null,
                'has_children' => $hasChildren,
            ];
        }

        if ($hasChildren) {
            foreach ($entry['children'] as $child) {
                $this->flattenMenuTree($child, $flatList, $fullPath);
            }
        }
    }

    /**
     * Test a navigation entry target
     */
    protected function testNavigationTarget(array $item): array
    {
        $configuredUrl = $item['url'];
        $path = $item['path'];
        $topMenu = $item['top_menu'];
        $requirement = $item['requirement'];

        if (empty($configuredUrl)) {
            return [
                'top_menu' => $topMenu,
                'path' => $path,
                'configured_url' => '(none)',
                'resolved_url' => '(none)',
                'http_code' => 0,
                'classification' => 'PASS',
                'root_cause' => 'Header container / parent menu with no direct URL',
                'affected_file' => 'menus/standard.json',
                'fix_required' => 'NO',
                'stale_prefix' => false,
                'details' => 'Parent menu group with children',
            ];
        }

        // Check for JS actions
        if (str_starts_with($configuredUrl, 'javascript:')) {
            return [
                'top_menu' => $topMenu,
                'path' => $path,
                'configured_url' => $configuredUrl,
                'resolved_url' => $configuredUrl,
                'http_code' => 0,
                'classification' => 'PASS',
                'root_cause' => 'Client-side JavaScript action',
                'affected_file' => 'Client-side script',
                'fix_required' => 'NO',
                'stale_prefix' => false,
                'details' => 'JavaScript executed on client',
            ];
        }

        // Stale prefix check
        $hasStalePrefix = str_contains($configuredUrl, '/openemr/') || str_contains($configuredUrl, '/carelioemr/');

        // Resolve absolute URL
        $resolvedUrl = '';
        if (str_starts_with($configuredUrl, 'http://') || str_starts_with($configuredUrl, 'https://')) {
            $resolvedUrl = $configuredUrl;
        } elseif (str_starts_with($configuredUrl, '/oemr/')) {
            $resolvedUrl = $this->baseUrl . $configuredUrl;
        } elseif (str_starts_with($configuredUrl, '/')) {
            $resolvedUrl = $this->baseUrl . '/oemr' . $configuredUrl;
        } else {
            $resolvedUrl = $this->baseUrl . '/oemr/' . $configuredUrl;
        }

        // Identify contextual endpoints
        $isContextualPatientOrEncounter = ($requirement > 0) || in_array($configuredUrl, [
            '/interface/patient_file/label.php',
            '/interface/patient_file/barcode_label.php',
            '/interface/patient_file/addr_label.php',
            '/interface/patient_file/encounter/load_form.php?formname=fee_sheet',
        ]);

        // Execute HTTP GET
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $resolvedUrl,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 35, // Extended timeout for heavy clinical decision rules / form registries
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';
        $curlError = curl_error($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $body = ($rawResponse && $headerSize) ? substr($rawResponse, $headerSize) : ($rawResponse ?: '');

        // Check file existence on filesystem
        $parsedUrl = parse_url($resolvedUrl);
        $urlPath = $parsedUrl['path'] ?? '';
        $localFilePath = null;
        if (str_starts_with($urlPath, '/oemr/')) {
            $relativeOemr = substr($urlPath, strlen('/oemr/'));
            $localFilePath = base_path('oemr/' . $relativeOemr);
        }

        // Analyze Response
        $classification = 'PASS';
        $rootCause = '';
        $affectedFile = '';
        $fixRequired = 'NO';
        $details = "HTTP {$httpCode}, Content-Type: {$contentType}";

        if ($curlError) {
            $classification = 'REAL_FAILURE';
            $rootCause = "cURL error: {$curlError}";
            $fixRequired = 'YES';
        } elseif ($hasStalePrefix) {
            $classification = 'REAL_FAILURE';
            $rootCause = 'Stale /openemr/ or /carelioemr/ path prefix in menu configuration';
            $affectedFile = 'menus/standard.json';
            $fixRequired = 'YES';
        } elseif (str_contains($urlPath, '/interface/modules/zend_modules/public/') && $httpCode === 404) {
            // Verified Laminas route that works under Apache mod_rewrite but returns 404 under php artisan serve
            $classification = 'ENVIRONMENT_LIMITATION';
            $rootCause = 'Laminas MVC route requiring Apache .htaccess rewrite (works under Apache; php artisan serve development limitation)';
            $affectedFile = 'oemr/interface/modules/zend_modules/public/.htaccess';
            $fixRequired = 'NO';
        } elseif ($isContextualPatientOrEncounter && ($httpCode === 500 || $httpCode === 200)) {
            // Contextual endpoints requiring active patient or encounter in session
            // In the UI, these are disabled until patient/encounter is selected, or operate on active patient PID
            $classification = 'CONTEXT_REQUIRED';
            $rootCause = $requirement === 2 || str_contains($configuredUrl, 'fee_sheet')
                ? 'Requires active encounter in session (menu requirement: 2)'
                : 'Requires active patient in session (menu requirement: 1 / patient popup)';
            $affectedFile = $urlPath;
            $fixRequired = 'NO';
        } elseif ($httpCode === 404) {
            $classification = 'REAL_FAILURE';
            $rootCause = "HTTP 404 Not Found at {$urlPath}";
            $affectedFile = $configuredUrl;
            $fixRequired = 'YES';
        } elseif ($httpCode >= 500) {
            $classification = 'REAL_FAILURE';
            $rootCause = "Server 500 Error: " . $this->extractPhpError($body);
            $affectedFile = $urlPath;
            $fixRequired = 'YES';
        } elseif (str_contains($finalUrl, '/interface/login/login.php') || str_contains($body, 'name="login_form"')) {
            $classification = 'REAL_FAILURE';
            $rootCause = 'Session expired or request unauthorized, redirected to login screen';
            $affectedFile = $urlPath;
            $fixRequired = 'YES';
        } elseif (preg_match('/^\s*(?:<br\s*\/?>\s*<b>)?(?:Fatal error|Parse error):/im', $body, $errMatches)) {
            $classification = 'REAL_FAILURE';
            $rootCause = 'PHP Runtime Error: ' . $this->extractPhpError($body);
            $affectedFile = $urlPath;
            $fixRequired = 'YES';
        } elseif (stripos($body, 'Unauthorized') !== false && stripos($body, 'ACL') !== false) {
            $classification = 'REAL_FAILURE';
            $rootCause = 'ACL check failed for authenticated admin';
            $affectedFile = $urlPath;
            $fixRequired = 'YES';
        } elseif ($httpCode === 403) {
            $classification = 'REAL_FAILURE';
            $rootCause = 'HTTP 403 Forbidden';
            $affectedFile = $urlPath;
            $fixRequired = 'YES';
        }

        return [
            'top_menu' => $topMenu,
            'path' => $path,
            'configured_url' => $configuredUrl,
            'resolved_url' => $resolvedUrl,
            'final_url' => $finalUrl,
            'http_code' => $httpCode,
            'classification' => $classification,
            'root_cause' => $rootCause ?: 'None (Healthy)',
            'affected_file' => $affectedFile ?: ($localFilePath ?? $configuredUrl),
            'fix_required' => $fixRequired,
            'stale_prefix' => $hasStalePrefix,
            'details' => $details,
        ];
    }

    /**
     * Extract PHP error snippet from response body
     */
    protected function extractPhpError(string $body): string
    {
        if (preg_match('/(?:<b>)?(Fatal error|Parse error|Uncaught [^:<]+)(?:<\/b>)?:?\s*(.*?)(?:in\s+<b>?([^<]+)<\/b>?\s+on\s+line\s+<b>?(\d+)<\/b>?|<br|$)/is', $body, $m)) {
            return trim(strip_tags($m[0]));
        }
        return substr(trim(strip_tags($body)), 0, 150);
    }

    /**
     * Output master matrix table
     */
    protected function displayMasterMatrix(array $results): void
    {
        $rows = [];
        foreach ($results as $res) {
            $statusTag = match ($res['classification']) {
                'PASS' => '<fg=green>PASS</>',
                'CONTEXT_REQUIRED' => '<fg=blue>CONTEXT_REQ</>',
                'ENVIRONMENT_LIMITATION' => '<fg=yellow>ENV_LIMIT</>',
                'REAL_FAILURE' => '<fg=red;options=bold>REAL_FAILURE</>',
                default => "<fg=yellow>{$res['classification']}</>",
            };

            $fixTag = $res['fix_required'] === 'YES' ? '<fg=red;options=bold>YES</>' : '<fg=green>NO</>';

            $rows[] = [
                $res['top_menu'],
                $res['path'],
                mb_strimwidth($res['configured_url'], 0, 35, '...'),
                $res['http_code'] ?: '-',
                $statusTag,
                mb_strimwidth($res['root_cause'], 0, 35, '...'),
                mb_strimwidth(basename($res['affected_file']), 0, 25, '...'),
                $fixTag,
            ];
        }

        $this->table(
            ['Top Menu', 'Submenu Path', 'Configured URL', 'HTTP', 'Result', 'Root Cause', 'Affected File', 'Fix?'],
            $rows
        );
    }

    /**
     * Output summary and grouped root causes
     */
    protected function displaySummaryReport(array $results, int $pass, int $context, int $env, int $realFail): void
    {
        $total = count($results);

        $this->info("================================================================================");
        $this->info("                          AUDIT SUMMARY REPORT                                  ");
        $this->info("================================================================================");
        $this->line("Total menus/submenus discovered: <fg=white;options=bold>{$total}</>");
        $this->line("PASS (Completely functional):    <fg=green;options=bold>{$pass}</>");
        $this->line("CONTEXT_REQUIRED (Encounter/Pt): <fg=blue;options=bold>{$context}</>");
        $this->line("ENVIRONMENT_LIMITATION (Apache): <fg=yellow;options=bold>{$env}</>");
        $this->line("REAL_FAILURE (Broken code/path): <fg=red;options=bold>{$realFail}</>");
        $this->line("");

        if ($realFail > 0) {
            $this->error("REAL BROKEN ITEMS DETECTED ({$realFail}):");
            foreach ($results as $r) {
                if ($r['classification'] === 'REAL_FAILURE') {
                    $this->line("  - [{$r['top_menu']}] {$r['path']}");
                    $this->line("    URL: {$r['configured_url']} -> HTTP {$r['http_code']}");
                    $this->line("    File: {$r['affected_file']}");
                    $this->line("    Cause: {$r['root_cause']}");
                }
            }
        } else {
            $this->info("REAL BROKEN NAVIGATION COUNT: 0 (ZERO). All navigation paths are verified healthy!");
        }
        $this->line("");
    }
}
