<?php

return [
    'to_many_attempts' => [
        'title' => "Te veel pogingen!",
        'message' => implode("\n", [
            "U heeft driemaal een verkeerde activatiecode ingevuld. ",
            "Probeer het over :available_in_min minuten opnieuw.",
        ]),
    ],
];