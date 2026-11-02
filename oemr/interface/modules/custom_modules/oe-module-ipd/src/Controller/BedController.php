<?php

namespace OpenEMR\Modules\Ipd\Controller;

use Exception;
use OpenEMR\Modules\Ipd\Database;
use OpenEMR\Modules\Ipd\BootstrapService;

/**
 * Bed, Ward and Infrastructure Management Controller
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class BedController
{
    /**
     * Get Real-time Bed Matrix and Occupancy Statistics
     */
    public function getBedMatrix(?int $wardId = null): array
    {
        $wardsSql = "SELECT w.*, f.floor_name, bld.building_name 
                     FROM ipd_wards w
                     LEFT JOIN ipd_floors f ON w.floor_id = f.id
                     LEFT JOIN ipd_buildings bld ON f.building_id = bld.id
                     WHERE w.is_active = 1";
        $wardParams = [];
        if ($wardId) {
            $wardsSql .= " AND w.id = ?";
            $wardParams[] = $wardId;
        }
        $wardsSql .= " ORDER BY w.id ASC";
        $wards = Database::fetchAll($wardsSql, $wardParams);

        $bedsSql = "SELECT b.*, 
                           w.ward_name, w.ward_code, w.ward_type,
                           r.room_number, r.room_type,
                           a.ipd_number, a.admission_date, a.pid,
                           p.fname, p.lname, p.sex, p.DOB,
                           u.fname AS doc_fname, u.lname AS doc_lname,
                           DATEDIFF(NOW(), a.admission_date) AS days_admitted
                    FROM ipd_beds b
                    JOIN ipd_wards w ON b.ward_id = w.id
                    LEFT JOIN ipd_rooms r ON b.room_id = r.id
                    LEFT JOIN ipd_admissions a ON b.current_admission_id = a.id AND a.admission_status IN ('admitted', 'transferred', 'discharge_requested')
                    LEFT JOIN patient_data p ON a.pid = p.pid
                    LEFT JOIN users u ON a.attending_doctor_id = u.id";
        $bedParams = [];
        if ($wardId) {
            $bedsSql .= " WHERE b.ward_id = ?";
            $bedParams[] = $wardId;
        }
        $bedsSql .= " ORDER BY b.ward_id ASC, b.bed_number ASC";
        $beds = Database::fetchAll($bedsSql, $bedParams);

        // Group beds by ward
        $wardMap = [];
        foreach ($wards as $ward) {
            $wId = (int)$ward['id'];
            $wardMap[$wId] = $ward;
            $wardMap[$wId]['beds'] = [];
            $wardMap[$wId]['stats'] = [
                'total' => 0,
                'available' => 0,
                'occupied' => 0,
                'cleaning' => 0,
                'maintenance' => 0,
                'reserved' => 0,
                'blocked' => 0
            ];
        }

        $overallStats = [
            'total' => count($beds),
            'available' => 0,
            'occupied' => 0,
            'cleaning' => 0,
            'maintenance' => 0,
            'reserved' => 0,
            'blocked' => 0
        ];

        foreach ($beds as $bed) {
            $wId = (int)$bed['ward_id'];
            $status = strtolower($bed['status']);

            if (isset($wardMap[$wId])) {
                $wardMap[$wId]['beds'][] = $bed;
                $wardMap[$wId]['stats']['total']++;
                if (isset($wardMap[$wId]['stats'][$status])) {
                    $wardMap[$wId]['stats'][$status]++;
                }
            }

            if (isset($overallStats[$status])) {
                $overallStats[$status]++;
            }
        }

        $overallStats['occupancy_rate'] = $overallStats['total'] > 0
            ? round(($overallStats['occupied'] / $overallStats['total']) * 100, 1)
            : 0;

        return [
            'wards' => array_values($wardMap),
            'all_beds' => $beds,
            'stats' => $overallStats
        ];
    }

    /**
     * Get available beds for allocation dropdown
     */
    public function getAvailableBeds(?int $wardId = null): array
    {
        $sql = "SELECT b.id, b.bed_number, b.bed_category, b.daily_charge, b.ward_id, w.ward_name, r.room_number 
                FROM ipd_beds b
                JOIN ipd_wards w ON b.ward_id = w.id
                LEFT JOIN ipd_rooms r ON b.room_id = r.id
                WHERE b.status = 'available' AND w.is_active = 1";
        $params = [];
        if ($wardId) {
            $sql .= " AND b.ward_id = ?";
            $params[] = $wardId;
        }
        $sql .= " ORDER BY w.ward_name ASC, b.bed_number ASC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Update Bed Status (e.g. mark 'available' after cleaning)
     */
    public function updateBedStatus(int $bedId, string $status, ?string $notes = null): array
    {
        $validStatuses = ['available', 'reserved', 'occupied', 'cleaning', 'maintenance', 'blocked'];
        if (!in_array($status, $validStatuses, true)) {
            throw new Exception("Invalid bed status: {$status}");
        }

        $bed = Database::fetchRow("SELECT * FROM ipd_beds WHERE id = ?", [$bedId]);
        if (!$bed) {
            throw new Exception("Bed not found.");
        }

        // If transitioning from occupied, ensure current admission is cleared or warn
        $admissionId = $bed['current_admission_id'];
        if ($status === 'available' || $status === 'cleaning' || $status === 'maintenance') {
            $admissionId = null;
        }

        Database::execute(
            "UPDATE `ipd_beds` SET `status` = ?, `current_admission_id` = ?, `notes` = COALESCE(?, `notes`), `updated_at` = NOW() WHERE `id` = ?",
            [$status, $admissionId, $notes, $bedId]
        );

        BootstrapService::logAudit(
            $bed['current_admission_id'],
            'BED_STATUS_CHANGED',
            'ipd_beds',
            $bedId,
            "Bed {$bed['bed_number']} status changed from {$bed['status']} to {$status}" . ($notes ? " ({$notes})" : "")
        );

        return [
            'success' => true,
            'message' => "Bed {$bed['bed_number']} status updated to " . ucfirst($status) . "."
        ];
    }

    /**
     * Create or update a ward
     */
    public function saveWard(array $data): array
    {
        $id = (int)($data['id'] ?? 0);
        $name = trim($data['ward_name'] ?? '');
        $code = trim($data['ward_code'] ?? '');
        $type = trim($data['ward_type'] ?? 'General');
        $dept = trim($data['department'] ?? 'General Medicine');
        $gender = trim($data['gender_preference'] ?? 'Any');
        $floorId = !empty($data['floor_id']) ? (int)$data['floor_id'] : 1;

        if (empty($name) || empty($code)) {
            throw new Exception("Ward name and code are required.");
        }

        if ($id > 0) {
            Database::execute(
                "UPDATE `ipd_wards` SET `ward_name` = ?, `ward_code` = ?, `ward_type` = ?, `department` = ?, `gender_preference` = ?, `floor_id` = ? WHERE `id` = ?",
                [$name, $code, $type, $dept, $gender, $floorId, $id]
            );
            return ['success' => true, 'message' => "Ward updated successfully."];
        } else {
            Database::insert(
                "INSERT INTO `ipd_wards` (`floor_id`, `ward_name`, `ward_code`, `ward_type`, `department`, `gender_preference`, `is_active`, `created_at`)
                 VALUES (?, ?, ?, ?, ?, ?, 1, NOW())",
                [$floorId, $name, $code, $type, $dept, $gender]
            );
            return ['success' => true, 'message' => "New ward created successfully."];
        }
    }

    /**
     * Create or update a bed
     */
    public function saveBed(array $data): array
    {
        $id = (int)($data['id'] ?? 0);
        $wardId = (int)($data['ward_id'] ?? 0);
        $roomId = !empty($data['room_id']) ? (int)$data['room_id'] : null;
        $bedNumber = trim($data['bed_number'] ?? '');
        $category = trim($data['bed_category'] ?? 'Standard');
        $dailyCharge = (float)($data['daily_charge'] ?? 0.00);
        $isIsolation = !empty($data['is_isolation']) ? 1 : 0;
        $notes = trim($data['notes'] ?? '');

        if ($wardId <= 0 || empty($bedNumber)) {
            throw new Exception("Ward selection and Bed number are required.");
        }

        if ($id > 0) {
            Database::execute(
                "UPDATE `ipd_beds` SET `ward_id` = ?, `room_id` = ?, `bed_number` = ?, `bed_category` = ?, `daily_charge` = ?, `is_isolation` = ?, `notes` = ? WHERE `id` = ?",
                [$wardId, $roomId, $bedNumber, $category, $dailyCharge, $isIsolation, $notes, $id]
            );
            return ['success' => true, 'message' => "Bed updated successfully."];
        } else {
            Database::insert(
                "INSERT INTO `ipd_beds` (`ward_id`, `room_id`, `bed_number`, `bed_category`, `daily_charge`, `status`, `is_isolation`, `notes`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, 'available', ?, ?, NOW())",
                [$wardId, $roomId, $bedNumber, $category, $dailyCharge, $isIsolation, $notes]
            );
            return ['success' => true, 'message' => "New bed created successfully."];
        }
    }
}
