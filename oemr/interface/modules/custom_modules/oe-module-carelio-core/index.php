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
use OpenEMR\Modules\CarelioCore\Services\LocationHierarchyService;
use OpenEMR\Modules\CarelioCore\Services\PrivacySensitivityService;

// Access control check (Admin, Front Desk, Clinicians, Medical Records, or Billing)
$isAdmin = AclMain::aclCheckCore('admin', 'super') || AclMain::aclCheckCore('admin', 'practice');
$isFrontDesk = $isAdmin || AclMain::aclCheckCore('patients', 'demo') || AclMain::aclCheckCore('patients', 'appt');
$isClinician = $isAdmin || AclMain::aclCheckCore('encounters', 'notes') || AclMain::aclCheckCore('patients', 'med');
$isTherapyStaff = $isClinician || $isFrontDesk;
$isMedicalRecords = $isAdmin || AclMain::aclCheckCore('patients', 'med');
$isBilling = $isAdmin || AclMain::aclCheckCore('acct', 'bill') || AclMain::aclCheckCore('acct', 'rep');

if (!$isFrontDesk && !$isClinician && !$isMedicalRecords && !$isBilling) {
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
        } elseif ($action === 'create_content') {
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $newId = ContentApprovalService::createItem($_POST, $userId);
            echo json_encode(['success' => true, 'id' => $newId]);
            exit;
        } elseif ($action === 'approve_content') {
            if (!$isAdmin) {
                echo json_encode(['success' => false, 'error' => 'Access Denied: Medical Director / Admin role required to approve clinical content.']);
                exit;
            }
            $userId = (int)($_SESSION['authUserID'] ?? 1);
            $ok = ContentApprovalService::approveItem((int)$_POST['id'], $userId);
            echo json_encode(['success' => $ok, 'message' => 'Clinical content approved and certified!']);
            exit;
        } elseif ($action === 'reject_content') {
            if (!$isAdmin) {
                echo json_encode(['success' => false, 'error' => 'Access Denied: Medical Director / Admin role required to reject clinical content.']);
                exit;
            }
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
        }
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Initial Data Fetch
$locationsTree = LocationHierarchyService::getTree(1);
$parentOptions = LocationHierarchyService::getParentOptions(1);
$page = $_GET['page'] ?? 'locations';

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
        .tree-node { margin-left: 22px; padding-left: 14px; border-left: 2px dashed #cbd5e1; margin-top: 6px; }
        .tree-node.tree-root { margin-left: 0; padding-left: 0; border-left: none; }
        .badge-site { background-color: #0284c7; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .4px; padding: 3px 8px; border-radius: 4px; }
        .badge-building { background-color: #4f46e5; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .4px; padding: 3px 8px; border-radius: 4px; }
        .badge-floor { background-color: #0d9488; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .4px; padding: 3px 8px; border-radius: 4px; }
        .badge-ward { background-color: #ea580c; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .4px; padding: 3px 8px; border-radius: 4px; }
        .badge-room { background-color: #8b5cf6; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .4px; padding: 3px 8px; border-radius: 4px; }
        .badge-bed { background-color: #10b981; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .4px; padding: 3px 8px; border-radius: 4px; }
        .badge-active { background-color: #dcfce7; color: #15803d; }
        .badge-draft { background-color: #fef3c7; color: #b45309; }
        .location-item { background: #fff; border: 1px solid #e2e8f0; border-left-width: 4px; padding: 10px 14px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; transition: background-color .15s, box-shadow .15s; }
        .location-item:hover { background: #f8fafc; box-shadow: 0 2px 5px rgba(15,23,42,0.06); }
        .location-item.loc-type-site { border-left-color: #0284c7; background: #f0f9ff; }
        .location-item.loc-type-building { border-left-color: #4f46e5; }
        .location-item.loc-type-floor { border-left-color: #0d9488; }
        .location-item.loc-type-ward { border-left-color: #ea580c; }
        .location-item.loc-type-room { border-left-color: #8b5cf6; }
        .location-item.loc-type-bed { border-left-color: #10b981; background: #f0fdf4; }
        .btn-toggle-branch { width: 24px; height: 24px; padding: 0; line-height: 22px; text-align: center; border-radius: 4px; }
        .loc-kpi-card { border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; padding: 12px 14px; }
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
                    <a class="nav-link tab-btn <?php echo $page === 'locations' ? 'active' : ''; ?>" href="?page=locations">
                        <i class="fa fa-sitemap mr-1"></i> Bed & Ward Hierarchy
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
            <?php if ($page === 'locations'): ?>
                <?php
                $locStats = ['site' => 0, 'building' => 0, 'floor' => 0, 'ward' => 0, 'room' => 0, 'bed' => 0];
                $countLocStats = function (array $nodes) use (&$countLocStats, &$locStats): void {
                    foreach ($nodes as $n) {
                        $t = $n['location_type'] ?? '';
                        if (isset($locStats[$t])) {
                            $locStats[$t]++;
                        }
                        if (!empty($n['children'])) {
                            $countLocStats($n['children']);
                        }
                    }
                };
                $countLocStats($locationsTree);
                ?>

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
                    <div class="row mb-3">
                        <div class="col-md-2 col-6 mb-2"><div class="loc-kpi-card shadow-sm"><small class="text-muted text-uppercase font-weight-bold">Sites</small><div class="h4 mb-0 font-weight-bold text-primary"><?php echo $locStats['site']; ?></div></div></div>
                        <div class="col-md-2 col-6 mb-2"><div class="loc-kpi-card shadow-sm"><small class="text-muted text-uppercase font-weight-bold">Buildings</small><div class="h4 mb-0 font-weight-bold text-indigo" style="color:#4f46e5;"><?php echo $locStats['building']; ?></div></div></div>
                        <div class="col-md-2 col-6 mb-2"><div class="loc-kpi-card shadow-sm"><small class="text-muted text-uppercase font-weight-bold">Floors</small><div class="h4 mb-0 font-weight-bold" style="color:#0d9488;"><?php echo $locStats['floor']; ?></div></div></div>
                        <div class="col-md-2 col-6 mb-2"><div class="loc-kpi-card shadow-sm"><small class="text-muted text-uppercase font-weight-bold">Wards</small><div class="h4 mb-0 font-weight-bold" style="color:#ea580c;"><?php echo $locStats['ward']; ?></div></div></div>
                        <div class="col-md-2 col-6 mb-2"><div class="loc-kpi-card shadow-sm"><small class="text-muted text-uppercase font-weight-bold">Rooms</small><div class="h4 mb-0 font-weight-bold" style="color:#8b5cf6;"><?php echo $locStats['room']; ?></div></div></div>
                        <div class="col-md-2 col-6 mb-2"><div class="loc-kpi-card shadow-sm"><small class="text-muted text-uppercase font-weight-bold">Total Beds</small><div class="h4 mb-0 font-weight-bold text-success"><?php echo $locStats['bed']; ?></div></div></div>
                    </div>

                    <div class="card p-2 mb-3 bg-light border">
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div class="input-group input-group-sm" style="max-width: 340px;">
                                <div class="input-group-prepend"><span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span></div>
                                <input type="text" id="locSearchInput" class="form-control" placeholder="Search ward, room, bed, or code...">
                            </div>
                            <div class="mt-2 mt-md-0">
                                <div class="btn-group btn-group-sm mr-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" id="btnExpandAllLoc"><i class="fa fa-plus-square-o mr-1"></i> Expand All</button>
                                    <button type="button" class="btn btn-outline-secondary" id="btnCollapseAllLoc"><i class="fa fa-minus-square-o mr-1"></i> Collapse to Wards</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="locationTreeContainer">
                        <?php
                        function renderTreeNode($node, $isRoot = false) {
                            $type = htmlspecialchars($node['location_type']);
                            $typeClass = 'badge-' . $type;
                            $hasChildren = !empty($node['children']);
                            $childCount = $hasChildren ? count($node['children']) : 0;
                            $searchKey = strtolower(trim(($node['name'] ?? '') . ' ' . ($node['code'] ?? '') . ' ' . ($node['specialty'] ?? '')));

                            echo '<div class="tree-node' . ($isRoot ? ' tree-root' : '') . '" data-type="' . $type . '" data-search="' . htmlspecialchars($searchKey) . '">';
                            echo '<div class="location-item loc-type-' . $type . '">';
                            echo '<div class="d-flex align-items-center flex-wrap">';
                            if ($hasChildren) {
                                echo '<button type="button" class="btn btn-light border btn-toggle-branch mr-2" title="Expand / Collapse"><i class="fa fa-chevron-down"></i></button>';
                            } else {
                                echo '<span class="mr-2 text-muted" style="width:24px;display:inline-block;text-align:center;"><i class="fa fa-' . ($type === 'bed' ? 'bed text-success' : 'circle-o') . '"></i></span>';
                            }
                            echo '<span class="' . $typeClass . ' mr-2">' . strtoupper($type) . '</span> ';
                            echo '<strong class="mr-1">' . htmlspecialchars($node['name']) . '</strong> ';
                            echo '<code class="small text-muted mr-2">' . htmlspecialchars($node['code']) . '</code> ';
                            if ($hasChildren) {
                                echo '<span class="badge badge-light border text-muted mr-1">' . $childCount . ' ' . ($childCount === 1 ? 'item' : 'items') . '</span>';
                            }
                            if (!empty($node['specialty'])) {
                                echo ' <span class="badge badge-secondary mr-1">' . htmlspecialchars(ucwords(str_replace('_', ' ', $node['specialty']))) . '</span>';
                            }
                            if (!empty($node['care_level'])) {
                                echo ' <span class="badge badge-info mr-1">' . htmlspecialchars(strtoupper(str_replace('_', ' ', $node['care_level']))) . '</span>';
                            }
                            if (!empty($node['accommodation_type'])) {
                                echo ' <span class="badge badge-light border mr-1">' . htmlspecialchars(ucwords(str_replace('_', ' ', $node['accommodation_type']))) . '</span>';
                            }
                            echo '</div>';
                            echo '<div class="text-nowrap ml-2">';
                            echo '<span class="badge badge-' . ($node['status'] === 'active' ? 'success' : 'warning') . ' mr-2">' . htmlspecialchars($node['status']) . '</span>';
                            if ($node['location_type'] !== 'bed') {
                                $nextType = $node['location_type'] === 'room' ? 'bed' : ($node['location_type'] === 'ward' ? 'room' : ($node['location_type'] === 'floor' ? 'ward' : 'building'));
                                echo '<button class="btn btn-outline-primary btn-xs py-0 px-2 mr-1 btn-add-child" data-id="' . $node['id'] . '" data-nexttype="' . $nextType . '" data-name="' . htmlspecialchars($node['name']) . '"><i class="fa fa-plus"></i> Add ' . ucfirst($nextType) . '</button>';
                            }
                            echo '<button class="btn btn-outline-danger btn-xs py-0 px-2 btn-delete-loc" data-id="' . $node['id'] . '" title="Delete"><i class="fa fa-trash"></i></button>';
                            echo '</div>';
                            echo '</div>';

                            if ($hasChildren) {
                                echo '<div class="tree-children">';
                                foreach ($node['children'] as $child) {
                                    renderTreeNode($child, false);
                                }
                                echo '</div>';
                            }
                            echo '</div>';
                        }

                        foreach ($locationsTree as $root) {
                            renderTreeNode($root, true);
                        }
                        ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($page === 'approvals'): ?>
                <?php require __DIR__ . '/templates/approvals.php'; ?>

            <?php elseif ($page === 'privacy'): ?>
                <?php require __DIR__ . '/templates/privacy.php'; ?>

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

    // Expand / Collapse individual tree branch
    $(document).on('click', '.btn-toggle-branch', function() {
        var node = $(this).closest('.tree-node');
        var children = node.children('.tree-children');
        var icon = $(this).find('i');
        children.slideToggle(120);
        icon.toggleClass('fa-chevron-down fa-chevron-right');
    });

    $('#btnExpandAllLoc').on('click', function() {
        $('.tree-children').show();
        $('.btn-toggle-branch i').removeClass('fa-chevron-right').addClass('fa-chevron-down');
    });

    $('#btnCollapseAllLoc').on('click', function() {
        $('.tree-node[data-type="ward"] > .tree-children').hide();
        $('.tree-node[data-type="ward"] > .location-item .btn-toggle-branch i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
    });

    // Live search across location tree
    $('#locSearchInput').on('input', function() {
        var q = $(this).val().toLowerCase().trim();
        var nodes = $('#locationTreeContainer .tree-node');
        if (!q) {
            nodes.show();
            return;
        }
        nodes.hide();
        nodes.each(function() {
            var text = String($(this).data('search') || '');
            if (text.indexOf(q) !== -1) {
                $(this).show();
                $(this).parents('.tree-node').show();
                $(this).parents('.tree-children').show();
            }
        });
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
