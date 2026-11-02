<?php
namespace Carelio\DentalEncounter;

use InvalidArgumentException;

final class Payload
{
    public const TABLES = [
        'dx' => ['tooth', 'diagnosis', 'severity', 'prognosis', 'notes'],
        'tx' => ['tooth', 'diagnosis', 'procedure', 'priority', 'cost', 'notes'],
        'rx' => ['medication', 'dose', 'frequency', 'duration', 'quantity', 'refills', 'indication', 'instructions'],
        'billing' => ['code', 'description', 'amount'],
    ];

    public static function schema(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../resources/schema.json'), true, 32, JSON_THROW_ON_ERROR);
    }

    public static function text($value, int $limit = 20000): string
    {
        if (!is_string($value) || strlen($value) > $limit || str_contains($value, "\0")) {
            throw new InvalidArgumentException('Invalid or oversized field value.');
        }
        return $value;
    }

    public static function validate(array $data): array
    {
        $schema = self::schema();
        $clean = ['schemaVersion' => 1, 'fields' => [], 'groups' => [], 'tables' => [], 'teeth' => []];
        foreach (['fields', 'groups', 'tables', 'teeth', 'meta'] as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                throw new InvalidArgumentException("Missing or invalid $key.");
            }
        }
        foreach ($data['fields'] as $key => $value) {
            if (!isset($schema['fields'][$key])) {
                throw new InvalidArgumentException('Unknown field.');
            }
            $spec = $schema['fields'][$key];
            if ($spec['type'] === 'checkbox') {
                if (!is_bool($value)) {
                    throw new InvalidArgumentException('Checkbox values must be boolean.');
                }
            } else {
                $value = self::text($value);
                if (isset($spec['options']) && !in_array($value, $spec['options'], true)) {
                    throw new InvalidArgumentException('Invalid choice for ' . $spec['label'] . '.');
                }
                if ($value !== '' && in_array($spec['type'], ['number', 'range'], true)
                    && (!is_numeric($value) || !is_finite((float)$value))) {
                    throw new InvalidArgumentException('Invalid number.');
                }
                if ($value !== '' && in_array($key, ['billDiscount', 'billTax', 'billPaid', 'billDeposit'], true) && (float)$value < 0) {
                    throw new InvalidArgumentException('Billing amounts must be nonnegative.');
                }
                if ($key === 'painScore' && (!ctype_digit($value) || (int)$value > 10)) {
                    throw new InvalidArgumentException('Pain score must be between 0 and 10.');
                }
                if ($value !== '' && $spec['type'] === 'date') {
                    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                    if (!$date || $date->format('Y-m-d') !== $value) {
                        throw new InvalidArgumentException('Invalid date.');
                    }
                }
                if ($value !== '' && $spec['type'] === 'time'
                    && !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?$/D', $value)) {
                    throw new InvalidArgumentException('Invalid time.');
                }
                if ($value !== '' && $spec['type'] === 'datetime-local') {
                    $format = strlen($value) === 16 ? 'Y-m-d\TH:i' : 'Y-m-d\TH:i:s';
                    $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
                    if (!$date || $date->format($format) !== $value) {
                        throw new InvalidArgumentException('Invalid completion date/time.');
                    }
                }
            }
            $clean['fields'][$key] = $value;
        }
        foreach ($schema['fields'] as $key => $spec) {
            if (!empty($spec['required']) && trim($clean['fields'][$key] ?? '') === '') {
                throw new InvalidArgumentException($spec['label'] . ' is required.');
            }
        }
        foreach ($data['groups'] as $key => $values) {
            if (!isset($schema['groups'][$key]) || !is_array($values) || count($values) > 100) {
                throw new InvalidArgumentException('Invalid checkbox group.');
            }
            foreach ($values as $value) {
                if (!is_string($value) || !in_array($value, $schema['groups'][$key], true)) {
                    throw new InvalidArgumentException('Invalid checkbox selection.');
                }
            }
            $clean['groups'][$key] = array_values(array_unique($values));
        }
        foreach (self::TABLES as $table => $keys) {
            $rows = $data['tables'][$table] ?? [];
            if (!is_array($rows) || !array_is_list($rows) || count($rows) > 200) {
                throw new InvalidArgumentException('Too many or invalid table rows.');
            }
            $clean['tables'][$table] = [];
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    throw new InvalidArgumentException('Invalid table row.');
                }
                $record = [];
                foreach ($keys as $key) {
                    $record[$key] = self::text($row[$key] ?? '');
                }
                foreach (['amount', 'cost', 'refills'] as $number) {
                    if (isset($record[$number]) && $record[$number] !== ''
                        && (!is_numeric($record[$number]) || !is_finite((float)$record[$number]) || (float)$record[$number] < 0)) {
                        throw new InvalidArgumentException('Amounts, costs and refills must be nonnegative numbers.');
                    }
                }
                $clean['tables'][$table][] = $record;
            }
        }
        foreach (['adult', 'pediatric'] as $dentition) {
            $teeth = $data['teeth'][$dentition] ?? [];
            if (!is_array($teeth) || count($teeth) > 32) {
                throw new InvalidArgumentException('Invalid tooth chart.');
            }
            $clean['teeth'][$dentition] = [];
            foreach ($teeth as $id => $tooth) {
                $valid = $dentition === 'adult' ? preg_match('/^(?:[1-9]|[12][0-9]|3[0-2])$/D', (string)$id)
                    : preg_match('/^[A-T]$/D', (string)$id);
                if (!$valid || !is_array($tooth)) {
                    throw new InvalidArgumentException('Invalid tooth identifier.');
                }
                $record = ['notes' => self::text($tooth['notes'] ?? '')];
                foreach (['findings', 'surfaces'] as $key) {
                    if (!isset($tooth[$key]) || !is_array($tooth[$key]) || count($tooth[$key]) > 30) {
                        throw new InvalidArgumentException('Invalid tooth findings.');
                    }
                    $record[$key] = array_map(static fn($v) => self::text($v, 150), array_values($tooth[$key]));
                }
                $clean['teeth'][$dentition][$id] = $record;
            }
        }
        $notation = $data['meta']['toothNotation'] ?? 'universal';
        $dentition = $data['meta']['dentition'] ?? 'adult';
        if (!in_array($notation, ['universal', 'fdi', 'palmer'], true)
            || !in_array($dentition, ['adult', 'pediatric'], true)) {
            throw new InvalidArgumentException('Invalid tooth notation or dentition.');
        }
        $clean['meta'] = ['toothNotation' => $notation, 'dentition' => $dentition];
        $clean['signature'] = self::text($data['signature'] ?? '', 700000);
        if ($clean['signature'] !== '') {
            if (!preg_match('~^data:image/png;base64,([A-Za-z0-9+/=]+)$~D', $clean['signature'], $match)) {
                throw new InvalidArgumentException('Signature must be a PNG image.');
            }
            $bytes = base64_decode($match[1], true);
            $size = $bytes === false ? false : @getimagesizefromstring($bytes);
            if (!$size || $size[2] !== IMAGETYPE_PNG || $size[0] > 8192 || $size[1] > 1024) {
                throw new InvalidArgumentException('Invalid signature image dimensions.');
            }
        }
        return $clean;
    }
}
