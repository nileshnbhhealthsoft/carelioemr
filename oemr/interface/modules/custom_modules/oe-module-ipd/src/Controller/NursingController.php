<?php

namespace OpenEMR\Modules\Ipd\Controller;

use Exception;
use OpenEMR\Modules\Ipd\Database;
use OpenEMR\Modules\Ipd\BootstrapService;

/**
 * Nursing Care, Vitals Monitoring, MAR, and Shift Management Controller
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class NursingController
{
    /**
     * Record Inpatient Vital Signs
     */
    public function recordVitals(array $data): array
    {
        $admissionId = (int)($data['admission_id'] ?? 0);
        $recordedAt = !empty($data['recorded_at']) ? $data['recorded_at'] : date('Y-m-d H:i:s');
        $recordedBy = BootstrapService::getAuthUserId();

        $pulse = !empty($data['pulse_rate']) ? (int)$data['pulse_rate'] : null;
        $bpSys = !empty($data['bp_systolic']) ? (int)$data['bp_systolic'] : null;
        $bpDia = !empty($data['bp_diastolic']) ? (int)$data['bp_diastolic'] : null;
        $temp = !empty($data['temperature']) ? (float)$data['temperature'] : null;
        $resp = !empty($data['resp_rate']) ? (int)$data['resp_rate'] : null;
        $spo2 = !empty($data['spo2']) ? (int)$data['spo2'] : null;
        $pain = isset($data['pain_score']) && $data['pain_score'] !== '' ? (int)$data['pain_score'] : null;
        $sugar = !empty($data['blood_sugar']) ? (float)$data['blood_sugar'] : null;
        $consciousness = trim($data['consciousness'] ?? 'Alert');
        $o2Support = trim($data['oxygen_support'] ?? 'Room Air');
        $notes = trim($data['clinical_notes'] ?? '');

        if ($admissionId <= 0) {
            throw new Exception("Invalid Inpatient Admission ID.");
        }

        $vitalsId = Database::insert(
            "INSERT INTO `ipd_vitals` (
                `admission_id`, `recorded_at`, `recorded_by`, `pulse_rate`, `bp_systolic`,
                `bp_diastolic`, `temperature`, `resp_rate`, `spo2`, `pain_score`,
                `blood_sugar`, `consciousness`, `oxygen_support`, `clinical_notes`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $admissionId, $recordedAt, $recordedBy, $pulse, $bpSys,
                $bpDia, $temp, $resp, $spo2, $pain,
                $sugar, $consciousness, $o2Support, $notes
            ]
        );

        return [
            'success' => true,
            'message' => "Vital signs recorded successfully.",
            'vitals_id' => $vitalsId
        ];
    }

    /**
     * Administer Medication (MAR Entry)
     */
    public function administerMedication(array $data): array
    {
        $orderId = (int)($data['order_id'] ?? 0);
        $admissionId = (int)($data['admission_id'] ?? 0);
        $scheduledTime = !empty($data['scheduled_time']) ? $data['scheduled_time'] : date('Y-m-d H:i:s');
        $administeredTime = date('Y-m-d H:i:s');
        $administeredBy = BootstrapService::getAuthUserId();
        $status = trim($data['status'] ?? 'given');
        $dosageGiven = trim($data['dosage_given'] ?? '');
        $site = trim($data['site_of_injection'] ?? '');
        $remarks = trim($data['remarks'] ?? '');

        if ($orderId <= 0 || $admissionId <= 0) {
            throw new Exception("Medication Order ID and Admission ID are required.");
        }

        $order = Database::fetchRow("SELECT * FROM ipd_medication_orders WHERE id = ?", [$orderId]);
        if (!$order) {
            throw new Exception("Medication order not found.");
        }

        if (empty($dosageGiven)) {
            $dosageGiven = $order['dosage'];
        }

        $marId = Database::insert(
            "INSERT INTO `ipd_mar_administrations` (
                `order_id`, `admission_id`, `scheduled_time`, `administered_time`,
                `administered_by`, `status`, `dosage_given`, `site_of_injection`, `remarks`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $orderId, $admissionId, $scheduledTime, $administeredTime,
                $administeredBy, $status, $dosageGiven, $site, $remarks
            ]
        );

        return [
            'success' => true,
            'message' => "Medication administration ({$status}) documented in MAR.",
            'mar_id' => $marId
        ];
    }

    /**
     * Add Nursing Care Note and Handover
     */
    public function addNursingNote(array $data): array
    {
        $admissionId = (int)($data['admission_id'] ?? 0);
        $nurseId = BootstrapService::getAuthUserId();
        $shift = trim($data['shift'] ?? 'Morning');
        $diagnosis = trim($data['nursing_diagnosis'] ?? '');
        $interventions = trim($data['interventions'] ?? '');
        $response = trim($data['patient_response'] ?? '');
        $handover = trim($data['handover_notes'] ?? '');

        if ($admissionId <= 0) {
            throw new Exception("Invalid Inpatient Admission ID.");
        }

        $noteId = Database::insert(
            "INSERT INTO `ipd_nursing_notes` (
                `admission_id`, `nurse_id`, `shift`, `nursing_diagnosis`,
                `interventions`, `patient_response`, `handover_notes`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $admissionId, $nurseId, $shift, $diagnosis,
                $interventions, $response, $handover
            ]
        );

        return [
            'success' => true,
            'message' => "Nursing note and handover saved successfully.",
            'note_id' => $noteId
        ];
    }

    /**
     * Record Risk Assessment (Fall Risk / Braden Pressure Ulcer Scale)
     */
    public function recordRiskAssessment(array $data): array
    {
        $admissionId = (int)($data['admission_id'] ?? 0);
        $type = trim($data['assessment_type'] ?? 'fall_risk');
        $totalScore = (int)($data['total_score'] ?? 0);
        $riskLevel = trim($data['risk_level'] ?? 'Low');
        $criteriaJson = !empty($data['criteria_json']) ? (is_array($data['criteria_json']) ? json_encode($data['criteria_json']) : $data['criteria_json']) : null;
        $assessedBy = BootstrapService::getAuthUserId();

        if ($admissionId <= 0) {
            throw new Exception("Invalid Inpatient Admission ID.");
        }

        $id = Database::insert(
            "INSERT INTO `ipd_risk_assessments` (
                `admission_id`, `assessment_type`, `total_score`, `risk_level`,
                `criteria_json`, `assessed_by`, `assessed_at`
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$admissionId, $type, $totalScore, $riskLevel, $criteriaJson, $assessedBy]
        );

        return [
            'success' => true,
            'message' => "Risk assessment ({$riskLevel}) saved successfully.",
            'assessment_id' => $id
        ];
    }

    /**
     * Get Nurse Shift Duty Roster
     */
    public function getDutyRoster(?string $date = null, ?int $wardId = null): array
    {
        $date = !empty($date) ? $date : date('Y-m-d');
        $sql = "SELECT s.*, u.fname, u.lname, w.ward_name, w.ward_code 
                FROM ipd_nurse_shifts s
                JOIN users u ON s.nurse_id = u.id
                JOIN ipd_wards w ON s.ward_id = w.id
                WHERE s.shift_date = ?";
        $params = [$date];

        if ($wardId) {
            $sql .= " AND s.ward_id = ?";
            $params[] = $wardId;
        }

        $sql .= " ORDER BY w.ward_name ASC, s.shift_type ASC";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Save Nurse Shift Assignment
     */
    public function saveShiftAssignment(array $data): array
    {
        $nurseId = (int)($data['nurse_id'] ?? 0);
        $wardId = (int)($data['ward_id'] ?? 0);
        $date = !empty($data['shift_date']) ? $data['shift_date'] : date('Y-m-d');
        $shiftType = trim($data['shift_type'] ?? 'Morning');
        $notes = trim($data['notes'] ?? '');

        if ($nurseId <= 0 || $wardId <= 0) {
            throw new Exception("Nurse and Ward selection are required.");
        }

        $id = Database::insert(
            "INSERT INTO `ipd_nurse_shifts` (`nurse_id`, `ward_id`, `shift_date`, `shift_type`, `notes`)
             VALUES (?, ?, ?, ?, ?)",
            [$nurseId, $wardId, $date, $shiftType, $notes]
        );

        return [
            'success' => true,
            'message' => "Shift assignment saved successfully.",
            'id' => $id
        ];
    }
}
