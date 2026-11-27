<?php
/**
 * Carelio Hospital EHR - Module 3: SCH (Scheduling & Resource Calendars)
 * Conforming to Specification Section 18.3 (Pages 38-39)
 */

use OpenEMR\Modules\CarelioCore\Services\LocationHierarchyService;
use OpenEMR\Modules\CarelioCore\Services\SchedulingService;

$currentUserId = (int)($_SESSION['authUserID'] ?? 1);
$resources = SchedulingService::getResources(false);
$availResources = array_filter($resources, fn($r) => (int)$r['is_available'] === 1);
$bookings = SchedulingService::getBookings();
$seriesList = SchedulingService::getSeriesList();
$appointments = SchedulingService::getProviderAppointments();
$flowBoard = SchedulingService::getTodayFlowBoard();
$noShowList = SchedulingService::getNoShowList();
$recalls = SchedulingService::getRecalls();
$reminders = SchedulingService::getReminders(25);
$patients = SchedulingService::getPatientsList();
$providers = SchedulingService::getProvidersList();
$reports = SchedulingService::getSchedulingReports();
$locOptions = LocationHierarchyService::getParentOptions(1);
$messagingProvider = SchedulingService::getMessagingProvider();
$initialCal = SchedulingService::getCalendarGrid('resource', 'week', date('Y-m-d'), null);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <span class="badge badge-primary mr-1">Roles Active:</span>
        <?php if (!empty($isFrontDesk)): ?><span class="badge badge-light border mr-1">Front Desk / Call Center</span><?php endif; ?>
        <?php if (!empty($isClinician)): ?><span class="badge badge-light border mr-1">Clinician</span><?php endif; ?>
        <?php if (!empty($isTherapyStaff)): ?><span class="badge badge-light border mr-1">Therapy &amp; Dialysis</span><?php endif; ?>
    </div>
    <div>
        <span class="badge badge-info px-2 py-1"><i class="fa fa-paper-plane mr-1"></i> Messaging Provider (Install): <strong><?php echo htmlspecialchars($messagingProvider); ?></strong></span>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-left-primary p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Physical Resources</h6>
                    <h3 class="mb-0 font-weight-bold text-primary"><?php echo count($availResources); ?> <small class="text-muted" style="font-size: 14px;">/ <?php echo count($resources); ?></small></h3>
                    <small class="text-muted">Rooms, Theatres &amp; Machines</small>
                </div>
                <div class="bg-primary text-white rounded-circle p-3"><i class="fa fa-hospital-o fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-left-success p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Today's Flow Board</h6>
                    <h3 class="mb-0 font-weight-bold text-success"><?php echo count($flowBoard['items']); ?></h3>
                    <small class="text-muted">Arrived: <?php echo (int)$flowBoard['counts']['arrived']; ?> &bull; In Room: <?php echo (int)$flowBoard['counts']['in_room']; ?></small>
                </div>
                <div class="bg-success text-white rounded-circle p-3"><i class="fa fa-calendar-check-o fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-left-info p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Active Treatment Series</h6>
                    <h3 class="mb-0 font-weight-bold text-info"><?php echo (int)($reports['series_summary']['active_series'] ?? 0); ?></h3>
                    <small class="text-muted">Dialysis, Chemo &amp; Physio</small>
                </div>
                <div class="bg-info text-white rounded-circle p-3"><i class="fa fa-repeat fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-left-warning p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">No-Shows &amp; Recalls</h6>
                    <h3 class="mb-0 font-weight-bold text-warning"><?php echo count($noShowList); ?> <small class="text-muted" style="font-size: 13px;">(<?php echo number_format((float)$reports['noshow_rate'], 1); ?>%)</small></h3>
                    <small class="text-muted"><?php echo (int)$reports['recalls_due_14d']; ?> recalls due (14d)</small>
                </div>
                <div class="bg-warning text-white rounded-circle p-3"><i class="fa fa-bell fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<div id="schAlertBox" class="alert d-none mb-3" role="alert"></div>

<ul class="nav nav-tabs mb-3" id="schSubTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#sch-calendar" role="tab">
            <i class="fa fa-calendar mr-1"></i> Day / Week Calendars
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#sch-bookings" role="tab">
            <i class="fa fa-clock-o mr-1"></i> Book Resource (<?php echo count($bookings); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#sch-resources" role="tab">
            <i class="fa fa-cogs mr-1"></i> Physical Resources (<?php echo count($resources); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#sch-series" role="tab">
            <i class="fa fa-repeat mr-1"></i> Treatment Series (<?php echo count($seriesList); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#sch-flowboard" role="tab">
            <i class="fa fa-user-md mr-1"></i> Live Flow Board
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#sch-noshows" role="tab">
            <i class="fa fa-user-times mr-1"></i> No-Show List (<?php echo count($noShowList); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#sch-recalls" role="tab">
            <i class="fa fa-bell-o mr-1"></i> Recalls &amp; Reminders
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#sch-reports" role="tab">
            <i class="fa fa-bar-chart mr-1"></i> SCH Reports
        </a>
    </li>
</ul>

<div class="tab-content">
    <!-- SUB-TAB 0: DAY / WEEK PROVIDER & RESOURCE CALENDARS -->
    <div class="tab-pane fade show active" id="sch-calendar" role="tabpanel">
        <div class="card shadow-sm p-3 mb-3">
            <div class="form-row align-items-end mb-3">
                <div class="col-md-2">
                    <label class="small font-weight-bold mb-1">Calendar Mode</label>
                    <select id="calMode" class="form-control form-control-sm">
                        <option value="resource">Physical Resources</option>
                        <option value="provider">Clinician / Provider</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold mb-1">View</label>
                    <select id="calViewType" class="form-control form-control-sm">
                        <option value="week">Week View</option>
                        <option value="day">Day View</option>
                    </select>
                </div>
                <div class="col-md-3" id="calResourceFilterWrap">
                    <label class="small font-weight-bold mb-1">Filter Resource</label>
                    <select id="calResourceFilter" class="form-control form-control-sm">
                        <option value="">All Physical Resources</option>
                        <?php foreach ($resources as $r): ?>
                            <option value="<?php echo (int)$r['id']; ?>"><?php echo htmlspecialchars($r['resource_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-none" id="calProviderFilterWrap">
                    <label class="small font-weight-bold mb-1">Filter Clinician</label>
                    <select id="calProviderFilter" class="form-control form-control-sm">
                        <option value="">All Clinicians</option>
                        <?php foreach ($providers as $pr): ?>
                            <option value="<?php echo (int)$pr['id']; ?>"><?php echo htmlspecialchars($pr['fname'] . ' ' . $pr['lname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold mb-1">Anchor Date</label>
                    <input type="date" id="calAnchorDate" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3 text-right">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnCalMySchedule" title="Filter Provider Calendar to Logged-in Clinician">
                        <i class="fa fa-user-md mr-1"></i> My Schedule
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnCalRefresh">
                        <i class="fa fa-refresh mr-1"></i> Load
                    </button>
                </div>
            </div>

            <div id="calGridContainer" class="table-responsive">
                <div class="row no-gutters border rounded">
                    <?php foreach ($initialCal['days'] as $day): ?>
                        <div class="col border-right p-2" style="min-height: 220px;">
                            <div class="font-weight-bold text-center pb-2 mb-2 border-bottom <?php echo $day === date('Y-m-d') ? 'text-primary' : 'text-dark'; ?>">
                                <?php echo date('D M j', strtotime($day)); ?>
                            </div>
                            <?php $evts = $initialCal['events_by_day'][$day] ?? []; ?>
                            <?php if (empty($evts)): ?>
                                <p class="text-muted small text-center mt-4">No bookings</p>
                            <?php else: ?>
                                <?php foreach ($evts as $ev): ?>
                                    <div class="border rounded p-1 mb-2 bg-light small">
                                        <div class="font-weight-bold text-primary"><?php echo htmlspecialchars(substr($ev['start_clk'], 0, 5) . ' - ' . substr($ev['end_clk'], 0, 5)); ?></div>
                                        <div><?php echo htmlspecialchars(trim(($ev['fname'] ?? '') . ' ' . ($ev['lname'] ?? ''))); ?></div>
                                        <div class="text-muted"><?php echo htmlspecialchars($ev['owner_name'] ?? ''); ?></div>
                                        <span class="badge badge-secondary"><?php echo htmlspecialchars($ev['status']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- SUB-TAB 1: RESOURCE CALENDAR & BOOKINGS -->
    <div class="tab-pane fade" id="sch-bookings" role="tabpanel">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-2"><i class="fa fa-plus-circle text-primary mr-1"></i> Book Physical Resource</h5>
                    <p class="text-muted small">Double-booking on overlapping time slots is automatically refused.</p>
                    <form id="formSchBooking">
                        <input type="hidden" name="action" value="sch_create_booking">
                        <div class="form-group">
                            <label><strong>Resource (Room / Theatre / Machine) *</strong></label>
                            <select name="resource_id" class="form-control form-control-sm" required>
                                <option value="">-- Select Resource --</option>
                                <?php foreach ($availResources as $r): ?>
                                    <option value="<?php echo (int)$r['id']; ?>">
                                        [<?php echo htmlspecialchars($r['resource_type']); ?>] <?php echo htmlspecialchars($r['resource_name']); ?> (<?php echo htmlspecialchars($r['resource_code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><strong>Patient *</strong></label>
                            <select name="pid" class="form-control form-control-sm" required>
                                <option value="">-- Select Patient --</option>
                                <?php foreach ($patients as $p): ?>
                                    <option value="<?php echo (int)$p['pid']; ?>">
                                        <?php echo htmlspecialchars($p['lname'] . ', ' . $p['fname'] . ' (' . ($p['pubpid'] ?: 'PID-' . $p['pid']) . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><strong>Start Time *</strong></label>
                                <input type="datetime-local" name="start_time" class="form-control form-control-sm" value="<?php echo date('Y-m-d\T09:00'); ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label><strong>End Time *</strong></label>
                                <input type="datetime-local" name="end_time" class="form-control form-control-sm" value="<?php echo date('Y-m-d\T10:30'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><strong>Purpose / Procedure</strong></label>
                            <input type="text" name="purpose" class="form-control form-control-sm" placeholder="e.g., CT Brain w/ Contrast, Appendectomy">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm btn-block">
                            <i class="fa fa-check mr-1"></i> Confirm Resource Booking
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm p-3">
                    <h5 class="mb-3"><i class="fa fa-list mr-1"></i> Scheduled Resource Bookings</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-light">
                                <tr><th>Resource</th><th>Patient</th><th>Time Window</th><th>Purpose</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No resource bookings yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $b): ?>
                                        <?php
                                        $badge = 'secondary';
                                        if ($b['booking_status'] === 'CONFIRMED') $badge = 'primary';
                                        elseif ($b['booking_status'] === 'IN_PROGRESS') $badge = 'info';
                                        elseif ($b['booking_status'] === 'COMPLETED') $badge = 'success';
                                        elseif ($b['booking_status'] === 'NO_SHOW') $badge = 'warning';
                                        elseif ($b['booking_status'] === 'CANCELLED') $badge = 'danger';
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($b['resource_name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($b['resource_code']); ?></small></td>
                                            <td><?php echo htmlspecialchars(trim(($b['fname'] ?? '') . ' ' . ($b['lname'] ?? ''))); ?><br><small class="text-muted"><?php echo htmlspecialchars($b['pubpid'] ?: 'PID-' . $b['pid']); ?></small></td>
                                            <td><small><?php echo htmlspecialchars(substr($b['start_time'], 0, 16)); ?><br>&rarr; <?php echo htmlspecialchars(substr($b['end_time'], 0, 16)); ?></small></td>
                                            <td><small><?php echo htmlspecialchars($b['purpose'] ?? '—'); ?></small></td>
                                            <td><span class="badge badge-<?php echo $badge; ?>"><?php echo htmlspecialchars($b['booking_status']); ?></span></td>
                                            <td>
                                                <?php if (in_array($b['booking_status'], ['REQUESTED', 'CONFIRMED', 'IN_PROGRESS'], true)): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-info btn-sch-status" data-id="<?php echo (int)$b['id']; ?>" data-status="IN_PROGRESS" title="In Room / In Progress"><i class="fa fa-play"></i></button>
                                                        <button class="btn btn-outline-success btn-sch-status" data-id="<?php echo (int)$b['id']; ?>" data-status="COMPLETED" title="Complete"><i class="fa fa-check"></i></button>
                                                        <button class="btn btn-outline-warning btn-sch-status" data-id="<?php echo (int)$b['id']; ?>" data-status="NO_SHOW" title="Mark No-Show"><i class="fa fa-user-times"></i></button>
                                                        <button class="btn btn-outline-danger btn-sch-status" data-id="<?php echo (int)$b['id']; ?>" data-status="CANCELLED" title="Cancel"><i class="fa fa-times"></i></button>
                                                    </div>
                                                <?php else: ?>
                                                    <small class="text-muted">—</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SUB-TAB 2: PHYSICAL RESOURCES -->
    <div class="tab-pane fade" id="sch-resources" role="tabpanel">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0"><i class="fa fa-plus text-success mr-1"></i> Add Resource</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnSeedSchResources"><i class="fa fa-magic mr-1"></i> Seed OKEU Resources</button>
                    </div>
                    <form id="formSchResource">
                        <input type="hidden" name="action" value="sch_create_resource">
                        <div class="form-row">
                            <div class="form-group col-md-5"><label><strong>Code *</strong></label><input type="text" name="resource_code" class="form-control form-control-sm" placeholder="DIAL-ST-05" required></div>
                            <div class="form-group col-md-7"><label><strong>Type *</strong></label><select name="resource_type" class="form-control form-control-sm" required><option value="ROOM">ROOM</option><option value="THEATRE">THEATRE</option><option value="EQUIPMENT">EQUIPMENT</option><option value="BED">BED</option></select></div>
                        </div>
                        <div class="form-group"><label><strong>Resource Name *</strong></label><input type="text" name="resource_name" class="form-control form-control-sm" placeholder="Dialysis Station 05" required></div>
                        <div class="form-group"><label><strong>Hospital Location (hms_location)</strong></label>
                            <select name="location_id" class="form-control form-control-sm">
                                <option value="">-- Optional Location Link --</option>
                                <?php foreach ($locOptions as $loc): ?>
                                    <option value="<?php echo (int)$loc['id']; ?>">[<?php echo strtoupper(htmlspecialchars($loc['location_type'])); ?>] <?php echo htmlspecialchars($loc['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label><strong>Specialty</strong></label><input type="text" name="specialty" class="form-control form-control-sm" placeholder="Nephrology, Surgery, Radiology"></div>
                        <button type="submit" class="btn btn-success btn-sm btn-block"><i class="fa fa-save mr-1"></i> Save Physical Resource</button>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm p-3">
                    <h5 class="mb-3"><i class="fa fa-cubes mr-1"></i> Registered Hospital Resources</h5>
                    <table class="table table-sm table-hover">
                        <thead class="thead-light"><tr><th>Code</th><th>Name</th><th>Type</th><th>Location</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if (empty($resources)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No resources configured.</td></tr>
                            <?php else: ?>
                                <?php foreach ($resources as $r): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($r['resource_code']); ?></code></td>
                                        <td><strong><?php echo htmlspecialchars($r['resource_name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($r['specialty'] ?? ''); ?></small></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($r['resource_type']); ?></span></td>
                                        <td><small><?php echo htmlspecialchars($r['location_name'] ?? 'Unlinked'); ?></small></td>
                                        <td><?php echo (int)$r['is_available'] === 1 ? '<span class="badge badge-success">Available</span>' : '<span class="badge badge-secondary">Unavailable</span>'; ?></td>
                                        <td><button class="btn btn-xs btn-outline-secondary btn-toggle-resource" data-id="<?php echo (int)$r['id']; ?>" data-avail="<?php echo (int)$r['is_available'] === 1 ? 0 : 1; ?>"><?php echo (int)$r['is_available'] === 1 ? 'Disable' : 'Enable'; ?></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SUB-TAB 3: TREATMENT SERIES -->
    <div class="tab-pane fade" id="sch-series" role="tabpanel">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-2"><i class="fa fa-repeat text-info mr-1"></i> New Treatment Series</h5>
                    <p class="text-muted small">Auto-schedules all sessions onto the selected resource and tracks per-session attendance &amp; billing.</p>
                    <form id="formSchSeries">
                        <input type="hidden" name="action" value="sch_create_series">
                        <div class="form-group"><label><strong>Patient *</strong></label>
                            <select name="pid" class="form-control form-control-sm" required>
                                <option value="">-- Select Patient --</option>
                                <?php foreach ($patients as $p): ?>
                                    <option value="<?php echo (int)$p['pid']; ?>"><?php echo htmlspecialchars($p['lname'] . ', ' . $p['fname'] . ' (' . ($p['pubpid'] ?: 'PID-' . $p['pid']) . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6"><label><strong>Treatment Type *</strong></label><select name="treatment_type" class="form-control form-control-sm" required><option value="DIALYSIS">DIALYSIS</option><option value="CHEMOTHERAPY">CHEMOTHERAPY</option><option value="PHYSIOTHERAPY">PHYSIOTHERAPY</option></select></div>
                            <div class="form-group col-md-6"><label><strong>Total Sessions *</strong></label><input type="number" name="total_sessions" class="form-control form-control-sm" value="12" min="1" max="100" required></div>
                        </div>
                        <div class="form-group"><label><strong>Assigned Resource *</strong></label>
                            <select name="resource_id" class="form-control form-control-sm" required>
                                <option value="">-- Select Station / Suite --</option>
                                <?php foreach ($availResources as $r): ?>
                                    <option value="<?php echo (int)$r['id']; ?>"><?php echo htmlspecialchars($r['resource_name']); ?> (<?php echo htmlspecialchars($r['resource_code']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6"><label><strong>Frequency *</strong></label><select name="frequency" class="form-control form-control-sm"><option value="3x weekly">3x weekly</option><option value="2x weekly">2x weekly</option><option value="Weekly">Weekly</option><option value="Daily">Daily</option></select></div>
                            <div class="form-group col-md-6"><label><strong>Fee / Session (XCD)</strong></label><input type="number" step="0.01" name="fee_per_session" class="form-control form-control-sm" value="350.00"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-5"><label><strong>Start Date *</strong></label><input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" required></div>
                            <div class="form-group col-md-4"><label><strong>Time *</strong></label><input type="time" name="start_time" class="form-control form-control-sm" value="08:00" required></div>
                            <div class="form-group col-md-3"><label><strong>Mins</strong></label><input type="number" name="session_duration_min" class="form-control form-control-sm" value="180" min="15"></div>
                        </div>
                        <button type="submit" class="btn btn-info btn-sm btn-block"><i class="fa fa-calendar-plus-o mr-1"></i> Create &amp; Schedule Series</button>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm p-3">
                    <h5 class="mb-3"><i class="fa fa-tasks mr-1"></i> Active &amp; Completed Treatment Series</h5>
                    <table class="table table-sm table-hover">
                        <thead class="thead-light"><tr><th>Patient</th><th>Regimen &amp; Resource</th><th>Progress</th><th>Fee/Visit</th><th>Status</th><th>Sessions</th></tr></thead>
                        <tbody>
                            <?php if (empty($seriesList)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No treatment series created yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($seriesList as $s): ?>
                                    <?php $pct = (int)$s['total_sessions'] > 0 ? round(((int)$s['completed_sessions'] / (int)$s['total_sessions']) * 100) : 0; ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars(trim(($s['fname'] ?? '') . ' ' . ($s['lname'] ?? ''))); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($s['pubpid'] ?: 'PID-' . $s['pid']); ?></small></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($s['treatment_type']); ?></span><small class="d-block text-muted"><?php echo htmlspecialchars($s['resource_name'] ?? ''); ?> &bull; <?php echo htmlspecialchars($s['frequency']); ?></small></td>
                                        <td style="min-width: 130px;"><small><?php echo (int)$s['completed_sessions']; ?> / <?php echo (int)$s['total_sessions']; ?> sessions</small><div class="progress" style="height: 6px;"><div class="progress-bar bg-success" style="width: <?php echo $pct; ?>%"></div></div></td>
                                        <td>$<?php echo number_format((float)$s['fee_per_session'], 2); ?></td>
                                        <td><span class="badge badge-<?php echo $s['status'] === 'COMPLETED' ? 'success' : 'primary'; ?>"><?php echo htmlspecialchars($s['status']); ?></span></td>
                                        <td><button class="btn btn-outline-primary btn-xs btn-view-series" data-id="<?php echo (int)$s['id']; ?>" data-title="<?php echo htmlspecialchars($s['title']); ?>"><i class="fa fa-list-ol mr-1"></i> Sessions</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SUB-TAB 4: LIVE FLOW BOARD (TODAY) + PROVIDER APPOINTMENTS -->
    <div class="tab-pane fade" id="sch-flowboard" role="tabpanel">
        <div class="row mb-3">
            <div class="col-md-3"><div class="card p-2 text-center border-secondary"><small class="text-muted">WAITING / SCHEDULED</small><h4 class="mb-0 text-secondary font-weight-bold"><?php echo (int)$flowBoard['counts']['waiting']; ?></h4></div></div>
            <div class="col-md-3"><div class="card p-2 text-center border-info"><small class="text-muted">ARRIVED</small><h4 class="mb-0 text-info font-weight-bold"><?php echo (int)$flowBoard['counts']['arrived']; ?></h4></div></div>
            <div class="col-md-3"><div class="card p-2 text-center border-primary"><small class="text-muted">IN ROOM / IN PROGRESS</small><h4 class="mb-0 text-primary font-weight-bold"><?php echo (int)$flowBoard['counts']['in_room']; ?></h4></div></div>
            <div class="col-md-3"><div class="card p-2 text-center border-success"><small class="text-muted">DONE / COMPLETED</small><h4 class="mb-0 text-success font-weight-bold"><?php echo (int)$flowBoard['counts']['done']; ?></h4></div></div>
        </div>

        <div class="card shadow-sm p-3 mb-3">
            <h5 class="mb-2"><i class="fa fa-hospital-o mr-1"></i> Today's Live Flow Board (<?php echo htmlspecialchars($flowBoard['date']); ?>)</h5>
            <table class="table table-sm table-hover">
                <thead class="thead-light"><tr><th>Time</th><th>Type</th><th>Patient</th><th>Provider / Resource</th><th>Stage</th></tr></thead>
                <tbody>
                    <?php if (empty($flowBoard['items'])): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No patients scheduled for today yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($flowBoard['items'] as $fi): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($fi['time']); ?></strong></td>
                                <td><span class="badge badge-light border"><?php echo htmlspecialchars($fi['source']); ?></span></td>
                                <td><?php echo htmlspecialchars($fi['patient']); ?> <small class="text-muted">(<?php echo htmlspecialchars($fi['pubpid']); ?>)</small></td>
                                <td><?php echo htmlspecialchars($fi['location_or_provider']); ?> &mdash; <small><?php echo htmlspecialchars($fi['title']); ?></small></td>
                                <td><span class="badge badge-info"><?php echo strtoupper(str_replace('_', ' ', $fi['bucket'])); ?></span> <small class="text-muted">(<?php echo htmlspecialchars($fi['raw_status']); ?>)</small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card shadow-sm p-3">
            <h5 class="mb-3"><i class="fa fa-user-md mr-1"></i> All OpenEMR Provider Appointments</h5>
            <table class="table table-sm table-hover">
                <thead class="thead-light"><tr><th>Date &amp; Time</th><th>Patient</th><th>Provider</th><th>Title / Room</th><th>Status</th><th>Flow Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No outpatient provider appointments found in <code>openemr_postcalendar_events</code>.</td></tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $a): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($a['pc_eventDate'] . ' ' . substr($a['pc_startTime'], 0, 5)); ?></td>
                                <td><strong><?php echo htmlspecialchars(trim(($a['fname'] ?? '') . ' ' . ($a['lname'] ?? ''))); ?></strong> <small class="text-muted">(<?php echo htmlspecialchars($a['pubpid'] ?: 'PID-' . $a['pc_pid']); ?>)</small></td>
                                <td><?php echo htmlspecialchars(trim($a['provider_name'] ?? 'Unassigned')); ?></td>
                                <td><?php echo htmlspecialchars($a['pc_title']); ?></td>
                                <td><span class="badge badge-<?php echo $a['pc_apptstatus'] === '?' ? 'danger' : 'info'; ?>"><?php echo htmlspecialchars($a['status_label'] ?: $a['pc_apptstatus']); ?></span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info btn-appt-status" data-eid="<?php echo (int)$a['pc_eid']; ?>" data-status="@">Arrived</button>
                                        <button class="btn btn-outline-primary btn-appt-status" data-eid="<?php echo (int)$a['pc_eid']; ?>" data-status="<">In Room</button>
                                        <button class="btn btn-outline-success btn-appt-status" data-eid="<?php echo (int)$a['pc_eid']; ?>" data-status=">">Done</button>
                                        <button class="btn btn-outline-danger btn-appt-status" data-eid="<?php echo (int)$a['pc_eid']; ?>" data-status="?">No-Show</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SUB-TAB 5: DEDICATED NO-SHOW LIST -->
    <div class="tab-pane fade" id="sch-noshows" role="tabpanel">
        <div class="card shadow-sm p-3">
            <h5 class="mb-2"><i class="fa fa-user-times text-danger mr-1"></i> Missed Appointments &amp; No-Show Follow-Up List</h5>
            <p class="text-muted small">Combined list of missed outpatient appointments and missed physical resource slots for staff follow-up.</p>
            <table class="table table-sm table-hover">
                <thead class="thead-light"><tr><th>Scheduled At</th><th>Source</th><th>Patient</th><th>Phone / Contact</th><th>Resource / Provider</th><th>Follow-Up Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($noShowList)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No missed appointments recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($noShowList as $ns): ?>
                            <tr>
                                <td><span class="badge badge-warning"><?php echo htmlspecialchars($ns['scheduled_at']); ?></span></td>
                                <td><span class="badge badge-light border"><?php echo htmlspecialchars($ns['source']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars(trim(($ns['fname'] ?? '') . ' ' . ($ns['lname'] ?? ''))); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($ns['pubpid'] ?: 'PID-' . $ns['pid']); ?></small></td>
                                <td><small><?php echo htmlspecialchars($ns['phone_cell'] ?: ($ns['email'] ?: 'No contact')); ?></small></td>
                                <td><?php echo htmlspecialchars($ns['detail'] ?? ''); ?><br><small class="text-muted"><?php echo htmlspecialchars($ns['reason'] ?? ''); ?></small></td>
                                <td>
                                    <button class="btn btn-outline-warning btn-xs btn-noshow-recall" data-pid="<?php echo (int)$ns['pid']; ?>" data-reason="Follow-up: Missed <?php echo htmlspecialchars($ns['detail'] ?? 'appointment'); ?>">
                                        <i class="fa fa-bell mr-1"></i> Add Recall
                                    </button>
                                    <button class="btn btn-outline-primary btn-xs btn-noshow-remind" data-pid="<?php echo (int)$ns['pid']; ?>">
                                        <i class="fa fa-send mr-1"></i> Send Follow-Up
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SUB-TAB 6: RECALLS & REMINDERS -->
    <div class="tab-pane fade" id="sch-recalls" role="tabpanel">
        <div class="row">
            <div class="col-md-5">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-2"><i class="fa fa-bell text-warning mr-1"></i> Create Patient Recall</h5>
                    <form id="formSchRecall">
                        <input type="hidden" name="action" value="sch_create_recall">
                        <div class="form-group"><label><strong>Patient *</strong></label>
                            <select name="pid" class="form-control form-control-sm" required>
                                <option value="">-- Select Patient --</option>
                                <?php foreach ($patients as $p): ?><option value="<?php echo (int)$p['pid']; ?>"><?php echo htmlspecialchars($p['lname'] . ', ' . $p['fname'] . ' (' . ($p['pubpid'] ?: 'PID-' . $p['pid']) . ')'); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6"><label><strong>Recall Due Date *</strong></label><input type="date" name="recall_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required></div>
                            <div class="form-group col-md-6"><label><strong>Provider</strong></label><select name="provider_id" class="form-control form-control-sm"><?php foreach ($providers as $pr): ?><option value="<?php echo (int)$pr['id']; ?>"><?php echo htmlspecialchars($pr['fname'] . ' ' . $pr['lname']); ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div class="form-group"><label><strong>Recall Reason *</strong></label><input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g., Post-op suture removal" required></div>
                        <button type="submit" class="btn btn-warning btn-sm btn-block"><i class="fa fa-plus mr-1"></i> Add to Recall List</button>
                    </form>
                </div>

                <div class="card shadow-sm p-3">
                    <h5 class="mb-2"><i class="fa fa-paper-plane text-primary mr-1"></i> Send / Log Reminder</h5>
                    <p class="text-muted small">Active Gateway: <strong><?php echo htmlspecialchars($messagingProvider); ?></strong></p>
                    <form id="formSchReminder">
                        <input type="hidden" name="action" value="sch_send_reminder">
                        <div class="form-row">
                            <div class="form-group col-md-8"><label><strong>Patient *</strong></label><select name="pid" class="form-control form-control-sm" required><option value="">-- Select Patient --</option><?php foreach ($patients as $p): ?><option value="<?php echo (int)$p['pid']; ?>"><?php echo htmlspecialchars($p['lname'] . ', ' . $p['fname']); ?></option><?php endforeach; ?></select></div>
                            <div class="form-group col-md-4"><label><strong>Channel</strong></label><select name="type" class="form-control form-control-sm"><option value="SMS">SMS</option><option value="Email">Email</option></select></div>
                        </div>
                        <div class="form-group"><label><strong>Message *</strong></label><input type="text" name="message" class="form-control form-control-sm" value="Reminder: You have a scheduled hospital appointment tomorrow at OKEU Hospital." required></div>
                        <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-send mr-1"></i> Dispatch &amp; Log Reminder</button>
                    </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-2"><i class="fa fa-list-alt mr-1"></i> Recall Due List (<code>medex_recalls</code>)</h5>
                    <table class="table table-sm"><thead class="thead-light"><tr><th>Due Date</th><th>Patient</th><th>Provider</th><th>Reason</th></tr></thead>
                        <tbody>
                            <?php if (empty($recalls)): ?><tr><td colspan="4" class="text-center text-muted py-3">No recalls scheduled.</td></tr>
                            <?php else: foreach ($recalls as $rc): ?><tr><td><span class="badge badge-warning"><?php echo htmlspecialchars($rc['r_eventDate']); ?></span></td><td><?php echo htmlspecialchars(trim(($rc['fname'] ?? '') . ' ' . ($rc['lname'] ?? ''))); ?></td><td><small><?php echo htmlspecialchars(trim($rc['provider_name'] ?? '')); ?></small></td><td><small><?php echo htmlspecialchars($rc['r_reason'] ?? ''); ?></small></td></tr><?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card shadow-sm p-3">
                    <h5 class="mb-2"><i class="fa fa-history mr-1"></i> Sent Reminders Log (<code>notification_log</code>)</h5>
                    <table class="table table-sm"><thead class="thead-light"><tr><th>Sent At</th><th>Patient</th><th>Gateway / Channel</th><th>Message</th></tr></thead>
                        <tbody>
                            <?php if (empty($reminders)): ?><tr><td colspan="4" class="text-center text-muted py-3">No reminders logged yet.</td></tr>
                            <?php else: foreach ($reminders as $rm): ?><tr><td><small><?php echo htmlspecialchars($rm['dSentDateTime']); ?></small></td><td><?php echo htmlspecialchars(trim(($rm['fname'] ?? '') . ' ' . ($rm['lname'] ?? ''))); ?></td><td><span class="badge badge-info"><?php echo htmlspecialchars($rm['type']); ?></span> <small class="text-muted"><?php echo htmlspecialchars($rm['sms_gateway_type']); ?></small></td><td><small><?php echo htmlspecialchars($rm['message']); ?></small></td></tr><?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SUB-TAB 7: SCH REPORTS -->
    <div class="tab-pane fade" id="sch-reports" role="tabpanel">
        <div class="row mb-3">
            <div class="col-md-4"><div class="card shadow-sm p-3 text-center"><h6 class="text-muted text-uppercase">Overall No-Show Rate</h6><h2 class="text-warning font-weight-bold"><?php echo number_format((float)$reports['noshow_rate'], 1); ?>%</h2><small class="text-muted"><?php echo (int)$reports['total_noshows']; ?> no-shows out of <?php echo (int)$reports['total_events']; ?> events</small></div></div>
            <div class="col-md-4"><div class="card shadow-sm p-3 text-center"><h6 class="text-muted text-uppercase">Series Session Completion</h6><h2 class="text-info font-weight-bold"><?php echo (int)($reports['series_summary']['sessions_completed'] ?? 0); ?> / <?php echo (int)($reports['series_summary']['sessions_planned'] ?? 0); ?></h2><small class="text-muted"><?php echo (int)($reports['series_summary']['total_series'] ?? 0); ?> total treatment series</small></div></div>
            <div class="col-md-4"><div class="card shadow-sm p-3 text-center"><h6 class="text-muted text-uppercase">Recalls Due (Next 14 Days)</h6><h2 class="text-primary font-weight-bold"><?php echo (int)$reports['recalls_due_14d']; ?></h2><small class="text-muted">Tracked in OpenEMR <code>medex_recalls</code></small></div></div>
        </div>
        <div class="card shadow-sm p-3">
            <h5 class="mb-3"><i class="fa fa-bar-chart mr-1"></i> Utilization by Physical Resource</h5>
            <table class="table table-sm table-bordered"><thead class="thead-light"><tr><th>Resource</th><th>Type</th><th>Total Bookings</th><th>Completed</th><th>No-Shows</th><th>Cancelled</th><th>Booked Hours</th></tr></thead>
                <tbody>
                    <?php if (empty($reports['utilization'])): ?><tr><td colspan="7" class="text-center text-muted py-3">No resource utilization data yet.</td></tr>
                    <?php else: foreach ($reports['utilization'] as $u): ?><tr><td><strong><?php echo htmlspecialchars($u['resource_name']); ?></strong> (<code><?php echo htmlspecialchars($u['resource_code']); ?></code>)</td><td><?php echo htmlspecialchars($u['resource_type']); ?></td><td><?php echo (int)$u['total_bookings']; ?></td><td class="text-success font-weight-bold"><?php echo (int)$u['completed_count']; ?></td><td class="text-warning font-weight-bold"><?php echo (int)$u['noshow_count']; ?></td><td class="text-danger"><?php echo (int)$u['cancelled_count']; ?></td><td><?php echo number_format(((int)$u['booked_minutes']) / 60, 1); ?> hrs</td></tr><?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Series Sessions -->
<div class="modal fade" id="modalSeriesSessions" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fa fa-list-ol mr-1"></i> <span id="seriesModalTitle">Series Sessions</span></h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
            <div class="modal-body" id="seriesModalBody">Loading...</div>
        </div>
    </div>
</div>

<script>
(function() {
    var currentUserId = <?php echo $currentUserId; ?>;

    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    function showSchAlert(msg, isSuccess) {
        var box = $('#schAlertBox');
        box.removeClass('d-none alert-success alert-danger')
           .addClass(isSuccess ? 'alert-success' : 'alert-danger')
           .html('<strong>' + (isSuccess ? 'Success: ' : 'Action Refused: ') + '</strong>' + esc(msg));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function schPost(data, onSuccess) {
        $.post('index.php', data, function(res) {
            if (res && res.success) {
                if (onSuccess) { onSuccess(res); } else { showSchAlert(res.message || 'Done', true); setTimeout(function() { location.reload(); }, 900); }
            } else {
                showSchAlert((res && res.error) ? res.error : 'Operation failed.', false);
            }
        }, 'json').fail(function() {
            showSchAlert('Server error communicating with Scheduling endpoint.', false);
        });
    }

    function loadCalendarGrid() {
        var mode = $('#calMode').val();
        var viewType = $('#calViewType').val();
        var anchorDate = $('#calAnchorDate').val();
        var filterId = mode === 'provider' ? $('#calProviderFilter').val() : $('#calResourceFilter').val();

        $.post('index.php', {
            action: 'sch_get_calendar_grid',
            mode: mode,
            view_type: viewType,
            anchor_date: anchorDate,
            filter_id: filterId
        }, function(res) {
            if (!res || !res.success || !res.calendar) return;
            var cal = res.calendar;
            var html = '<div class="row no-gutters border rounded">';
            cal.days.forEach(function(day) {
                var evts = cal.events_by_day[day] || [];
                html += '<div class="col border-right p-2" style="min-height:220px;">';
                html += '<div class="font-weight-bold text-center pb-2 mb-2 border-bottom">' + esc(day) + '</div>';
                if (!evts.length) {
                    html += '<p class="text-muted small text-center mt-4">No bookings</p>';
                } else {
                    evts.forEach(function(ev) {
                        var timeStr = (ev.start_clk || '').substr(0, 5) + ' - ' + (ev.end_clk || '').substr(0, 5);
                        var pat = ((ev.fname || '') + ' ' + (ev.lname || '')).trim();
                        html += '<div class="border rounded p-1 mb-2 bg-light small">';
                        html += '<div class="font-weight-bold text-primary">' + esc(timeStr) + '</div>';
                        html += '<div>' + esc(pat) + '</div>';
                        html += '<div class="text-muted">' + esc(ev.owner_name || '') + '</div>';
                        html += '<span class="badge badge-secondary">' + esc(ev.status) + '</span>';
                        html += '</div>';
                    });
                }
                html += '</div>';
            });
            html += '</div>';
            $('#calGridContainer').html(html);
        }, 'json');
    }

    $('#calMode').on('change', function() {
        if ($(this).val() === 'provider') {
            $('#calResourceFilterWrap').addClass('d-none');
            $('#calProviderFilterWrap').removeClass('d-none');
        } else {
            $('#calProviderFilterWrap').addClass('d-none');
            $('#calResourceFilterWrap').removeClass('d-none');
        }
        loadCalendarGrid();
    });

    $('#btnCalRefresh, #calViewType, #calResourceFilter, #calProviderFilter, #calAnchorDate').on('change click', function(e) {
        if (e.type === 'click' && this.id !== 'btnCalRefresh') return;
        loadCalendarGrid();
    });

    $('#btnCalMySchedule').on('click', function() {
        $('#calMode').val('provider').trigger('change');
        $('#calProviderFilter').val(currentUserId);
        loadCalendarGrid();
    });

    $('#formSchBooking, #formSchResource, #formSchSeries, #formSchRecall, #formSchReminder').on('submit', function(e) {
        e.preventDefault();
        schPost($(this).serialize());
    });

    $('#btnSeedSchResources').on('click', function() { schPost({ action: 'sch_seed_resources' }); });
    $('.btn-toggle-resource').on('click', function() { schPost({ action: 'sch_toggle_resource', resource_id: $(this).data('id'), is_available: $(this).data('avail') }); });
    $('.btn-sch-status').on('click', function() { schPost({ action: 'sch_update_booking_status', booking_id: $(this).data('id'), status: $(this).data('status') }); });
    $('.btn-appt-status').on('click', function() { schPost({ action: 'sch_update_appt_status', pc_eid: $(this).data('eid'), status: $(this).data('status') }); });

    $('.btn-noshow-recall').on('click', function() {
        var todayPlus3 = new Date(Date.now() + 3 * 86400000).toISOString().slice(0, 10);
        schPost({ action: 'sch_create_recall', pid: $(this).data('pid'), recall_date: todayPlus3, reason: $(this).data('reason') });
    });

    $('.btn-noshow-remind').on('click', function() {
        schPost({ action: 'sch_send_reminder', pid: $(this).data('pid'), type: 'SMS', message: 'We missed you at your scheduled appointment. Please contact OKEU Hospital to reschedule.' });
    });

    $('.btn-view-series').on('click', function() {
        var sid = $(this).data('id');
        $('#seriesModalTitle').text($(this).data('title') || ('Series #' + sid));
        $('#seriesModalBody').html('<p class="text-muted">Loading sessions...</p>');
        $('#modalSeriesSessions').modal('show');
        $.post('index.php', { action: 'sch_get_series_sessions', series_id: sid }, function(res) {
            if (!res || !res.success) { $('#seriesModalBody').html('<div class="alert alert-danger">Could not load sessions.</div>'); return; }
            var html = '<table class="table table-sm"><thead><tr><th>#</th><th>Date &amp; Time</th><th>Resource</th><th>Fee</th><th>Status</th><th>Action</th></tr></thead><tbody>';
            (res.sessions || []).forEach(function(s) {
                var btn = s.booking_status === 'COMPLETED' ? '<span class="text-success"><i class="fa fa-check-circle"></i> Billed</span>' : '<button class="btn btn-success btn-xs btn-attend-session" data-bid="' + s.id + '">Mark Attended &amp; Bill</button>';
                html += '<tr><td>' + s.session_number + '</td><td>' + esc(s.start_time) + '</td><td>' + esc(s.resource_name || '') + '</td><td>$' + parseFloat(s.charge_amount).toFixed(2) + '</td><td><span class="badge badge-secondary">' + esc(s.booking_status) + '</span></td><td>' + btn + '</td></tr>';
            });
            html += '</tbody></table>';
            $('#seriesModalBody').html(html);
        }, 'json');
    });

    $(document).on('click', '.btn-attend-session', function() { schPost({ action: 'sch_mark_session_attended', booking_id: $(this).data('bid') }); });
})();
</script>
