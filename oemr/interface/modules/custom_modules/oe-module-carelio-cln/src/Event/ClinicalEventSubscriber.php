<?php

namespace OpenEMR\Modules\CarelioCln\Event;

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Events\Encounter\EncounterButtonEvent;
use OpenEMR\Events\Encounter\EncounterMenuEvent;
use OpenEMR\Events\Encounter\LoadEncounterFormFilterEvent;
use OpenEMR\Modules\CarelioCore\Services\ClinicalDocumentationService;
use OpenEMR\Modules\CarelioCore\Services\ContentApprovalService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Integrates Carelio CLN governance and follow-up workflows directly into
 * OpenEMR's native Encounter UI using Symfony events.
 */
class ClinicalEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            EncounterMenuEvent::MENU_RENDER => ['onEncounterMenuRender', -10],
            LoadEncounterFormFilterEvent::EVENT_NAME => ['onLoadEncounterFormFilter', 10],
            EncounterButtonEvent::BUTTON_RENDER => ['onEncounterButtonRender', 10],
        ];
    }

    /**
     * Ensure Carelio LBF layouts exist in layout_group_properties if not yet seeded
     */
    private static function ensureLayoutsSeeded(): void
    {
        $exists = sqlQuery(
            "SELECT `grp_form_id` FROM `layout_group_properties` WHERE `grp_form_id` = ? AND `grp_group_id` = '' LIMIT 1",
            [ClinicalDocumentationService::LBF_CONSULT_ID]
        );
        if (!$exists && class_exists(ClinicalDocumentationService::class)) {
            ClinicalDocumentationService::seedSpecialtyLbfLayouts(1);
        }
        if (class_exists(ContentApprovalService::class)) {
            ContentApprovalService::syncOpenEmrLbfLayouts(1);
            $approvedForms = sqlStatement(
                "SELECT * FROM `hms_content_approval` WHERE `content_type` = 'form' AND `status` = 'approved'"
            );
            while ($af = sqlFetchArray($approvedForms)) {
                ContentApprovalService::ensureOpenEmrLbfExists($af);
            }
        }
    }

    /**
     * Check whether an LBF form is governed in hms_content_approval and approved.
     * Also enforces "edit to approved template returns it to draft" if modified in OpenEMR Layout Editor.
     */
    private static function checkLbfGovernance(string $formName): array
    {
        if (!str_starts_with($formName, 'LBF')) {
            return ['governed' => false, 'approved' => true];
        }

        // Match either payload containing lbf_form_id or direct content_code match
        $row = sqlQuery(
            "SELECT `id`, `content_code`, `title`, `version`, `status`, `approved_at`
             FROM `hms_content_approval`
             WHERE `content_type` = 'form'
               AND `status` != 'superseded'
               AND (`content_code` = ? OR `payload` LIKE ?)
             ORDER BY `id` DESC
             LIMIT 1",
            [$formName, '%' . $formName . '%']
        );

        if (!$row) {
            return ['governed' => false, 'approved' => true];
        }

        // Auto-demote to draft if layout_group_properties was edited in OpenEMR after approval
        if ($row['status'] === 'approved' && !empty($row['approved_at'])) {
            $grp = sqlQuery(
                "SELECT `grp_last_update` FROM `layout_group_properties` WHERE `grp_form_id` = ? AND `grp_group_id` = '' LIMIT 1",
                [$formName]
            );
            if (!empty($grp['grp_last_update']) && strtotime($grp['grp_last_update']) > strtotime($row['approved_at'])) {
                $parts = explode('.', (string)$row['version']);
                $newVersion = ((int)($parts[0] ?? 1)) . '.' . (((int)($parts[1] ?? 0)) + 1);
                sqlStatement("UPDATE `hms_content_approval` SET `status` = 'superseded' WHERE `id` = ?", [$row['id']]);
                sqlInsert(
                    "INSERT INTO `hms_content_approval` (`content_type`, `content_code`, `version`, `title`, `payload`, `status`, `author_id`, `created_at`)
                     SELECT `content_type`, `content_code`, ?, `title`, `payload`, 'draft', `author_id`, NOW()
                     FROM `hms_content_approval` WHERE `id` = ?",
                    [$newVersion, $row['id']]
                );
                return ['governed' => true, 'approved' => false, 'status' => 'draft', 'title' => $row['title'], 'code' => $row['content_code']];
            }
        }

        return [
            'governed' => true,
            'approved' => ($row['status'] === 'approved'),
            'status' => $row['status'],
            'title' => $row['title'],
            'code' => $row['content_code'],
        ];
    }

    /**
     * Filter unapproved governed LBF forms out of OpenEMR's native Encounter forms menu
     */
    public function onEncounterMenuRender(EncounterMenuEvent $event): void
    {
        self::ensureLayoutsSeeded();

        $menu = $event->getMenuData();
        if (empty($menu)) {
            return;
        }

        foreach ($menu as $category => $catData) {
            if (empty($catData['children']) || !is_array($catData['children'])) {
                continue;
            }
            foreach ($catData['children'] as $idx => $item) {
                $formDir = (string)($item['directory'] ?? '');
                if (str_starts_with($formDir, 'LBF')) {
                    $gov = self::checkLbfGovernance($formDir);
                    if ($gov['governed'] && !$gov['approved']) {
                        unset($menu[$category]['children'][$idx]);
                    }
                }
            }
            $menu[$category]['children'] = array_values($menu[$category]['children']);
        }

        $event->setMenuData($menu);
    }

    /**
     * Hard governance gate: block loading any draft/pending/rejected governed LBF form
     */
    public function onLoadEncounterFormFilter(LoadEncounterFormFilterEvent $event): void
    {
        $formName = (string)$event->getFormName();
        $gov = self::checkLbfGovernance($formName);

        if ($gov['governed'] && !$gov['approved']) {
            $projectDir = OEGlobalsBag::getInstance()->getKernel()->getProjectDir();
            $modDir = $projectDir . '/interface/modules/custom_modules/oe-module-carelio-cln/';
            $event->setDir($modDir);
            $event->setPageName('blocked_form.php');
        }
    }

    /**
     * Inject "Carelio: Follow-Up Recall" button into OpenEMR's native Encounter toolbar
     */
    public function onEncounterButtonRender(EncounterButtonEvent $event): void
    {
        $webRoot = $GLOBALS['webroot'] ?? '/carelioemr/oemr';
        $url = htmlspecialchars($webRoot . '/interface/modules/custom_modules/oe-module-carelio-cln/recall_from_encounter.php');
        $btn = '<a href="' . $url . '" class="btn btn-warning btn-sm ml-1" target="_blank" title="Schedule Follow-Up Recall from Encounter">'
             . '<i class="fa fa-bell mr-1"></i> Follow-Up Recall</a>';
        $existing = (string)$event->displayButton();
        $event->setButton($existing . $btn);
    }
}
