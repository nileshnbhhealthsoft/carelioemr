<?php

/**
 * Claim Forms module metadata for OpenEMR's Module Manager.
 *
 * Keeping this file declarative means the module is visible to Register/Install,
 * but it does not add menu items or touch data until the user installs/enables it.
 */

declare(strict_types=1);

$module_config = 1;

return [
    'name' => 'Insurance Claim Forms',
    'description' => 'Patient insurance claim forms with chart pre-fill, review, signatures, and stamped PDF generation.',
    'version' => '1.0.0',
    'author' => 'Carelio',
    'license' => 'GPL-3.0-or-later',
    'acl_category' => 'patients',
    'acl_section' => 'med',

    'require' => [
        'openemr' => '>=8.0.0',
        'php' => '>=8.1',
    ],

    'tables' => [
        'claimforms_claim',
        'claimforms_claim_encounter',
        'claimforms_claim_line',
        'claimforms_event',
    ],

    'install' => [
        'sql' => 'sql/table.sql',
    ],
];
