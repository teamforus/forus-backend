<?php

return [
    'relation' => [
        'type' => [
            'partner' => 'Partner',
            'parent_child' => 'Ouder-kind',
            'housemate' => 'Medebewoner',
        ],
        'subtype' => [
            'partner_married' => 'Partner gehuwd',
            'partner_registered' => 'Partner geregistreerd',
            'partner_unmarried' => 'Partner ongehuwd',
            'partner_other' => 'Overige familierelatie (partnerschap)',

            'parent_child' => 'Ouder - Kind',
            'foster_parent_foster_child' => 'Pleegouder - Pleegkind',

            'parent' => 'Ouder',
            'in_law' => 'Schoonouder',
            'grandparent_or_sibling' => 'Opa, oma, broer of zus',
            'room_renter' => 'Iemand die een kamer bij mij huurt',
            'room_landlord' => 'Iemand waarvan ik een kamer huur',
            'boarder_or_host' => 'Kostganger of kostgever',
            'other' => 'Anders',
        ],
        'living_together' => [
            'yes' => 'Ja',
            'no' => 'Nee',
        ],
    ],
];
