<?php

namespace OpenEMR\Modules\ClaimForms;

/**
 * Each claim form is a folder under forms/<id>/ holding form.json (title, UI),
 * map.json (where each value goes on the PDF) and background.pdf.
 * Adding another insurer's form means adding a folder, with no PHP changes.
 */
class FormRegistry
{
    public static function dir(): string
    {
        return dirname(__DIR__) . '/forms';
    }

    /** @return array<string,array> id => form.json contents */
    public static function all(): array
    {
        $out = [];
        foreach (glob(self::dir() . '/*/form.json') ?: [] as $file) {
            $def = json_decode((string)file_get_contents($file), true);
            if (is_array($def) && !empty($def['id'])) {
                $out[$def['id']] = $def;
            }
        }
        ksort($out);
        return $out;
    }

    public static function exists(string $id): bool
    {
        return (bool)preg_match('/^[a-z0-9_]+$/', $id) && is_file(self::dir() . '/' . $id . '/form.json');
    }

    public static function definition(string $id): array
    {
        self::assert($id);
        return json_decode((string)file_get_contents(self::dir() . "/$id/form.json"), true);
    }

    public static function map(string $id): array
    {
        self::assert($id);
        return json_decode((string)file_get_contents(self::dir() . "/$id/map.json"), true);
    }

    public static function background(string $id): string
    {
        self::assert($id);
        return self::dir() . "/$id/background.pdf";
    }

    private static function assert(string $id): void
    {
        if (!self::exists($id)) {
            throw new \InvalidArgumentException('Unknown claim form');
        }
    }
}
