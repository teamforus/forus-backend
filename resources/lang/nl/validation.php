<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'header'                => 'De opgegeven gegevens waren ongeldig.',
    "prohibited"            => ":attribute is niet toegestaan.",
    "prohibited_if"         => ":attribute is niet toegestaan indien :other gelijk is aan :value.",
    "prohibited_unless"     => ":attribute is niet toegestaan tenzij :other gelijk is aan :values.",
    "prohibits"             => ":attribute is niet toegestaan in combinatie met :other.",

    'accepted'             => ':attribute dient te worden geaccepteerd.',
    'active_url'           => ':attribute is geen geldige URL.',
    'after'                => ':attribute dient een datum te zijn na :date.',
    'after_or_equal'       => ':attribute dient een datum gelijkwaardig of later te zijn dan :date',
    'alpha'                => ':attribute mag alleen letters bevatten.',
    'alpha_dash'           => ':attribute mag alleen letters, nummers, and strepen bevatten.',
    'alpha_num'            => ':attribute mag alleen letters en nummers bevatten.',
    'array'                => ':attribute moet een array zijn.',
    'before'               => ':attribute moet een datum zijn eerder dan :date.',
    'before_or_equal'      => ':attribute moet een datum voor of gelijk zijn aan :date.',
    'between'              => [
        'numeric' => ':attribute moet tussen :min en :max liggen.',
        'file'    => ':attribute moet tussen :min en :max kilobytes zijn.',
        'string'  => ':attribute moet tussen :min en :max karakters lang zijn.',
        'array'   => ':attribute moet tussen :min en :max items bevatten.',
    ],
    'boolean'              => ':attribute kan enkel true of false zijn.',
    'confirmed'            => ':attribute bevestiging komt niet overeen.',
    'date'                 => ':attribute is geen geldige datum.',
    'date_format'          => ':attribute komt niet overeen met het formaat :format.',
    'different'            => ':attribute en :other dienen verschillend te zijn.',
    'digits'               => ':attribute moet :digits cijfers zijn.',
    'digits_between'       => ':attribute moet tussen :min en :max cijfers zijn.',
    'dimensions'           => ':attribute heeft een verkeerd formaat.',
    'distinct'             => ':attribute heeft dubbele waarde',
    'email'                => ':attribute moet een geldig e-mail adres zijn',
    'exists'               => 'geselecteerde :attribute is ongeldig',
    'file'                 => ':attribute moet een bestand zijn',
    'filled'               => ':attribute veld moet een waarde bevatten.',
    'gt'             => [
        'numeric'   => 'De :attribute moet groter zijn dan :value.',
        'file'      => 'De :attribute moet groter zijn dan :value kilobytes.',
        'string'    => 'De :attribute moet meer dan :value tekens bevatten.',
        'array'     => 'De :attribute moet meer dan :value waardes bevatten.',
    ],
    'gte' => [
        'numeric'   => 'De :attribute moet groter of gelijk zijn aan :value.',
        'file'      => 'De :attribute moet groter of gelijk zijn aan :value kilobytes.',
        'string'    => 'De :attribute moet minimaal :value tekens bevatten.',
        'array'     => 'De :attribute moet :value waardes of meer bevatten.',
    ],
    'image'                => ':attribute moet een afbeelding zijn',
    'in'                   => 'geselecteerde :attribute is ongeldig.',
    'in_array'             => ':attribute veld bestaat niet in :other.',
    'integer'              => ':attribute moet integer zijn',
    'ip'                   => ':attribute dient een geldig IP adres te zijn.',
    'ipv4'                 => ':attribute dient een geldig IPv4 adres te zijn.',
    'ipv6'                 => ':attribute dient een geldig IPv6 adres te zijn.',
    'json'                 => ':attribute dient een geldig JSON string te zijn.',
    'lt'       => [
        'numeric' => 'De :attribute moet kleiner zijn dan :value.',
        'file'    => 'De :attribute moet kleiner zijn dan :value kilobytes.',
        'string'  => 'De :attribute moet minder dan :value tekens bevatten.',
        'array'   => 'De :attribute moet minder dan :value waardes bevatten.',
    ],
    'lte' => [
        'numeric' => 'De :attribute moet kleiner of gelijk zijn aan :value.',
        'file'    => 'De :attribute moet kleiner of gelijk zijn aan :value kilobytes.',
        'string'  => 'De :attribute moet maximaal :value tekens bevatten.',
        'array'   => 'De :attribute moet :value waardes of minder bevatten.',
    ],
    'max'                  => [
        'numeric' => ':attribute mag niet groter zijn dan :max.',
        'file'    => ':attribute mag niet groter zijn dan :max kilobytes.',
        'string'  => ':attribute mag niet groter zijn dan :max karakters.',
        'array'   => ':attribute mag niet meer dan :max items bevatten.',
    ],
    'mimes'                => ':attribute dient een bestand te zijn van het type: :values.',
    'mimetypes'            => ':attribute dient een bestand te zijn van het type: :values.',
    'min'                  => [
        'numeric' => ':attribute dient minimaal :min te zijn.',
        'file'    => ':attribute dient minimaal :min kilobytes te zijn.',
        'string'  => ':attribute dient minimaal :min karakters te bevatten.',
        'array'   => ':attribute dient minimaal :min items te bevatten.',
    ],
    'not_in'               => 'Het geselecteerde :attribute is ongeldig.',
    'numeric'              => 'Het :attribute dient een nummer te zijn.',
    'present'              => 'Het :attribute veld moet aanwezig zijn.',
    'regex'                => 'Het :attribute formaat is ongeldig.',
    'required'             => 'Het :attribute veld is verplicht.',
    'required_if'          => 'Het :attribute veld is verplicht wanneer :other is :value.',
    'required_if_accepted' => "Het is verplicht om het :attribute in te vullen als het ':other' veld is aangevinkt.",
    'required_with'        => 'Het :attribute veld is verplicht wanneer :values aanwezig is.',
    'required_unless'      => 'Het :attribute veld in nodig tenzij :other in :values zit.',
    'required_with_all'    => 'Het :attribute veld is verplicht wanneer :values aanwezig is.',
    'required_without'     => 'Het :attribute veld is verplicht wanneer :values niet aanwezig is.',
    'required_without_all' => 'Het :attribute veld is verplicht wanneer geen van :values aanwezig is.',
    'required_not_filled'  => 'Het :attribute veld is verplicht maar nog niet ingevuld',
    'same'                 => 'Het :attribute en :other moeten hetzelfde zijn.',
    'size'                 => [
        'numeric' => ':attribute moet :size zijn.',
        'file'    => ':attribute moet :size kilobytes groot zijn.',
        'string'  => ':attribute moet :size karakters lang zijn.',
        'array'   => ':attribute moet :size items bevatten.',
    ],
    'starts_with' => ':attribute moet starten met een van de volgende: :values',
    'string'               => 'Het :attribute moet een reeks zijn.',
    'timezone'             => ':attribute moet een geldige tijdszone zijn.',
    'unique'               => ':attribute is al bezet.',
    'uploaded'             => 'Het is niet gelukt om :attribute te uploaden',
    'url'                  => ':attribute moet beginnen met http: // of https: //.',

    // Custom
    'old_pin_code'          => 'De oude en nieuwe inlogcode komen niet overeen.',
    'unknown_record_key'    => 'Unknown record key: ":key".',
    'unique_record'         => 'Het :attribute eigenschap is al gekozen.',

    'organization_fund'     => [
        'wrong_categories'  => 'Verkeerde categorieën.',
        'already_requested' => 'U heeft dit al een keer aangevraagd.',
    ],

    'city_name' => 'Het lijkt erop dat de :attribute niet klopt.',
    'street_name' => 'Het lijkt erop dat de :attribute niet klopt.',
    'house_number' => 'Het lijkt erop dat het :attribute niet klopt.',
    'house_addition' => 'Het lijkt erop dat de :attribute niet klopt.',
    'postcode' => 'Het lijkt erop dat de :attribute niet klopt.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention 'attribute.rule' to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of 'email'. This simply helps us make messages a little cleaner.
    |
    */
    'prevalidation_missing_required_keys' => 'Het bestand bevat niet alle verplichte velden.',
    'prevalidation_invalid_record_key' => 'Een veldtype dat is opgenomen in het bestand bestaat niet.',
    'validation.prevalidation_invalid_type_primary_email' => 'Het primaire e-mailadres veld is een systeemveld en kan hier niet gebruikt worden.',
    'validation.prevalidation_missing_primary_key' => 'Het sleutelveld ontbreekt in het bestand.',
    'fund_request_request_field_incomplete' => 'Dit veld mag niet leeg zijn.',
    'fund_request_request_eligible_field_incomplete' => 'Ga akkoord met de voorwaarden.',
    'email_already_used' => 'Dit e-mailadres is al ingenomen door een ander account.',
    'iban' => 'Vul een geldig IBAN-nummer in, bijvoorbeeld NL02ABNA0123456789',
    'kvk' => 'Het KVK-nummer is verplicht en moet geldig zijn.',
    'business_type_id' => 'Organisatie type',
    'voucher' => [
        'expired' => 'Dit tegoed is niet meer geldig.',
        'pending' => 'Dit tegoed is niet actief',
        'deactivated' => 'De QR-code is sinds :deactivation_date niet meer geldig.',
        'product_voucher_used' => 'Het tegoed voor dit aanbod is al gebruikt!',
        'provider_not_applied' => 'U mag dit tegoed niet scannen! Uw organisatie is nog niet aangemeld bij het fonds van dit tegoed.',
        'provider_pending' => 'U mag dit tegoed niet scannen! Status voor aanmelding van het fonds van dit tegoed is wachtend.',
        'provider_declined' => 'Dit tegoed kan niet gescand worden. Uw organisatie heeft niet de juiste rechten. Neem contact op met de partij die het tegoed uitgeeft of via support@forus.io.',
        'fund_not_active' => 'U mag dit tegoed nog niet scannen! Het fonds is niet actief.',
        'not_enough_funds' => 'Onvoldoende tegoed.',
        'product_sold_out' => 'Uw aanbod is uitverkocht, verhoog in uw beheeromgeving het aantal dat nog te koop is.',
        'reservation_used' => 'De reservering is al gebruikt.',
        'reservation_product_removed' => 'Het aanbod is verwijderd van deze reservering.',
        'throttled' => "Sorry, u kunt maar één transactie per voucher maken in :hardLimit seconden.\n Probeer het in :hardLimit seconden nog een keer.",
    ],
    'product_voucher' => [
        'product_not_found' => 'Aanbod niet gevonden.',
        'product_sold_out' => 'Niet genoeg voorraad voor het aanbod. Het aanbod kan verhoogd worden in de beheeromgeving.',
        'reservation_used' => 'De reservering is al gebruikt',
        'reservation_product_removed' => 'Het aanbod is verwijderd van deze reservering.',
    ],
    'product_reservation' => [
        'product_not_found' => 'Aanbod niet gevonden.',
        'product_sold_out' => 'Niet genoeg voorraad voor het aanbod. Het aanbod kan verhoogd worden in de beheeromgeving.',
        'reservation_not_enabled' => 'Reserveren staat niet aan voor dit aanbod.',
        'no_identity_stock' => 'Het aanbod heeft het limiet bereikt!',
        'no_total_stock' => 'Het aanbod heeft het limiet bereikt!',
        'reservation_not_pending' => implode(" ", [
            'De reservering (#:code) kan niet gescant worden, de status van deze reservering is ":state".',
            'Ga naar de beheeromgeving om de reservering te beoordelen.',
        ]),
        'reservations_limit_reached' => 'Reserveringslimiet bereikt, u kunt tot :count reserveringen hebben.',
        'too_many_canceled_reservations_for_product' => implode(' ', [
            'U heeft :count geannuleerd in het afgelopen uur.',
            'Nieuwe reserveringen kunnen pas weer na een uur worden gemaakt.',
        ]),
        'not_enough_voucher_funds' => 'Onvoldoende budget op de voucher',
        'reservations_has_unpaid_extra' => 'Er bestaat al een reservering voor dit aanbod waar de bijbetaling nog niet is afgerond.',
    ],
    'employees' => [
        'employee_already_exists' => 'Er bestaat al een werknemer met hetzelfde e-mailadres.',
    ],
    'attributes' => [
        'pin_code' => 'pincode',
        'records' => 'Eigenschappen',
        'email' => 'e-mail',
        'bsn' => 'bsn',
        'primary_email' => 'e-mail',
        'records.primary_email' => 'e-mailadres',
        'name' => 'naam',
        'phone' => 'telefoonnummer',
        'iban' => 'IBAN-nummer',
        'btw' => 'BTW-nummer',
        'kvk' => 'KvK-nummer',
        'code' => 'activatiecode',
        'note' => 'notitie',
        'amount' => 'hoeveelheid',
        'product_categories' => 'categorieën',
        'description' => 'omschrijving',
        'price' => 'prijs',
        'total_amount' => 'aanbod',
        'expire_at' => 'verloopdatum',
        'today' => 'vandaag',
        'product_category_id' => 'categorie',
        'address' => 'adres',
        'schedule' => 'openingstijden',
        'state' => 'status',
        'start_date' => 'startdatum',
        'end_date' => 'einddatum',
        'value' => "waarde",
        'file' => "bestand",
        'templates.*.title' => 'titel',
        'templates.*.content' => 'omschrijving',
        'price_type' => "prijs type",
        'price_discount' => "korting",
        'limit_total' => "totaal aanbod",
        'city' => "plaats",
        'limit_total_per_identity' => "limiet per aanvrager",
        'first_name' => "voornaam",
        'last_name' => "achternaam",
        'user_note' => "opmerking",
        'request_btn_text' => 'knoptekst aanvragen',
        'external_link_text' => 'externe linktekst',
        'external_link_url' => 'externe link-url',
        'employee_id' => 'medewerker',
        'direct_payment_iban' => 'IBAN-nummer',
        'direct_payment_name' => 'payment name',
        'voucher_id' => 'voucher',
        'product_id' => 'product',
        'record_type_key_multiplier' => 'record type',
        'label' => 'label',
        'type' => 'type',
        'birth_date' => 'geboortedatum',
        'external_page_url' => 'externe url',

        'house_nr' => 'huisnummer',
        'house_nr_addition' => 'huisnummertoevoeging',
        'postal_code' => 'postcode',
        'street' => 'straat',
        'ip' => "IP",
        'title' => 'titel',
        'iban_name' => 'naam rekeninghouder',

        'help_enabled' => 'toon hulpknop in het aanvraagformulier',
        'help_title' => 'titel hulp informatie pagina',
        'help_block_text' => 'banner tekst',
        'help_button_text' => 'knop tekst',
        'help_email' => 'e-mailadres',
        'help_phone' => 'telefoonnummer',
        'help_website' => 'website link',
        'help_chat' => 'link naar chat',
        'help_description' => 'omschrijving',
        'help_show_email' => 'toon e-mailadres',
        'help_show_phone' => 'toon telefoonnummer',
        'help_show_website' => 'toon website',
        'help_show_chat' => 'toon chat',
    ],
    'voucher_generator' => [
        'budget_exceeded' => 'De som van alle tegoeden overschrijven het saldo op het fonds.',
    ],
    'values' => [
        'price_type' => [
            'free' => 'gratis',
            'discount_fixed' => 'korting',
            'discount_percentage' => 'korting',
        ],
        'birth_date' => [
            'today' => 'vandaag',
        ],
    ],
    'reimbursement' => [
        'files' => [
            'required' => 'Om verder te gaan, moet er een bon, factuur of rekening worden toegevoegd. Dit veld is verplicht.',
        ],
    ],
];
