<?php

namespace OpenEMR\Modules\Ipd\Controller;

use Exception;
use OpenEMR\Modules\Ipd\Database;
use OpenEMR\Modules\Ipd\BootstrapService;

/**
 * Inpatient (IPD) Admission and Patient Movement Controller
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class AdmissionController
{
    /**
     * List Inpatient Admissions with filters
     */
    public function listAdmissions(?string $status = null, ?int $wardId = null, ?string $search = null): array
    {
        $sql = "SELECT a.*, 
                       p.fname, p.lname, p.mname, p.DOB, p.sex, p.phone_cell,
                       w.ward_name, w.ward_code, w.ward_type,
                       b.bed_number, b.bed_category, b.daily_charge,
                       u.fname AS doc_fname, u.lname AS doc_lname, u.specialty AS doc_specialty,
                       DATEDIFF(COALESCE(a.discharged_at, NOW()), a.admission_date) AS length_of_stay_days
                FROM ipd_admissions a
                LEFT JOIN patient_data p ON a.pid = p.pid
                LEFT JOIN ipd_wards w ON a.ward_id = w.id
                LEFT JOIN ipd_beds b ON a.bed_id = b.id
                LEFT JOIN users u ON a.attending_doctor_id = u.id
                WHERE 1=1";

        $params = [];

        if (!empty($status)) {
            if ($status === 'active') {
                $sql .= " AND a.admission_status IN ('admitted', 'transferred', 'discharge_requested')";
            } else {
                $sql .= " AND a.admission_status = ?";
                $params[] = $status;
            }
        }

        if (!empty($wardId)) {
            $sql .= " AND a.ward_id = ?";
            $params[] = $wardId;
        }

        if (!empty($search)) {
            $sql .= " AND (a.ipd_number LIKE ? OR p.fname LIKE ? OR p.lname LIKE ? OR b.bed_number LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY a.id DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get Complete Inpatient Dossier
     */
    public function getAdmission(int $id): ?array
    {
        $admission = Database::fetchRow(
            "SELECT a.*, 
                    p.fname, p.lname, p.mname, p.DOB, p.sex, p.phone_cell, p.street, p.city, p.state, p.postal_code,
                    w.ward_name, w.ward_code, w.ward_type, w.department AS ward_department,
                    b.bed_number, b.bed_category, b.daily_charge,
                    u.fname AS doc_fname, u.lname AS doc_lname, u.specialty AS doc_specialty,
                    adm_doc.fname AS admitting_doc_fname, adm_doc.lname AS admitting_doc_lname,
                    DATEDIFF(COALESCE(a.discharged_at, NOW()), a.admission_date) AS length_of_stay_days
             FROM ipd_admissions a
             LEFT JOIN patient_data p ON a.pid = p.pid
             LEFT JOIN ipd_wards w ON a.ward_id = w.id
             LEFT JOIN ipd_beds b ON a.bed_id = b.id
             LEFT JOIN users u ON a.attending_doctor_id = u.id
             LEFT JOIN users adm_doc ON a.admitting_doctor_id = adm_doc.id
             WHERE a.id = ?",
            [$id]
        );

        if (!$admission) {
            return null;
        }

        // Fetch related clinical & operational data
        $admission['bed_history'] = Database::fetchAll(
            "SELECT ba.*, b.bed_number, w.ward_name 
             FROM ipd_bed_allocations ba
             LEFT JOIN ipd_beds b ON ba.bed_id = b.id
             LEFT JOIN ipd_wards w ON ba.ward_id = w.id
             WHERE ba.admission_id = ?
             ORDER BY ba.id DESC",
            [$id]
        );

        $admission['vitals'] = Database::fetchAll(
            "SELECT v.*, u.fname AS nurse_fname, u.lname AS nurse_lname
             FROM ipd_vitals v
             LEFT JOIN users u ON v.recorded_by = u.id
             WHERE v.admission_id = ?
             ORDER BY v.recorded_at DESC",
            [$id]
        );

        $admission['doctor_rounds'] = Database::fetchAll(
            "SELECT r.*, u.fname AS doc_fname, u.lname AS doc_lname
             FROM ipd_doctor_rounds r
             LEFT JOIN users u ON r.doctor_id = u.id
             WHERE r.admission_id = ?
             ORDER BY r.round_date DESC",
            [$id]
        );

        $admission['medication_orders'] = Database::fetchAll(
            "SELECT m.*, u.fname AS doc_fname, u.lname AS doc_lname
             FROM ipd_medication_orders m
             LEFT JOIN users u ON m.doctor_id = u.id
             WHERE m.admission_id = ?
             ORDER BY m.id DESC",
            [$id]
        );

        $admission['mar_records'] = Database::fetchAll(
            "SELECT mar.*, m.medicine_name, m.dosage, m.route, u.fname AS nurse_fname, u.lname AS nurse_lname
             FROM ipd_mar_administrations mar
             JOIN ipd_medication_orders m ON mar.order_id = m.id
             LEFT JOIN users u ON mar.administered_by = u.id
             WHERE mar.admission_id = ?
             ORDER BY mar.scheduled_time DESC",
            [$id]
        );

        $admission['intake_output'] = Database::fetchAll(
            "SELECT io.*, u.fname AS user_fname, u.lname AS user_lname
             FROM ipd_intake_output io
             LEFT JOIN users u ON io.recorded_by = u.id
             WHERE io.admission_id = ?
             ORDER BY io.record_datetime DESC",
            [$id]
        );

        $admission['nursing_notes'] = Database::fetchAll(
            "SELECT n.*, u.fname AS nurse_fname, u.lname AS nurse_lname
             FROM ipd_nursing_notes n
             LEFT JOIN users u ON n.nurse_id = u.id
             WHERE n.admission_id = ?
             ORDER BY n.id DESC",
            [$id]
        );

        $admission['surgeries'] = Database::fetchAll(
            "SELECT s.*, ot.theatre_name, u.fname AS surgeon_fname, u.lname AS surgeon_lname
             FROM ipd_surgeries s
             LEFT JOIN ipd_operation_theatres ot ON s.ot_id = ot.id
             LEFT JOIN users u ON s.lead_surgeon_id = u.id
             WHERE s.admission_id = ?
             ORDER BY s.scheduled_start DESC",
            [$id]
        );

        $admission['diagnostics'] = Database::fetchAll(
            "SELECT d.*, u.fname AS doc_fname, u.lname AS doc_lname
             FROM ipd_diagnostics_orders d
             LEFT JOIN users u ON d.doctor_id = u.id
             WHERE d.admission_id = ?
             ORDER BY d.order_date DESC",
            [$id]
        );

        $admission['bills'] = Database::fetchAll(
            "SELECT b.* 
             FROM ipd_bills b 
             WHERE b.admission_id = ? 
             ORDER BY b.id DESC",
            [$id]
        );

        $admission['payments'] = Database::fetchAll(
            "SELECT p.*, u.fname AS cashier_fname, u.lname AS cashier_lname
             FROM ipd_payments p
             LEFT JOIN users u ON p.received_by = u.id
             WHERE p.admission_id = ?
             ORDER BY p.payment_date DESC",
            [$id]
        );

        $admission['attendant_passes'] = Database::fetchAll(
            "SELECT pass.* 
             FROM ipd_attendant_passes pass 
             WHERE pass.admission_id = ? 
             ORDER BY pass.id DESC",
            [$id]
        );

        $admission['discharge'] = Database::fetchRow(
            "SELECT d.*, u.fname AS doc_fname, u.lname AS doc_lname
             FROM ipd_discharges d
             LEFT JOIN users u ON d.discharged_by = u.id
             WHERE d.admission_id = ?",
            [$id]
        );

        return $admission;
    }

    /**
     * Create Inpatient Admission
     */
    public function createAdmission(array $data): array
    {
        $pid = (int)($data['pid'] ?? 0);
        $wardId = (int)($data['ward_id'] ?? 0);
        $bedId = (int)($data['bed_id'] ?? 0);
        $doctorId = (int)($data['attending_doctor_id'] ?? 0);
        $admissionType = trim($data['admission_type'] ?? 'Elective');
        $provisionalDiagnosis = trim($data['provisional_diagnosis'] ?? '');
        $chiefComplaints = trim($data['chief_complaints'] ?? '');
        $admissionDate = !empty($data['admission_date']) ? $data['admission_date'] : date('Y-m-d H:i:s');

        if ($pid <= 0) {
            throw new Exception("Please select a valid OpenEMR patient record.");
        }
        if ($bedId <= 0) {
            throw new Exception("Please select an available bed for admission.");
        }
        if ($doctorId <= 0) {
            throw new Exception("Please assign an attending physician.");
        }

        // Verify patient exists
        $patient = BootstrapService::getPatient($pid);
        if (!$patient) {
            throw new Exception("Patient with PID {$pid} does not exist in OpenEMR.");
        }

        // Check if patient already has an active admission
        $activeAdmission = Database::fetchRow(
            "SELECT id, ipd_number FROM ipd_admissions WHERE pid = ? AND admission_status IN ('admitted', 'transferred', 'discharge_requested')",
            [$pid]
        );
        if ($activeAdmission) {
            throw new Exception("Patient {$patient['fname']} {$patient['lname']} is already admitted under {$activeAdmission['ipd_number']}.");
        }

        // Check bed status
        $bed = Database::fetchRow("SELECT * FROM ipd_beds WHERE id = ?", [$bedId]);
        if (!$bed) {
            throw new Exception("Selected bed does not exist.");
        }
        if ($bed['status'] !== 'available') {
            throw new Exception("Selected bed {$bed['bed_number']} is currently {$bed['status']}. Please select an available bed.");
        }

        Database::beginTransaction();
        try {
            $ipdNumber = BootstrapService::generateIpdNumber();
            $authUserId = BootstrapService::getAuthUserId();

            // Insert admission
            $admissionId = Database::insert(
                "INSERT INTO `ipd_admissions` (
                    `ipd_number`, `pid`, `encounter_id`, `admission_date`, `admission_type`,
                    `attending_doctor_id`, `admitting_doctor_id`, `department`, `ward_id`, `bed_id`,
                    `provisional_diagnosis`, `icd10_code`, `chief_complaints`, `medical_history`,
                    `known_allergies`, `attendant_name`, `attendant_relation`, `attendant_phone`,
                    `emergency_contact`, `admission_status`, `created_by`, `created_at`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'admitted', ?, NOW())",
                [
                    $ipdNumber,
                    $pid,
                    !empty($data['encounter_id']) ? (int)$data['encounter_id'] : null,
                    $admissionDate,
                    $admissionType,
                    $doctorId,
                    !empty($data['admitting_doctor_id']) ? (int)$data['admitting_doctor_id'] : $doctorId,
                    $data['department'] ?? 'General Medicine',
                    $wardId,
                    $bedId,
                    $provisionalDiagnosis,
                    $data['icd10_code'] ?? null,
                    $chiefComplaints,
                    $data['medical_history'] ?? null,
                    $data['known_allergies'] ?? null,
                    $data['attendant_name'] ?? null,
                    $data['attendant_relation'] ?? null,
                    $data['attendant_phone'] ?? null,
                    $data['emergency_contact'] ?? null,
                    $authUserId
                ]
            );

            // Update Bed status to 'occupied'
            Database::execute(
                "UPDATE `ipd_beds` SET `status` = 'occupied', `current_admission_id` = ? WHERE `id` = ?",
                [$admissionId, $bedId]
            );

            // Record Bed Allocation History
            Database::insert(
                "INSERT INTO `ipd_bed_allocations` (
                    `admission_id`, `patient_id`, `bed_id`, `ward_id`, `allocated_from`, `daily_rate`, `allocated_by`, `created_at`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $admissionId,
                    $pid,
                    $bedId,
                    $wardId,
                    $admissionDate,
                    $bed['daily_charge'],
                    $authUserId
                ]
            );

            // Write Audit Log
            BootstrapService::logAudit(
                $admissionId,
                'ADMISSION_CREATED',
                'ipd_admissions',
                $admissionId,
                "Admitted patient {$patient['fname']} {$patient['lname']} (PID {$pid}) to Bed {$bed['bed_number']} with IPD No {$ipdNumber}",
                ['ipd_number' => $ipdNumber, 'bed_id' => $bedId, 'doctor_id' => $doctorId]
            );

            Database::commit();

            return [
                'success' => true,
                'message' => "Patient successfully admitted with IPD Number: {$ipdNumber}",
                'admission_id' => $admissionId,
                'ipd_number' => $ipdNumber
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Transfer patient to a new bed
     */
    public function transferBed(int $admissionId, int $newBedId, string $transferReason): array
    {
        $admission = Database::fetchRow("SELECT * FROM ipd_admissions WHERE id = ?", [$admissionId]);
        if (!$admission) {
            throw new Exception("Admission record not found.");
        }
        if ($admission['admission_status'] === 'discharged') {
            throw new Exception("Cannot transfer a discharged patient.");
        }

        $oldBedId = (int)$admission['bed_id'];
        if ($oldBedId === $newBedId) {
            throw new Exception("Patient is already in this bed.");
        }

        $newBed = Database::fetchRow("SELECT * FROM ipd_beds WHERE id = ?", [$newBedId]);
        if (!$newBed) {
            throw new Exception("Target bed does not exist.");
        }
        if ($newBed['status'] !== 'available') {
            throw new Exception("Target bed {$newBed['bed_number']} is currently {$newBed['status']}. Must be available.");
        }

        Database::beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $authUserId = BootstrapService::getAuthUserId();

            // 1. Close current bed allocation
            Database::execute(
                "UPDATE `ipd_bed_allocations` SET `allocated_to` = ? WHERE `admission_id` = ? AND `bed_id` = ? AND `allocated_to` IS NULL",
                [$now, $admissionId, $oldBedId]
            );

            // 2. Mark old bed as 'cleaning'
            Database::execute(
                "UPDATE `ipd_beds` SET `status` = 'cleaning', `current_admission_id` = NULL WHERE `id` = ?",
                [$oldBedId]
            );

            // 3. Mark new bed as 'occupied'
            Database::execute(
                "UPDATE `ipd_beds` SET `status` = 'occupied', `current_admission_id` = ? WHERE `id` = ?",
                [$admissionId, $newBedId]
            );

            // 4. Update admission record
            Database::execute(
                "UPDATE `ipd_admissions` SET `bed_id` = ?, `ward_id` = ?, `admission_status` = 'transferred' WHERE `id` = ?",
                [$newBedId, $newBed['ward_id'], $admissionId]
            );

            // 5. Insert new bed allocation record
            Database::insert(
                "INSERT INTO `ipd_bed_allocations` (
                    `admission_id`, `patient_id`, `bed_id`, `ward_id`, `allocated_from`, `daily_rate`, `transfer_reason`, `allocated_by`, `created_at`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $admissionId,
                    $admission['pid'],
                    $newBedId,
                    $newBed['ward_id'],
                    $now,
                    $newBed['daily_charge'],
                    $transferReason,
                    $authUserId
                ]
            );

            // Write Audit Log
            BootstrapService::logAudit(
                $admissionId,
                'BED_TRANSFERRED',
                'ipd_bed_allocations',
                $newBedId,
                "Transferred patient to Bed {$newBed['bed_number']}. Reason: {$transferReason}",
                ['from_bed_id' => $oldBedId, 'to_bed_id' => $newBedId, 'reason' => $transferReason]
            );

            Database::commit();

            return [
                'success' => true,
                'message' => "Patient successfully transferred to Bed {$newBed['bed_number']}."
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
}
