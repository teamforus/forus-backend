<?php

use App\Models\FundRequest;

return [
    'fund_providers' => [
        'pending' => 'Wachtend',
        'accepted' => 'Geaccepteerd',
        'rejected' => 'Geweigerd',
    ],
    'fund_requests' => [
        FundRequest::STATE_PENDING => 'Wachten',
        FundRequest::STATE_APPROVED => 'Geaccepteerd',
        FundRequest::STATE_DECLINED => 'Geweigerd',
        FundRequest::STATE_DISREGARDED => 'Buiten behandeling geplaatst',
    ],
    'mollie_connection' => [
        'needs-data' => 'In afwachting',
        'in-review' => 'In afwachting',
        'completed' => 'Verbonden',
    ],
    'product_reservations' => [
        'waiting' => 'Wachtend op bijbetaling',
        'pending' => 'In afwachting',
        'accepted' => 'Geaccepteerd',
        'canceled' => 'Geannuleerd',
        'canceled_by_client' => 'Geannuleerd door aanvrager',
        'canceled_by_sponsor' => 'Geannuleerd door sponsor',
        'canceled_payment_failed' => 'Geannuleerd door mislukte bijbetaling',
        'canceled_payment_expired' => 'Geannuleerd door verlopen bijbetaling',
        'canceled_payment_canceled' => 'Geannuleerd door ingetrokken bijbetaling',
        'rejected' => 'Geweigerd',
    ],
    'reservation_extra_payment_refunds' => [
        'queued' => 'In de wachtrij',
        'failed' => 'Mislukt',
        'pending' => 'In afwachting',
        'refunded' => 'Terugbetaald',
        'canceled' => 'Geannuleerd',
        'processing' => 'In behandeling',
    ],
    'reservation_extra_payments' => [
        'open' => 'Open',
        'paid' => 'Betaald',
        'failed' => 'Mislukt',
        'pending' => 'In afwachting',
        'canceled' => 'Geannuleerd',
        'expired' => 'Verlopen',
    ],
    'vouchers' => [
        'active' => 'Actief',
        'pending' => 'Inactief',
        'deactivated' => 'Gedeactiveerd',
    ],
];
