<?php

return [
    'title_formal' => ':fund_name is niet meer geldig',
    'title_informal' => ':fund_name niet meer geldig',

    'description_formal' => join("\n\n", [
        'Beste inwoner, ',
        'Je :fund_name is vanaf :date niet meer geldig. ',
        'Voor meer informatie kan je contact opnemen met de :sponsor_name.',
        "Telefoonnummer: :sponsor_phone\nE-mailadres: :sponsor_email",
    ]),

    'description_informal'  => join("\n\n", [
        'Beste inwoner, ',
        'Uw :fund_name is vanaf nu niet meer geldig.',
        'Voor meer informatie kunt u contact opnemen met de :sponsor_name.',
        "Telefoonnummer: :sponsor_phone\nE-mailadres: :sponsor_email",
    ]),
];