<?php

require_once __DIR__ . '/_bootstrap.php';

use OpenEMR\Modules\ClaimForms\ClaimRepository;
use OpenEMR\Modules\ClaimForms\ClaimService;
use OpenEMR\Modules\ClaimForms\DraftBuilder;
use OpenEMR\Modules\ClaimForms\EyeCodes;
use OpenEMR\Modules\ClaimForms\FormRegistry;
use OpenEMR\Modules\ClaimForms\Sanitizer;
use OpenEMR\Modules\ClaimForms\SignatureStore;

$pid = claimforms_pid();
$uid = claimforms_user_id();
if ($pid <= 0 || $uid <= 0) {
    claimforms_json(['error' => 'No active patient'], 400);
}

$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$body = [];
if ($isPost) {
    $body = json_decode((string)file_get_contents('php://input'), true) ?: [];
    claimforms_verify_csrf($body['csrf_token_form'] ?? null);
}
$action = $isPost ? (string)($body['action'] ?? '') : (string)($_GET['action'] ?? '');

/** Loads a claim and refuses anything that belongs to a different patient than the open chart. */
function claimforms_load_claim(int $id, int $pid): array
{
    $claim = ClaimRepository::get($id);
    if (!$claim || (int)$claim['pid'] !== $pid) {
        claimforms_json(['error' => 'Claim not found'], 404);
    }
    return $claim;
}

function claimforms_context(string $formId, array $encounters, int $uid): array
{
    return [
        'definition' => FormRegistry::definition($formId),
        'referrers' => DraftBuilder::referrers(),
        'signature' => [
            'signature' => (bool)SignatureStore::path($uid, 'signature'),
            'stamp' => (bool)SignatureStore::path($uid, 'stamp'),
        ],
    ];
}

try {
    switch ($action) {
        case 'draft':
            $formId = (string)($_GET['form'] ?? '');
            if (!FormRegistry::exists($formId)) {
                claimforms_json(['error' => 'Unknown form'], 400);
            }
            $enc = array_filter(array_map('intval', explode(',', (string)($_GET['enc'] ?? ''))));
            $draft = DraftBuilder::build($pid, $enc);
            claimforms_json(['claimId' => null, 'formId' => $formId, 'encounters' => array_values($enc),
                'payload' => $draft['payload'], 'icdOptions' => $draft['icdOptions'], 'status' => 'draft']
                + claimforms_context($formId, $enc, $uid));

        case 'claim':
            $claim = claimforms_load_claim((int)($_GET['id'] ?? 0), $pid);
            ClaimRepository::event((int)$claim['id'], $uid, 'opened');
            $draft = DraftBuilder::build($pid, $claim['encounters']);
            claimforms_json(['claimId' => (int)$claim['id'], 'formId' => $claim['form_id'], 'encounters' => $claim['encounters'],
                'payload' => $claim['payload'], 'icdOptions' => $draft['icdOptions'], 'status' => $claim['status']]
                + claimforms_context($claim['form_id'], $claim['encounters'], $uid));

        case 'icd':
            $q = (string)($_GET['q'] ?? '');
            if (($_GET['scope'] ?? '') === 'eye') {
                // Optometry / ophthalmology picker: the built-in eye codes first, then anything in the ICD-10 tables.
                $res = EyeCodes::search($q);
                $have = array_column($res, 'code');
                foreach (DraftBuilder::searchIcd($q) as $r) {
                    if (!in_array($r['code'], $have, true)) {
                        $res[] = $r;
                    }
                }
                claimforms_json(['results' => array_slice($res, 0, 60)]);
            }
            claimforms_json(['results' => DraftBuilder::searchIcd($q)]);

        case 'cpt':
            claimforms_json(['results' => DraftBuilder::searchCpt((string)($_GET['q'] ?? ''))]);

        case 'save':
            $formId = (string)($body['form'] ?? '');
            if (!FormRegistry::exists($formId)) {
                claimforms_json(['error' => 'Unknown form'], 400);
            }
            $payload = Sanitizer::payload(is_array($body['payload'] ?? null) ? $body['payload'] : []);
            $enc = array_filter(array_map('intval', (array)($body['encounters'] ?? [])));
            $id = (int)($body['claimId'] ?? 0);
            if ($id > 0) {
                claimforms_load_claim($id, $pid);
                ClaimRepository::update($id, $payload, $uid, $enc);
            } else {
                $id = ClaimRepository::create($pid, $formId, $payload, $uid, $enc);
            }
            claimforms_json(['ok' => true, 'claimId' => $id]);

        case 'generate':
            $id = (int)($body['claimId'] ?? 0);
            claimforms_load_claim($id, $pid);
            $res = ClaimService::generate($id, $uid);
            if ($res['ok']) {
                $res['downloadUrl'] = 'download.php?id=' . $id;
            }
            claimforms_json($res, $res['ok'] ? 200 : 422);

        default:
            claimforms_json(['error' => 'Unknown action'], 400);
    }
} catch (\Throwable $e) {
    error_log('claim-forms: ' . $e->getMessage());
    claimforms_json(['error' => 'Something went wrong. See the server log.'], 500);
}
