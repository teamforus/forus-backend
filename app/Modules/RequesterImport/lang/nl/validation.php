<?php

return [
    'record_type_not_configured' => 'The record type :key is not configured.',
    'validation_preset_incompatible' => 'Validation preset :preset cannot be used with type :type for record :key.',
    'row_unavailable' => 'This BSN belongs to a deleted row and cannot be imported.',
    'client_uuid_required' => 'This BSN already exists. Supply its client UUID to update the row.',
    'client_uuid_mismatch' => 'This client UUID does not match the existing row for this BSN.',
    'client_uuid_conflict' => 'This client UUID belongs to a row with a different BSN.',
    'client_uuid_immutable' => 'An existing client UUID cannot be changed or cleared.',
    'update_failed' => 'The requester record could not be saved. Please try again.',
];
