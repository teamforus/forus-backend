<?php

return [
    'mapping_unavailable' => 'The configured CSV mapping is unavailable. Contact your administrator.',
    'mapping_columns_ambiguous' => 'The CSV mapping contains duplicate column names. Contact your administrator.',
    'mapping_records_unavailable' => 'The CSV mapping uses unavailable fields. Contact your administrator.',
    'record_type_not_configured' => 'The record type :key is not configured.',
    'row_unavailable' => 'This BSN belongs to a deleted row and cannot be imported.',
    'identity_ambiguous' => 'This BSN matches more than one identity. Resolve the duplicate identities before syncing.',
    'profile_mismatch' => 'This BSN now resolves to a different profile. Review the identity assignment before syncing.',
    'client_number_required' => 'This BSN already exists. Supply its client number to update the row.',
    'client_number_mismatch' => 'This client number does not match the existing row for this BSN.',
    'client_number_conflict' => 'This client number belongs to a row with a different BSN.',
    'client_number_immutable' => 'An existing client number cannot be changed or cleared.',
    'update_failed' => 'The requester record could not be saved. Please try again.',
];
