<?php

namespace OpenEMR\Modules\Ipd\Controller;

use Exception;
use OpenEMR\Modules\Ipd\Database;
use OpenEMR\Modules\Ipd\BootstrapService;

/**
 * Emergency & Casualty Triage Management Controller
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class EmergencyController
{
    /**
     * List Emergency Triage Queue
     */
    public function listTriage(?string $status = null, ?string $level = null): array
    {
        $sql = "SELECT t.*, 
                       p.fname, p.lname, p.DOB, p.sex, p.phone_cell,
                       u.fname AS doc_fname, u.lname AS doc_lname,
                       nurse.fname AS nurse_fname, nurse.lname AS nurse_lname,
                       a.ipd_number,
                       TIMESTAMPDIFF(MINUTE, t.arrival_time, NOW()) AS wait_time_minutes
                FROM ipd_emergency_triage t
                JOIN patient_data p ON t.pid = p.pid
                LEFT JOIN users u ON t.assigned_doctor_id = u.id
                LEFT JOIN users nurse ON t.triage_nurse_id = nurse.id
                LEFT JOIN ipd_admissions a ON t.converted_admission_id = a.id
                WHERE 1=1";

        $params = [];

        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }

        if ($level) {
            $sql .= " AND t.triage_level = ?";
            $params[] = $level;
        }

        $sql .= " ORDER BY FIELD(t.triage_level, 'Red', 'Orange', 'Yellow', 'Green', 'Blue'), t.arrival_time DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Register Emergency Patient & Perform Initial Triage Assessment
     */
    public function registerTriage(array $data): array
    {
        $pid = (int)($data['pid'] ?? 0);
        $level = trim($data['triage_level'] ?? 'Yellow');
        $complaint = trim($data['chief_complaint'] ?? '');
        $arrivalTime = !empty($data['arrival_time']) ? $data['arrival_time'] : date('Y-m-d H:i:s');
        $doctorId = !empty($data['assigned_doctor_id']) ? (int)$data['assigned_doctor_id'] : null;
        $bay = trim($data['bay_number'] ?? '');
        $nurseId = BootstrapService::getAuthUserId();

        $sys = !empty($data['bp_systolic']) ? (int)$data['bp_systolic'] : null;
        $dia = !empty($data['bp_diastolic']) ? (int)$data['bp_diastolic'] : null;
        $pulse = !empty($data['pulse']) ? (int)$data['pulse'] : null;
        $temp = !empty($data['temp']) ? (float)$data['temp'] : null;
        $spo2 = !empty($data['spo2']) ? (int)$data['spo2'] : null;

        if ($pid <= 0) {
            throw new Exception("Please select a valid OpenEMR patient record.");
        }
        if (empty($complaint)) {
            throw new Exception("Chief complaint is required for emergency triage.");
        }

        $triageNumber = BootstrapService::generateTriageNumber();

        $id = Database::insert(
            "INSERT INTO `ipd_emergency_triage` (
                `pid`, `triage_number`, `arrival_time`, `triage_level`, `chief_complaint`,
                `bp_systolic`, `bp_diastolic`, `pulse`, `temp`, `spo2`,
                `assigned_doctor_id`, `bay_number`, `status`, `triage_nurse_id`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'triaged', ?, NOW())",
            [
                $pid, $triageNumber, $arrivalTime, $level, $complaint,
                $sys, $dia, $pulse, $temp, $spo2,
                $doctorId, $bay, $nurseId
            ]
        );

        BootstrapService::logAudit(
            null,
            'EMERGENCY_TRIAGED',
            'ipd_emergency_triage',
            $id,
            "Emergency triage performed: {$triageNumber} ({$level} Priority) for Patient PID {$pid}",
            ['triage_number' => $triageNumber, 'level' => $level, 'pid' => $pid]
        );

        return [
            'success' => true,
            'message' => "Patient successfully triaged with Number: {$triageNumber} ({$level} Priority)",
            'triage_id' => $id,
            'triage_number' => $triageNumber
        ];
    }

    /**
     * 1-Click Convert Emergency Triage Patient to Full Inpatient Admission
     */
    public function convertToAdmission(int $triageId, int $bedId, int $doctorId, ?string $diagnosis = null): array
    {
        $triage = Database::fetchRow("SELECT * FROM ipd_emergency_triage WHERE id = ?", [$triageId]);
        if (!$triage) {
            throw new Exception("Triage record not found.");
        }
        if ($triage['status'] === 'admitted_to_ipd') {
            throw new Exception("Patient has already been admitted to IPD from this emergency record.");
        }

        $bed = Database::fetchRow("SELECT * FROM ipd_beds WHERE id = ?", [$bedId]);
        if (!$bed || $bed['status'] !== 'available') {
            throw new Exception("Target bed is not available for inpatient admission.");
        }

        Database::beginTransaction();
        try {
            $admController = new AdmissionController();
            $admResult = $admController->createAdmission([
                'pid' => $triage['pid'],
                'ward_id' => $bed['ward_id'],
                'bed_id' => $bedId,
                'attending_doctor_id' => $doctorId,
                'admission_type' => 'Emergency',
                'provisional_diagnosis' => !empty($diagnosis) ? $diagnosis : "Emergency Admission: " . $triage['chief_complaint'],
                'chief_complaints' => $triage['chief_complaint'],
                'admission_date' => date('Y-m-d H:i:s')
            ]);

            $admissionId = (int)$admResult['admission_id'];

            // Update triage status
            Database::execute(
                "UPDATE `ipd_emergency_triage` SET `status` = 'admitted_to_ipd', `converted_admission_id` = ? WHERE `id` = ?",
                [$admissionId, $triageId]
            );

            // Copy triage vitals to admission vitals monitoring sheet
            if (!empty($triage['pulse']) || !empty($triage['bp_systolic'])) {
                Database::insert(
                    "INSERT INTO `ipd_vitals` (
                        `admission_id`, `recorded_at`, `recorded_by`, `pulse_rate`,
                        `bp_systolic`, `bp_diastolic`, `temperature`, `spo2`, `consciousness`, `clinical_notes`
                    ) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, 'Alert', 'Transferred from Emergency Triage')",
                    [
                        $admissionId, BootstrapService::getAuthUserId(),
                        $triage['pulse'], $triage['bp_systolic'], $triage['bp_diastolic'],
                        $triage['temp'], $triage['spo2']
                    ]
                );
            }

            Database::commit();

            return [
                'success' => true,
                'message' => "Emergency patient successfully admitted to IPD ({$admResult['ipd_number']}) in Bed {$bed['bed_number']}.",
                'admission_id' => $admissionId,
                'ipd_number' => $admResult['ipd_number']
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
}
