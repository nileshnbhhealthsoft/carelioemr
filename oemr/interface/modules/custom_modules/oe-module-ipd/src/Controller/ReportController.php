<?php

namespace OpenEMR\Modules\Ipd\Controller;

use OpenEMR\Modules\Ipd\Database;

/**
 * Inpatient Operational & Financial Reports and KPI Dashboard Controller
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class ReportController
{
    /**
     * Get Executive KPI Overview for Hospital Dashboard
     */
    public function getHospitalKpis(): array
    {
        // 1. Bed Statistics
        $totalBeds = (int)Database::fetchColumn("SELECT COUNT(*) FROM ipd_beds");
        $occupiedBeds = (int)Database::fetchColumn("SELECT COUNT(*) FROM ipd_beds WHERE status = 'occupied'");
        $availableBeds = (int)Database::fetchColumn("SELECT COUNT(*) FROM ipd_beds WHERE status = 'available'");
        $cleaningBeds = (int)Database::fetchColumn("SELECT COUNT(*) FROM ipd_beds WHERE status = 'cleaning'");
        $maintenanceBeds = (int)Database::fetchColumn("SELECT COUNT(*) FROM ipd_beds WHERE status = 'maintenance'");
        $occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0;

        // 2. Patient Counts
        $currentInpatients = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM ipd_admissions WHERE admission_status IN ('admitted', 'transferred', 'discharge_requested')"
        );
        $admissionsToday = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM ipd_admissions WHERE DATE(admission_date) = CURDATE()"
        );
        $dischargesToday = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM ipd_admissions WHERE DATE(discharged_at) = CURDATE() AND admission_status = 'discharged'"
        );
        $emergencyToday = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM ipd_emergency_triage WHERE DATE(arrival_time) = CURDATE()"
        );
        $surgeriesToday = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM ipd_surgeries WHERE DATE(scheduled_start) = CURDATE()"
        );

        // 3. Average Length of Stay (ALOS)
        $alos = (float)Database::fetchColumn(
            "SELECT COALESCE(AVG(DATEDIFF(COALESCE(discharged_at, NOW()), admission_date)), 0) 
             FROM ipd_admissions 
             WHERE admission_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        // 4. Financial KPI Totals
        $revenueStats = Database::fetchRow(
            "SELECT 
                COALESCE(SUM(gross_amount), 0) AS total_gross_billed,
                COALESCE(SUM(net_amount), 0) AS total_net_billed,
                COALESCE(SUM(amount_paid), 0) AS total_bill_payments,
                COALESCE(SUM(balance_due), 0) AS total_outstanding
             FROM ipd_bills"
        );

        $totalDeposits = (float)Database::fetchColumn(
            "SELECT COALESCE(SUM(amount), 0) FROM ipd_payments"
        );

        // 5. Ward Occupancy Breakdown
        $wardUtilization = Database::fetchAll(
            "SELECT w.id, w.ward_name, w.ward_type, w.department,
                    COUNT(b.id) AS total_beds,
                    SUM(CASE WHEN b.status = 'occupied' THEN 1 ELSE 0 END) AS occupied_beds,
                    SUM(CASE WHEN b.status = 'available' THEN 1 ELSE 0 END) AS available_beds
             FROM ipd_wards w
             LEFT JOIN ipd_beds b ON w.id = b.ward_id
             WHERE w.is_active = 1
             GROUP BY w.id, w.ward_name, w.ward_type, w.department
             ORDER BY w.id ASC"
        );

        // 6. Doctor Workload (assigned active inpatients)
        $doctorWorkload = Database::fetchAll(
            "SELECT u.id, u.fname, u.lname, u.specialty, COUNT(a.id) AS active_patients
             FROM users u
             LEFT JOIN ipd_admissions a ON u.id = a.attending_doctor_id AND a.admission_status IN ('admitted', 'transferred', 'discharge_requested')
             WHERE u.active = 1 AND u.authorized = 1
             GROUP BY u.id, u.fname, u.lname, u.specialty
             HAVING active_patients > 0
             ORDER BY active_patients DESC"
        );

        return [
            'beds' => [
                'total' => $totalBeds,
                'occupied' => $occupiedBeds,
                'available' => $availableBeds,
                'cleaning' => $cleaningBeds,
                'maintenance' => $maintenanceBeds,
                'occupancy_rate' => $occupancyRate
            ],
            'patients' => [
                'current_inpatients' => $currentInpatients,
                'admissions_today' => $admissionsToday,
                'discharges_today' => $dischargesToday,
                'emergency_today' => $emergencyToday,
                'surgeries_today' => $surgeriesToday,
                'average_length_of_stay' => round($alos, 1)
            ],
            'financials' => [
                'gross_billed' => (float)($revenueStats['total_gross_billed'] ?? 0),
                'net_billed' => (float)($revenueStats['total_net_billed'] ?? 0),
                'total_collected' => $totalDeposits,
                'outstanding_balance' => (float)($revenueStats['total_outstanding'] ?? 0)
            ],
            'ward_utilization' => $wardUtilization,
            'doctor_workload' => $doctorWorkload
        ];
    }
}
