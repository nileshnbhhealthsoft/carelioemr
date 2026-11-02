<?php

namespace OpenEMR\Modules\Ipd\Controller;

use Exception;
use OpenEMR\Modules\Ipd\Database;
use OpenEMR\Modules\Ipd\BootstrapService;

/**
 * Inpatient Hospital Billing, Tariff Master, Packages, and Payments Controller
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class BillingController
{
    /**
     * Get Tariff Catalog
     */
    public function listTariffs(): array
    {
        return Database::fetchAll("SELECT * FROM ipd_charge_catalog WHERE is_active = 1 ORDER BY category ASC, service_name ASC");
    }

    /**
     * Get Hospital Packages
     */
    public function listPackages(): array
    {
        return Database::fetchAll("SELECT * FROM ipd_packages WHERE is_active = 1 ORDER BY package_name ASC");
    }

    /**
     * Calculate Complete Inpatient Financial Statement / Summary
     */
    public function getBillingStatement(int $admissionId): array
    {
        $admission = Database::fetchRow(
            "SELECT a.*, p.fname, p.lname, p.DOB, p.sex, p.phone_cell,
                    w.ward_name, b.bed_number, b.daily_charge,
                    u.fname AS doc_fname, u.lname AS doc_lname,
                    DATEDIFF(COALESCE(a.discharged_at, NOW()), a.admission_date) AS days_stay
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

        // 1. Calculate Bed Stay Charges from Bed Allocations
        $allocations = Database::fetchAll(
            "SELECT ba.*, b.bed_number, w.ward_name,
                    TIMESTAMPDIFF(HOUR, ba.allocated_from, COALESCE(ba.allocated_to, NOW())) AS total_hours
             FROM ipd_bed_allocations ba
             JOIN ipd_beds b ON ba.bed_id = b.id
             JOIN ipd_wards w ON ba.ward_id = w.id
             WHERE ba.admission_id = ?
             ORDER BY ba.id ASC",
            [$admissionId]
        );

        $calculatedBedCharges = [];
        $totalBedChargeAmount = 0.00;

        foreach ($allocations as $alloc) {
            $hours = max(1, (int)$alloc['total_hours']);
            $days = ceil($hours / 24);
            $rate = (float)$alloc['daily_rate'];
            $lineTotal = round($days * $rate, 2);
            $totalBedChargeAmount += $lineTotal;

            $calculatedBedCharges[] = [
                'bed_number' => $alloc['bed_number'],
                'ward_name' => $alloc['ward_name'],
                'from' => $alloc['allocated_from'],
                'to' => $alloc['allocated_to'] ?? 'Current',
                'days' => $days,
                'rate' => $rate,
                'total' => $lineTotal
            ];
        }

        // 2. Fetch all generated bills and their line items
        $bills = Database::fetchAll(
            "SELECT b.* FROM ipd_bills b WHERE b.admission_id = ? ORDER BY b.id DESC",
            [$admissionId]
        );

        foreach ($bills as &$bill) {
            $bill['items'] = Database::fetchAll(
                "SELECT * FROM ipd_bill_items WHERE bill_id = ? ORDER BY id ASC",
                [$bill['id']]
            );
        }

        // 3. Fetch all payments / deposits
        $payments = Database::fetchAll(
            "SELECT p.*, u.fname AS cashier_fname, u.lname AS cashier_lname 
             FROM ipd_payments p
             LEFT JOIN users u ON p.received_by = u.id
             WHERE p.admission_id = ?
             ORDER BY p.payment_date DESC",
            [$admissionId]
        );

        $totalDeposits = 0.00;
        foreach ($payments as $pmt) {
            $totalDeposits += (float)$pmt['amount'];
        }

        // Calculate grand totals across all bills or unbilled
        $totalBilledGross = 0.00;
        $totalDiscount = 0.00;
        $totalNetBilled = 0.00;
        $totalAmountPaid = 0.00;

        foreach ($bills as $bill) {
            $totalBilledGross += (float)$bill['gross_amount'];
            $totalDiscount += (float)$bill['discount_amount'];
            $totalNetBilled += (float)$bill['net_amount'];
            $totalAmountPaid += (float)$bill['amount_paid'];
        }

        $balanceDue = max(0, $totalNetBilled - $totalDeposits);

        return [
            'admission' => $admission,
            'bed_charges_calculated' => $calculatedBedCharges,
            'total_bed_charge_amount' => $totalBedChargeAmount,
            'bills' => $bills,
            'payments' => $payments,
            'financial_summary' => [
                'gross_total' => $totalBilledGross,
                'discount_total' => $totalDiscount,
                'net_total' => $totalNetBilled,
                'total_paid_or_deposited' => $totalDeposits,
                'balance_due' => $balanceDue
            ]
        ];
    }

    /**
     * Generate an Inpatient Bill (Interim or Final)
     */
    public function generateBill(array $data): array
    {
        $admissionId = (int)($data['admission_id'] ?? 0);
        $billType = trim($data['bill_type'] ?? 'interim');
        $discount = (float)($data['discount_amount'] ?? 0.00);
        $tax = (float)($data['tax_amount'] ?? 0.00);
        $insurance = (float)($data['insurance_covered'] ?? 0.00);
        $items = $data['items'] ?? [];
        $authUserId = BootstrapService::getAuthUserId();

        if ($admissionId <= 0) {
            throw new Exception("Invalid Inpatient Admission ID.");
        }

        Database::beginTransaction();
        try {
            $billNumber = BootstrapService::generateBillNumber();
            $now = date('Y-m-d H:i:s');

            // Calculate item totals
            $totalBed = 0.00;
            $totalDoc = 0.00;
            $totalNursing = 0.00;
            $totalSurgery = 0.00;
            $totalOther = 0.00;
            $grossAmount = 0.00;

            foreach ($items as $item) {
                $qty = max(1, (float)($item['quantity'] ?? 1));
                $price = (float)($item['unit_price'] ?? 0);
                $lineTotal = round($qty * $price, 2);
                $grossAmount += $lineTotal;

                $cat = strtolower($item['category'] ?? 'other');
                if (str_contains($cat, 'bed')) {
                    $totalBed += $lineTotal;
                } elseif (str_contains($cat, 'doc') || str_contains($cat, 'round') || str_contains($cat, 'consult')) {
                    $totalDoc += $lineTotal;
                } elseif (str_contains($cat, 'nurs')) {
                    $totalNursing += $lineTotal;
                } elseif (str_contains($cat, 'surg') || str_contains($cat, 'ot')) {
                    $totalSurgery += $lineTotal;
                } else {
                    $totalOther += $lineTotal;
                }
            }

            $netAmount = max(0, ($grossAmount - $discount + $tax));
            $patientPayable = max(0, $netAmount - $insurance);

            // Fetch prior deposits that can be credited to this bill
            $existingPaid = (float)Database::fetchColumn(
                "SELECT COALESCE(SUM(amount), 0) FROM ipd_payments WHERE admission_id = ? AND bill_id IS NULL",
                [$admissionId]
            );

            $creditedAmount = min($existingPaid, $patientPayable);
            $balanceDue = max(0, $patientPayable - $creditedAmount);
            $status = ($balanceDue <= 0) ? 'paid' : ($creditedAmount > 0 ? 'partially_paid' : 'pending');

            $billId = Database::insert(
                "INSERT INTO `ipd_bills` (
                    `bill_number`, `admission_id`, `bill_date`, `bill_type`,
                    `total_bed_charges`, `total_doctor_charges`, `total_nursing_charges`,
                    `total_surgery_charges`, `total_other_charges`, `gross_amount`,
                    `discount_amount`, `tax_amount`, `net_amount`, `insurance_covered`,
                    `patient_payable`, `amount_paid`, `balance_due`, `status`, `created_by`, `created_at`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $billNumber, $admissionId, $now, $billType,
                    $totalBed, $totalDoc, $totalNursing,
                    $totalSurgery, $totalOther, $grossAmount,
                    $discount, $tax, $netAmount, $insurance,
                    $patientPayable, $creditedAmount, $balanceDue, $status, $authUserId
                ]
            );

            // Insert line items
            foreach ($items as $item) {
                $qty = max(1, (float)($item['quantity'] ?? 1));
                $price = (float)($item['unit_price'] ?? 0);
                $lineTotal = round($qty * $price, 2);

                Database::insert(
                    "INSERT INTO `ipd_bill_items` (`bill_id`, `item_date`, `service_name`, `category`, `quantity`, `unit_price`, `total_price`, `notes`)
                     VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)",
                    [
                        $billId,
                        trim($item['service_name'] ?? 'Hospital Service'),
                        trim($item['category'] ?? 'General'),
                        $qty,
                        $price,
                        $lineTotal,
                        trim($item['notes'] ?? '')
                    ]
                );
            }

            // Link credited prior deposits to this bill
            if ($creditedAmount > 0) {
                Database::execute(
                    "UPDATE `ipd_payments` SET `bill_id` = ? WHERE `admission_id` = ? AND `bill_id` IS NULL",
                    [$billId, $admissionId]
                );
            }

            BootstrapService::logAudit(
                $admissionId,
                'BILL_GENERATED',
                'ipd_bills',
                $billId,
                "Generated {$billType} bill {$billNumber} for Net Amount {$netAmount}",
                ['bill_number' => $billNumber, 'net_amount' => $netAmount, 'balance_due' => $balanceDue]
            );

            Database::commit();

            return [
                'success' => true,
                'message' => "Bill {$billNumber} successfully generated.",
                'bill_id' => $billId,
                'bill_number' => $billNumber,
                'net_amount' => $netAmount,
                'balance_due' => $balanceDue
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Record Inpatient Deposit / Payment
     */
    public function recordPayment(array $data): array
    {
        $admissionId = (int)($data['admission_id'] ?? 0);
        $billId = !empty($data['bill_id']) ? (int)$data['bill_id'] : null;
        $amount = (float)($data['amount'] ?? 0.00);
        $type = trim($data['payment_type'] ?? 'advance');
        $method = trim($data['payment_method'] ?? 'cash');
        $ref = trim($data['transaction_reference'] ?? '');
        $notes = trim($data['notes'] ?? '');
        $authUserId = BootstrapService::getAuthUserId();

        if ($admissionId <= 0 || $amount <= 0) {
            throw new Exception("Admission ID and a positive payment amount are required.");
        }

        Database::beginTransaction();
        try {
            $receiptNumber = BootstrapService::generateReceiptNumber();
            $now = date('Y-m-d H:i:s');

            $paymentId = Database::insert(
                "INSERT INTO `ipd_payments` (
                    `receipt_number`, `admission_id`, `bill_id`, `payment_date`,
                    `amount`, `payment_type`, `payment_method`, `transaction_reference`,
                    `notes`, `received_by`, `created_at`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $receiptNumber, $admissionId, $billId, $now,
                    $amount, $type, $method, $ref,
                    $notes, $authUserId
                ]
            );

            // If linked to a specific bill, update the bill's paid amount & status
            if ($billId) {
                $bill = Database::fetchRow("SELECT * FROM ipd_bills WHERE id = ?", [$billId]);
                if ($bill) {
                    $newPaid = (float)$bill['amount_paid'] + $amount;
                    $newBal = max(0, (float)$bill['patient_payable'] - $newPaid);
                    $newStatus = ($newBal <= 0) ? 'paid' : 'partially_paid';

                    Database::execute(
                        "UPDATE `ipd_bills` SET `amount_paid` = ?, `balance_due` = ?, `status` = ? WHERE `id` = ?",
                        [$newPaid, $newBal, $newStatus, $billId]
                    );
                }
            }

            BootstrapService::logAudit(
                $admissionId,
                'PAYMENT_RECORDED',
                'ipd_payments',
                $paymentId,
                "Received {$method} payment of {$amount} (Receipt {$receiptNumber})",
                ['receipt_number' => $receiptNumber, 'amount' => $amount, 'method' => $method]
            );

            Database::commit();

            return [
                'success' => true,
                'message' => "Payment of {$amount} recorded successfully with Receipt No: {$receiptNumber}",
                'payment_id' => $paymentId,
                'receipt_number' => $receiptNumber
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
}
