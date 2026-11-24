<?php

/**
 * Carelio Hospital EHR - CORE Foundation Administration Console
 * Location & Bed Hierarchy Management, Module Catalog & Hospital Setup
 *
 * @package   OpenEMR
 * @subpackage Carelio Hospital EHR
 * @link      https://www.carelioemr.com
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

$sessionAllowWrite = true;
require_once(__DIR__ . "/../../../globals.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Core\Header;
use OpenEMR\Modules\CarelioCore\Services\ContentApprovalService;
use OpenEMR\Modules\CarelioCore\Services\InstallWizardService;
use OpenEMR\Modules\CarelioCore\Services\LocationHierarchyService;
use OpenEMR\Modules\CarelioCore\Services\ModuleCatalogService;
use OpenEMR\Modules\CarelioCore\Services\PrivacySensitivityService;
use OpenEMR\Modules\CarelioCore\Services\RegistrationService;

// Access control check
if (!AclMain::aclCheckCore('admin', 'super') && !AclMain::aclCheckCore('admin', 'practice') && !AclMain::aclCheckCore('patients', 'demo')) {
    echo "<h3>" . (function_exists('xlt') ? xlt("Access Denied: Administrator permissions required.") : "Access Denied") . "</h3>";
    exit;
}

// Handle AJAX actions
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        $action = $_POST['action'];
        if ($action === 'seed_saint_lucia') {
            LocationHierarchyService::seedSaintLuciaHospital(1);
            echo json_encode(['success' => true, 'message' => 'Saint Lucia OKEU Hospital layout seeded successfully!']);
            exit;
        } elseif ($action === 'add_location') {
            $id = LocationHierarchyService::addLocation($_POST);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Location added successfully!']);
            exit;
        } elseif ($action === 'delete_location') {
            LocationHierarchyService::deleteLocation((int)$_POST['id']);
            echo json_encode(['success' => true, 'message' => 'Location deleted successfully!']);
            exit;
        } elseif ($action === 'apply_wizard') {
            parse_str($_POST['formData'] ?? '', $formData);
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $result = InstallWizardService::applyWizard($formData, $userId);
            echo json_encode($result);
            exit;
        } elseif ($action === 'create_content') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $newId = ContentApprovalService::createItem($_POST, $userId);
            echo json_encode(['success' => true, 'id' => $newId]);
            exit;
        } elseif ($action === 'approve_content') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $ok = ContentApprovalService::approveItem((int)$_POST['id'], $userId);
            echo json_encode(['success' => $ok, 'message' => 'Clinical content approved and certified!']);
            exit;
        } elseif ($action === 'reject_content') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $reason = trim($_POST['reason'] ?? 'Not approved');
            $ok = ContentApprovalService::rejectItem((int)$_POST['id'], $userId, $reason);
            echo json_encode(['success' => $ok, 'message' => 'Clinical content rejected.']);
            exit;
        } elseif ($action === 'resubmit_content') {
            $ok = ContentApprovalService::submitForApproval((int)$_POST['id']);
            echo json_encode(['success' => $ok]);
            exit;
        } elseif ($action === 'flag_sensitivity') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $newId = PrivacySensitivityService::flagRecord($_POST['record_type'], (int)$_POST['record_id'], $_POST['sensitivity_level'], $_POST['reason'] ?? '', $userId);
            echo json_encode(['success' => true, 'id' => $newId]);
            exit;
        } elseif ($action === 'trigger_breakglass') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $bgId = PrivacySensitivityService::logBreakGlass((int)$_POST['pid'], null, $userId, $_POST['sensitivity_level'], $_POST['reason']);
            echo json_encode(['success' => true, 'id' => $bgId]);
            exit;
        } elseif ($action === 'review_breakglass') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $ok = PrivacySensitivityService::reviewBreakGlass((int)$_POST['id'], $userId, $_POST['status'], $_POST['notes']);
            echo json_encode(['success' => $ok]);
            exit;
        } elseif ($action === 'set_module_status') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $res = ModuleCatalogService::setModuleStatus($_POST['code'], $_POST['status'], $userId);
            echo json_encode($res);
            exit;
        } elseif ($action === 'rapid_register') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $res = RegistrationService::rapidRegisterUnknownPatient($_POST, $userId);
            echo json_encode($res);
            exit;
        } elseif ($action === 'reconcile_identity') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $res = RegistrationService::reconcileIdentity((int)$_POST['temp_id'], (int)$_POST['target_pid'], $userId);
            echo json_encode($res);
            exit;
        } elseif ($action === 'collect_deposit') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $res = RegistrationService::collectDeposit($_POST, $userId);
            echo json_encode($res);
            exit;
        } elseif ($action === 'detect_duplicates') {
            $res = RegistrationService::detectDuplicates($_POST);
            echo json_encode(['success' => true, 'matches' => $res]);
            exit;
        } elseif ($action === 'get_patient_profile') {
            $res = RegistrationService::getPatientProfile((int)$_POST['pid']);
            echo json_encode(['success' => ($res !== null), 'patient' => $res]);
            exit;
        } elseif ($action === 'merge_patients') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $res = RegistrationService::mergePatients((int)$_POST['source_pid'], (int)$_POST['target_pid'], $userId, $_POST['reason'] ?? '');
            echo json_encode($res);
            exit;
        } elseif ($action === 'unmerge_patients') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $res = RegistrationService::unmergePatients((int)$_POST['merge_log_id'], $userId, $_POST['reason'] ?? '');
            echo json_encode($res);
            exit;
        } elseif ($action === 'capture_consent') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $res = RegistrationService::captureConsent($_POST, $userId);
            echo json_encode($res);
            exit;
        } elseif ($action === 'get_insurance') {
            $res = RegistrationService::getInsuranceVerification((int)$_POST['pid']);
            echo json_encode(['success' => true, 'insurance' => $res]);
            exit;
        }
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Initial Data Fetch
$locationsTree = LocationHierarchyService::getTree(1);
$parentOptions = LocationHierarchyService::getParentOptions(1);
$moduleCatalog = ModuleCatalogService::getAll();
$page = $_GET['page'] ?? 'wizard';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Carelio Hospital EHR - CORE Foundation</title>
    <?php Header::setupHeader(['common']); ?>
    <style>
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .hms-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .hms-header { background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #fff; padding: 20px 24px; border-radius: 8px 8px 0 0; }
        .tree-node { margin-left: 20px; padding: 6px 12px; border-left: 2px solid #cbd5e1; margin-top: 6px; }
        .badge-site { background-color: #0284c7; color: #fff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px; }
        .badge-building { background-color: #4f46e5; color: #fff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px; }
        .badge-floor { background-color: #0d9488; color: #fff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px; }
        .badge-ward { background-color: #ea580c; color: #fff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px; }
        .badge-room { background-color: #8b5cf6; color: #fff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px; }
        .badge-bed { background-color: #10b981; color: #fff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px; }
        .badge-active { background-color: #dcfce7; color: #15803d; }
        .badge-draft { background-color: #fef3c7; color: #b45309; }
        .location-item { background: #fff; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
        .location-item:hover { background: #f1f5f9; }
        .tab-btn { font-weight: 600; border-radius: 6px; padding: 8px 16px; }
    </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="hms-card">
        <div class="hms-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1"><i class="fa fa-hospital-o mr-2"></i>Carelio Hospital EHR — Core Foundation</h4>
                <p class="mb-0 text-white-50">Inpatient Architecture, Ward & Bed Hierarchy, Module Registry & Governance (Specification Baseline)</p>
            </div>
            <div>
                <span class="badge badge-light px-3 py-2 text-dark font-weight-bold">OpenEMR 8.4.1 Baseline</span>
            </div>
        </div>

        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link tab-btn <?php echo $page === 'wizard' ? 'active' : ''; ?>" href="?page=wizard">
                        <i class="fa fa-magic mr-1"></i> 9-Step Install Wizard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-btn <?php echo $page === 'locations' ? 'active' : ''; ?>" href="?page=locations">
                        <i class="fa fa-sitemap mr-1"></i> Bed & Ward Hierarchy
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-btn <?php echo $page === 'modules' ? 'active' : ''; ?>" href="?page=modules">
                        <i class="fa fa-cubes mr-1"></i> HMS Module Catalog (23 Modules)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-btn <?php echo $page === 'approvals' ? 'active' : ''; ?>" href="?page=approvals">
                        <i class="fa fa-stethoscope mr-1"></i> Content Approvals
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-btn <?php echo $page === 'privacy' ? 'active' : ''; ?>" href="?page=privacy">
                        <i class="fa fa-shield mr-1"></i> Privacy & Break-Glass
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-btn <?php echo $page === 'registration' ? 'active' : ''; ?>" href="?page=registration">
                        <i class="fa fa-user-plus mr-1"></i> Registration (REG)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-btn <?php echo $page === 'config' ? 'active' : ''; ?>" href="?page=config">
                        <i class="fa fa-sliders mr-1"></i> Saint Lucia Hospital Settings
                    </a>
                </li>
            </ul>

            <?php if ($page === 'locations'): ?>
            <div>
                <button class="btn btn-outline-primary btn-sm mr-2" id="btnSeedHospital">
                    <i class="fa fa-magic mr-1"></i> Seed Saint Lucia Hospital Layout
                </button>
                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalAddLocation">
                    <i class="fa fa-plus-circle mr-1"></i> Add Location Node
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="p-4">
            <?php if ($page === 'wizard'): ?>
                <?php require __DIR__ . '/templates/wizard.php'; ?>
            <?php elseif ($page === 'locations'): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle mr-1"></i> <strong>Physical Hierarchy:</strong> OpenEMR's native <code>facility</code> is flat. This tree establishes the full 6-tier inpatient hierarchy (<strong>Site &rarr; Building &rarr; Floor &rarr; Ward &rarr; Room &rarr; Bed</strong>) conforming to Section 18.1 of the Carelio Specification.
                </div>

                <?php if (empty($locationsTree)): ?>
                    <div class="text-center py-5">
                        <i class="fa fa-bed fa-3x text-muted mb-3"></i>
                        <h5>No Hospital Locations Configured</h5>
                        <p class="text-muted">Click <strong>"Seed Saint Lucia Hospital Layout"</strong> to automatically generate the national hospital layout (OKEU Hospital model with ED, Maternity, Medical/Surgical, and ICU wards).</p>
                        <button class="btn btn-primary" onclick="document.getElementById('btnSeedHospital').click();">
                            <i class="fa fa-magic mr-1"></i> Seed Saint Lucia Layout
                        </button>
                    </div>
                <?php else: ?>
                    <div id="locationTreeContainer">
                        <?php
                        function renderTreeNode($node) {
                            $typeClass = 'badge-' . htmlspecialchars($node['location_type']);
                            echo '<div class="tree-node">';
                            echo '<div class="location-item">';
                            echo '<div>';
                            echo '<span class="' . $typeClass . ' mr-2">' . strtoupper(htmlspecialchars($node['location_type'])) . '</span> ';
                            echo '<strong>' . htmlspecialchars($node['name']) . '</strong> ';
                            echo '<small class="text-muted">(' . htmlspecialchars($node['code']) . ')</small> ';
                            echo '<span class="badge badge-light border text-muted ml-1"><small>ID: ' . $node['id'] . '</small></span>';
                            if (!empty($node['specialty'])) {
                                echo ' <span class="badge badge-secondary ml-1">' . htmlspecialchars(ucfirst($node['specialty'])) . '</span>';
                            }
                            if (!empty($node['care_level'])) {
                                echo ' <span class="badge badge-info ml-1">' . htmlspecialchars(strtoupper($node['care_level'])) . '</span>';
                            }
                            if (!empty($node['accommodation_type'])) {
                                echo ' <span class="badge badge-light ml-1 border">' . htmlspecialchars(ucfirst($node['accommodation_type'])) . '</span>';
                            }
                            echo '</div>';
                            echo '<div>';
                            echo '<span class="badge badge-success mr-2">' . htmlspecialchars($node['status']) . '</span>';
                            if ($node['location_type'] !== 'bed') {
                                $nextType = $node['location_type'] === 'room' ? 'bed' : ($node['location_type'] === 'ward' ? 'room' : ($node['location_type'] === 'floor' ? 'ward' : 'building'));
                                echo '<button class="btn btn-outline-primary btn-xs py-0 px-2 mr-1 btn-add-child" data-id="' . $node['id'] . '" data-nexttype="' . $nextType . '" data-name="' . htmlspecialchars($node['name']) . '"><i class="fa fa-plus"></i> Add ' . ucfirst($nextType) . '</button>';
                            }
                            echo '<button class="btn btn-outline-danger btn-xs py-0 px-2 btn-delete-loc" data-id="' . $node['id'] . '"><i class="fa fa-trash"></i></button>';
                            echo '</div>';
                            echo '</div>';

                            if (!empty($node['children'])) {
                                foreach ($node['children'] as $child) {
                                    renderTreeNode($child);
                                }
                            }
                            echo '</div>';
                        }

                        foreach ($locationsTree as $root) {
                            renderTreeNode($root);
                        }
                        ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($page === 'modules'): ?>
                <div class="alert alert-info">
                    <i class="fa fa-cubes mr-1"></i> <strong>HMS Module Catalog:</strong> All 23 hospital modules as defined in Section 17 of the Carelio Specification, categorized across Phases 1 through 4.
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Code</th>
                                <th>Module Name</th>
                                <th>Phase</th>
                                <th>Status</th>
                                <th>Dependencies</th>
                                <th>Specification Scope</th>
                                <th class="text-right">Lifecycle Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($moduleCatalog as $mod): ?>
                                <tr>
                                    <td><strong><code><?php echo htmlspecialchars($mod['module_code']); ?></code></strong></td>
                                    <td><?php echo htmlspecialchars($mod['name']); ?></td>
                                    <td><span class="badge badge-pill badge-primary">Phase <?php echo htmlspecialchars($mod['phase']); ?></span></td>
                                    <td>
                                        <?php if ($mod['status'] === 'active'): ?>
                                            <span class="badge badge-success px-2 py-1"><i class="fa fa-check mr-1"></i>Active</span>
                                        <?php elseif ($mod['status'] === 'paused'): ?>
                                            <span class="badge badge-warning text-dark px-2 py-1"><i class="fa fa-pause mr-1"></i>Paused</span>
                                        <?php elseif ($mod['status'] === 'retired'): ?>
                                            <span class="badge badge-secondary px-2 py-1"><i class="fa fa-archive mr-1"></i>Retired</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning px-2 py-1"><i class="fa fa-pencil mr-1"></i>Drafting</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($mod['deps_array'])): ?>
                                            <?php foreach ($mod['deps_array'] as $dep): ?>
                                                <span class="badge badge-light border"><code><?php echo htmlspecialchars($dep); ?></code></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted"><small>None (Base)</small></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted">OpenEMR 8.4 Extension (Table prefix: <code>hms_</code>)</small></td>
                                    <td class="text-right">
                                        <?php if ($mod['status'] !== 'active'): ?>
                                            <button class="btn btn-outline-success btn-xs py-1 px-2 btn-change-mod" data-code="<?php echo $mod['module_code']; ?>" data-status="active">
                                                <i class="fa fa-play"></i> Activate
                                            </button>
                                        <?php else: ?>
                                            <?php if ($mod['module_code'] !== 'CORE'): ?>
                                                <button class="btn btn-outline-warning btn-xs py-1 px-2 btn-change-mod" data-code="<?php echo $mod['module_code']; ?>" data-status="paused">
                                                    <i class="fa fa-pause"></i> Pause
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($mod['module_code'] !== 'CORE'): ?>
                                            <button class="btn btn-outline-secondary btn-xs py-1 px-2 btn-change-mod" data-code="<?php echo $mod['module_code']; ?>" data-status="retired">
                                                <i class="fa fa-archive"></i> Retire
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page === 'approvals'): ?>
                <?php require __DIR__ . '/templates/approvals.php'; ?>

            <?php elseif ($page === 'privacy'): ?>
                <?php require __DIR__ . '/templates/privacy.php'; ?>

            <?php elseif ($page === 'registration'): ?>
                <?php require __DIR__ . '/templates/registration.php'; ?>

            <?php elseif ($page === 'config'): ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card p-3 mb-3">
                            <h5 class="card-title"><i class="fa fa-globe mr-1"></i> Regional Profile: Saint Lucia</h5>
                            <p class="text-muted mb-3">Loaded from Saint Lucia deployment requirements (Specification Section 1.1).</p>
                            <table class="table table-sm">
                                <tr><td><strong>Deployment Territory:</strong></td><td>Saint Lucia (LC)</td></tr>
                                <tr><td><strong>National Currency:</strong></td><td>East Caribbean Dollar (XCD)</td></tr>
                                <tr><td><strong>Timezone:</strong></td><td>America/St_Lucia (UTC -4)</td></tr>
                                <tr><td><strong>Language:</strong></td><td>English (Saint Lucia)</td></tr>
                                <tr><td><strong>Demographics Synced:</strong></td><td>10 Districts & 368 Communities</td></tr>
                                <tr><td><strong>Patient Source of Truth:</strong></td><td>OpenEMR (<code>patient_data.pid</code>, <code>pubpid</code>)</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-3 mb-3">
                            <h5 class="card-title"><i class="fa fa-shield mr-1"></i> Security & Core Rules</h5>
                            <p class="text-muted mb-3">Enforced architectural rules.</p>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Table Prefix:</strong> Strictly <code>hms_</code> on all new tables.</li>
                                <li class="list-group-item"><strong>Core Integrity:</strong> Zero modification to OpenEMR 8.4 core files.</li>
                                <li class="list-group-item"><strong>Isolation:</strong> Single-tenant database per hospital.</li>
                                <li class="list-group-item"><strong>Break-Glass:</strong> 24-hour review audit queue enabled.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Add Location Node -->
<div class="modal fade" id="modalAddLocation" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-plus-circle mr-1"></i> Add Location Node</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formAddLocation">
                <input type="hidden" name="action" value="add_location">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><strong>Location Type *</strong></label>
                            <select name="location_type" id="selectLocationType" class="form-control" required>
                                <option value="building">Building</option>
                                <option value="floor">Floor</option>
                                <option value="ward">Ward</option>
                                <option value="room">Room</option>
                                <option value="bed">Bed</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label><strong>Parent Location *</strong></label>
                            <select name="parent_id" id="selectParentId" class="form-control">
                                <option value="">-- None (Top Level Site) --</option>
                                <?php foreach ($parentOptions as $parent): ?>
                                    <option value="<?php echo $parent['id']; ?>">
                                        [<?php echo strtoupper($parent['location_type']); ?>] <?php echo htmlspecialchars($parent['name']); ?> (ID: <?php echo $parent['id']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Where this location belongs (e.g. Bed goes into a Room).</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><strong>Location Code *</strong></label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. WRD-MED-01, BED-102" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label><strong>Name *</strong></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Male Medical Ward, Bed 102-A" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Specialty</label>
                            <input type="text" name="specialty" class="form-control" placeholder="e.g. general_medicine, surgery">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Care Level</label>
                            <select name="care_level" class="form-control">
                                <option value="">Select Level</option>
                                <option value="general">General</option>
                                <option value="step_down">Step Down</option>
                                <option value="icu">ICU</option>
                                <option value="hdu">HDU</option>
                                <option value="observation">Observation</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Accommodation</label>
                            <select name="accommodation_type" class="form-control">
                                <option value="">Select Type</option>
                                <option value="public">Public</option>
                                <option value="semi_private">Semi-Private</option>
                                <option value="private">Private</option>
                                <option value="isolation">Isolation</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i> Save Location Node</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Seed Saint Lucia default hospital layout
    $('#btnSeedHospital').on('click', function() {
        if (!confirm('This will seed the Saint Lucia OKEU Hospital baseline (Emergency, Maternity, Medical/Surgical, ICU wards and beds). Proceed?')) {
            return;
        }
        $.post('index.php', { action: 'seed_saint_lucia' }, function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // Add location form submit
    $('#formAddLocation').on('submit', function(e) {
        e.preventDefault();
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // Add child shortcut on tree nodes
    $('.btn-add-child').on('click', function() {
        var parentId = $(this).data('id');
        var nextType = $(this).data('nexttype');
        $('#selectParentId').val(parentId);
        $('#selectLocationType').val(nextType);
        $('#modalAddLocation').modal('show');
    });

    // Delete location node
    $('.btn-delete-loc').on('click', function() {
        var id = $(this).data('id');
        if (!confirm('Delete this location node? Note: nodes with child rooms or beds cannot be deleted.')) {
            return;
        }
        $.post('index.php', { action: 'delete_location', id: id }, function(resp) {
            if (resp.success) {
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // Toggle module lifecycle status with dependency blocker
    $('.btn-change-mod').on('click', function() {
        var code = $(this).data('code');
        var status = $(this).data('status');
        if (!confirm('Set status of ' + code + ' to ' + status.toUpperCase() + '?')) {
            return;
        }
        $.post('index.php', {
            action: 'set_module_status',
            code: code,
            status: status
        }, function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert('Dependency Warning: ' + resp.error);
            }
        }, 'json');
    });
});
</script>
</body>
</html>
