<?php

namespace OpenEMR\Modules\Ipd;

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Session\SessionWrapperFactory;

/**
 * Service & Helper methods for oe-module-ipd
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class BootstrapService
{
    /**
     * Get current authenticated user ID
     */
    public static function getAuthUserId(): int
    {
        if (class_exists(SessionWrapperFactory::class)) {
            try {
                $session = SessionWrapperFactory::getInstance()->getActiveSession();
                $authUserId = $session ? $session->get('authUserID') : ($_SESSION['authUserID'] ?? null);
                if (!empty($authUserId)) {
                    return (int)$authUserId;
                }
            } catch (\Throwable $e) {
                // proceed to session fallback
            }
        }
        return (int)($_SESSION['authUserID'] ?? 1);
    }

    /**
     * Check ACL permissions
     */
    public static function verifyAcl(string $section = 'patients', string $aco = 'med'): bool
    {
        if (class_exists(AclMain::class)) {
            return (bool)AclMain::aclCheckCore($section, $aco);
        }
        return true;
    }

    /**
     * Generate unique IPD Number (e.g. IPD-2026-00001)
     */
    public static function generateIpdNumber(): string
    {
        $year = date('Y');
        $prefix = "IPD-{$year}-";
        $lastNum = Database::fetchColumn(
            "SELECT ipd_number FROM ipd_admissions WHERE ipd_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["{$prefix}%"]
        );

        $nextSeq = 1;
        if (!empty($lastNum)) {
            $parts = explode('-', (string)$lastNum);
            $seq = end($parts);
            $nextSeq = (int)$seq + 1;
        }

        return $prefix . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique Bill Number (e.g. BILL-IPD-2026-00001)
     */
    public static function generateBillNumber(): string
    {
        $year = date('Y');
        $prefix = "BILL-IPD-{$year}-";
        $lastNum = Database::fetchColumn(
            "SELECT bill_number FROM ipd_bills WHERE bill_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["{$prefix}%"]
        );

        $nextSeq = 1;
        if (!empty($lastNum)) {
            $parts = explode('-', (string)$lastNum);
            $seq = end($parts);
            $nextSeq = (int)$seq + 1;
        }

        return $prefix . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique Receipt Number (e.g. RCPT-2026-00001)
     */
    public static function generateReceiptNumber(): string
    {
        $year = date('Y');
        $prefix = "RCPT-{$year}-";
        $lastNum = Database::fetchColumn(
            "SELECT receipt_number FROM ipd_payments WHERE receipt_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["{$prefix}%"]
        );

        $nextSeq = 1;
        if (!empty($lastNum)) {
            $parts = explode('-', (string)$lastNum);
            $seq = end($parts);
            $nextSeq = (int)$seq + 1;
        }

        return $prefix . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique Triage Number (e.g. TRG-2026-00001)
     */
    public static function generateTriageNumber(): string
    {
        $year = date('Y');
        $prefix = "TRG-{$year}-";
        $lastNum = Database::fetchColumn(
            "SELECT triage_number FROM ipd_emergency_triage WHERE triage_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["{$prefix}%"]
        );

        $nextSeq = 1;
        if (!empty($lastNum)) {
            $parts = explode('-', (string)$lastNum);
            $seq = end($parts);
            $nextSeq = (int)$seq + 1;
        }

        return $prefix . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique Attendant Pass Number (e.g. PASS-2026-00001)
     */
    public static function generatePassNumber(): string
    {
        $year = date('Y');
        $prefix = "PASS-{$year}-";
        $lastNum = Database::fetchColumn(
            "SELECT pass_number FROM ipd_attendant_passes WHERE pass_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["{$prefix}%"]
        );

        $nextSeq = 1;
        if (!empty($lastNum)) {
            $parts = explode('-', (string)$lastNum);
            $seq = end($parts);
            $nextSeq = (int)$seq + 1;
        }

        return $prefix . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Write an audit log entry
     */
    public static function logAudit(?int $admissionId, string $actionType, string $entityType, ?int $entityId, string $description, ?array $details = null): void
    {
        try {
            $userId = self::getAuthUserId();
            Database::insert(
                "INSERT INTO `ipd_audit_logs` (`admission_id`, `action_type`, `entity_type`, `entity_id`, `user_id`, `description`, `details_json`, `created_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $admissionId,
                    $actionType,
                    $entityType,
                    $entityId,
                    $userId,
                    $description,
                    $details ? json_encode($details) : null,
                ]
            );
        } catch (\Throwable $e) {
            error_log("IPD Audit Log Failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch list of authorized physicians/doctors from OpenEMR `users` table
     */
    public static function getActiveDoctors(): array
    {
        return Database::fetchAll(
            "SELECT id, fname, lname, specialty, federaltaxid 
             FROM users 
             WHERE active = 1 AND authorized = 1 
             ORDER BY lname ASC, fname ASC"
        );
    }

    /**
     * Fetch list of nurses/clinical staff from OpenEMR `users` table
     */
    public static function getActiveStaff(): array
    {
        return Database::fetchAll(
            "SELECT id, fname, lname, specialty 
             FROM users 
             WHERE active = 1 
             ORDER BY lname ASC, fname ASC"
        );
    }

    /**
     * Search existing OpenEMR patients by name, PID, or phone
     */
    public static function searchPatients(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        $term = "%{$query}%";
        return Database::fetchAll(
            "SELECT pid, pubpid, fname, lname, mname, DOB, sex, phone_cell, phone_home, street, city, state, postal_code
             FROM patient_data
             WHERE pid = ? OR pubpid LIKE ? OR fname LIKE ? OR lname LIKE ? OR phone_cell LIKE ?
             ORDER BY lname ASC, fname ASC
             LIMIT {$limit}",
            [
                is_numeric($query) ? (int)$query : 0,
                $term,
                $term,
                $term,
                $term
            ]
        );
    }

    /**
     * Get single patient record by PID
     */
    public static function getPatient(int $pid): ?array
    {
        return Database::fetchRow(
            "SELECT pid, pubpid, fname, lname, mname, DOB, sex, phone_cell, phone_home, street, city, state, postal_code, status
             FROM patient_data 
             WHERE pid = ?",
            [$pid]
        );
    }
}
