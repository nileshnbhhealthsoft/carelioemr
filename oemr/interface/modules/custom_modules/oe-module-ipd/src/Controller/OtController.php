<?php

namespace OpenEMR\Modules\Ipd\Controller;

use Exception;
use OpenEMR\Modules\Ipd\Database;
use OpenEMR\Modules\Ipd\BootstrapService;

/**
 * Operation Theatre (OT) and Surgical Management Controller
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class OtController
{
    /**
     * List Operation Theatres
     */
    public function listTheatres(): array
    {
        return Database::fetchAll("SELECT * FROM ipd_operation_theatres ORDER BY id ASC");
    }

    /**
     * List Surgeries with filters
     */
    public function listSurgeries(?string $status = null, ?int $otId = null, ?string $date = null): array
    {
        $sql = "SELECT s.*, 
                       ot.theatre_name, ot.theatre_code,
                       a.ipd_number, a.pid,
                       p.fname AS pat_fname, p.lname AS pat_lname, p.DOB AS pat_dob, p.sex AS pat_sex,
                       surg.fname AS surgeon_fname, surg.lname AS surgeon_lname, surg.specialty AS surgeon_specialty,
                       anesth.fname AS anesth_fname, anesth.lname AS anesth_lname,
                       b.bed_number, w.ward_name
                FROM ipd_surgeries s
                JOIN ipd_operation_theatres ot ON s.ot_id = ot.id
                JOIN ipd_admissions a ON s.admission_id = a.id
                JOIN patient_data p ON a.pid = p.pid
                LEFT JOIN users surg ON s.lead_surgeon_id = surg.id
                LEFT JOIN users anesth ON s.anesthetist_id = anesth.id
                LEFT JOIN ipd_beds b ON a.bed_id = b.id
                LEFT JOIN ipd_wards w ON a.ward_id = w.id
                WHERE 1=1";

        $params = [];

        if ($status) {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }

        if ($otId) {
            $sql .= " AND s.ot_id = ?";
            $params[] = $otId;
        }

        if ($date) {
            $sql .= " AND DATE(s.scheduled_start) = ?";
            $params[] = $date;
        }

        $sql .= " ORDER BY s.scheduled_start DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Schedule a Surgery
     */
    public function scheduleSurgery(array $data): array
    {
        $admissionId = (int)($data['admission_id'] ?? 0);
        $surgeryName = trim($data['surgery_name'] ?? '');
        $otId = (int)($data['ot_id'] ?? 0);
        $leadSurgeon = (int)($data['lead_surgeon_id'] ?? 0);
        $assistantSurgeon = !empty($data['assistant_surgeon_id']) ? (int)$data['assistant_surgeon_id'] : null;
        $anesthetist = !empty($data['anesthetist_id']) ? (int)$data['anesthetist_id'] : null;
        $anesthesiaType = trim($data['anesthesia_type'] ?? 'General');
        $start = trim($data['scheduled_start'] ?? '');
        $end = trim($data['scheduled_end'] ?? '');

        if ($admissionId <= 0 || empty($surgeryName) || $otId <= 0 || $leadSurgeon <= 0 || empty($start) || empty($end)) {
            throw new Exception("Please fill all required surgery details (Admission, Procedure, OT, Surgeon, Schedule Times).");
        }

        $surgeryId = Database::insert(
            "INSERT INTO `ipd_surgeries` (
                `admission_id`, `surgery_name`, `ot_id`, `lead_surgeon_id`, `assistant_surgeon_id`,
                `anesthetist_id`, `anesthesia_type`, `scheduled_start`, `scheduled_end`, `status`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())",
            [
                $admissionId, $surgeryName, $otId, $leadSurgeon, $assistantSurgeon,
                $anesthetist, $anesthesiaType, $start, $end
            ]
        );

        BootstrapService::logAudit(
            $admissionId,
            'SURGERY_SCHEDULED',
            'ipd_surgeries',
            $surgeryId,
            "Scheduled surgery '{$surgeryName}' in OT #{$otId}"
        );

        return [
            'success' => true,
            'message' => "Surgery successfully scheduled.",
            'surgery_id' => $surgeryId
        ];
    }

    /**
     * Update Surgery Status and Documentation
     */
    public function updateSurgeryStatus(int $surgeryId, string $status, ?array $extra = null): array
    {
        $validStatuses = ['scheduled', 'pre_op', 'in_progress', 'recovery', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses, true)) {
            throw new Exception("Invalid surgery status: {$status}");
        }

        $surgery = Database::fetchRow("SELECT * FROM ipd_surgeries WHERE id = ?", [$surgeryId]);
        if (!$surgery) {
            throw new Exception("Surgery record not found.");
        }

        $now = date('Y-m-d H:i:s');
        $actualStart = $surgery['actual_start'];
        $actualEnd = $surgery['actual_end'];

        if ($status === 'in_progress' && empty($actualStart)) {
            $actualStart = $now;
        }
        if ($status === 'completed' && empty($actualEnd)) {
            $actualEnd = $now;
        }

        $opNotes = !empty($extra['operation_notes']) ? $extra['operation_notes'] : $surgery['operation_notes'];
        $postOp = !empty($extra['post_op_instructions']) ? $extra['post_op_instructions'] : $surgery['post_op_instructions'];

        Database::execute(
            "UPDATE `ipd_surgeries` SET 
                `status` = ?, `actual_start` = ?, `actual_end` = ?, 
                `operation_notes` = ?, `post_op_instructions` = ? 
             WHERE `id` = ?",
            [$status, $actualStart, $actualEnd, $opNotes, $postOp, $surgeryId]
        );

        // Update OT room status
        $otStatus = ($status === 'in_progress' || $status === 'pre_op') ? 'occupied' : 'available';
        Database::execute("UPDATE `ipd_operation_theatres` SET `status` = ? WHERE `id` = ?", [$otStatus, $surgery['ot_id']]);

        BootstrapService::logAudit(
            $surgery['admission_id'],
            'SURGERY_STATUS_UPDATED',
            'ipd_surgeries',
            $surgeryId,
            "Surgery '{$surgery['surgery_name']}' updated to status {$status}"
        );

        return [
            'success' => true,
            'message' => "Surgery status updated to {$status}."
        ];
    }

    /**
     * Save WHO Surgical Safety Checklist
     */
    public function saveSafetyChecklist(int $surgeryId, array $checklist): array
    {
        $surgery = Database::fetchRow("SELECT * FROM ipd_surgeries WHERE id = ?", [$surgeryId]);
        if (!$surgery) {
            throw new Exception("Surgery record not found.");
        }

        $json = json_encode($checklist);
        Database::execute(
            "UPDATE `ipd_surgeries` SET `surgical_safety_checklist` = ? WHERE `id` = ?",
            [$json, $surgeryId]
        );

        return [
            'success' => true,
            'message' => "WHO Surgical Safety Checklist saved."
        ];
    }
}
