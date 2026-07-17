<?php

return [
    'pdf_archive' => [
        'warning' => implode(PHP_EOL, [
            'Let op: PDF-bestanden kunnen actieve inhoud of andere risico\'s bevatten.',
            'Dit ZIP-bestand bevat het originele PDF-bestand en afbeeldingen van alle pagina\'s.',
            'Gebruik bij voorkeur de afbeeldingen voor controle.',
            'Open het originele PDF-bestand alleen als u de bron vertrouwt.',
        ]),
        'warning_without_preview_pages' => implode(PHP_EOL, [
            'Let op: er zijn geen voorbeeldafbeeldingen beschikbaar voor dit PDF-bestand.',
            'Dit ZIP-bestand bevat daarom alleen het originele PDF-bestand.',
            'Open dit bestand alleen als u de bron vertrouwt.',
        ]),
    ],
];
