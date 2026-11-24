<?php

return [
    'status_unknown' => 'DCP-status :status',
    'statuses' => [
        -1 => 'Niet gevonden',
        100 => 'Geaccepteerd door de DCP-API',
        200 => 'Geaccepteerd door de DCP-backoffice',
        300 => 'In verwerking',
        400 => 'Verwerkt',
        500 => 'Verzonden',
        1000 => 'Afgesloten',
        9000 => 'Verwijderd door de klant',
        9001 => 'Geannuleerd door DCP',
        9422 => 'Kan niet worden geproduceerd',
    ],
    'accepted' => 'De printopdracht is geaccepteerd door DCP.',
    'source_unavailable' => 'De bron staat niet langer toe dat deze printopdracht wordt afgedrukt.',
    'invalid_snapshot' => 'De opgeslagen afdrukgegevens zijn ongeldig. De printopdracht is niet naar DCP verstuurd.',
    'abandoned' => 'De verzending naar DCP is onderbroken. De uitkomst is onbekend.',
    'submission_failed' => 'De uitkomst van de verzending naar DCP is onbekend. De printopdracht wordt niet ' .
        'automatisch opnieuw verstuurd.',
    'persistence_failed' => 'De uitkomst van de verzending naar DCP kon niet betrouwbaar worden opgeslagen. De ' .
        'printopdracht wordt niet opnieuw verstuurd.',
    'status_failed' => 'De status van de printopdracht kon niet worden bijgewerkt. De vorige status blijft behouden.',
    'errors' => [
        'transport' => 'DCP kon niet worden bereikt of het verzoek duurde te lang.',
        'http' => 'DCP heeft een onverwacht HTTP-antwoord teruggestuurd.',
        'invalid_response' => 'DCP heeft een onvolledig of ongeldig antwoord teruggestuurd.',
        'reference_mismatch' => 'Het antwoord verwijst niet naar de verwachte printopdracht.',
        'duplicate' => 'DCP heeft een bestaande printopdracht gemeld. De uitkomst van de verzending ' .
            'moet worden gecontroleerd.',
        'not_found' => 'DCP kon de printopdracht niet vinden. Deze wordt niet opnieuw verstuurd.',
        'rejected' => 'DCP heeft de printopdracht geweigerd.',
    ],
];
