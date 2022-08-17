<?php

return [
    'to_many_attempts' => [
        'title' => "Te veel uitnodigingen!",
        'message' => implode("\n", [
            "U heeft het maximaal aantal uitnodigingen dat u kunt versturen bereikt. ",
            "Probeer het over :available_in_min minuten opnieuw.",
        ]),
    ],
];
