<?php

namespace OpenEMR\Modules\CarelioCore\Services;

class ClinicalDocumentationService
{
    public const LBF_ROUNDS_ID = 'LBFcarelio_rounds';
    public const LBF_CONSULT_ID = 'LBFcarelio_consult';

    /**
     * Seed Carelio Inpatient Ward Round & Specialty Consult LBF layouts into OpenEMR's LBF engine
     * and ensure matching governance records exist in hms_content_approval.
     */
    public static function seedSpecialtyLbfLayouts(int $userId = 1): array
    {
        $layouts = [
            [
                'form_id' => self::LBF_ROUNDS_ID,
                'title' => 'Carelio Inpatient Daily Ward Rounds',
                'subtitle' => 'Multidisciplinary Inpatient Rounding Sheet',
                'fields' => [
                    ['field_id' => 'attending_md', 'title' => 'Attending Physician', 'seq' => 10, 'data_type' => 2, 'fld_length' => 40],
                    ['field_id' => 'consulting_spec', 'title' => 'Consulting Specialists', 'seq' => 20, 'data_type' => 2, 'fld_length' => 60],
                    ['field_id' => 'problem_list', 'title' => 'Active Problem List', 'seq' => 30, 'data_type' => 3, 'fld_length' => 60, 'fld_rows' => 3],
                    ['field_id' => 'daily_care_plan', 'title' => 'Daily Care Plan & Goals', 'seq' => 40, 'data_type' => 3, 'fld_length' => 60, 'fld_rows' => 3],
                    ['field_id' => 'edd_date', 'title' => 'Estimated Discharge Date (EDD)', 'seq' => 50, 'data_type' => 4, 'fld_length' => 12],
                ]
            ],
            [
                'form_id' => self::LBF_CONSULT_ID,
                'title' => 'Carelio Specialty Consult Note',
                'subtitle' => 'Inpatient / Outpatient Specialist Consultation',
                'fields' => [
                    ['field_id' => 'consult_specialty', 'title' => 'Consulting Specialty', 'seq' => 10, 'data_type' => 2, 'fld_length' => 40],
                    ['field_id' => 'consult_question', 'title' => 'Clinical Question', 'seq' => 20, 'data_type' => 3, 'fld_length' => 60, 'fld_rows' => 2],
                    ['field_id' => 'consult_impression', 'title' => 'Impression & Recommendations', 'seq' => 30, 'data_type' => 3, 'fld_length' => 60, 'fld_rows' => 4],
                ]
            ]
        ];

        $seededFields = 0;
        foreach ($layouts as $lbf) {
            $exists = sqlQuery(
                "SELECT `grp_form_id` FROM `layout_group_properties` WHERE `grp_form_id` = ? AND `grp_group_id` = '' LIMIT 1",
                [$lbf['form_id']]
            );
            if (!$exists) {
                sqlStatement(
                    "INSERT INTO `layout_group_properties` (`grp_form_id`, `grp_group_id`, `grp_title`, `grp_subtitle`, `grp_mapping`, `grp_seq`, `grp_activity`, `grp_columns`)
                     VALUES (?, '', ?, ?, 'Clinical', 1, 1, 2)",
                    [$lbf['form_id'], $lbf['title'], $lbf['subtitle']]
                );
                sqlStatement(
                    "INSERT INTO `layout_group_properties` (`grp_form_id`, `grp_group_id`, `grp_title`, `grp_subtitle`, `grp_mapping`, `grp_seq`, `grp_activity`, `grp_columns`)
                     VALUES (?, '1', 'Clinical Details', '', 'Clinical', 1, 1, 2)",
                    [$lbf['form_id']]
                );
            }

            foreach ($lbf['fields'] as $f) {
                $fExists = sqlQuery(
                    "SELECT `field_id` FROM `layout_options` WHERE `form_id` = ? AND `field_id` = ? LIMIT 1",
                    [$lbf['form_id'], $f['field_id']]
                );
                if (!$fExists) {
                    sqlStatement(
                        "INSERT INTO `layout_options` (`form_id`, `field_id`, `group_id`, `title`, `seq`, `data_type`, `uor`, `fld_length`, `fld_rows`, `source`)
                         VALUES (?, ?, '1', ?, ?, ?, 1, ?, ?, 'F')",
                        [$lbf['form_id'], $f['field_id'], $f['title'], $f['seq'], $f['data_type'], $f['fld_length'], $f['fld_rows'] ?? 0]
                    );
                    $seededFields++;
                }
            }
        }

        // Ensure a draft specialty consult template exists in hms_content_approval for Medical Director sign-off
        $consultAppr = sqlQuery(
            "SELECT `id` FROM `hms_content_approval` WHERE `content_code` = 'FORM-SPEC-CONSULT' LIMIT 1"
        );
        if (!$consultAppr) {
            ContentApprovalService::createItem([
                'content_type' => 'form',
                'content_code' => 'FORM-SPEC-CONSULT',
                'version' => '1.0',
                'title' => 'Multi-Specialty Consultation LBF Template (LBFcarelio_consult)',
                'payload' => json_encode([
                    'lbf_form_id' => self::LBF_CONSULT_ID,
                    'sections' => ['Consulting Specialty', 'Clinical Question', 'Impression & Recommendations']
                ])
            ], $userId);
        }

        return ['success' => true, 'seeded_fields' => $seededFields, 'message' => "Specialty LBF layouts verified ($seededFields new fields seeded)."];
    }

    /**
     * Compute CLN Reports (Spec Section 18.4):
     * - Encounters by clinic / facility & class
     * - Templates awaiting Medical Director approval
     */
    public static function getClnReports(): array
    {
        $encRows = sqlStatement(
            "SELECT COALESCE(NULLIF(facility, ''), 'Unassigned Clinic') AS clinic_name,
                    class_code,
                    COUNT(*) AS total_encounters,
                    MAX(date) AS last_visit
             FROM `form_encounter`
             GROUP BY clinic_name, class_code
             ORDER BY total_encounters DESC"
        );
        $encountersByClinic = [];
        while ($row = sqlFetchArray($encRows)) {
            $encountersByClinic[] = $row;
        }

        $awaitingRows = sqlStatement(
            "SELECT ca.*, CONCAT(u.fname, ' ', u.lname) AS author_name
             FROM `hms_content_approval` ca
             LEFT JOIN `users` u ON u.id = ca.author_id
             WHERE ca.status IN ('draft', 'pending_approval')
             ORDER BY ca.id DESC"
        );
        $awaitingApproval = [];
        while ($row = sqlFetchArray($awaitingRows)) {
            $awaitingApproval[] = $row;
        }

        $counts = [
            'approved_templates' => (int)(sqlQuery("SELECT COUNT(*) AS cnt FROM `hms_content_approval` WHERE `content_type` = 'form' AND `status` = 'approved'")['cnt'] ?? 0),
            'awaiting_approval' => count($awaitingApproval),
            'total_encounters' => (int)(sqlQuery("SELECT COUNT(*) AS cnt FROM `form_encounter`")['cnt'] ?? 0),
        ];

        return [
            'encounters_by_clinic' => $encountersByClinic,
            'awaiting_approval' => $awaitingApproval,
            'counts' => $counts,
        ];
    }
}
