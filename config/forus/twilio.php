<?php

return [
    "sid" => env('TWILIO_SID'),
    "from" => env('TWILIO_FROM'),
    "token" => env('TWILIO_TOKEN'),
    "debug" => env('TWILIO_DEBUG', false),
];