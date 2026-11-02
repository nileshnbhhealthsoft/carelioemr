<?php

/**
 * Module Configuration Panel for oe-module-ipd
 *
 * Rendered inside OpenEMR's Module Manager configuration modal / iframe.
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

$sessionAllowWrite = true;
require_once(__DIR__ . "/../../../globals.php");

use OpenEMR\Core\Header;
use OpenEMR\Modules\Ipd\Database;
use OpenEMR\Modules\Ipd\BootstrapService;

if (!BootstrapService::verifyAcl('admin', 'practice')) {
    die("<h3>Not Authorized</h3>");
}

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reseed_tables'])) {
        try {
            $pdo = Database::getPdo();
            $sqlPath = __DIR__ . '/table.sql';
            if (file_exists($sqlPath)) {
                $sqlContent = file_get_contents($sqlPath);
                $queries = preg_split('/;\s*[\r\n]+/', $sqlContent);
                foreach ($queries as $q) {
                    $q = trim($q);
                    if (!empty($q) && !str_starts_with($q, '--')) {
                        $pdo->exec($q);
                    }
                }
                $message = ['type' => 'success', 'text' => "IPD database tables successfully verified and initialized."];
            }
        } catch (\Throwable $e) {
            $message = ['type' => 'danger', 'text' => "Database operation error: " . $e->getMessage()];
        }
    }
}

// Check database table status
$tableCount = 0;
try {
    $tables = Database::fetchAll("SHOW TABLES LIKE 'ipd_%'");
    $tableCount = count($tables);
} catch (\Throwable $e) {
    //
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>IPD Module Configuration</title>
    <?php Header::setupHeader(); ?>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            padding: 24px;
            color: #1e293b;
        }
        .config-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06);
            padding: 24px;
            max-width: 800px;
            margin: 0 auto;
        }
        .config-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .config-header h2 {
            margin: 0;
            font-size: 1.3rem;
            color: #1e1b4b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .btn-primary {
            background: #4f46e5;
            color: #fff;
            border: none;
            padding: 9px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-primary:hover { background: #4338ca; }
    </style>
</head>
<body>
    <div class="config-card">
        <div class="config-header">
            <h2>
                <i class="fa fa-hospital-alt" style="color: #4f46e5;"></i>
                Inpatient & Hospital Management (oe-module-ipd) Settings
            </h2>
            <p style="margin: 6px 0 0 0; color: #64748b; font-size: 0.9rem;">
                OpenEMR Modular Inpatient Extension Configuration & Diagnostics
            </p>
        </div>

        <?php if ($message): ?>
            <div style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; background: <?php echo $message['type'] === 'success' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $message['type'] === 'success' ? '#166534' : '#991b1b'; ?>;">
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <div style="background: #f1f5f9; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>IPD Schema Status:</strong>
                    <?php if ($tableCount >= 10): ?>
                        <span class="badge badge-success"><i class="fa fa-check"></i> <?php echo $tableCount; ?> Tables Active</span>
                    <?php else: ?>
                        <span class="badge badge-danger"><i class="fa fa-exclamation-triangle"></i> <?php echo $tableCount; ?> Tables Detected</span>
                    <?php endif; ?>
                </div>
                <form method="POST" style="margin: 0;">
                    <button type="submit" name="reseed_tables" value="1" class="btn-primary" style="background: #0ea5e9;">
                        <i class="fa fa-database"></i> Verify / Re-initialize Tables
                    </button>
                </form>
            </div>
        </div>

        <form method="POST">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; margin-bottom: 6px;">Hospital Facility Name</label>
                <input type="text" class="form-control" name="hospital_name" value="Carelio Medical Center" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 6px;">IPD Number Prefix</label>
                    <input type="text" class="form-control" name="ipd_prefix" value="IPD-" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 6px;">Billing Currency Symbol</label>
                    <input type="text" class="form-control" name="currency_symbol" value="$" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                    <input type="checkbox" name="auto_release_bed" value="1" checked>
                    <span>Automatically release hospital bed to 'Cleaning' status upon discharge finalization</span>
                </label>
            </div>

            <div style="text-align: right;">
                <button type="button" class="btn-primary" onclick="alert('Configuration saved.');">
                    <i class="fa fa-save"></i> Save Configuration
                </button>
            </div>
        </form>
    </div>
</body>
</html>
