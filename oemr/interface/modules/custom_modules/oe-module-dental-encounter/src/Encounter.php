<?php
namespace Carelio\DentalEncounter;

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Database\QueryUtils;
use RuntimeException;

final class Encounter
{
    public const FORM = 'dental_encounter';

    public static function row(string $sql, array $params = []): ?array
    {
        return QueryUtils::fetchRecords($sql, $params)[0] ?? null;
    }

    public static function context(int $pid, int $encounter): array
    {
        if (Module::userId() < 1 || !AclMain::aclCheckCore('patients', 'demo')
            || !AclMain::aclCheckForm(self::FORM)) {
            throw new RuntimeException('You do not have access to dental encounter records.', 403);
        }
        $context = self::row(
            'SELECT e.encounter, e.pid, e.date, e.sensitivity, p.fname, p.mname, p.lname, p.DOB, p.pubpid, p.squad
             FROM form_encounter e JOIN patient_data p ON p.pid = e.pid WHERE e.pid = ? AND e.encounter = ?',
            [$pid, $encounter]
        );
        if (!$context) {
            throw new RuntimeException('Select a patient and an existing encounter in OpenEMR.', 404);
        }
        if ((!empty($context['squad']) && !AclMain::aclCheckCore('squads', $context['squad']))
            || (!empty($context['sensitivity']) && !AclMain::aclCheckCore('sensitivities', $context['sensitivity']))) {
            throw new RuntimeException('You do not have access to this patient or encounter.', 403);
        }
        return $context;
    }

    public static function load(int $pid, int $encounter, int $id = 0): ?array
    {
        $sql = 'SELECT d.*, f.id AS registry_id FROM form_dental_encounter d
                JOIN forms f ON f.form_id = d.id AND f.formdir = ?
                AND f.pid = d.pid AND f.encounter = d.encounter
                WHERE d.pid = ? AND d.encounter = ? AND d.activity = 1 AND f.deleted = 0';
        $params = [self::FORM, $pid, $encounter];
        if ($id > 0) {
            $sql .= ' AND d.id = ?';
            $params[] = $id;
        }
        return self::row($sql . ' ORDER BY d.id DESC LIMIT 1', $params);
    }

    public static function locked(int $encounter, ?array $record): bool
    {
        $api = new \ESign\Api();
        if ($api->createEncounterESign($encounter)->isLocked()) {
            return true;
        }
        return $record && $api->createFormESign((int)$record['registry_id'], self::FORM, $encounter)->isLocked();
    }

    public static function canWrite(?array $record): bool
    {
        return AclMain::aclCheckCore('encounters', 'notes', '', $record ? 'write' : ['write', 'addonly']);
    }

    public static function save(array $context, int $id, int $revision, array $data): array
    {
        $pid = (int)$context['pid'];
        $encounter = (int)$context['encounter'];
        QueryUtils::sqlStatementThrowException('START TRANSACTION', []);
        try {
            // Serializes creation and updates, including competing new-form requests.
            self::row('SELECT id FROM form_encounter WHERE pid = ? AND encounter = ? FOR UPDATE', [$pid, $encounter]);
            $record = self::load($pid, $encounter, $id);
            if ($id > 0 && !$record) {
                throw new RuntimeException('This record is unavailable or has been deleted.', 404);
            }
            if (!$id && $record) {
                throw new RuntimeException('A dental record already exists. Reopen it from the encounter before saving.', 409);
            }
            if (!self::canWrite($record)) {
                throw new RuntimeException('You do not have permission to save this record.', 403);
            }
            if (self::locked($encounter, $record)) {
                throw new RuntimeException('This form or encounter is signed and locked.', 423);
            }
            if (($record && (int)$record['revision'] !== $revision) || (!$record && $revision !== 0)) {
                throw new RuntimeException('Another user saved a newer revision. Reopen the record before making further edits.', 409);
            }
            $data['fields']['encounterId'] = (string)$encounter;
            $data['fields']['chartNo'] = (string)($context['pubpid'] ?: $pid);
            $payload = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $uid = Module::userId();
            $username = Module::username();
            $provider = Module::provider();
            $authorized = Module::userAuthorized();
            $revision++;
            if ($record) {
                QueryUtils::sqlStatementThrowException(
                    'UPDATE form_dental_encounter SET payload = ?, revision = ?, updated_at = NOW(), updated_by = ? WHERE id = ? AND pid = ? AND encounter = ?',
                    [$payload, $revision, $uid, $id, $pid, $encounter]
                );
            } else {
                $id = QueryUtils::sqlInsert(
                    'INSERT INTO form_dental_encounter (pid, encounter, date, user, groupname, authorized, activity, revision, payload, updated_at, updated_by)
                     VALUES (?, ?, NOW(), ?, ?, ?, 1, 1, ?, NOW(), ?)',
                    [$pid, $encounter, $username, $provider, $authorized, $payload, $uid]
                );
                // Same registry linkage as OpenEMR addForm(), using the throwing DB API for atomic saves.
                QueryUtils::sqlInsert(
                    'INSERT INTO forms (date, encounter, form_name, form_id, pid, user, groupname, authorized, formdir, therapy_group_id)
                     VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, NULL)',
                    [$encounter, 'Dental Encounter', $id, $pid, $username, $provider, $authorized, self::FORM]
                );
            }
            QueryUtils::sqlInsert(
                'INSERT INTO mod_dental_encounter_history (form_id, revision, payload, saved_at, saved_by) VALUES (?, ?, ?, NOW(), ?)',
                [$id, $revision, $payload, $uid]
            );
            QueryUtils::sqlStatementThrowException('COMMIT', []);
            return ['id' => (int)$id, 'revision' => $revision];
        } catch (\Throwable $error) {
            QueryUtils::sqlStatementThrowException('ROLLBACK', []);
            throw $error;
        }
    }
}
