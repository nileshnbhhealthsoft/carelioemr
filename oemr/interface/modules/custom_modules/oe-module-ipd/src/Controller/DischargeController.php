<?php

namespace OpenEMR\Modules\Ipd\Controller;

use Exception;
use OpenEMR\Modules\Ipd\Database;
use OpenEMR\Modules\Ipd\BootstrapService;

/**
 * Discharge Planning, Multi-disciplinary Clearances, and Summary Generation Controller
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class DischargeController
{
    /**
     * Get Discharge Clearances & Summary Data
     */
    public function getDischargeStatus(int $admissionId): array
    {
        $admission = Database::fetchRow(
            "SELECT a.*, p.fname, p.lname, p.DOB, p.sex, p.phone_cell,
                    w.ward_name, b.bed_number, u.fname AS doc_fname, u.lname AS doc_lname
             FROM ipd_admissions a
             JOIN patient_data p ON a.pid = p.pid
             JOIN ipd_wards w ON a.ward_id = w.id
             JOIN ipd_beds b ON a.bed_id = b.id
             JOIN users u ON a.attending_doctor_id = u.id
             WHERE a.id = ?",
            [$admissionId]
        );

        if (!$admission) {
            throw new Exception("Inpatient admission not found.");
        }

        $discharge = Database::fetchRow(
            "SELECT * FROM ipd_discharges WHERE admission_id = ?",
            [$admissionId]
        );

        // Check billing balance
        $outstandingBill = (float)Database::fetchColumn(
            "SELECT COALESCE(SUM(balance_due), 0) FROM ipd_bills WHERE admission_id = ?",
            [$admissionId]
        );

        return [
            'admission' => $admission,
            'discharge' => $discharge,
            'outstanding_bill' => $outstandingBill,
            'can_discharge' => ($discharge && $discharge['doctor_clearance'] && $discharge['nursing_clearance'] && $discharge['pharmacy_clearance'] && $discharge['billing_clearance'])
        ];
    }

    /**
     * Update Departmental Discharge Clearance (Doctor, Nursing, Pharmacy, Billing)
     */
    public function updateClearance(int $admissionId, string $department, int $cleared): array
    {
        $validDepts = ['doctor', 'nursing', 'pharmacy', 'billing'];
        if (!in_array($department, $validDepts, true)) {
            throw new Exception("Invalid clearance department: {$department}");
        }

        $col = "{$department}_clearance";

        // Ensure discharge record exists
        $discharge = Database::fetchRow("SELECT id FROM ipd_discharges WHERE admission_id = ?", [$admissionId]);
        if (!$discharge) {
            Database::insert(
                "INSERT INTO `ipd_discharges` (`admission_id`, `discharge_date`, `{$col}`, `created_at`)
                 VALUES (?, NOW(), ?, NOW())",
                [$admissionId, $cleared]
            );
        } else {
            Database::execute(
                "UPDATE `ipd_discharges` SET `{$col}` = ? WHERE `admission_id` = ?",
                [$cleared, $admissionId]
            );
        }

        BootstrapService::logAudit(
            $admissionId,
            'DISCHARGE_CLEARANCE_UPDATED',
            'ipd_discharges',
            $admissionId,
            ucfirst($department) . " discharge clearance marked as " . ($cleared ? "CLEARED" : "PENDING")
        );

        return [
            'success' => true,
            'message' => ucfirst($department) . " clearance updated successfully."
        ];
    }

    /**
     * Complete Discharge Workflow and Auto-Release Bed
     */
    public function completeDischarge(array $data): array
    {
        $admissionId = (int)($data['admission_id'] ?? 0);
        $dischargeDate = !empty($data['discharge_date']) ? $data['discharge_date'] : date('Y-m-d H:i:s');
        $dischargeType = trim($data['discharge_type'] ?? 'Normal');
        $finalDiagnosis = trim($data['final_diagnosis'] ?? '');
        $icd10 = trim($data['icd10_code'] ?? '');
        $course = trim($data['hospital_course'] ?? '');
        $condition = trim($data['condition_at_discharge'] ?? 'Stable');
        $medications = trim($data['discharge_medications'] ?? '');
        $instructions = trim($data['followup_instructions'] ?? '');
        $followupDate = !empty($data['followup_date']) ? $data['followup_date'] : null;
        $authUserId = BootstrapService::getAuthUserId();

        if ($admissionId <= 0) {
            throw new Exception("Invalid Inpatient Admission ID.");
        }
        if (empty($finalDiagnosis)) {
            throw new Exception("Final diagnosis is required to complete discharge.");
        }

        $admission = Database::fetchRow("SELECT * FROM ipd_admissions WHERE id = ?", [$admissionId]);
        if (!$admission) {
            throw new Exception("Admission record not found.");
        }
        if ($admission['admission_status'] === 'discharged') {
            throw new Exception("Patient has already been discharged.");
        }

        Database::beginTransaction();
        try {
            // 1. Upsert Discharge Summary Record
            $discharge = Database::fetchRow("SELECT id FROM ipd_discharges WHERE admission_id = ?", [$admissionId]);
            if ($discharge) {
                Database::execute(
                    "UPDATE `ipd_discharges` SET
                        `discharge_date` = ?, `discharge_type` = ?, `doctor_clearance` = 1,
                        `nursing_clearance` = 1, `pharmacy_clearance` = 1, `billing_clearance` = 1,
                        `final_diagnosis` = ?, `icd10_code` = ?, `hospital_course` = ?,
                        `condition_at_discharge` = ?, `discharge_medications` = ?,
                        `followup_instructions` = ?, `followup_date` = ?, `bed_released` = 1,
                        `discharged_by` = ?
                     WHERE `admission_id` = ?",
                    [
                        $dischargeDate, $dischargeType, $finalDiagnosis, $icd10,
                        $course, $condition, $medications, $instructions,
                        $followupDate, $authUserId, $admissionId
                    ]
                );
            } else {
                Database::insert(
                    "INSERT INTO `ipd_discharges` (
                        `admission_id`, `discharge_date`, `discharge_type`, `doctor_clearance`,
                        `nursing_clearance`, `pharmacy_clearance`, `billing_clearance`,
                        `final_diagnosis`, `icd10_code`, `hospital_course`,
                        `condition_at_discharge`, `discharge_medications`,
                        `followup_instructions`, `followup_date`, `bed_released`, `discharged_by`, `created_at`
                    ) VALUES (?, ?, ?, 1, 1, 1, 1, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())",
                    [
                        $admissionId, $dischargeDate, $dischargeType, $finalDiagnosis,
                        $icd10, $course, $condition, $medications,
                        $instructions, $followupDate, $authUserId
                    ]
                );
            }

            // 2. Update admission status
            $status = match (strtolower($dischargeType)) {
                'dama', 'lama' => 'dama',
                'deceased', 'expired' => 'deceased',
                default => 'discharged',
            };

            Database::execute(
                "UPDATE `ipd_admissions` SET `admission_status` = ?, `discharged_at` = ? WHERE `id` = ?",
                [$status, $dischargeDate, $admissionId]
            );

            // 3. Auto-release bed: transition bed from 'occupied' to 'cleaning'
            $bedId = (int)$admission['bed_id'];
            Database::execute(
                "UPDATE `ipd_beds` SET `status` = 'cleaning', `current_admission_id` = NULL WHERE `id` = ?",
                [$bedId]
            );

            // 4. Close bed allocation record
            Database::execute(
                "UPDATE `ipd_bed_allocations` SET `allocated_to` = ? WHERE `admission_id` = ? AND `allocated_to` IS NULL",
                [$dischargeDate, $admissionId]
            );

            // 5. Audit Log
            BootstrapService::logAudit(
                $admissionId,
                'PATIENT_DISCHARGED',
                'ipd_admissions',
                $admissionId,
                "Patient successfully discharged ({$dischargeType}). Bed released for cleaning.",
                ['type' => $dischargeType, 'bed_id' => $bedId, 'final_diagnosis' => $finalDiagnosis]
            );

            Database::commit();

            return [
                'success' => true,
                'message' => "Patient successfully discharged. Bed has been automatically released for sanitation."
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Generate Comprehensive Printable Discharge Summary
     */
    public function getDischargeSummary(int $admissionId): array
    {
        $admission = Database::fetchRow(
            "SELECT a.*, 
                    p.fname, p.lname, p.mname, p.DOB, p.sex, p.phone_cell, p.street, p.city, p.state, p.postal_code,
                    w.ward_name, b.bed_number,
                    u.fname AS doc_fname, u.lname AS doc_lname, u.specialty AS doc_specialty,
                    DATEDIFF(COALESCE(a.discharged_at, NOW()), a.admission_date) AS length_of_stay_days
             FROM ipd_admissions a
             JOIN patient_data p ON a.pid = p.pid
             JOIN ipd_wards w ON a.ward_id = w.id
             JOIN ipd_beds b ON a.bed_id = b.id
             JOIN users u ON a.attending_doctor_id = u.id
             WHERE a.id = ?",
            [$admissionId]
        );

        if (!$admission) {
            throw new Exception("Admission not found.");
        }

        $discharge = Database::fetchRow(
            "SELECT d.*, u.fname AS discharged_by_fname, u.lname AS discharged_by_lname
             FROM ipd_discharges d
             LEFT JOIN users u ON d.discharged_by = u.id
             WHERE d.admission_id = ?",
            [$admissionId]
        );

        $rounds = Database::fetchAll(
            "SELECT * FROM ipd_doctor_rounds WHERE admission_id = ? ORDER BY round_date ASC",
            [$admissionId]
        );

        $surgeries = Database::fetchAll(
            "SELECT s.*, ot.theatre_name, u.fname AS surgeon_fname, u.lname AS surgeon_lname
             FROM ipd_surgeries s
             LEFT JOIN ipd_operation_theatres ot ON s.ot_id = ot.id
             LEFT JOIN users u ON s.lead_surgeon_id = u.id
             WHERE s.admission_id = ?
             ORDER BY s.scheduled_start ASC",
            [$admissionId]
        );

        $medications = Database::fetchAll(
            "SELECT * FROM ipd_medication_orders WHERE admission_id = ? ORDER BY id ASC",
            [$admissionId]
        );

        $latestVitals = Database::fetchRow(
            "SELECT * FROM ipd_vitals WHERE admission_id = ? ORDER BY recorded_at DESC LIMIT 1",
            [$admissionId]
        );

        return [
            'admission' => $admission,
            'discharge' => $discharge,
            'rounds' => $rounds,
            'surgeries' => $surgeries,
            'medications' => $medications,
            'latest_vitals' => $latestVitals
        ];
    }
}
