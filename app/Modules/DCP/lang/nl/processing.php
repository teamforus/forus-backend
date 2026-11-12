<?php

return [
    'status_unknown' => 'DCP status :status',
    'statuses' => [
        -1 => 'Not found',
        100 => 'Accepted by the DCP API',
        200 => 'Accepted by DCP backoffice',
        300 => 'Processing',
        400 => 'Processed',
        500 => 'Shipped',
        1000 => 'Closed',
        9000 => 'Deleted by the client',
        9001 => 'Canceled by DCP',
        9422 => 'Cannot be manufactured',
    ],
    'accepted' => 'The order was accepted by DCP.',
    'source_unavailable' => 'The source no longer allows this order to be printed.',
    'invalid_snapshot' => 'The saved printing data is invalid. The order was not sent.',
    'abandoned' => 'The sending process was interrupted. The submission outcome is unknown.',
    'submission_failed' => 'The submission outcome is unknown. The order will not be sent again automatically.',
    'persistence_failed' => 'The submission outcome could not be saved reliably. The order will not be sent again.',
    'status_failed' => 'The order status could not be updated. The previous status is retained.',
    'errors' => [
        'transport' => 'DCP could not be reached or the request timed out.',
        'http' => 'DCP returned an unexpected HTTP response.',
        'invalid_response' => 'DCP returned an incomplete or invalid response.',
        'reference_mismatch' => 'The response does not identify the expected order.',
        'duplicate' => 'DCP reported an existing order. Its submission outcome must be verified.',
        'not_found' => 'DCP could not find the order. It will not be submitted again.',
        'rejected' => 'DCP rejected the order.',
    ],
];
