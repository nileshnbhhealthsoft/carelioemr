<?php

namespace OpenEMR\Modules\Ipd\Controller;

use Exception;
use Throwable;
use OpenEMR\Modules\Ipd\BootstrapService;

/**
 * Main Central Request Dispatcher for Inpatient (IPD) API & AJAX Actions
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class IpdDispatch
{
    private AdmissionController $admissionCtrl;
    private BedController $bedCtrl;
    private ClinicalController $clinicalCtrl;
    private NursingController $nursingCtrl;
    private OtController $otCtrl;
    private BillingController $billingCtrl;
    private DischargeController $dischargeCtrl;
    private EmergencyController $emergencyCtrl;
    private AttendantController $attendantCtrl;
    private ReportController $reportCtrl;

    public function __construct()
    {
        $this->admissionCtrl = new AdmissionController();
        $this->bedCtrl = new BedController();
        $this->clinicalCtrl = new ClinicalController();
        $this->nursingCtrl = new NursingController();
        $this->otCtrl = new OtController();
        $this->billingCtrl = new BillingController();
        $this->dischargeCtrl = new DischargeController();
        $this->emergencyCtrl = new EmergencyController();
        $this->attendantCtrl = new AttendantController();
        $this->reportCtrl = new ReportController();
    }

    /**
     * Dispatch an action and return JSON response
     */
    public function dispatch(string $action, array $request): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $result = match ($action) {
                // Patient Search & Autocomplete
                'search_patients' => BootstrapService::searchPatients($request['query'] ?? '', 15),
                'get_patient' => BootstrapService::getPatient((int)($request['pid'] ?? 0)),

                // Admission Actions
                'list_admissions' => $this->admissionCtrl->listAdmissions(
                    $request['status'] ?? null,
                    !empty($request['ward_id']) ? (int)$request['ward_id'] : null,
                    $request['search'] ?? null
                ),
                'get_admission' => $this->admissionCtrl->getAdmission((int)($request['id'] ?? 0)),
                'create_admission' => $this->admissionCtrl->createAdmission($request),
                'transfer_bed' => $this->admissionCtrl->transferBed(
                    (int)($request['admission_id'] ?? 0),
                    (int)($request['new_bed_id'] ?? 0),
                    $request['transfer_reason'] ?? ''
                ),

                // Bed & Ward Actions
                'get_bed_matrix' => $this->bedCtrl->getBedMatrix(!empty($request['ward_id']) ? (int)$request['ward_id'] : null),
                'get_available_beds' => $this->bedCtrl->getAvailableBeds(!empty($request['ward_id']) ? (int)$request['ward_id'] : null),
                'update_bed_status' => $this->bedCtrl->updateBedStatus(
                    (int)($request['bed_id'] ?? 0),
                    $request['status'] ?? 'available',
                    $request['notes'] ?? null
                ),
                'save_ward' => $this->bedCtrl->saveWard($request),
                'save_bed' => $this->bedCtrl->saveBed($request),

                // Clinical & Doctor Actions
                'add_doctor_round' => $this->clinicalCtrl->addDoctorRound($request),
                'add_medication_order' => $this->clinicalCtrl->addMedicationOrder($request),
                'update_medication_status' => $this->clinicalCtrl->updateMedicationStatus(
                    (int)($request['order_id'] ?? 0),
                    $request['status'] ?? 'completed'
                ),
                'record_intake_output' => $this->clinicalCtrl->recordIntakeOutput($request),
                'get_intake_output_summary' => $this->clinicalCtrl->getIntakeOutputSummary((int)($request['admission_id'] ?? 0)),

                // Nursing Actions
                'record_vitals' => $this->nursingCtrl->recordVitals($request),
                'administer_medication' => $this->nursingCtrl->administerMedication($request),
                'add_nursing_note' => $this->nursingCtrl->addNursingNote($request),
                'record_risk_assessment' => $this->nursingCtrl->recordRiskAssessment($request),
                'get_duty_roster' => $this->nursingCtrl->getDutyRoster($request['date'] ?? null, !empty($request['ward_id']) ? (int)$request['ward_id'] : null),
                'save_shift_assignment' => $this->nursingCtrl->saveShiftAssignment($request),

                // OT & Surgeries
                'list_theatres' => $this->otCtrl->listTheatres(),
                'list_surgeries' => $this->otCtrl->listSurgeries(
                    $request['status'] ?? null,
                    !empty($request['ot_id']) ? (int)$request['ot_id'] : null,
                    $request['date'] ?? null
                ),
                'schedule_surgery' => $this->otCtrl->scheduleSurgery($request),
                'update_surgery_status' => $this->otCtrl->updateSurgeryStatus(
                    (int)($request['surgery_id'] ?? 0),
                    $request['status'] ?? 'completed',
                    $request
                ),
                'save_safety_checklist' => $this->otCtrl->saveSafetyChecklist((int)($request['surgery_id'] ?? 0), $request['checklist'] ?? []),

                // Billing & Payments
                'list_tariffs' => $this->billingCtrl->listTariffs(),
                'list_packages' => $this->billingCtrl->listPackages(),
                'get_billing_statement' => $this->billingCtrl->getBillingStatement((int)($request['admission_id'] ?? 0)),
                'generate_bill' => $this->billingCtrl->generateBill($request),
                'record_payment' => $this->billingCtrl->recordPayment($request),

                // Discharge Workflow
                'get_discharge_status' => $this->dischargeCtrl->getDischargeStatus((int)($request['admission_id'] ?? 0)),
                'update_clearance' => $this->dischargeCtrl->updateClearance(
                    (int)($request['admission_id'] ?? 0),
                    $request['department'] ?? 'doctor',
                    (int)($request['cleared'] ?? 0)
                ),
                'complete_discharge' => $this->dischargeCtrl->completeDischarge($request),
                'get_discharge_summary' => $this->dischargeCtrl->getDischargeSummary((int)($request['admission_id'] ?? 0)),

                // Emergency & Casualty Triage
                'list_triage' => $this->emergencyCtrl->listTriage($request['status'] ?? null, $request['level'] ?? null),
                'register_triage' => $this->emergencyCtrl->registerTriage($request),
                'convert_triage_to_admission' => $this->emergencyCtrl->convertToAdmission(
                    (int)($request['triage_id'] ?? 0),
                    (int)($request['bed_id'] ?? 0),
                    (int)($request['doctor_id'] ?? 0),
                    $request['provisional_diagnosis'] ?? null
                ),

                // Attendants & Passes
                'list_passes' => $this->attendantCtrl->listPasses(!empty($request['admission_id']) ? (int)$request['admission_id'] : null, $request['status'] ?? null),
                'issue_pass' => $this->attendantCtrl->issuePass($request),
                'update_pass_status' => $this->attendantCtrl->updatePassStatus((int)($request['pass_id'] ?? 0), $request['status'] ?? 'returned'),

                // Dashboard & KPIs
                'get_hospital_kpis' => $this->reportCtrl->getHospitalKpis(),

                default => throw new Exception("Unknown action: {$action}")
            };

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}
