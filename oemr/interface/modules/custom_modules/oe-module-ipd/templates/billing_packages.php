<?php
/** @var array $billsList */
/** @var array $paymentsList */
/** @var array $tariffsList */
/** @var array $packagesList */
?>

<div class="ipd-form-row" style="margin-bottom: 24px;">
    <!-- Inpatient Invoices -->
    <div style="grid-column: span 2;">
        <div class="ipd-card">
            <div class="ipd-card-header">
                <h2>
                    <i class="fa fa-file-invoice-dollar text-primary"></i>
                    Inpatient Bills & Invoices
                </h2>
                <div style="display: flex; gap: 8px;">
                    <button class="ipd-btn ipd-btn-primary" onclick="IPD.openModal('modal-generate-bill')">
                        <i class="fa fa-receipt"></i> Generate Inpatient Bill
                    </button>
                    <button class="ipd-btn ipd-btn-success" onclick="IPD.openModal('modal-record-payment')">
                        <i class="fa fa-hand-holding-usd"></i> Record Payment / Deposit
                    </button>
                </div>
            </div>
            <div class="ipd-card-body" style="padding: 0;">
                <div style="overflow-x: auto;">
                    <table class="ipd-table">
                        <thead>
                            <tr>
                                <th>Bill Number</th>
                                <th>Patient / IPD</th>
                                <th>Date & Type</th>
                                <th>Gross Amount</th>
                                <th>Discount / Tax</th>
                                <th>Net Payable</th>
                                <th>Paid</th>
                                <th>Balance Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($billsList)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 24px; color: var(--ipd-slate);">
                                        No inpatient invoices generated yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($billsList as $bill): 
                                    $status = strtolower($bill['status']);
                                    $badge = match($status) {
                                        'paid' => 'green',
                                        'partially_paid' => 'yellow',
                                        'pending' => 'orange',
                                        'draft' => 'blue',
                                        default => 'blue'
                                    };
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--ipd-primary);"><?php echo htmlspecialchars($bill['bill_number']); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(($bill['fname'] ?? '') . ' ' . ($bill['lname'] ?? '')); ?></strong>
                                            <div style="font-size: 0.75rem; color: var(--ipd-slate);">
                                                <?php echo htmlspecialchars($bill['ipd_number'] ?? ''); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?php echo date('M d, Y', strtotime($bill['bill_date'])); ?></div>
                                            <span class="ipd-badge" style="background:#f1f5f9; color:#475569;"><?php echo htmlspecialchars(ucfirst($bill['bill_type'])); ?></span>
                                        </td>
                                        <td>$<?php echo number_format((float)$bill['gross_amount'], 2); ?></td>
                                        <td>
                                            <small class="text-muted">-$<?php echo number_format((float)$bill['discount_amount'], 2); ?></small> / 
                                            <small class="text-muted">+$<?php echo number_format((float)$bill['tax_amount'], 2); ?></small>
                                        </td>
                                        <td><strong>$<?php echo number_format((float)$bill['patient_payable'], 2); ?></strong></td>
                                        <td><span style="color: var(--ipd-success); font-weight: 700;">$<?php echo number_format((float)$bill['amount_paid'], 2); ?></span></td>
                                        <td>
                                            <?php if ((float)$bill['balance_due'] > 0): ?>
                                                <strong style="color: var(--ipd-danger);">$<?php echo number_format((float)$bill['balance_due'], 2); ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">$0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="ipd-badge <?php echo $badge; ?>">
                                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status))); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hospital Packages & Tariffs Grid -->
<div class="ipd-form-row">
    <!-- Predefined Hospital Packages -->
    <div>
        <div class="ipd-card">
            <div class="ipd-card-header">
                <h2>
                    <i class="fa fa-box text-primary"></i>
                    Predefined Hospital Treatment Packages
                </h2>
            </div>
            <div class="ipd-card-body" style="padding: 0;">
                <table class="ipd-table">
                    <thead>
                        <tr>
                            <th>Package Name</th>
                            <th>Stay</th>
                            <th>Total Tariff</th>
                            <th>Included Services</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($packagesList)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:16px;">No packages configured.</td></tr>
                        <?php else: ?>
                            <?php foreach ($packagesList as $pkg): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($pkg['package_name']); ?></strong>
                                        <div style="font-size:0.75rem; color:var(--ipd-slate);"><?php echo htmlspecialchars($pkg['package_code']); ?></div>
                                    </td>
                                    <td><?php echo (int)$pkg['duration_days']; ?> Days</td>
                                    <td><strong style="color:var(--ipd-primary);">$<?php echo number_format((float)$pkg['total_price'], 2); ?></strong></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($pkg['inclusions'] ?? '-'); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tariff Catalog Master -->
    <div>
        <div class="ipd-card">
            <div class="ipd-card-header">
                <h2>
                    <i class="fa fa-list-alt text-primary"></i>
                    Hospital Charge Catalog & Standard Tariffs
                </h2>
            </div>
            <div class="ipd-card-body" style="padding: 0; max-height: 400px; overflow-y: auto;">
                <table class="ipd-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Service Name</th>
                            <th>Standard Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tariffsList)): ?>
                            <tr><td colspan="3" style="text-align:center; padding:16px;">No tariffs configured.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tariffsList as $t): ?>
                                <tr>
                                    <td><span class="ipd-badge" style="background:#f1f5f9; color:#475569;"><?php echo htmlspecialchars($t['category']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($t['service_name']); ?></strong></td>
                                    <td><strong style="color:var(--ipd-dark);">$<?php echo number_format((float)$t['standard_rate'], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
