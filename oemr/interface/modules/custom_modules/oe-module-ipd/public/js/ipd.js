/**
 * Inpatient (IPD) & Hospital Management Client Application
 * CarelioEMR / OpenEMR
 */

const IPD = {
    apiBase: 'index.php',

    init: function() {
        this.bindTabs();
        this.bindModals();
        this.bindPatientSearch();
        this.bindForms();
    },

    // Tab Navigation
    bindTabs: function() {
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'dashboard';

        this.switchTab(currentTab);

        document.querySelectorAll('.ipd-tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const target = btn.getAttribute('data-tab');
                this.switchTab(target);
                const url = new URL(window.location);
                url.searchParams.set('tab', target);
                window.history.pushState({}, '', url);
            });
        });
    },

    switchTab: function(tabId) {
        document.querySelectorAll('.ipd-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.ipd-view-panel').forEach(p => p.classList.remove('active'));

        const activeBtn = document.querySelector(`.ipd-tab-btn[data-tab="${tabId}"]`);
        const activePanel = document.getElementById(`panel-${tabId}`);

        if (activeBtn) activeBtn.classList.add('active');
        if (activePanel) activePanel.classList.add('active');
    },

    // Modal Manager
    openModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    },

    closeModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    },

    bindModals: function() {
        document.querySelectorAll('.ipd-modal-backdrop').forEach(backdrop => {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    backdrop.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.getAttribute('data-close-modal');
                this.closeModal(modalId);
            });
        });
    },

    // Live Patient Search / Autocomplete
    bindPatientSearch: function() {
        const searchInput = document.getElementById('search-patient-input');
        const resultsBox = document.getElementById('patient-search-results');
        const pidHidden = document.getElementById('admission-pid');
        const patientDetailsBox = document.getElementById('selected-patient-details');

        if (!searchInput || !resultsBox) return;

        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const query = searchInput.value.trim();
            if (query.length < 1) {
                resultsBox.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`${this.apiBase}?action=search_patients&query=${encodeURIComponent(query)}`)
                    .then(r => r.json())
                    .then(res => {
                        if (res.success && res.data.length > 0) {
                            resultsBox.innerHTML = '';
                            res.data.forEach(p => {
                                const item = document.createElement('div');
                                item.className = 'ipd-search-item';
                                item.innerHTML = `<strong>${p.fname} ${p.lname}</strong> (PID: ${p.pid}) | DOB: ${p.DOB || 'N/A'} | Phone: ${p.phone_cell || 'N/A'}`;
                                item.addEventListener('click', () => {
                                    searchInput.value = `${p.fname} ${p.lname} (PID: ${p.pid})`;
                                    if (pidHidden) pidHidden.value = p.pid;
                                    if (patientDetailsBox) {
                                        patientDetailsBox.innerHTML = `
                                            <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:10px 14px; border-radius:8px; margin-top:8px;">
                                                <strong>Selected Patient:</strong> ${p.fname} ${p.lname} | <strong>PID:</strong> ${p.pid} | <strong>Gender:</strong> ${p.sex || 'N/A'} | <strong>DOB:</strong> ${p.DOB || 'N/A'} | <strong>Contact:</strong> ${p.phone_cell || p.phone_home || 'N/A'}
                                            </div>
                                        `;
                                    }
                                    resultsBox.style.display = 'none';
                                });
                                resultsBox.appendChild(item);
                            });
                            resultsBox.style.display = 'block';
                        } else {
                            resultsBox.innerHTML = '<div class="ipd-search-item text-muted">No matching patient found in OpenEMR.</div>';
                            resultsBox.style.display = 'block';
                        }
                    })
                    .catch(err => console.error(err));
            }, 300);
        });

        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
                resultsBox.style.display = 'none';
            }
        });
    },

    // Fast Bed Status Update (e.g. from cleaning to available)
    updateBedStatus: function(bedId, newStatus) {
        if (!confirm(`Mark bed status as ${newStatus}?`)) return;

        const formData = new FormData();
        formData.append('bed_id', bedId);
        formData.append('status', newStatus);

        fetch(`${this.apiBase}?action=update_bed_status`, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert(res.data.message || 'Bed status updated.');
                window.location.reload();
            } else {
                alert(res.error || 'Failed to update bed status.');
            }
        })
        .catch(err => alert('Network error: ' + err.message));
    },

    // Open Bed Transfer Modal
    openBedTransferModal: function(admissionId, currentBedNum) {
        document.getElementById('transfer-admission-id').value = admissionId;
        document.getElementById('transfer-current-bed-label').textContent = currentBedNum;
        this.openModal('modal-bed-transfer');
    },

    // Open Add Vitals Modal
    openAddVitalsModal: function(admissionId, patientName, bedNum) {
        document.getElementById('vitals-admission-id').value = admissionId;
        document.getElementById('vitals-patient-label').textContent = `${patientName} (${bedNum})`;
        this.openModal('modal-add-vitals');
    },

    // Open Doctor Round Modal
    openDoctorRoundModal: function(admissionId, patientName) {
        document.getElementById('round-admission-id').value = admissionId;
        document.getElementById('round-patient-label').textContent = patientName;
        this.openModal('modal-doctor-round');
    },

    // Open Prescribe Medication Modal
    openPrescribeMedModal: function(admissionId, patientName) {
        document.getElementById('med-admission-id').value = admissionId;
        document.getElementById('med-patient-label').textContent = patientName;
        this.openModal('modal-prescribe-med');
    },

    // Open MAR Administration Modal
    openMarModal: function(orderId, admissionId, medicineName, dosage) {
        document.getElementById('mar-order-id').value = orderId;
        document.getElementById('mar-admission-id').value = admissionId;
        document.getElementById('mar-medicine-label').textContent = `${medicineName} (${dosage})`;
        document.getElementById('mar-dosage-input').value = dosage;
        this.openModal('modal-administer-mar');
    },

    // Open Schedule Surgery Modal
    openScheduleSurgeryModal: function(admissionId, patientName) {
        if (admissionId) {
            document.getElementById('surgery-admission-id').value = admissionId;
            document.getElementById('surgery-patient-label').textContent = patientName || `Admission #${admissionId}`;
        }
        this.openModal('modal-schedule-surgery');
    },

    // Open Discharge Modal
    openDischargeModal: function(admissionId, patientName, ipdNumber) {
        document.getElementById('discharge-admission-id').value = admissionId;
        document.getElementById('discharge-patient-label').textContent = `${patientName} (${ipdNumber})`;
        this.openModal('modal-complete-discharge');
    },

    // Open Issue Attendant Pass Modal
    openAttendantPassModal: function(admissionId, patientName) {
        document.getElementById('pass-admission-id').value = admissionId;
        document.getElementById('pass-patient-label').textContent = patientName;
        this.openModal('modal-issue-pass');
    },

    // Update Discharge Clearance (Doctor, Nursing, Pharmacy, Billing)
    updateClearance: function(admissionId, dept, cleared) {
        const formData = new FormData();
        formData.append('admission_id', admissionId);
        formData.append('department', dept);
        formData.append('cleared', cleared ? 1 : 0);

        fetch(`${this.apiBase}?action=update_clearance`, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert(res.data.message || 'Clearance updated.');
                window.location.reload();
            } else {
                alert(res.error || 'Failed to update clearance.');
            }
        })
        .catch(err => alert('Error: ' + err.message));
    },

    // 1-Click Convert Emergency Triage to IPD Admission
    openTriageConvertModal: function(triageId, triageNumber, patientName, pid) {
        document.getElementById('convert-triage-id').value = triageId;
        document.getElementById('convert-triage-label').textContent = `${patientName} (Triage: ${triageNumber})`;
        this.openModal('modal-triage-convert');
    },

    // Bind all generic AJAX form submissions
    bindForms: function() {
        document.querySelectorAll('form[data-ipd-action]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const action = form.getAttribute('data-ipd-action');
                const formData = new FormData(form);

                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Processing...';
                }

                fetch(`${this.apiBase}?action=${action}`, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert(res.data.message || 'Operation completed successfully.');
                        window.location.reload();
                    } else {
                        alert(res.error || 'Error completing request.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Submit';
                        }
                    }
                })
                .catch(err => {
                    alert('Submission error: ' + err.message);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit';
                    }
                });
            });
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    IPD.init();
});
