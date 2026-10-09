<?php

namespace OpenEMR\Modules\ClaimForms;

/** Generates the PDF for a saved claim and records where it went. */
class ClaimService
{
    /**
     * @return array{ok:bool,errors:string[],warnings:string[],revision?:int}
     */
    public static function generate(int $claimId, int $userId): array
    {
        $claim = ClaimRepository::get($claimId);
        if (!$claim) {
            return ['ok' => false, 'errors' => ['Claim not found'], 'warnings' => []];
        }
        $formId = $claim['form_id'];
        $map = FormRegistry::map($formId);
        $images = [
            'user_signature' => SignatureStore::path($userId, 'signature'),
            'user_stamp' => SignatureStore::path($userId, 'stamp'),
        ];
        $errors = Validator::validate($map, $claim['payload'], $images);
        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'warnings' => []];
        }

        $result = PdfRenderer::render(
            $map,
            FormRegistry::background($formId),
            $claim['payload'],
            $images,
            date('Y-m-d'),
            Storage::root() . '/tmp'
        );

        $pid = (int)$claim['pid'];
        $rev = (int)$claim['revision'] + 1;
        $file = Storage::claimDir($pid) . "/claim_{$claimId}_r{$rev}.pdf";
        if (file_put_contents($file, $result['pdf']) === false) {
            return ['ok' => false, 'errors' => ['Could not write the PDF'], 'warnings' => []];
        }
        $docId = self::registerDocument($pid, $formId, $claimId, $rev, $result['pdf']);
        $revision = ClaimRepository::markGenerated($claimId, basename($file), $docId);
        ClaimRepository::event($claimId, $userId, 'generated', "r$revision");
        self::audit($userId, $pid, "Claim form $claimId generated (r$revision)");

        return ['ok' => true, 'errors' => [], 'warnings' => $result['warnings'], 'revision' => $revision];
    }

    /** Best effort: also file the PDF in the patient's Documents. The claim works without it. */
    private static function registerDocument(int $pid, string $formId, int $claimId, int $rev, string $pdf): ?int
    {
        try {
            if (!class_exists('\Document')) {
                require_once rtrim((string)$GLOBALS['fileroot'], '/') . '/library/classes/Document.class.php';
            }
            $title = FormRegistry::definition($formId)['documentCategory'] ?? 'Insurance';
            $cat = Db::one("SELECT id FROM categories WHERE name = ? LIMIT 1", [$title]);
            $catId = (int)($cat['id'] ?? 1);
            $doc = new \Document();
            $name = "{$formId}_claim{$claimId}_r{$rev}.pdf";
            $err = $doc->createDocument($pid, $catId, $name, 'application/pdf', $pdf);
            return $err ? null : (int)$doc->get_id();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function audit(int $userId, int $pid, string $msg): void
    {
        try {
            \OpenEMR\Common\Logging\EventAuditLogger::instance()->newEvent(
                'claim-forms',
                (string)(\claimforms_session('authUser') ?? ''),
                (string)(\claimforms_session('authProvider') ?? ''),
                1,
                $msg,
                $pid
            );
        } catch (\Throwable $e) {
            // the module's own claimforms_event table still has the record
        }
    }
}
