<?php

namespace OpenEMR\Modules\CarelioCore\Services;

class SchedulingService
{
    public const RESOURCE_TYPES = ['ROOM', 'THEATRE', 'EQUIPMENT', 'BED'];
    public const ACTIVE_BOOKING_STATUSES = ['REQUESTED', 'CONFIRMED', 'IN_PROGRESS'];
    public const ALL_BOOKING_STATUSES = ['REQUESTED', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'NO_SHOW'];
    public const TREATMENT_TYPES = ['DIALYSIS', 'CHEMOTHERAPY', 'PHYSIOTHERAPY'];

    /**
     * Publish an event to the CORE hms_event bus
     */
    private static function publishEvent(string $eventType, int $pid, ?int $encounterId, int $actorId, array $payload): void
    {
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `facility_id`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES (?, ?, ?, 1, ?, NOW(), NOW(), ?, 'published')",
            [$eventType, $pid, $encounterId, $actorId, json_encode($payload)]
        );
    }

    /**
     * Seed default Saint Lucia OKEU Hospital physical resources (Theatres, Dialysis, Imaging, Rehab)
     */
    public static function seedDefaultResources(int $userId): array
    {
        $defaults = [
            ['code' => 'OR-MAIN-01', 'name' => 'Main Operating Theatre 1', 'type' => 'THEATRE', 'specialty' => 'General Surgery'],
            ['code' => 'OR-MAIN-02', 'name' => 'Main Operating Theatre 2 (Obstetrics/Ortho)', 'type' => 'THEATRE', 'specialty' => 'Surgery & Obstetrics'],
            ['code' => 'DIAL-ST-01', 'name' => 'Dialysis Station 01', 'type' => 'EQUIPMENT', 'specialty' => 'Nephrology'],
            ['code' => 'DIAL-ST-02', 'name' => 'Dialysis Station 02', 'type' => 'EQUIPMENT', 'specialty' => 'Nephrology'],
            ['code' => 'DIAL-ST-03', 'name' => 'Dialysis Station 03', 'type' => 'EQUIPMENT', 'specialty' => 'Nephrology'],
            ['code' => 'DIAL-ST-04', 'name' => 'Dialysis Station 04', 'type' => 'EQUIPMENT', 'specialty' => 'Nephrology'],
            ['code' => 'RAD-CT-01', 'name' => 'CT Scanner Suite 1', 'type' => 'EQUIPMENT', 'specialty' => 'Radiology'],
            ['code' => 'RAD-XR-01', 'name' => 'Digital X-Ray Room 1', 'type' => 'ROOM', 'specialty' => 'Radiology'],
            ['code' => 'ENDO-01', 'name' => 'Endoscopy & Bronchoscopy Suite', 'type' => 'ROOM', 'specialty' => 'Gastroenterology'],
            ['code' => 'PHYS-BAY-01', 'name' => 'Physiotherapy Rehab Bay 1', 'type' => 'ROOM', 'specialty' => 'Physiotherapy'],
        ];

        // Try to link to an existing hms_location root/ward if seeded
        $locRow = sqlQuery("SELECT `id` FROM `hms_location` WHERE `location_type` IN ('ward', 'building', 'site') ORDER BY `id` ASC LIMIT 1");
        $defaultLocId = !empty($locRow['id']) ? (int)$locRow['id'] : null;

        $inserted = 0;
        foreach ($defaults as $res) {
            $exists = sqlQuery("SELECT `id` FROM `hms_resource` WHERE `resource_code` = ? LIMIT 1", [$res['code']]);
            if (!$exists) {
                sqlStatement(
                    "INSERT INTO `hms_resource` (`resource_code`, `resource_name`, `resource_type`, `location_id`, `specialty`, `is_available`, `created_by`)
                     VALUES (?, ?, ?, ?, ?, 1, ?)",
                    [$res['code'], $res['name'], $res['type'], $defaultLocId, $res['specialty'], $userId]
                );
                $inserted++;
            }
        }

        return ['success' => true, 'inserted' => $inserted, 'message' => "Seeded $inserted physical hospital resources."];
    }

    /**
     * List physical hospital resources joined with hms_location
     */
    public static function getResources(bool $onlyAvailable = false): array
    {
        $where = $onlyAvailable ? "WHERE r.is_available = 1" : "";
        $sql = "SELECT r.*, l.name AS location_name, l.code AS location_code
                FROM `hms_resource` r
                LEFT JOIN `hms_location` l ON l.id = r.location_id
                $where
                ORDER BY r.resource_type ASC, r.resource_name ASC";
        $rows = sqlStatement($sql);
        $list = [];
        while ($row = sqlFetchArray($rows)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Create a new physical hospital resource
     */
    public static function createResource(array $data, int $userId): array
    {
        $code = strtoupper(trim($data['resource_code'] ?? ''));
        $name = trim($data['resource_name'] ?? '');
        $type = strtoupper(trim($data['resource_type'] ?? 'ROOM'));
        $locationId = !empty($data['location_id']) ? (int)$data['location_id'] : null;
        $specialty = trim($data['specialty'] ?? '');
        $notes = trim($data['notes'] ?? '');

        if ($code === '' || $name === '') {
            return ['success' => false, 'error' => 'Resource Code and Resource Name are required.'];
        }
        if (!in_array($type, self::RESOURCE_TYPES, true)) {
            return ['success' => false, 'error' => 'Invalid resource type.'];
        }

        $dup = sqlQuery("SELECT `id` FROM `hms_resource` WHERE `resource_code` = ? LIMIT 1", [$code]);
        if ($dup) {
            return ['success' => false, 'error' => "Resource code '$code' already exists."];
        }

        $id = sqlInsert(
            "INSERT INTO `hms_resource` (`resource_code`, `resource_name`, `resource_type`, `location_id`, `specialty`, `is_available`, `notes`, `created_by`)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?)",
            [$code, $name, $type, $locationId, $specialty ?: null, $notes ?: null, $userId]
        );

        return ['success' => true, 'id' => $id, 'message' => "Resource '$name' ($code) created."];
    }

    /**
     * Toggle physical resource availability
     */
    public static function toggleResourceAvailability(int $resourceId, int $isAvailable): array
    {
        sqlStatement("UPDATE `hms_resource` SET `is_available` = ? WHERE `id` = ?", [$isAvailable ? 1 : 0, $resourceId]);
        return ['success' => true, 'message' => 'Resource availability updated.'];
    }

    /**
     * Check for overlapping active bookings on a resource
     */
    public static function checkConflict(int $resourceId, string $startTime, string $endTime, ?int $excludeBookingId = null): ?array
    {
        $sql = "SELECT b.id, b.start_time, b.end_time, b.booking_status, p.fname, p.lname, p.pubpid
                FROM `hms_resource_booking` b
                LEFT JOIN `patient_data` p ON p.pid = b.pid
                WHERE b.resource_id = ?
                  AND b.booking_status IN ('REQUESTED', 'CONFIRMED', 'IN_PROGRESS')
                  AND b.start_time < ?
                  AND b.end_time > ?";
        $params = [$resourceId, $endTime, $startTime];

        if ($excludeBookingId !== null) {
            $sql .= " AND b.id != ?";
            $params[] = $excludeBookingId;
        }
        $sql .= " LIMIT 1";

        $conflict = sqlQuery($sql, $params);
        return $conflict ?: null;
    }

    /**
     * Create a physical resource booking with strict double-booking refusal
     */
    public static function createBooking(array $data, int $userId): array
    {
        $resourceId = (int)($data['resource_id'] ?? 0);
        $pid = (int)($data['pid'] ?? 0);
        $encounterId = !empty($data['encounter_id']) ? (int)$data['encounter_id'] : null;
        $startTime = trim($data['start_time'] ?? '');
        $endTime = trim($data['end_time'] ?? '');
        $purpose = trim($data['purpose'] ?? '');
        $notes = trim($data['notes'] ?? '');
        $status = strtoupper(trim($data['booking_status'] ?? 'CONFIRMED'));

        if ($resourceId <= 0 || $pid <= 0 || $startTime === '' || $endTime === '') {
            return ['success' => false, 'error' => 'Resource, Patient, Start Time, and End Time are required.'];
        }

        // Normalize HTML5 datetime-local (YYYY-MM-DDTHH:MM)
        $startTime = str_replace('T', ' ', $startTime);
        $endTime = str_replace('T', ' ', $endTime);
        if (strlen($startTime) === 16) {
            $startTime .= ':00';
        }
        if (strlen($endTime) === 16) {
            $endTime .= ':00';
        }

        if (strtotime($endTime) <= strtotime($startTime)) {
            return ['success' => false, 'error' => 'End Time must be after Start Time.'];
        }

        $resource = sqlQuery("SELECT * FROM `hms_resource` WHERE `id` = ? LIMIT 1", [$resourceId]);
        if (!$resource) {
            return ['success' => false, 'error' => 'Selected resource does not exist.'];
        }
        if ((int)$resource['is_available'] !== 1) {
            return ['success' => false, 'error' => "Resource '{$resource['resource_name']}' is currently marked unavailable."];
        }

        $patient = sqlQuery("SELECT `pid`, `fname`, `lname`, `pubpid` FROM `patient_data` WHERE `pid` = ? LIMIT 1", [$pid]);
        if (!$patient) {
            return ['success' => false, 'error' => 'Selected patient not found in patient_data.'];
        }

        if (!in_array($status, self::ACTIVE_BOOKING_STATUSES, true)) {
            $status = 'CONFIRMED';
        }

        // Atomic conflict check
        $conflict = self::checkConflict($resourceId, $startTime, $endTime);
        if ($conflict) {
            $patLabel = trim(($conflict['fname'] ?? '') . ' ' . ($conflict['lname'] ?? '')) . ' (' . ($conflict['pubpid'] ?? '') . ')';
            return [
                'success' => false,
                'conflict' => true,
                'error' => "Double-booking refused: '{$resource['resource_name']}' is already booked by $patLabel from {$conflict['start_time']} to {$conflict['end_time']}."
            ];
        }

        $bookingId = sqlInsert(
            "INSERT INTO `hms_resource_booking` (`resource_id`, `pid`, `encounter_id`, `start_time`, `end_time`, `booking_status`, `purpose`, `notes`, `booked_by`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$resourceId, $pid, $encounterId, $startTime, $endTime, $status, $purpose ?: null, $notes ?: null, $userId]
        );

        self::publishEvent('resource_booked', $pid, $encounterId, $userId, [
            'booking_id' => $bookingId,
            'resource_id' => $resourceId,
            'resource_name' => $resource['resource_name'],
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);

        return ['success' => true, 'booking_id' => $bookingId, 'message' => "Booked '{$resource['resource_name']}' for {$patient['fname']} {$patient['lname']}."];
    }

    /**
     * Update booking status (and auto-record series completion/charge if applicable)
     */
    public static function updateBookingStatus(int $bookingId, string $newStatus, int $userId): array
    {
        $newStatus = strtoupper(trim($newStatus));
        if (!in_array($newStatus, self::ALL_BOOKING_STATUSES, true)) {
            return ['success' => false, 'error' => "Invalid booking status: $newStatus"];
        }

        $booking = sqlQuery("SELECT * FROM `hms_resource_booking` WHERE `id` = ? LIMIT 1", [$bookingId]);
        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found.'];
        }

        if ($newStatus === 'COMPLETED' && !empty($booking['series_id'])) {
            return self::markSessionAttended($bookingId, $userId);
        }

        sqlStatement("UPDATE `hms_resource_booking` SET `booking_status` = ? WHERE `id` = ?", [$newStatus, $bookingId]);

        self::publishEvent('resource_booking_status_changed', (int)$booking['pid'], $booking['encounter_id'] ? (int)$booking['encounter_id'] : null, $userId, [
            'booking_id' => $bookingId,
            'old_status' => $booking['booking_status'],
            'new_status' => $newStatus
        ]);

        return ['success' => true, 'message' => "Booking #$bookingId updated to $newStatus."];
    }

    /**
     * Fetch resource bookings with optional filters
     */
    public static function getBookings(array $filters = []): array
    {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['resource_id'])) {
            $where[] = "b.resource_id = ?";
            $params[] = (int)$filters['resource_id'];
        }
        if (!empty($filters['date'])) {
            $where[] = "DATE(b.start_time) = ?";
            $params[] = $filters['date'];
        }
        if (!empty($filters['status'])) {
            $where[] = "b.booking_status = ?";
            $params[] = $filters['status'];
        }

        $sql = "SELECT b.*, r.resource_name, r.resource_code, r.resource_type,
                       p.fname, p.lname, p.pubpid,
                       u.username AS booked_by_username
                FROM `hms_resource_booking` b
                INNER JOIN `hms_resource` r ON r.id = b.resource_id
                LEFT JOIN `patient_data` p ON p.pid = b.pid
                LEFT JOIN `users` u ON u.id = b.booked_by
                WHERE " . implode(' AND ', $where) . "
                ORDER BY b.start_time DESC
                LIMIT 100";

        $rows = sqlStatement($sql, $params);
        $list = [];
        while ($row = sqlFetchArray($rows)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Create a multi-visit treatment series (Dialysis, Chemotherapy, Physiotherapy)
     * Automatically schedules all N sessions onto the assigned resource and calculates end_date.
     */
    public static function createTreatmentSeries(array $data, int $userId): array
    {
        $pid = (int)($data['pid'] ?? 0);
        $resourceId = (int)($data['resource_id'] ?? 0);
        $providerId = !empty($data['provider_id']) ? (int)$data['provider_id'] : null;
        $treatmentType = strtoupper(trim($data['treatment_type'] ?? 'DIALYSIS'));
        $title = trim($data['title'] ?? '');
        $totalSessions = max(1, min(100, (int)($data['total_sessions'] ?? 12)));
        $frequency = trim($data['frequency'] ?? '3x weekly');
        $startDate = trim($data['start_date'] ?? date('Y-m-d'));
        $startHour = trim($data['start_time'] ?? '08:00');
        $durationMin = max(15, min(720, (int)($data['session_duration_min'] ?? 180)));
        $feePerSession = (float)($data['fee_per_session'] ?? 0);
        $notes = trim($data['notes'] ?? '');

        if ($pid <= 0 || $resourceId <= 0) {
            return ['success' => false, 'error' => 'Patient and Physical Resource (e.g., Dialysis Station) are required.'];
        }
        if (!in_array($treatmentType, self::TREATMENT_TYPES, true)) {
            return ['success' => false, 'error' => 'Invalid treatment type.'];
        }

        $resource = sqlQuery("SELECT * FROM `hms_resource` WHERE `id` = ? LIMIT 1", [$resourceId]);
        if (!$resource || (int)$resource['is_available'] !== 1) {
            return ['success' => false, 'error' => 'Selected resource is not available.'];
        }

        // Compute session dates based on frequency
        $dates = self::generateSeriesDates($startDate, $totalSessions, $frequency);
        $endDate = end($dates);

        // Pre-check conflicts for all generated session slots
        $startClock = strlen($startHour) === 5 ? $startHour . ':00' : $startHour;
        foreach ($dates as $idx => $d) {
            $slotStart = "$d $startClock";
            $slotEnd = date('Y-m-d H:i:s', strtotime($slotStart) + ($durationMin * 60));
            $conflict = self::checkConflict($resourceId, $slotStart, $slotEnd);
            if ($conflict) {
                $sessNum = $idx + 1;
                return [
                    'success' => false,
                    'conflict' => true,
                    'error' => "Conflict on Session #$sessNum ($slotStart - $slotEnd) for '{$resource['resource_name']}'. Please choose a different time or station."
                ];
            }
        }

        if ($title === '') {
            $title = ucfirst(strtolower($treatmentType)) . " ($totalSessions sessions, $frequency)";
        }

        $seriesId = sqlInsert(
            "INSERT INTO `hms_session_series` (`pid`, `resource_id`, `provider_id`, `treatment_type`, `title`, `total_sessions`, `completed_sessions`, `frequency`, `session_duration_min`, `fee_per_session`, `start_date`, `end_date`, `status`, `notes`, `created_by`)
             VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, 'ACTIVE', ?, ?)",
            [$pid, $resourceId, $providerId, $treatmentType, $title, $totalSessions, $frequency, $durationMin, $feePerSession, $startDate, $endDate, $notes ?: null, $userId]
        );

        foreach ($dates as $idx => $d) {
            $sessNum = $idx + 1;
            $slotStart = "$d $startClock";
            $slotEnd = date('Y-m-d H:i:s', strtotime($slotStart) + ($durationMin * 60));
            sqlInsert(
                "INSERT INTO `hms_resource_booking` (`resource_id`, `pid`, `series_id`, `session_number`, `start_time`, `end_time`, `booking_status`, `purpose`, `charge_amount`, `charge_posted`, `booked_by`)
                 VALUES (?, ?, ?, ?, ?, ?, 'CONFIRMED', ?, ?, 0, ?)",
                [$resourceId, $pid, $seriesId, $sessNum, $slotStart, $slotEnd, "$treatmentType Session #$sessNum/$totalSessions", $feePerSession, $userId]
            );
        }

        self::publishEvent('treatment_series_created', $pid, null, $userId, [
            'series_id' => $seriesId,
            'treatment_type' => $treatmentType,
            'total_sessions' => $totalSessions,
            'fee_per_session' => $feePerSession,
            'resource_id' => $resourceId
        ]);

        return [
            'success' => true,
            'series_id' => $seriesId,
            'message' => "Created $treatmentType series (#$seriesId) with $totalSessions scheduled sessions on '{$resource['resource_name']}'."
        ];
    }

    /**
     * Generate session dates from start_date, total count, and frequency string
     */
    private static function generateSeriesDates(string $startDate, int $total, string $frequency): array
    {
        $freqLower = strtolower($frequency);
        $stepDays = 2; // default 3x weekly (~every 2 days, skipping Sunday if needed)
        if (strpos($freqLower, 'daily') !== false) {
            $stepDays = 1;
        } elseif (strpos($freqLower, '2x') !== false) {
            $stepDays = 3;
        } elseif (strpos($freqLower, 'weekly') !== false && strpos($freqLower, '3x') === false && strpos($freqLower, '2x') === false) {
            $stepDays = 7;
        } elseif (strpos($freqLower, 'biweekly') !== false || strpos($freqLower, '2 weeks') !== false) {
            $stepDays = 14;
        }

        $dates = [];
        $current = strtotime($startDate);
        for ($i = 0; $i < $total; $i++) {
            // Avoid Sunday for 3x weekly / 2x weekly clinic schedules
            if ($stepDays > 1 && (int)date('w', $current) === 0) {
                $current = strtotime('+1 day', $current);
            }
            $dates[] = date('Y-m-d', $current);
            $current = strtotime("+{$stepDays} days", $current);
        }
        return $dates;
    }

    /**
     * Mark a treatment series session as attended and post per-session charge event
     */
    public static function markSessionAttended(int $bookingId, int $userId): array
    {
        $booking = sqlQuery("SELECT * FROM `hms_resource_booking` WHERE `id` = ? LIMIT 1", [$bookingId]);
        if (!$booking) {
            return ['success' => false, 'error' => 'Session booking not found.'];
        }
        if ($booking['booking_status'] === 'COMPLETED') {
            return ['success' => false, 'error' => 'Session is already marked completed.'];
        }

        sqlStatement(
            "UPDATE `hms_resource_booking` SET `booking_status` = 'COMPLETED', `charge_posted` = 1 WHERE `id` = ?",
            [$bookingId]
        );

        $seriesId = (int)$booking['series_id'];
        if ($seriesId > 0) {
            $completedRow = sqlQuery(
                "SELECT COUNT(*) AS cnt FROM `hms_resource_booking` WHERE `series_id` = ? AND `booking_status` = 'COMPLETED'",
                [$seriesId]
            );
            $completedCount = (int)($completedRow['cnt'] ?? 0);
            $series = sqlQuery("SELECT `total_sessions` FROM `hms_session_series` WHERE `id` = ? LIMIT 1", [$seriesId]);
            $newSeriesStatus = ($series && $completedCount >= (int)$series['total_sessions']) ? 'COMPLETED' : 'ACTIVE';

            sqlStatement(
                "UPDATE `hms_session_series` SET `completed_sessions` = ?, `status` = ? WHERE `id` = ?",
                [$completedCount, $newSeriesStatus, $seriesId]
            );
        }

        self::publishEvent('series_session_attended', (int)$booking['pid'], $booking['encounter_id'] ? (int)$booking['encounter_id'] : null, $userId, [
            'booking_id' => $bookingId,
            'series_id' => $seriesId,
            'session_number' => (int)$booking['session_number'],
            'charge_amount' => (float)$booking['charge_amount']
        ]);

        return [
            'success' => true,
            'message' => "Session #{$booking['session_number']} marked attended & per-session charge (" . number_format((float)$booking['charge_amount'], 2) . " XCD) queued."
        ];
    }

    /**
     * List treatment series with patient & resource details
     */
    public static function getSeriesList(): array
    {
        $sql = "SELECT s.*, p.fname, p.lname, p.pubpid,
                       r.resource_name, r.resource_code,
                       CONCAT(u.fname, ' ', u.lname) AS provider_name
                FROM `hms_session_series` s
                LEFT JOIN `patient_data` p ON p.pid = s.pid
                LEFT JOIN `hms_resource` r ON r.id = s.resource_id
                LEFT JOIN `users` u ON u.id = s.provider_id
                ORDER BY s.created_at DESC
                LIMIT 50";
        $rows = sqlStatement($sql);
        $list = [];
        while ($row = sqlFetchArray($rows)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Get individual sessions for a series
     */
    public static function getSeriesSessions(int $seriesId): array
    {
        $sql = "SELECT b.*, r.resource_name
                FROM `hms_resource_booking` b
                LEFT JOIN `hms_resource` r ON r.id = b.resource_id
                WHERE b.series_id = ?
                ORDER BY b.session_number ASC, b.start_time ASC";
        $rows = sqlStatement($sql, [$seriesId]);
        $list = [];
        while ($row = sqlFetchArray($rows)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Query OpenEMR native provider appointments & flow board (openemr_postcalendar_events)
     */
    public static function getProviderAppointments(string $date = ''): array
    {
        $params = [];
        $where = "e.pc_pid IS NOT NULL AND e.pc_pid != '' AND e.pc_pid != '0'";
        if ($date !== '') {
            $where .= " AND e.pc_eventDate = ?";
            $params[] = $date;
        }

        $sql = "SELECT e.pc_eid, e.pc_pid, e.pc_aid, e.pc_title, e.pc_eventDate, e.pc_startTime, e.pc_endTime,
                       e.pc_duration, e.pc_apptstatus, e.pc_room, e.pc_hometext,
                       p.fname, p.lname, p.pubpid, p.phone_cell,
                       CONCAT(u.fname, ' ', u.lname) AS provider_name,
                       lo.title AS status_label
                FROM `openemr_postcalendar_events` e
                LEFT JOIN `patient_data` p ON p.pid = e.pc_pid
                LEFT JOIN `users` u ON u.id = e.pc_aid
                LEFT JOIN `list_options` lo ON lo.list_id = 'apptstat' AND lo.option_id = e.pc_apptstatus
                WHERE $where
                ORDER BY e.pc_eventDate DESC, e.pc_startTime ASC
                LIMIT 100";
        $rows = sqlStatement($sql, $params);
        $list = [];
        while ($row = sqlFetchArray($rows)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Update OpenEMR appointment status (e.g., '?' = No-Show, '@' = Arrived, '>' = Checked Out)
     */
    public static function updateAppointmentStatus(int $pcEid, string $status, int $userId): array
    {
        $appt = sqlQuery("SELECT `pc_eid`, `pc_pid`, `pc_apptstatus` FROM `openemr_postcalendar_events` WHERE `pc_eid` = ? LIMIT 1", [$pcEid]);
        if (!$appt) {
            return ['success' => false, 'error' => 'Appointment not found.'];
        }

        sqlStatement("UPDATE `openemr_postcalendar_events` SET `pc_apptstatus` = ? WHERE `pc_eid` = ?", [$status, $pcEid]);

        self::publishEvent('appointment_status_changed', (int)$appt['pc_pid'], null, $userId, [
            'pc_eid' => $pcEid,
            'old_status' => $appt['pc_apptstatus'],
            'new_status' => $status
        ]);

        return ['success' => true, 'message' => "Appointment #$pcEid status updated."];
    }

    /**
     * Create a patient recall entry in OpenEMR's native medex_recalls table
     */
    public static function createRecall(array $data, int $userId): array
    {
        $pid = (int)($data['pid'] ?? 0);
        $recallDate = trim($data['recall_date'] ?? '');
        $providerId = !empty($data['provider_id']) ? (int)$data['provider_id'] : $userId;
        $reason = trim($data['reason'] ?? '');

        if ($pid <= 0 || $recallDate === '' || $reason === '') {
            return ['success' => false, 'error' => 'Patient, Recall Date, and Reason are required.'];
        }

        $id = sqlInsert(
            "INSERT INTO `medex_recalls` (`r_PRACTID`, `r_pid`, `r_eventDate`, `r_facility`, `r_provider`, `r_reason`)
             VALUES (1, ?, ?, 1, ?, ?)",
            [$pid, $recallDate, $providerId, $reason]
        );

        self::publishEvent('recall_created', $pid, null, $userId, [
            'recall_id' => $id,
            'recall_date' => $recallDate,
            'reason' => $reason
        ]);

        return ['success' => true, 'id' => $id, 'message' => "Recall scheduled for $recallDate."];
    }

    /**
     * List recalls from OpenEMR's medex_recalls
     */
    public static function getRecalls(): array
    {
        $sql = "SELECT m.*, p.fname, p.lname, p.pubpid, p.phone_cell,
                       CONCAT(u.fname, ' ', u.lname) AS provider_name
                FROM `medex_recalls` m
                LEFT JOIN `patient_data` p ON p.pid = m.r_pid
                LEFT JOIN `users` u ON u.id = m.r_provider
                ORDER BY m.r_eventDate ASC
                LIMIT 100";
        $rows = sqlStatement($sql);
        $list = [];
        while ($row = sqlFetchArray($rows)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Send / log an appointment or recall reminder into OpenEMR's notification_log
     */
    public static function sendReminder(array $data, int $userId): array
    {
        $pid = (int)($data['pid'] ?? 0);
        $pcEid = !empty($data['pc_eid']) ? (int)$data['pc_eid'] : null;
        $type = strtoupper(trim($data['type'] ?? 'SMS')) === 'EMAIL' ? 'Email' : 'SMS';
        $message = trim($data['message'] ?? '');
        $eventDate = trim($data['event_date'] ?? date('Y-m-d'));
        $startTime = trim($data['start_time'] ?? '09:00:00');

        if ($pid <= 0 || $message === '') {
            return ['success' => false, 'error' => 'Patient and Reminder Message are required.'];
        }

        $patient = sqlQuery("SELECT `fname`, `lname`, `phone_cell`, `email` FROM `patient_data` WHERE `pid` = ? LIMIT 1", [$pid]);
        if (!$patient) {
            return ['success' => false, 'error' => 'Patient not found.'];
        }

        $contact = $type === 'Email' ? ($patient['email'] ?? 'no-email') : ($patient['phone_cell'] ?? 'no-phone');
        $patInfo = trim($patient['fname'] . ' ' . $patient['lname']) . " ($contact)";
        $provider = self::getMessagingProvider();

        $logId = sqlInsert(
            "INSERT INTO `notification_log` (`pid`, `pc_eid`, `sms_gateway_type`, `smsgateway_info`, `message`, `email_sender`, `email_subject`, `type`, `patient_info`, `pc_eventDate`, `pc_endDate`, `pc_startTime`, `pc_endTime`, `dSentDateTime`)
             VALUES (?, ?, ?, ?, ?, 'carelio@hospital.local', 'Appointment Reminder', ?, ?, ?, ?, ?, ?, NOW())",
            [$pid, $pcEid, $provider, $contact, $message, $type, $patInfo, $eventDate, $eventDate, $startTime, $startTime]
        );

        self::publishEvent('reminder_sent', $pid, null, $userId, [
            'notification_log_id' => $logId,
            'channel' => $type,
            'provider' => $provider,
            'contact' => $contact
        ]);

        return ['success' => true, 'id' => $logId, 'provider' => $provider, 'message' => "$type reminder logged via $provider for {$patient['fname']} {$patient['lname']}."];
    }

    /**
     * Read configured messaging provider from hms_install_config
     */
    public static function getMessagingProvider(): string
    {
        foreach (['wizard_step8', 'wizard_step8_integrations'] as $key) {
            $row = sqlQuery("SELECT `config_value` FROM `hms_install_config` WHERE `config_key` = ? LIMIT 1", [$key]);
            if (!empty($row['config_value'])) {
                $decoded = json_decode($row['config_value'], true);
                if (!empty($decoded['messaging_provider'])) {
                    return (string)$decoded['messaging_provider'];
                }
            }
        }
        return 'Twilio SMS + SMTP';
    }

    /**
     * Build Day or Week calendar grid for Physical Resources or Clinician Schedules
     */
    public static function getCalendarGrid(string $mode = 'resource', string $viewType = 'week', string $anchorDate = '', ?int $filterId = null): array
    {
        $baseTs = $anchorDate !== '' ? strtotime($anchorDate) : time();
        if ($viewType === 'day') {
            $days = [date('Y-m-d', $baseTs)];
        } else {
            // Monday through Sunday of anchorDate's week
            $dow = (int)date('N', $baseTs); // 1 (Mon) .. 7 (Sun)
            $monTs = strtotime('-' . ($dow - 1) . ' days', $baseTs);
            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $days[] = date('Y-m-d', strtotime("+$i days", $monTs));
            }
        }

        $startDate = $days[0];
        $endDate = end($days);
        $byDay = array_fill_keys($days, []);

        if ($mode === 'provider') {
            $params = [$startDate, $endDate];
            $extra = "";
            if ($filterId !== null && $filterId > 0) {
                $extra = " AND e.pc_aid = ?";
                $params[] = $filterId;
            }
            $sql = "SELECT e.pc_eid AS id, e.pc_eventDate AS evt_date, e.pc_startTime AS start_clk, e.pc_endTime AS end_clk,
                           e.pc_title AS title, e.pc_apptstatus AS status, e.pc_room AS room,
                           p.fname, p.lname, p.pubpid,
                           CONCAT(u.fname, ' ', u.lname) AS owner_name
                    FROM `openemr_postcalendar_events` e
                    LEFT JOIN `patient_data` p ON p.pid = e.pc_pid
                    LEFT JOIN `users` u ON u.id = e.pc_aid
                    WHERE e.pc_pid IS NOT NULL AND e.pc_pid != '' AND e.pc_pid != '0'
                      AND e.pc_eventDate BETWEEN ? AND ? $extra
                    ORDER BY e.pc_eventDate ASC, e.pc_startTime ASC";
            $rows = sqlStatement($sql, $params);
            while ($r = sqlFetchArray($rows)) {
                $d = $r['evt_date'];
                if (isset($byDay[$d])) {
                    $byDay[$d][] = $r;
                }
            }
        } else {
            $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
            $extra = "";
            if ($filterId !== null && $filterId > 0) {
                $extra = " AND b.resource_id = ?";
                $params[] = $filterId;
            }
            $sql = "SELECT b.id, DATE(b.start_time) AS evt_date, TIME(b.start_time) AS start_clk, TIME(b.end_time) AS end_clk,
                           COALESCE(b.purpose, r.resource_name) AS title, b.booking_status AS status,
                           r.resource_name AS owner_name, r.resource_code AS room,
                           p.fname, p.lname, p.pubpid
                    FROM `hms_resource_booking` b
                    INNER JOIN `hms_resource` r ON r.id = b.resource_id
                    LEFT JOIN `patient_data` p ON p.pid = b.pid
                    WHERE b.start_time BETWEEN ? AND ? AND b.booking_status != 'CANCELLED' $extra
                    ORDER BY b.start_time ASC";
            $rows = sqlStatement($sql, $params);
            while ($r = sqlFetchArray($rows)) {
                $d = $r['evt_date'];
                if (isset($byDay[$d])) {
                    $byDay[$d][] = $r;
                }
            }
        }

        return [
            'mode' => $mode,
            'view_type' => $viewType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
            'events_by_day' => $byDay,
        ];
    }

    /**
     * Today's Live Flow Board (Arrived, Waiting, In Room, Done)
     */
    public static function getTodayFlowBoard(): array
    {
        $today = date('Y-m-d');
        $counts = ['waiting' => 0, 'arrived' => 0, 'in_room' => 0, 'done' => 0];
        $items = [];

        // Outpatient provider appointments today
        $appts = self::getProviderAppointments($today);
        foreach ($appts as $a) {
            $st = $a['pc_apptstatus'] ?? '-';
            if ($st === '?') {
                continue;
            }
            $bucket = 'waiting';
            if (in_array($st, ['@', '~'], true)) {
                $bucket = 'arrived';
            } elseif (in_array($st, ['<', '+'], true)) {
                $bucket = 'in_room';
            } elseif ($st === '>') {
                $bucket = 'done';
            }
            $counts[$bucket]++;
            $items[] = [
                'source' => 'CLINIC',
                'id' => (int)$a['pc_eid'],
                'time' => substr($a['pc_startTime'], 0, 5),
                'patient' => trim(($a['fname'] ?? '') . ' ' . ($a['lname'] ?? '')),
                'pubpid' => $a['pubpid'] ?: 'PID-' . $a['pc_pid'],
                'location_or_provider' => trim($a['provider_name'] ?? 'Provider') . ($a['pc_room'] ? ' (' . $a['pc_room'] . ')' : ''),
                'title' => $a['pc_title'],
                'bucket' => $bucket,
                'raw_status' => $a['status_label'] ?: $st,
            ];
        }

        // Physical resource bookings today
        $resBookings = self::getBookings(['date' => $today]);
        foreach ($resBookings as $b) {
            if (in_array($b['booking_status'], ['CANCELLED', 'NO_SHOW'], true)) {
                continue;
            }
            $bucket = 'waiting';
            if ($b['booking_status'] === 'CONFIRMED') {
                $bucket = 'arrived';
            } elseif ($b['booking_status'] === 'IN_PROGRESS') {
                $bucket = 'in_room';
            } elseif ($b['booking_status'] === 'COMPLETED') {
                $bucket = 'done';
            }
            $counts[$bucket]++;
            $items[] = [
                'source' => 'RESOURCE',
                'id' => (int)$b['id'],
                'time' => substr($b['start_time'], 11, 5),
                'patient' => trim(($b['fname'] ?? '') . ' ' . ($b['lname'] ?? '')),
                'pubpid' => $b['pubpid'] ?: 'PID-' . $b['pid'],
                'location_or_provider' => $b['resource_name'],
                'title' => $b['purpose'] ?: $b['resource_type'],
                'bucket' => $bucket,
                'raw_status' => $b['booking_status'],
            ];
        }

        usort($items, fn($x, $y) => strcmp($x['time'], $y['time']));

        return ['date' => $today, 'counts' => $counts, 'items' => $items];
    }

    /**
     * Dedicated No-Show List for follow-up (missed appointments + resource no-shows)
     */
    public static function getNoShowList(): array
    {
        $list = [];

        $resSql = "SELECT b.id AS ref_id, 'RESOURCE' AS source, b.pid, b.start_time AS scheduled_at,
                          r.resource_name AS detail, b.purpose AS reason,
                          p.fname, p.lname, p.pubpid, p.phone_cell, p.email
                   FROM `hms_resource_booking` b
                   INNER JOIN `hms_resource` r ON r.id = b.resource_id
                   LEFT JOIN `patient_data` p ON p.pid = b.pid
                   WHERE b.booking_status = 'NO_SHOW'
                   ORDER BY b.start_time DESC
                   LIMIT 50";
        $rows = sqlStatement($resSql);
        while ($r = sqlFetchArray($rows)) {
            $list[] = $r;
        }

        $apptSql = "SELECT e.pc_eid AS ref_id, 'APPOINTMENT' AS source, e.pc_pid AS pid,
                           CONCAT(e.pc_eventDate, ' ', e.pc_startTime) AS scheduled_at,
                           CONCAT(u.fname, ' ', u.lname) AS detail, e.pc_title AS reason,
                           p.fname, p.lname, p.pubpid, p.phone_cell, p.email
                    FROM `openemr_postcalendar_events` e
                    LEFT JOIN `patient_data` p ON p.pid = e.pc_pid
                    LEFT JOIN `users` u ON u.id = e.pc_aid
                    WHERE e.pc_apptstatus = '?'
                      AND e.pc_pid IS NOT NULL AND e.pc_pid != '' AND e.pc_pid != '0'
                    ORDER BY e.pc_eventDate DESC, e.pc_startTime DESC
                    LIMIT 50";
        $rows2 = sqlStatement($apptSql);
        while ($r = sqlFetchArray($rows2)) {
            $list[] = $r;
        }

        usort($list, fn($a, $b) => strcmp($b['scheduled_at'], $a['scheduled_at']));
        return $list;
    }

    /**
     * List recent sent reminders from notification_log
     */
    public static function getReminders(int $limit = 30): array
    {
        $sql = "SELECT n.*, p.fname, p.lname, p.pubpid
                FROM `notification_log` n
                LEFT JOIN `patient_data` p ON p.pid = n.pid
                ORDER BY n.dSentDateTime DESC
                LIMIT " . (int)$limit;
        $rows = sqlStatement($sql);
        $list = [];
        while ($row = sqlFetchArray($rows)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Fetch patients for scheduling dropdowns
     */
    public static function getPatientsList(): array
    {
        $rows = sqlStatement("SELECT `pid`, `pubpid`, `fname`, `lname`, `phone_cell` FROM `patient_data` ORDER BY `pid` DESC LIMIT 200");
        $list = [];
        while ($row = sqlFetchArray($rows)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Fetch active clinicians/providers for dropdowns
     */
    public static function getProvidersList(): array
    {
        $rows = sqlStatement("SELECT `id`, `fname`, `lname`, `specialty` FROM `users` WHERE `active` = 1 AND `authorized` = 1 ORDER BY `lname` ASC, `fname` ASC");
        $list = [];
        while ($row = sqlFetchArray($rows)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Compute SCH Reports: Resource Utilization, No-Show Rate, and Recall Due List
     */
    public static function getSchedulingReports(): array
    {
        // 1. Resource utilization summary
        $utilRows = sqlStatement(
            "SELECT r.id, r.resource_code, r.resource_name, r.resource_type, r.is_available,
                    COUNT(b.id) AS total_bookings,
                    SUM(CASE WHEN b.booking_status IN ('CONFIRMED','IN_PROGRESS','COMPLETED') THEN 1 ELSE 0 END) AS active_or_done,
                    SUM(CASE WHEN b.booking_status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed_count,
                    SUM(CASE WHEN b.booking_status = 'NO_SHOW' THEN 1 ELSE 0 END) AS noshow_count,
                    SUM(CASE WHEN b.booking_status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled_count,
                    COALESCE(SUM(CASE WHEN b.booking_status IN ('CONFIRMED','IN_PROGRESS','COMPLETED')
                        THEN TIMESTAMPDIFF(MINUTE, b.start_time, b.end_time) ELSE 0 END), 0) AS booked_minutes
             FROM `hms_resource` r
             LEFT JOIN `hms_resource_booking` b ON b.resource_id = r.id
             GROUP BY r.id
             ORDER BY total_bookings DESC, r.resource_name ASC"
        );
        $utilization = [];
        while ($row = sqlFetchArray($utilRows)) {
            $utilization[] = $row;
        }

        // 2. Combined No-Show Rate (Resource bookings + OpenEMR appointments)
        $resStats = sqlQuery(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN `booking_status` = 'NO_SHOW' THEN 1 ELSE 0 END) AS noshow
             FROM `hms_resource_booking` WHERE `booking_status` != 'CANCELLED'"
        );
        $apptStats = sqlQuery(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN `pc_apptstatus` = '?' THEN 1 ELSE 0 END) AS noshow
             FROM `openemr_postcalendar_events`
             WHERE `pc_pid` IS NOT NULL AND `pc_pid` != '' AND `pc_pid` != '0'"
        );

        $totalEvents = (int)($resStats['total'] ?? 0) + (int)($apptStats['total'] ?? 0);
        $totalNoShows = (int)($resStats['noshow'] ?? 0) + (int)($apptStats['noshow'] ?? 0);
        $noShowRate = $totalEvents > 0 ? round(($totalNoShows / $totalEvents) * 100, 1) : 0.0;

        // 3. Active series & reminders count
        $seriesSummary = sqlQuery(
            "SELECT COUNT(*) AS total_series,
                    SUM(CASE WHEN `status` = 'ACTIVE' THEN 1 ELSE 0 END) AS active_series,
                    COALESCE(SUM(`completed_sessions`), 0) AS sessions_completed,
                    COALESCE(SUM(`total_sessions`), 0) AS sessions_planned
             FROM `hms_session_series`"
        );

        $recallsDue = sqlQuery("SELECT COUNT(*) AS cnt FROM `medex_recalls` WHERE `r_eventDate` <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)");

        return [
            'utilization' => $utilization,
            'total_events' => $totalEvents,
            'total_noshows' => $totalNoShows,
            'noshow_rate' => $noShowRate,
            'series_summary' => $seriesSummary ?: ['total_series' => 0, 'active_series' => 0, 'sessions_completed' => 0, 'sessions_planned' => 0],
            'recalls_due_14d' => (int)($recallsDue['cnt'] ?? 0),
        ];
    }
}
