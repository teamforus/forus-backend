<?php

return [
    'title' => implode('|', [
        "Update: :count_requests new validation request",
        "Update: :count_requests new validation requests",
    ]),
    'greetings' => implode('|', [
        "Beste :organization_name,\n Er zijn :count_requests notificatie die betrekking hebben tot uw organisatie.",
        "Beste :organization_name,\n Er zijn :count_requests notificaties die betrekking hebben tot uw organisatie.",
    ]),
    'fund_header' => implode('|', [
        ":count_requests nieuwe aanvragen voor :fund_name",
        ":count_requests nieuwe aanvragen voor :fund_name",
    ]),
    'fund_details' => implode('|', [
        "U heeft :count_requests nieuwe aanvragen wachtende op uw dashboard.\n" .
        "Ga naar het dashboard om deze aanvragen goed te keuren.",
        "U heeft :count_requests nieuwe aanvragen wachtende op uw dashboard.\n" .
        "Ga naar het dashboard om deze aanvragen goed te keuren.",
    ]),
    'dashboard_button' => 'GA NAAR HET DASHBOARD',
];