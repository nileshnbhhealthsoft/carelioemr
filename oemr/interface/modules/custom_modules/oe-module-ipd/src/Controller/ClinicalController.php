<?php

namespace OpenEMR\Modules\Ipd\Controller;

use Exception;
use OpenEMR\Modules\Ipd\Database;
use OpenEMR\Modules\Ipd\BootstrapService;

/**
 * Doctor Rounds, Clinical Progress Notes, Medication Orders, and Fluid Balance
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class ClinicalController
{
    /**
     * Record Inpatient Doctor Round / Progress Note
     */
    public function addDoctorRound(array $data): array
    {
        $admissionId = (int)($data['admission_id'] ?? 0);
        $doctorId = (int)($data['doctor_id'] ?? BootstrapService::getAuthUserId());
        $roundDate = !empty($data['round_date']) ? $data['round_date'] : date('Y-m-d H:i:s');
        $subjective = trim($data['subjective_notes'] ?? '');
        $objective = trim($data['objective_findings'] ?? '');
        $assessment = trim($data['assessment'] ?? '');
        $treatmentPlan = trim($data['treatment_plan'] ?? '');
        $diet = trim($data['diet_orders'] ?? '');
        $activity = trim($data['activity_orders'] ?? '');
        $nextRound = !empty($data['next_round_time']) ? $data['next_round_time'] : null;

        if ($admissionId <= 0) {
            throw new Exception("Invalid Inpatient Admission ID.");
        }
        if (empty($assessment) && empty($treatmentPlan)) {
            throw new Exception("Please provide Clinical Assessment or Treatment Plan.");
        }

        $roundId = Database::insert(
            "INSERT INTO `ipd_doctor_rounds` (
                `admission_id`, `round_date`, `doctor_id`, `subjective_notes`, `objective_findings`,
                `assessment`, `treatment_plan`, `diet_orders`, `activity_orders`, `next_round_time`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $admissionId, $roundDate, $doctorId, $subjective, $objective,
                $assessment, $treatmentPlan, $diet, $activity, $nextRound
            ]
        );

        BootstrapService::logAudit(
            $admissionId,
            'DOCTOR_ROUND_RECORDED',
            'ipd_doctor_rounds',
            $roundId,
            "Recorded daily clinical round for Admission #{$admissionId}"
        );

        return [
            'success' => true,
            'message' => "Daily doctor round recorded successfully.",
            'round_id' => $roundId
        ];
    }

    /**
     * Prescribe Inpatient Medication Order (MAR order)
     */
    public function addMedicationOrder(array $data): array
    {
        $admissionId = (int)($data['admission_id'] ?? 0);
        $doctorId = (int)($data['doctor_id'] ?? BootstrapService::getAuthUserId());
        $medicine = trim($data['medicine_name'] ?? '');
        $dosage = trim($data['dosage'] ?? '');
        $route = trim($data['route'] ?? 'Oral');
        $frequency = trim($data['frequency'] ?? 'BD');
        $startDate = !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d');
        $endDate = !empty($data['end_date']) ? $data['end_date'] : null;
        $instructions = trim($data['instructions'] ?? '');

        if ($admissionId <= 0 || empty($medicine) || empty($dosage)) {
            throw new Exception("Admission ID, Medicine Name, and Dosage are required.");
        }

        $orderId = Database::insert(
            "INSERT INTO `ipd_medication_orders` (
                `admission_id`, `doctor_id`, `medicine_name`, `dosage`, `route`,
                `frequency`, `start_date`, `end_date`, `instructions`, `status`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
            [
                $admissionId, $doctorId, $medicine, $dosage, $route,
                $frequency, $startDate, $endDate, $instructions
            ]
        );

        BootstrapService::logAudit(
            $admissionId,
            'MEDICATION_ORDERED',
            'ipd_medication_orders',
            $orderId,
            "Prescribed {$medicine} ({$dosage}, {$route}, {$frequency})"
        );

        return [
            'success' => true,
            'message' => "Medication order prescribed successfully.",
            'order_id' => $orderId
        ];
    }

    /**
     * Update Medication Order Status (discontinue, complete, on hold)
     */
    public function updateMedicationStatus(int $orderId, string $status): array
    {
        $validStatuses = ['active', 'completed', 'discontinued', 'on_hold'];
        if (!in_array($status, $validStatuses, true)) {
            throw new Exception("Invalid medication status: {$status}");
        }

        $order = Database::fetchRow("SELECT * FROM ipd_medication_orders WHERE id = ?", [$orderId]);
        if (!$order) {
            throw new Exception("Medication order not found.");
        }

        Database::execute(
            "UPDATE `ipd_medication_orders` SET `status` = ?, `end_date` = COALESCE(`end_date`, CURDATE()) WHERE `id` = ?",
            [$status, $orderId]
        );

        BootstrapService::logAudit(
            $order['admission_id'],
            'MEDICATION_STATUS_CHANGED',
            'ipd_medication_orders',
            $orderId,
            "Medication {$order['medicine_name']} status changed to {$status}"
        );

        return [
            'success' => true,
            'message' => "Medication status updated to {$status}."
        ];
    }

    /**
     * Record Fluid Intake and Output
     */
    public function recordIntakeOutput(array $data): array
    {
        $admissionId = (int)($data['admission_id'] ?? 0);
        $recordTime = !empty($data['record_datetime']) ? $data['record_datetime'] : date('Y-m-d H:i:s');
        $intakeType = trim($data['intake_type'] ?? 'Oral');
        $intakeAmount = (float)($data['intake_amount_ml'] ?? 0.00);
        $outputType = trim($data['output_type'] ?? 'Urine');
        $outputAmount = (float)($data['output_amount_ml'] ?? 0.00);
        $notes = trim($data['notes'] ?? '');
        $userId = BootstrapService::getAuthUserId();

        if ($admissionId <= 0) {
            throw new Exception("Invalid Admission ID.");
        }
        if ($intakeAmount <= 0 && $outputAmount <= 0) {
            throw new Exception("Please specify an intake amount or output amount.");
        }

        $id = Database::insert(
            "INSERT INTO `ipd_intake_output` (
                `admission_id`, `record_datetime`, `intake_type`, `intake_amount_ml`,
                `output_type`, `output_amount_ml`, `recorded_by`, `notes`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $admissionId, $recordTime, $intakeType, $intakeAmount,
                $outputType, $outputAmount, $userId, $notes
            ]
        );

        return [
            'success' => true,
            'message' => "Fluid balance entry recorded successfully.",
            'id' => $id
        ];
    }

    /**
     * Calculate 24-hour Fluid Intake and Output Summary
     */
    public function getIntakeOutputSummary(int $admissionId): array
    {
        $summary = Database::fetchRow(
            "SELECT 
                COALESCE(SUM(intake_amount_ml), 0) AS total_intake,
                COALESCE(SUM(output_amount_ml), 0) AS total_output,
                (COALESCE(SUM(intake_amount_ml), 0) - COALESCE(SUM(output_amount_ml), 0)) AS net_balance
             FROM ipd_intake_output
             WHERE admission_id = ? AND record_datetime >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$admissionId]
        );

        return [
            'total_intake' => (float)($summary['total_intake'] ?? 0),
            'total_output' => (float)($summary['total_output'] ?? 0),
            'net_balance' => (float)($summary['net_balance'] ?? 0)
        ];
    }
}
