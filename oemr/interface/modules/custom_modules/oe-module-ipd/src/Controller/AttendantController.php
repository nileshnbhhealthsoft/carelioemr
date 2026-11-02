<?php

namespace OpenEMR\Modules\Ipd\Controller;

use Exception;
use OpenEMR\Modules\Ipd\Database;
use OpenEMR\Modules\Ipd\BootstrapService;

/**
 * Attendant, Visitor Passes and Dietary Services Controller
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class AttendantController
{
    /**
     * List Attendant Passes
     */
    public function listPasses(?int $admissionId = null, ?string $status = null): array
    {
        $sql = "SELECT pass.*, 
                       a.ipd_number, a.pid,
                       p.fname AS pat_fname, p.lname AS pat_lname,
                       b.bed_number, w.ward_name
                FROM ipd_attendant_passes pass
                JOIN ipd_admissions a ON pass.admission_id = a.id
                JOIN patient_data p ON a.pid = p.pid
                JOIN ipd_beds b ON a.bed_id = b.id
                JOIN ipd_wards w ON a.ward_id = w.id
                WHERE 1=1";

        $params = [];

        if ($admissionId) {
            $sql .= " AND pass.admission_id = ?";
            $params[] = $admissionId;
        }

        if ($status) {
            $sql .= " AND pass.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY pass.id DESC";

        return Database::fetchAll($sql, $params);
    }

    /**
     * Issue Attendant & Visitor Badge Pass
     */
    public function issuePass(array $data): array
    {
        $admissionId = (int)($data['admission_id'] ?? 0);
        $name = trim($data['attendant_name'] ?? '');
        $relation = trim($data['relationship'] ?? 'Family');
        $phone = trim($data['phone'] ?? '');
        $from = !empty($data['valid_from']) ? $data['valid_from'] : date('Y-m-d H:i:s');
        $to = !empty($data['valid_to']) ? $data['valid_to'] : date('Y-m-d H:i:s', strtotime('+3 days'));
        $meal = trim($data['meal_plan'] ?? 'Normal');

        if ($admissionId <= 0 || empty($name)) {
            throw new Exception("Admission ID and Attendant name are required.");
        }

        $passNumber = BootstrapService::generatePassNumber();

        $id = Database::insert(
            "INSERT INTO `ipd_attendant_passes` (
                `pass_number`, `admission_id`, `attendant_name`, `relationship`,
                `phone`, `valid_from`, `valid_to`, `meal_plan`, `status`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
            [
                $passNumber, $admissionId, $name, $relation,
                $phone, $from, $to, $meal
            ]
        );

        BootstrapService::logAudit(
            $admissionId,
            'ATTENDANT_PASS_ISSUED',
            'ipd_attendant_passes',
            $id,
            "Issued Attendant Pass {$passNumber} to {$name} ({$relation})"
        );

        return [
            'success' => true,
            'message' => "Attendant pass issued with Pass No: {$passNumber}",
            'pass_id' => $id,
            'pass_number' => $passNumber
        ];
    }

    /**
     * Update Pass Status
     */
    public function updatePassStatus(int $passId, string $status): array
    {
        $validStatuses = ['active', 'expired', 'returned'];
        if (!in_array($status, $validStatuses, true)) {
            throw new Exception("Invalid pass status: {$status}");
        }

        Database::execute(
            "UPDATE `ipd_attendant_passes` SET `status` = ? WHERE `id` = ?",
            [$status, $passId]
        );

        return [
            'success' => true,
            'message' => "Pass status updated to {$status}."
        ];
    }
}
