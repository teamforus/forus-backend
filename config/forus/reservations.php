<?php

return [
    'extra_payment_waiting_time' => env('RESERVATION_EXTRA_PAYMENT_WAITING_TIME', 60),
    'throttle_total_pending' => env('RESERVATION_THROTTLE_TOTAL_PENDING', 100),
    'throttle_recently_canceled' => env('RESERVATION_THROTTLE_RECENTLY_CANCELED', 10),
];
